<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
ini_set('max_execution_time', 0);
use Aws\S3\Model\ClearBucket;
use Aws\Sts\StsClient;

class Beltdrive extends CI_Controller {

	public $instance = null;
	public $qb;
	public $serverId = null;
	public $reserveCount = array();
	public $remoteFileName = null;
	
	function getMacLinux() {
		exec('netstat -ie', $result);
		if(is_array($result)) {
			$iface = array();
			foreach($result as $key => $line) {
				if($key > 0) {
					$tmp = str_replace(" ", "", substr($line, 0, 10));
					if($tmp <> "") {
						$macpos = strpos($line, "ether");
						if($macpos !== false) {
							$iface[] = array('iface' => $tmp, 'mac' => strtolower(substr($line, $macpos+6, 17)));
						}
					}
				}
			}
			return $iface[0]['mac'];
		} else {
			return "notfound";
		}
	}

	public function __construct()
	{
		parent::__construct();

		\Sentry\init([
			'dsn' => $this->config->item('sentry_dsn'),
			'environment' => (defined(ENVIRONMENT) ? ENVIRONMENT:"development"),
			'server_name' => $this->config->item('authHelper')
		]);
		$this->serverId = $this->getMacLinux();
		$this->doctrine->extendTimeout();

		$this->load->model("asset_model");
		$this->load->model("asset_template");
		$this->config->set_item("enableCaching", false);
		$this->asset_template->useCache = FALSE;

	}

	public function index()
	{

	}


	public function updateIndexes() {
		$this->pheanstalk = new \Pheanstalk\Pheanstalk($this->config->item("beanstalkd"));
		$cnt=0;

		while(1) {
			$job = $this->pheanstalk->watch('reindex')->ignore('default')->reserve();
			if(!is_object($job)) {
				usleep(5);
				continue;
			}
			//reset doctrine in case we've lost the DB
			// TODO: doctrine 2.5 should let us move to pingable and avoid this?
			$this->doctrine->reset();


			$job_encoded = json_decode($job->getData(), true);

			$instanceId = $job_encoded["instance"];
			$this->instance = $this->doctrine->em->find("Entity\Instance", $instanceId);
			$objectId = $job_encoded["objectId"];
			if(!$objectId || !is_string($objectId)) {
				$this->pheanstalk->delete($job);
				continue;
			}
			echo "Reindexing " . $objectId . "\n";

			$this->asset_model->loadAssetById($objectId);

			$parentArray = array();

			$this->asset_model->reindex($parentArray);
			$this->pheanstalk->delete($job);
			$this->doctrine->em->clear();
		}
	}

