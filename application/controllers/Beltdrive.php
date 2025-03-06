<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
ini_set('max_execution_time', 0);


class Beltdrive extends CI_Controller {

	public $instance = null;
	public $qb;
	public $reserveCount = array();
	public $remoteFileName = null;
	

	public function __construct()
	{
		parent::__construct();

		\Sentry\init([
			'dsn' => $this->config->item('sentry_dsn'),
			'environment' => (defined(ENVIRONMENT) ? ENVIRONMENT:"development"),
			'server_name' => $this->config->item('authHelper')
		]);
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

		$pheanstalk =  Pheanstalk\Pheanstalk::create($this->config->item("beanstalkd"));
		$tube = new Pheanstalk\Values\TubeName('reindex');
		$cnt=0;
		
		while(1) {
			$pheanstalk->watch($tube);
			$job = $pheanstalk->reserve();
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
				$pheanstalk->delete($job);
				continue;
			}
			echo "Reindexing " . $objectId . "\n";
			$assetModel = new Asset_model();
			$assetModel->loadAssetById($objectId);

			$parentArray = array();

			$assetModel->reindex($parentArray);
			$assetModel = null;
			$pheanstalk->delete($job);
			$this->doctrine->em->clear();
		}
	}

	public function processAWSBatchJob($fileObjectId) {

		if(strlen($fileObjectId) != 24) {
			return 0;
		}

		// set scratchspace to include fileobject
		$this->config->set_item("scratchSpace", $this->config->item("scratchSpace") . "/" . $fileObjectId);
		
		$runOnce = true;
		while($runOnce) {
			$runOnce = false;
			$fileHandler = $this->filehandler_router->getHandlerForObject($fileObjectId);

			if(!$fileHandler) {
				return 0;
			}
			
			$fileHandler->loadByObjectId($fileObjectId);
			$collection = $this->collection_model->getCollection($fileHandler->collectionId);
			if($collection) {
				$instances = $collection->getInstances();
				if(count($instances) > 0) {
					$instance = $instances[0];
					$instanceId = $instance->getId();
					$this->instance = $instance;
				}
			}
			foreach($fileHandler->taskArray as $task) {
				echo "Performing task " . $task["taskType"] . "\n";
				if($task["taskType"] == "waitForCompletion") {
					continue;
				}
				// reload each time to make sure artifacts get properly populated
				$fileHandler->loadByObjectId($fileObjectId);
				// lookup instance based on this file's collection.
				$performTaskByName = $fileHandler->performTaskByName($task["taskType"], array_merge($task["config"], ["runInLoop"=>true]));
				if($performTaskByName == JOB_FAILED) {
					// do some logging?
					return JOB_FAILED;
				}
				else {
					echo "Success!\n";
					$fileHandler->save();
				}

				if($fileHandler->taskListHasChanged) {
					echo "Task list changed\n";
					$runOnce = true;
					break;
				}
			}
		}
	}


	public function prepareDrawers() {

		$pheanstalk =  Pheanstalk\Pheanstalk::create($this->config->item("beanstalkd"));
		$tube = new Pheanstalk\Values\TubeName('archiveTube');
		$cnt=0;
		while(1) {
			$pheanstalk->watch($tube);
			$job = $pheanstalk->reserve();

			$stats = $pheanstalk->statsJob($job);
			if($stats->reserves > 200) {
				$this->logging->processingInfo("job", "drawerPrep", "drawer attempted 200 times", $job_encoded['drawerId'], $job->getId());
				$pheanstalk->bury($job);
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

			// mount the storage
			exec("sudo /usr/local/bin/ebs-mount.sh");
			// make sure we hold an open file on the mount while we're working
			$fp = fopen("/scratch/hold_file", "w");
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
					$pheanstalk->touch($job);
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
					$pheanstalk->touch($job);
					$this->s3_model->putObject($zipname, "drawer/".$drawerId.".zip", $instance->getS3StorageType());

					foreach($derivativeArray as $bestDerivative) {
						$bestDerivative->removeLocalFile();
					}
					unlink($zipname);

					$pheanstalk->delete($job);

					$targetURL = site_url($instance->getDomain() . "/drawers/downloadDrawer/" . $drawerId);

					$drawerContent = $this->load->view("email/drawerReady", ["drawerId"=>$drawerId, "targetURL"=>$targetURL], true);

					$this->load->library('email');
					$this->email->from('no-reply@elevatorapp.net', 'Elevator');
					$this->email->set_newline("\r\n");
					$this->email->to($userEmail);
					$this->email->subject("Drawer Ready for Download");
					$this->email->message($drawerContent);
					$this->email->send();
					
					echo "Job Complete\n";

				}
				else {
					echo "Postponing drawer " . $job_encoded['drawerId'] . "\n";
					$pheanstalk->release($job, Pheanstalk\Pheanstalk::DEFAULT_PRIORITY, 5);
				}

			}
			else {
				$pheanstalk->delete($job);
			}

			fclose($fp);
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


		$this->load->model("filehandlerbase");
		$pheanstalk =  Pheanstalk\Pheanstalk::create($this->config->item("beanstalkd"));
		$tube = new Pheanstalk\Values\TubeName('restoreTube');
		$cnt=0;
		$pheanstalk->watch($tube);
		while(1) {
			$job = $pheanstalk->reserve();

			//reset doctrine in case we've lost the DB
			// TODO: doctrine 2.5 should let us move to pingable and avoid this?
			$this->doctrine->reset();


			$job_encoded = json_decode($job->getData(), true);

			echo "running job ". $job_encoded["objectId"]. "\n";

			$objectId = $job_encoded["objectId"];
			$userEmail = $job_encoded["userContact"];
			$instanceId = $job_encoded["instance"];
			$pathToFile = $job_encoded["pathToFile"];
			$nextTask = $job_encoded["nextTask"];
			$instance = $this->doctrine->em->find("Entity\Instance", $instanceId);
			$this->filehandlerbase = $this->filehandler_router->getHandledObject($objectId);

			if($this->filehandlerbase) {

				if($this->filehandlerbase->sourceFile->isArchived()) {
					echo "Postpononing restore watch for " . $objectId . "\n";
					$pheanstalk->release($job, Pheanstalk\Pheanstalk::DEFAULT_PRIORITY, 120);
				}
				else {
					$pheanstalk->delete($job);
					if($nextTask == "notify") {
						echo "File finished". $objectId . "\n";
						$fileContent = $this->load->view("email/fileReady", ["pathToFile"=>$pathToFile], true);
						$this->load->library('email');
						$this->email->from('no-reply@elevatorapp.net', 'Elevator');
						$this->email->set_newline("\r\n");
						$this->email->to($userEmail);
						$this->email->subject("File Ready for Download");
						$this->email->message($fileContent);
						$this->email->send();
					}
					else if($nextTask == "create_derivative") {
						$this->filehandlerbase->queueBatchItem();
					}
					


				}
			}
			else {
				echo "File handler not found";
				$pheanstalk->delete($job);
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

		
		$this->load->model("filehandlerbase");
		$this->load->model("asset_model");
		$pheanstalk =  Pheanstalk\Pheanstalk::create($this->config->item("beanstalkd"));
		$tube = new Pheanstalk\Values\TubeName('collectionMigration');
		$cnt=0;
		$pheanstalk->watch($tube);
		while(1) {
			$job = $pheanstalk->reserve();
			
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
				$pheanstalk->release($job, Pheanstalk\Pheanstalk::DEFAULT_PRIORITY, 900);
			}
			else {
				exec("sudo /usr/local/bin/ebs-mount.sh");
				// make sure we hold an open file on the mount while we're working
				$fp = fopen("/scratch/hold_file", "w");
				foreach($uploadHandlers as $uploadHandler) {
					foreach($uploadHandler->fieldContentsArray as $key=>$uploadContents) {

						$fileHandler = $uploadContents->getFileHandler();
						$fileHandler->sourceFile->copyToLocalStorage();
						$localFile = $fileHandler->sourceFile->getPathToLocalFile();
						$originalName = $fileHandler->sourceFile->originalFilename;
						$pheanstalk->touch($job);
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

							$pheanstalk->touch($job);
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
				fclose($fp);
				$assetModel->setGlobalValue("collectionMigration", false);
				$assetModel->setGlobalValue("collectionId", $targetCollection);
				$assetModel->forceCollection = true;
				$assetModel->save();

				echo "Files finished: ". $objectId . "\n";
				$pheanstalk->delete($job);


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
		$pheanstalk =  Pheanstalk\Pheanstalk::create($this->config->item("beanstalkd"));
		$cnt=0;
		while(1) {
			$tube = new Pheanstalk\Values\TubeName('cacheRebuild');
			$pheanstalk->watch($tube);
			$job = $pheanstalk->reserve();

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
					$tube = new Pheanstalk\Values\TubeName('reindex');
					$pheanstalk->useTube($tube);
					$jobId = $pheanstalk->put($newTask, Pheanstalk\Pheanstalk::DEFAULT_PRIORITY, 1);
					$entry = null;
					$newTask = null;
					$count++;
					if($count % 100 == 0) {
						gc_collect_cycles();
						$pheanstalk->touch($job);
						$this->doctrine->em->clear();
					}
				}
				echo "Finished reindexing assets.\n";
				
				
			}

			$pheanstalk->delete($job);
			$this->doctrine->em->clear();
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
		
		$count=0;
		$pheanstalk =  Pheanstalk\Pheanstalk::create($this->config->item("beanstalkd"));
		$tube = new Pheanstalk\Values\TubeName('urlImport');
		$count=0;
		while(1) {
			$pheanstalk->watch($tube);
			$job = $pheanstalk->reserve();
			//reset doctrine in case we've lost the DB
			// TODO: doctrine 2.5 should let us move to pingable and avoid this?
			$this->doctrine->reset();
			
			$this->job = $job;
			
			$job_encoded = json_decode($job->getData(), true);

			$instanceId = $job_encoded["instance"];
			$this->instance = $this->doctrine->em->find("Entity\Instance", $instanceId);

			$objectId = $job_encoded["objectId"];
			if(!$objectId || !is_string($objectId)) {
				$pheanstalk->delete($job);
				continue;
			}
			echo "Importing files for: " . $objectId . "\n";

			// run the ebs-mount shell script to mount the storage
			exec("sudo /usr/local/bin/ebs-mount.sh");
			// make sure we hold an open file on the mount while we're working
			$fp = fopen("/scratch/hold_file", "w");
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
					$pheanstalk->touch($job);
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
				$pheanstalk->touch($job);

			}
			$assetModel->createObjectFromJSON($assetArray);
			$assetModel->save(true,false);

			$pheanstalk->delete($job);
			$this->doctrine->em->clear();
			$count++;
			fclose($fp);
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
