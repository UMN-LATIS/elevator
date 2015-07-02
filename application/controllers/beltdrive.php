<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
ini_set('max_execution_time', 0);
use Aws\S3\Model\ClearBucket;
use Aws\Sts\StsClient;

class Beltdrive extends CI_Controller {

	public $instance = null;
	public $qb;
	public $serverId = null;
	public $reserveCount = array();


	function getMacLinux() {
		exec('netstat -ie', $result);
		if(is_array($result)) {
			$iface = array();
			foreach($result as $key => $line) {
				if($key > 0) {
					$tmp = str_replace(" ", "", substr($line, 0, 10));
					if($tmp <> "") {
						$macpos = strpos($line, "HWaddr");
						if($macpos !== false) {
							$iface[] = array('iface' => $tmp, 'mac' => strtolower(substr($line, $macpos+7, 17)));
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
		$this->serverId = $this->getMacLinux();
		$this->doctrine->extendTimeout();

		$this->load->model("asset_model");
		$this->load->model("asset_template");
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
			$this->asset_model->reindex();
			$this->pheanstalk->delete($job);
			$this->doctrine->em->clear();
		}
	}

	public function processFileTask() {

		$this->pheanstalk = new \Pheanstalk\Pheanstalk($this->config->item("beanstalkd"));
		$cnt=0;
		while(1) {
			$job = $this->pheanstalk->watch('newUploads')->ignore('default')->reserve();
			if(!is_object($job)) {
				usleep(5);
				continue;
			}

			//reset doctrine in case we've lost the DB
			// TODO: doctrine 2.5 should let us move to pingable and avoid this?
			$this->doctrine->reset();


			$job_encoded = json_decode($job->getData(), true);

			$jobId = $job->getId();
			$stats = $this->pheanstalk->statsJob($job);
			$jobPriority = $stats->pri;


			if($stats->reserves > 200) { // if we've tried to process it 200 times, it's probably not gonna work
				$this->logging->processingInfo("job", "transcoder", "file attempted 200 times", $job_encoded['fileHandlerId'], $job->getId());
				$this->pheanstalk->bury($job);
				continue;
			}

			if(isset($job_encoded['host_affinity'])) {

				$targetHost = $job_encoded['host_affinity'];


				if($targetHost != $this->serverId) {
					if(!isset($this->reserveCount[$jobId])) {
						$this->reserveCount[$jobId] = 0;
					}
					$this->reserveCount[$jobId]++;
					if($this->reserveCount[$jobId] <5 ) {
						$this->pheanstalk->release($job, $jobPriority, 1);
						continue;
					}
					elseif($this->reserveCount[$jobId] <10 ) {
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
			echo "Processing " . $fileHandler->sourceFile->originalFilename . " : " . $job_encoded['task'] . "\n";

			$success=false;

			$result = $fileHandler->performTask($job);

			if($result == JOB_SUCCESS) {
				if($fileHandler->save() != false) {
					$fileHandler->removeJob($job->getId());
					$this->pheanstalk->delete($job);
				}
				else {
					echo "Failed updating " . $fileHandler->getObjectId() . "\n";
					$this->pheanstalk->bury($job);
				}
			}
			else if($result == JOB_FAILED) {
				echo "Job Failed\n";
				$this->pheanstalk->bury($job);
			}
			else if($result == JOB_POSTPONE) {
				echo "Postponing " . $fileHandler->sourceFile->originalFilename . " : " . $job_encoded['task'] . "\n";

				$this->pheanstalk->release($job, $jobPriority, $fileHandler->postponeTime);
			}

			echo "Job Complete\n";

			$cnt++;

			if($cnt % 100 == 0) {
				$this->reserveCount = array();
			}

			$memory = memory_get_usage();

			if($memory > 100000000) {
				echo "exiting run due to memory limit";
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

							$handler = $content->fileHandler;
							$bestDerivative = $handler->highestQualityDerivativeForAccessLevel(PERM_ORIGINALSWITHOUTDERIVATIVES);
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
					$id = new MongoId();
					$zipname = $this->config->item("scratchSpace") . $id.'.zip';
    				$zip = new ZipArchive;
    				$zip->open($zipname, ZipArchive::OVERWRITE);
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
					$this->s3_model->putObject($this->config->item("scratchSpace") . $id.'.zip', "drawer/".$drawerId.".zip", $instance->getS3StorageType());

					foreach($derivativeArray as $bestDerivative) {
						$bestDerivative->removeLocalFile();
					}
					unlink($this->config->item("scratchSpace").$id.".zip");

					$this->pheanstalk->delete($job);

					$drawerContent = $this->load->view("email/drawerReady", ["drawerId"=>$drawerId], true);

					mail($userEmail, "Drawer Ready for Download", $drawerContent);
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

					mail($userEmail, "File Ready for Download",	$fileContent);
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
					if($uploadContents->fileHandler->sourceFile && $uploadContents->fileHandler->sourceFile->isArchived()) {
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

						$fileHandler = $uploadContents->fileHandler;
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

		$directory = new RecursiveDirectoryIterator($this->config->item("scratchSpace"),RecursiveDirectoryIterator::SKIP_DOTS);
		$iterator = new RecursiveIteratorIterator($directory, RecursiveIteratorIterator::CHILD_FIRST);
		if (file_exists($this->config->item("scratchSpace"))) {
    		foreach ($iterator as $fileInfo) {
        		if (time() - $fileInfo->getCTime() >= 12*60*60) {
            		if(is_file($fileInfo->getRealPath())) {
            			unlink($fileInfo->getRealPath());
            		}
            		else {
            			rmdir($fileInfo->getRealPath());
            		}

     		   	}
    		}
		}
	}

}

/* End of file  */
/* Location: ./application/controllers/ */