	public function processFileTask() {
		echo "Starting processing thread\n";
		$this->pheanstalk = new \Pheanstalk\Pheanstalk($this->config->item("beanstalkd"));
		$cnt=0;
		while(1) {
			$job = $this->pheanstalk->watch('newUploads')->ignore('default')->reserve();
			if(!is_object($job)) {
				usleep(5);
				continue;
			}
			$this->job = $job;

			//reset doctrine in case we've lost the DB
			// TODO: doctrine 2.5 should let us move to pingable and avoid this?
			$this->doctrine->reset();

			$job_encoded = json_decode($job->getData(), true);

			$jobId = $job->getId();
			$stats = $this->pheanstalk->statsJob($job);
			$jobPriority = $stats->pri;



			if($stats->reserves > 200 && $job_encoded['task'] != "waitForCompletion") { // if we've tried to process it 200 times, it's probably not gonna work
				$this->logging->processingInfo("job", "transcoder", "file attempted 200 times", $job_encoded['fileHandlerId'], $job->getId());
				$this->pheanstalk->bury($job);
				continue;
			}

			// Ideally, a single file will live its whole life on one node.
			// We give it a bunch fo chances, but eventually bail and just do it on a new host.
			// This will result in stale files needing to be cleaned up!
			// reserveCount is a local cache in each instance - if a given node has been assigned htis job more than 20 times
			// and has rejected it each time, it gives up and just does it.
			if(isset($job_encoded['host_affinity'])) {
				$targetHost = $job_encoded['host_affinity'];

				if($targetHost != $this->serverId) {
					if(!isset($this->reserveCount[$jobId])) {
						$this->reserveCount[$jobId] = 0;
					}
					$this->reserveCount[$jobId]++;
					if($this->reserveCount[$jobId] < 10 ) {
						$this->pheanstalk->release($job, $jobPriority, 1);
						continue;
					}
					elseif($this->reserveCount[$jobId] < 20 ) {
						$this->pheanstalk->release($job, $jobPriority, 5);
						continue;
					}
					else {
						$this->reserveCount[$jobId] = 0;
					}
				}
			}

			$instanceId = $job_encoded["instance"];

			$this->instance = $this->doctrine->em->find("Entity\Instance", $instanceId);

			$fileHandler = $this->filehandler_router->getHandlerForObject($job_encoded["fileHandlerId"]);

			if(!$fileHandler) {
				$this->pheanstalk->release($job, $jobPriority, 15);
				continue;
			}
			$fileHandler->loadByObjectId($job_encoded["fileHandlerId"]);
			$fileHandler->pheanstalk = $this->pheanstalk;
			echo "Processing " . $fileHandler->sourceFile->originalFilename . " (" . $fileHandler->getObjectId() . ") : " . $job_encoded['task'] . "\n";

			$success=false;


			$result = $fileHandler->performTask($job);

			if($result == JOB_SUCCESS) {
				if($fileHandler->save() != false) {
					$fileHandler->removeJob($job->getId());
					$this->pheanstalk->delete($job);
					$this->logging->processingInfo("taskEnd",get_class($fileHandler),"Task Ended",$fileHandler->getObjectId(),$job->getId());
				}
				else {
					echo "Failed updating " . $fileHandler->getObjectId() . "\n";
					$this->pheanstalk->bury($job);
					$this->logging->processingInfo("taskEnd",get_class($fileHandler),"Task Updating Failed",$fileHandler->getObjectId(),$job->getId());
				}
			}
			else if($result == JOB_FAILED) {
				echo "Job Failed\n";
				$this->pheanstalk->bury($job);
				$this->logging->processingInfo("taskEnd",get_class($fileHandler),"Job Failed",$fileHandler->getObjectId(),$job->getId());
			}
			else if($result == JOB_POSTPONE) {
				echo "Postponing " . $fileHandler->sourceFile->originalFilename . " : " . $job_encoded['task'] . "\n";
				$this->pheanstalk->release($job, $jobPriority, $fileHandler->postponeTime);
				$this->logging->processingInfo("taskEnd",get_class($fileHandler),"Postponing task " . $job_encoded['task'],$fileHandler->getObjectId(),$job->getId());
			}

			echo "Job Complete\n";

			$cnt++;

			// every 100 jobs, let's clear our reserve count cache
			if($cnt % 100 == 0) {
				$this->reserveCount = array();
			}

			$memory = memory_get_usage();

			if($memory > 100000000) {
				echo "exiting run due to memory limit\n";
				exit;
			}
			$this->doctrine->em->clear();
			sleep(1);
		}
	}

	public function prepareDrawers() {

		$this->pheanstalk = new \Pheanstalk\Pheanstalk($this->config->item("beanstalkd"));
		$cnt=0;
		while(1) {
			$job = $this->pheanstalk->watch('archiveTube')->ignore('default')->reserve();
			if(!is_object($job)) {
				usleep(5);
				continue;
			}

			$stats = $this->pheanstalk->statsJob($job);
			if($stats->reserves > 200) {
				$this->logging->processingInfo("job", "drawerPrep", "drawer attempted 200 times", $job_encoded['drawerId'], $job->getId());
				$this->pheanstalk->bury($job);
				continue;
			}

			//reset doctrine in case we've lost the DB
			// TODO: doctrine 2.5 should let us move to pingable and avoid this?
			$this->doctrine->reset();

			$job_encoded = json_decode($job->getData(), true);

			echo "running job ". $job_encoded["drawerId"]. "\n";

			$drawerId = $job_encoded["drawerId"];
			$userEmail = $job_encoded["userContact"];
			$instanceId = $job_encoded["instance"];

			$instance = $this->doctrine->em->find("Entity\Instance", $instanceId);

			$drawer = $this->doctrine->em->find("Entity\Drawer", $drawerId);

			if($drawer) {
				$allDerivativesLocal = true;
				$derivativeArray = array();
				foreach($drawer->getItems() as $item) {
					$assetId = $item->getAsset();
					$this->asset_model->loadAssetById($assetId);
					$uploads = $this->asset_model->getAllWithinAsset("Upload", null, 1);

					foreach($uploads as $upload) {
						foreach($upload->fieldContentsArray as $content) {

							$handler = $content->getFileHandler();
							try {
								$bestDerivative = $handler->highestQualityDerivativeForAccessLevel(PERM_ORIGINALSWITHOUTDERIVATIVES);
							}
							catch (Exception $e) {
								continue;
							}
							
							$derivativeArray[] = $bestDerivative;
							if($bestDerivative->isLocal() !== FILE_LOCAL) {
								$allDerivativesLocal = false;
								if($bestDerivative->makeLocal() !== FILE_LOCAL) {
									echo "Error making this file local:" . $bestDerivative->getCompositeName() . "\n";
								}
							}
						}
					}
				}

				if($allDerivativesLocal) {
					$this->pheanstalk->touch($job);
					echo "compressing\n";
					$id = new MongoDB\BSON\ObjectId();
					$zipname = $this->config->item("scratchSpace") ."/". $id.'.zip';
    				$zip = new ZipArchive;
    				$zip->open($zipname, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE);
					foreach($derivativeArray as $bestDerivative) {
						echo "adding file " . $bestDerivative->getPathToLocalFile() . "\n";
						$zip->addFile($bestDerivative->getPathToLocalFile(),$bestDerivative->originalFilename);
						//$bestDerivative->removeLocalFile();
					}
					$zip->close();
					$this->load->model("s3_model");


					$this->s3_model->loadFromInstance($instance);
					$this->s3_model->bucket = $instance->getDefaultBucket();
					$this->pheanstalk->touch($job);
					$this->s3_model->putObject($zipname, "drawer/".$drawerId.".zip", $instance->getS3StorageType());

					foreach($derivativeArray as $bestDerivative) {
						$bestDerivative->removeLocalFile();
					}
					unlink($zipname);

					$this->pheanstalk->delete($job);

					$targetURL = site_url($instance->getDomain() . "/drawers/downloadDrawer/" . $drawerId);

					$drawerContent = $this->load->view("email/drawerReady", ["drawerId"=>$drawerId, "targetURL"=>$targetURL], true);

					$this->load->library('email');
					$this->email->from('elevator@umn.edu', 'Elevator');
					$this->email->set_newline("\r\n");
					$this->email->to($userEmail);
					$this->email->subject("Drawer Ready for Download");
					$this->email->message($drawerContent);
					$this->email->send();

					echo "Job Complete\n";

				}
				else {
					echo "Postponing drawer " . $job_encoded['drawerId'] . "\n";
					$this->pheanstalk->release($job, NULL, 5);
				}

			}
			else {
				$this->pheanstalk->delete($job);
			}


			$cnt++;

			$memory = memory_get_usage();

			if($memory > 100000000) {
				echo "exiting run due to memory limit";
				exit;
			}
			$this->doctrine->em->clear();
			sleep(1);
		}
	}

	public function restoreFiles() {

		$this->pheanstalk = new Pheanstalk\Pheanstalk($this->config->item("beanstalkd"));

		$this->load->model("filehandlerbase");
		$cnt=0;
		while(1) {
			$job = $this->pheanstalk->watch('restoreTube')->ignore('default')->reserve();
			if(!is_object($job)) {
				usleep(5);
				continue;
			}

			//reset doctrine in case we've lost the DB
			// TODO: doctrine 2.5 should let us move to pingable and avoid this?
			$this->doctrine->reset();


			$job_encoded = json_decode($job->getData(), true);

			echo "running job ". $job_encoded["objectId"]. "\n";

			$objectId = $job_encoded["objectId"];
			$userEmail = $job_encoded["userContact"];
			$instanceId = $job_encoded["instance"];
			$pathToFile = $job_encoded["pathToFile"];

			$instance = $this->doctrine->em->find("Entity\Instance", $instanceId);
			$this->filehandlerbase = new filehandlerbase();
			$this->filehandlerbase->loadByObjectId($objectId);

			if($this->filehandlerbase) {

				if($this->filehandlerbase->sourceFile->isArchived()) {
					echo "Postpononing restore watch for " . $objectId . "\n";
					$this->pheanstalk->release($job, NULL, 120);
				}
				else {
					echo "File finished". $objectId . "\n";
					$this->pheanstalk->delete($job);
					$fileContent = $this->load->view("email/fileReady", ["pathToFile"=>$pathToFile], true);
					$this->load->library('email');
					$this->email->from('elevator@umn.edu', 'Elevator');
					$this->email->set_newline("\r\n");
					$this->email->to($userEmail);
					$this->email->subject("File Ready for Download");
					$this->email->message($fileContent);
					$this->email->send();


				}
			}
			else {
				echo "File handler not found";
				$this->pheanstalk->delete($job);
			}


			$cnt++;

			$memory = memory_get_usage();

			if($memory > 100000000) {
				echo "exiting run due to memory limit";
				exit;
			}
			$this->doctrine->em->clear();
			sleep(1);
		}
	}


	public function migrateCollections() {

		$this->pheanstalk = new Pheanstalk\Pheanstalk($this->config->item("beanstalkd"));

		$this->load->model("filehandlerbase");
		$this->load->model("asset_model");
		$cnt=0;
		while(1) {
			$job = $this->pheanstalk->watch('collectionMigration')->ignore('default')->reserve();
			if(!is_object($job)) {
				usleep(5);
				continue;
			}

			//reset doctrine in case we've lost the DB
			// TODO: doctrine 2.5 should let us move to pingable and avoid this?
			$this->doctrine->reset();


			$job_encoded = json_decode($job->getData(), true);

			echo "running job ". $job_encoded["objectId"]. "\n";

			$objectId = $job_encoded["objectId"];
			$instanceId = $job_encoded["instance"];
			$targetCollection = $job_encoded["targetCollection"];

			$this->instance = $this->doctrine->em->find("Entity\Instance", $instanceId);


			$assetModel = new Asset_model();
			$assetModel->loadAssetById($objectId);

			$uploadHandlers = $assetModel->getAllWithinAsset("Upload");
			$haveArchivedItems = FALSE;
			foreach($uploadHandlers as $uploadHandler) {
				foreach($uploadHandler->fieldContentsArray as $uploadContents) {
					if($uploadContents->getFileHandler()->sourceFile && $uploadContents->getFileHandler()->sourceFile->isArchived()) {
						$uploadContents->fileHandler->sourceFile->restoreFromArchive();
						$haveArchivedItems = TRUE;
					}
				}
			}

			if($haveArchivedItems) {
				echo "Have archied items, postponing collection migration for " . $objectId . "\n";
				$this->pheanstalk->release($job, NULL, 900);
			}
			else {

				foreach($uploadHandlers as $uploadHandler) {
					foreach($uploadHandler->fieldContentsArray as $key=>$uploadContents) {

						$fileHandler = $uploadContents->getFileHandler();
						$fileHandler->sourceFile->copyToLocalStorage();
						$localFile = $fileHandler->sourceFile->getPathToLocalFile();
						$originalName = $fileHandler->sourceFile->originalFilename;
						$this->pheanstalk->touch($job);
						echo "Migrating ". $localFile . "\n";
						$targetClass = get_class($fileHandler);
						$newFileHandler = new $targetClass;
						$newFileHandler->setCollectionId($targetCollection);

						$fileContainer = new fileContainerS3();
						$fileContainer->path = "original";
						$fileContainer->originalFilename = $originalName;
						$fileContainer->derivativeType = "source";

						$newFileHandler->sourceFile = $fileContainer;

						$newFileHandler->save();
						$fileContainer->setParent($newFileHandler);

						$targetPath = $newFileHandler->sourceFile->getPathToLocalFile();
						rename($localFile, $targetPath);

						if($newFileHandler->sourceFile->copyToRemoteStorage()) {
							$newFileHandler->sourceFile->ready = true;

							$fileHandler->deleteFile();

							$this->pheanstalk->touch($job);
							$newFileHandler->regenerate = true;

							$newFileHandler->save();
							$uploadContents->fileHandler = $newFileHandler;
							$newFileHandler->sourceFile->removeLocalFile();

						}
						else {
							$this->logging->logError("collectionMigration", "error migrating asset to new collection " . $targetCollection, $objectId);
						}

					}
				}

				$assetModel->setGlobalValue("collectionMigration", false);
				$assetModel->setGlobalValue("collectionId", $targetCollection);
				$assetModel->forceCollection = true;
				$assetModel->save();

				echo "Files finished: ". $objectId . "\n";
				$this->pheanstalk->delete($job);


			}




			$cnt++;

			$memory = memory_get_usage();

			if($memory > 100000000) {
				echo "exiting run due to memory limit";
				exit;
			}
			$this->doctrine->em->clear();
			sleep(1);
		}
	}


	public function cleanupSource() {
		if (file_exists($this->config->item("scratchSpace"))) {
			$this->deleteDir($this->config->item("scratchSpace"));
		}

	}

	private function deleteDir($path) {
		$directory = new RecursiveDirectoryIterator($path,RecursiveDirectoryIterator::SKIP_DOTS);
		$iterator = new RecursiveIteratorIterator($directory, RecursiveIteratorIterator::CHILD_FIRST);

		foreach ($iterator as $fileInfo) {
    		if (time() - $fileInfo->getCTime() >= 12*60*60) {
        		if(is_file($fileInfo->getRealPath())) {
					if($fileInfo->getRealPath() == "/scratch/swap") {
						continue;
					}
        			unlink($fileInfo->getRealPath());
        		}
        		else {
        			$this->deleteDir($fileInfo->getRealPath());
        			@rmdir($fileInfo->getRealPath());
        		}

 		   	}
		}
	}

	

	public function populateCacheTube() {
		$this->pheanstalk = new \Pheanstalk\Pheanstalk($this->config->item("beanstalkd"));
		echo "Launching populateCacheTube\n";
		while(1) {

			$job = $this->pheanstalk->watch('cacheRebuild')->ignore('default')->reserve();
			if(!is_object($job)) {
				usleep(5);
				continue;
			}
			//reset doctrine in case we've lost the DB
			// TODO: doctrine 2.5 should let us move to pingable and avoid this?
			$this->doctrine->reset();

			$job_encoded = json_decode($job->getData(), true);

			$jobId = $job->getId();
			$templateId = $job_encoded["templateId"];
			$instanceId = $job_encoded["instance"];
			echo "Starting index job for " . $templateId . "\n";
			$this->instance = $this->doctrine->em->find("Entity\Instance", $instanceId);
			

			$parentArray = array();
			$parentArray[] = $templateId;
			$this->reindexTemplate($templateId, $parentArray);
			
			// this array should already be free from duplicates, but just in case
			$deduped = array_unique($parentArray);
			

			$count = 0;
			foreach($deduped as $templateToReindex) {
				echo "Rebuilding template: " . $templateToReindex . "\n";
				$qb = $this->doctrine->em->createQueryBuilder();
				$qb->from("Entity\Asset", 'a')
					->select("a.assetId")
					->where("a.deleted != TRUE")
					->orWhere("a.deleted IS NULL")
					->andWhere("a.assetId IS NOT NULL")
					->orderby("a.id", "desc");
				$qb->andWhere("a.templateId = ?1");
				$qb->setParameter(1, $templateToReindex);

				$result = $qb->getQuery()->iterate();

				foreach($result as $entry) {
					$entry = array_pop($entry);
					$newTask = json_encode(["objectId"=>$entry["assetId"], "instance"=>$instanceId]);
					$jobId= $this->pheanstalk->useTube('reindex')->put($newTask, NULL, 1);
					$entry = null;
					$newTask = null;
					$count++;
					if($count % 100 == 0) {
						gc_collect_cycles();
						$this->pheanstalk->touch($job);
						$this->doctrine->em->clear();
					}
				}
				echo "Finished reindexing assets.\n";
				
				
			}

			$this->pheanstalk->delete($job);
			$this->doctrine->em->clear();
		}

	}

	public function rebuildCache() {
		while(1) {
			sleep(10);
		}
	}


	public function reindexTemplate($templateId, &$parentArray=array()) {
		
		$manager = $this->doctrine->em->getConnection();
		$results = $manager->query('select template_id from widgets where field_data @> \'{"defaultTemplate": ' . $templateId . '}\' OR field_data @> \'{"matchAgainst": [' . $templateId . ']}\'');
		$foundItems = array();
		if($results) {
			$records = $results->fetchAll();
			if(count($records)>0) {
				foreach($records as $record) {
					if($record['template_id'] != null) {
						if(!in_array($record['template_id'], $parentArray)) {
							$foundItems[] = $record['template_id'];
						}
					}
				}
			}
		}
		$parentArray = array_merge($foundItems, $parentArray);
		foreach($foundItems as $rootTemplate) {
			$this->reindexTemplate($rootTemplate, $parentArray);
		}
	}


	
	public function urlImport() {
		$this->pheanstalk = new \Pheanstalk\Pheanstalk($this->config->item("beanstalkd"));

		$count=0;
		while(1) {
			$job = $this->pheanstalk->watch('urlImport')->ignore('default')->reserve();
			if(!is_object($job)) {
				usleep(5);
				continue;
			}
			//reset doctrine in case we've lost the DB
			// TODO: doctrine 2.5 should let us move to pingable and avoid this?
			$this->doctrine->reset();
			
			$this->job = $job;
			
			$job_encoded = json_decode($job->getData(), true);

			$instanceId = $job_encoded["instance"];
			$this->instance = $this->doctrine->em->find("Entity\Instance", $instanceId);

			$objectId = $job_encoded["objectId"];
			if(!$objectId || !is_string($objectId)) {
				$this->pheanstalk->delete($job);
				continue;
			}
			echo "Importing files for: " . $objectId . "\n";

			$assetModel = new Asset_model($objectId);
			$assetArray = $assetModel->getAsArray();

			$fileArray = $job_encoded["importItems"];
			foreach($fileArray as $importEntry) {

				if(!is_numeric($assetModel->getGlobalValue("collectionId"))) {
					continue;
				}
				$finalURL = getFinalURL($importEntry['url']);
				$parsedURL = parse_url($finalURL, PHP_URL_PATH);
				$urlFile = basename($parsedURL);
				$description = isset($importEntry["description"])?$importEntry["description"]:null;
				$fileContainer = new fileContainerS3();
				$fileContainer->originalFilename = $urlFile;

				// this handler type may get overwritten later - for example, once we identify the contents of a zip
				$fileHandler = $this->filehandler_router->getHandlerForType($fileContainer->getType());
				$captionText = null;
				$chapterText = null;
				if($importEntry['captions']) {
					try {
						echo "Importing Captions\n";
						$captionText = file_get_contents($importEntry['captions']);
					}
					catch(Exception $e) {
					}
				}
				if($importEntry["chapters"]) {
					try {
						echo "Importing Chapters\n";
						$chapterText = file_get_contents($importEntry["chapters"]);
					}
					catch(Exception $e) {
					}
					
				}
				$fileHandler->sourceFile = $fileContainer;
				$fileHandler->parentObjectId = $assetModel->getObjectId();
				$fileHandler->setCollectionId($assetModel->getGlobalValue("collectionId"));
				$fileId = $fileHandler->save();
				
				$fileContainer->path = "original";
				$fileContainer->storageType = $this->instance->getS3StorageType();
				$fileContainer->derivativeType = "source";
				$fileContainer->setParent($fileHandler);
				$fileContainer->ready = false;

				
				
				
				$localPath = $fileContainer->getPathToLocalFile();
				stream_context_set_default( [
					'ssl' => [
						'verify_peer' => false,
						'verify_peer_name' => false,
					],
				]);
				$headers = get_headers($finalURL);
				foreach($headers as $header) {

					$len = strlen($header);
					if( !strstr($header, ':') )
					{
					    $this->response = trim($header);
					    continue;
					}
					list($name, $value) = explode(':', $header, 2);
					if( strcasecmp($name, 'Content-Disposition') == 0 )
					{
					    $parts = explode(';', $value);
					    if( count($parts) > 1 )
					    {
					        foreach($parts AS $crumb)
					        {
					            if( strstr($crumb, '=') )
					            {
					                list($pname, $pval) = explode('=', $crumb);
					                $pname = trim($pname);
					                if( strcasecmp($pname, 'filename') == 0 )
					                {
					                    // Using basename to prevent path injection
					                    // in malicious headers.
					                    $this->remoteFileName = basename(
					                        $this->unquote(trim($pval)));
					                }
					            }
					        }
					    }
					}

					$this->headers[$name] = trim($value);
				}
      
				$extractString = "curl -k -L -s -o '" . $localPath . "' " . escapeshellarg($finalURL);
				$process = new Cocur\BackgroundProcess\BackgroundProcess($extractString);
				$process->run();
				while($process->isRunning()) {
					sleep(5);
					$this->pheanstalk->touch($job);
					echo ".";
				}

				if($this->remoteFileName) {
					$fileContainer->originalFilename = $this->remoteFileName;
				}
				$this->remoteFileName = null;

				echo $localPath . "\n";
				if(file_exists($localPath)) {
					if($fileContainer->copyToRemoteStorage()) {
						$assetArray[$importEntry['field']][] = ["fileId"=>$fileId, "regenerate"=>"On", "fileDescription"=>$description];							
						if(isset($captionText)) {
							$assetArray[$importEntry['field']][0]['sidecars'] = [];
							$assetArray[$importEntry['field']][0]['sidecars']['captions'] = $captionText;
							var_dump($assetArray);
						}
						if(isset($chapterText)) {
							if(!isset($assetArray[$importEntry['field']][0]['sidecars'])) {
								$assetArray[$importEntry['field']][0]['sidecars'] = [];
							}
							$assetArray[$importEntry['field']][0]['sidecars']['chapters'] = $chapterText;
						}
						unlink($localPath);
					}
					else {
						$this->logging->logError("error importing to " . $assetModel->getObjectId(), $importEntry);
						echo "Error Importing: " . $fileId . " " . $assetModel->getObjectId() . "\n";
					}
				}
				else {
					$this->logging->logError("error importing to " . $assetModel->getObjectId(), $importEntry);
					echo "Error loading " . $localPath . "\n";
				}
				$fileHandler->sourceFile->ready = true;
				$fileHandler->save();
				echo $fileContainer->getURLForFile() . "\n";
				$this->pheanstalk->touch($job);

			}
			$assetModel->createObjectFromJSON($assetArray);
			$assetModel->save();

			$this->pheanstalk->delete($job);
			$this->doctrine->em->clear();
			$count++;
			if($count % 10 == 0) {
				gc_collect_cycles();
			}
		}

	}

	
	private function unquote($string)
    {
        return str_replace(array("'", '"'), '', $string);
    }
}
/* End of file  */
/* Location: ./application/controllers/ */
