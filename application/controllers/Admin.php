<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require_once 'beanstalk_console/lib/include.php';

class admin extends Admin_Controller {

	public function __construct()
	{
		parent::__construct();

		if(!$this->input->is_cli_request() && !$this->user_model->getIsSuperAdmin()) {
			instance_redirect("errorHandler/error/noPermission");
			return;
		}
	}

 
	public function index()
	{

		$this->template->javascript->add("/assets/js/handlebars-v1.1.2.js");
		$this->template->javascript->add('assets/js/groupCreation.js');
		$this->template->content->view('handlebarsTemplates');
		$this->template->content->view('admin/index');
		$this->template->publish();

	}

	public function userLookup() {
		$userId = $this->input->post("inputGroupValue");
		instance_redirect("permissions/editUser/" . $userId);
	}


	public function loadRecordAndReindex() {
		$this->load->model("asset_model");
		$start = microtime(true);
		$this->instance = $this->doctrine->em->find("Entity\Instance", 1);
		$this->asset_model->loadAssetById("585d3408ba98a8f9404059c2");
		$this->asset_model->save(false, false);
		$this->asset_model->reindex();
		$end = microtime(true);
		echo "took" . ($end - $start) . "\n";
	}

	public function clearRecordsFromSearch() {
		$searchArchiveEntry = $this->doctrine->em->find('Entity\SearchEntry', "8c3c897f-c096-4e27-8b3e-e81fc1c66e6b");
		$searchArray = $searchArchiveEntry->getSearchData();
		if(isset($searchArray['showHidden'])) {
			// This will include items that are not yet flagged "Ready for display"
			$showHidden = true;
		}
		$this->instance = $this->doctrine->em->find("Entity\Instance", 31);
		$this->load->model("asset_model");
		$this->load->model("search_model");
		$matchArray = $this->search_model->find($searchArray, !$showHidden, 0, true);
		foreach($matchArray["searchResults"] as $match) {
			$params['index'] = $this->config->item('elasticIndex');
    		$params['type']  = 'asset';
    		$params['id']    = $match;
    		if(!$params['id'] || strlen($params['id']<5)) {
    			// if you don't pass an id, elasticsearch will eat all your data
    			echo "crap";
    			break;
    		}
    		$ret = $this->search_model->es->delete($params);
		}
	}

	
	public function fixRecords() {
		return;
		$this->load->model("asset_model");
		$this->load->model("search_model");
		
		$this->instance = $this->doctrine->em->find("Entity\Instance", 12);

		$qb = $this->doctrine->em->createQueryBuilder();
		$qb->from("Entity\Asset", 'a')
		->select("a")
		->where("a.deleted != TRUE")
		->orWhere("a.deleted IS NULL")
		->andWhere("a.assetId IS NOT NULL");

		$qb->andWhere("a.collectionId = ?1");
		$qb->setParameter(1, 113);



		$result = $qb->getQuery()->iterate();


		$count = $startValue;
		foreach($result as $entry) {
			$entry = $entry[0];
			$assetModel = new asset_model();
			$searchModel = new search_model();
			// $before = microtime(true);
			if($assetModel->loadAssetFromRecord($entry)) {

				$json = $assetModel->getAsArray();

				$changed = false;
				if(array_key_exists("location_12", $json) && stristr($json["location_12"][0]["fieldContents"], "Dittman")) {
					$changed = true;
					$json["location_12"][0]["fieldContents"] = str_replace("Dittmann", "Center for Art and Dance", $json["location_12"][0]["fieldContents"]);

				}
				if(array_key_exists("loc_12", $json) && strstr($json["loc_12"][0]["fieldContents"], "DC")) {
					$changed = true;
					$json["loc_12"][0]["fieldContents"] = str_replace("DC", "CAD", $json["loc_12"][0]["fieldContents"]);
					
				}
				if($changed) {
					echo "Saving" . $assetModel->getObjectId();
					$assetModel->loadWidgetsFromArray($json);
					$assetModel->save();
				}
			}
		}

	}


	public function reindex($targetIndex=null, $wipe=null, $startValue=0, $maxValue=0, $searchKey = null, $searchValue = null, $lastModifiedDate=null) {
		set_time_limit(0);
		ini_set('max_execution_time', 0);
		$this->doctrine->extendTimeout();

		$this->load->model("asset_model");
		$this->load->model("search_model");

		if($targetIndex !== "false" && $targetIndex) {
			$this->config->set_item('elasticIndex', $targetIndex);
		}


		$qb = $this->doctrine->em->createQueryBuilder();
		$qb->from("Entity\Asset", 'a')
		->select("a")
		->where("a.deleted != TRUE")
		->orWhere("a.deleted IS NULL")
		->andWhere("a.assetId IS NOT NULL")
		->orderby("a.id", "desc");

		if($searchKey && $searchValue && $searchKey !== "false" && $searchValue !== "false") {
			$qb->andWhere("a." . $searchKey ." = ?1");
			$qb->setParameter(1, $searchValue);
		}

		if($startValue > 0) {
			$qb->setFirstResult($startValue);
		}
		if($maxValue > 0) {
			$qb->setMaxResults($maxValue);
		}

		if($lastModifiedDate !== "false" && $lastModifiedDate) {
			$qb->andWhere("a.modifiedAt > ?1");
			$qb->setParameter(1, $lastModifiedDate);
		}

		$result = $qb->getQuery()->iterate();

		if($wipe == "true") {
			// echo "are you sure?"; // adding this because we had an index go missing, need to see if it's a bug in this logic.
			// die;
			$this->search_model->wipeIndex();
		}


		$count = $startValue;
		$searchModel = new search_model();
		foreach($result as $entry) {
			$entry = $entry[0];
			$assetModel = new asset_model();
			// $searchModel = new search_model();
			// $before = microtime(true);
			if($assetModel->loadAssetFromRecord($entry)) {
				// $after = microtime(true);
				// echo "Load Took:" . ($after - $before) . "\n";
				$noIndex = false;
				if($assetModel->getGlobalValue('availableAfter')) {
					date_default_timezone_set('UTC');
					$afterDate = $assetModel->getGlobalValue('availableAfter');
					if($afterDate > new DateTime()) {
						$noIndex=true;
					}
				}

				if($assetModel->asset_template && !$noIndex) {
					echo "updating: " . $assetModel->getObjectId(). " (".$count.")\n";
					$searchModel->addOrUpdate($assetModel, true);
					$count++;
				}

			}
			// unset($searchModel);
			unset($assetModel);
			$this->doctrine->em->clear();
			if($count % 100 == 0) {
				gc_collect_cycles();
			}
			if($count % 500 == 0) {
				echo "Flushing bulk update\n";
				$searchModel->flushBulkUpdates();
			}
			if($count % 10000 == 0) {
				$this->doctrine->reset();
				$this->doctrine->extendTimeout();
			}
		}
		echo "Flushing final bulk update\n";
		$searchModel->flushBulkUpdates();
		echo "Completed reindexing " . $count . "\n";

		instance_redirect("admin");

	}

	public function regenerateFilesOfType($handlerClass, $inCollection = null) {

		$handlers = $this->doctrine->em->getRepository("Entity\FileHandler")->findBy(["handler"=>$handlerClass, "deleted"=>false]);
		foreach($handlers as $handler) {
			$collection = $this->collection_model->getCollection($handler->getCollectionId());
			if(!$collection) {
				echo "Bad Item: " . $handler->getFileObjectId() . "\n";
				continue;
			}
			if($inCollection != $collection->getId()) {
				continue;
			}
			$instance = $collection->getInstances();
			$this->instance = $instance[0];
			echo "Regenerating " . $handler->getFileObjectId() . "\n";
			$fileHandler = $this->filehandler_router->getHandledObject($handler->getFileObjectId());
			$fileHandler->regenerate = true;
			$fileHandler->save();
		}
		echo "done.\n";

	}

	public function recacheFilesFromCollection($collectionId, $skip=0) {
		$saveArray["collectionId"] = $collectionId;
		if($templateId) {
			$saveArray["templateId"] = $templateId;
		}
		$qb = $this->doctrine->em->createQueryBuilder();
		$qb->from("Entity\Asset", 'a')
		->select("a")
		->where("a.collectionId = ?1")
		->setParameter(1, $collectionId);

		if($skip>0) {
			$qb->setFirstResult($skip);
		}

		$assets = $qb->getQuery()->iterate();
		// $assets = $this->doctrine->em->getRepository("Entity\Asset")->findBy($saveArray);
		$this->load->model("asset_model");
		$this->load->model("asset_template");
		$countStart = $skip;
		foreach($assets as $assetRecord) {
			if(!$assetRecord[0]->getAssetId()) {
				continue;
			}
			$asset = new Asset_model();
			echo "Loading Asset: " . $assetRecord[0]->getAssetId() . "\n";
			$asset->loadAssetFromRecord($assetRecord[0]);
			echo "Recaching: " . $asset->getObjectId() . "\n";
			$asset->buildCache();
			$this->doctrine->em->clear();
			echo "count: " . $countStart . "\n";
			$countStart++;
		}
		echo "done.\n";

	}

	public function resaveAll($targetIndex=null, $wipe=null, $startValue=0, $maxValue=0, $searchKey = null, $searchValue = null, $lastModifiedDate=null) {
		
		if($targetIndex !== "false" && $targetIndex) {
			$this->config->set_item('elasticIndex', $targetIndex);
		}


		$qb = $this->doctrine->em->createQueryBuilder();
		$qb->from("Entity\Asset", 'a')
		->select("a")
		->where("a.deleted != TRUE")
		->orWhere("a.deleted IS NULL")
		->andWhere("a.assetId IS NOT NULL")
		->orderby("a.id", "desc");

		if($searchKey && $searchValue && $searchKey !== "false" && $searchValue !== "false") {
			$qb->andWhere("a." . $searchKey ." = ?1");
			$qb->setParameter(1, $searchValue);
		}

		if($startValue > 0) {
			$qb->setFirstResult($startValue);
		}
		if($maxValue > 0) {
			$qb->setMaxResults($maxValue);
		}

		if($lastModifiedDate !== "false" && $lastModifiedDate) {
			$qb->andWhere("a.modifiedAt > ?1");
			$qb->setParameter(1, $lastModifiedDate);
		}

		$result = $qb->getQuery()->iterate();

		$this->load->model("asset_model");
		$this->load->model("asset_template");
		foreach($result as $entry) {
			$entry = $entry[0];
			if($entry->getAssetId() === NULL) {
				continue;
			}
			$asset = new Asset_model();
			$asset->loadAssetFromRecord($entry);
			echo "Loading: " . $asset->getObjectId() . "\n";
			echo "Resaving: " . $asset->getObjectId() . "\n";
			// $asset->save(false, false);
			$this->doctrine->em->clear();


		}
		echo "done.\n";

	}

	public function logs() {

		$data['lastErrors'] = $this->doctrine->em->getRepository("Entity\Log")->findBy([], ["id"=>"desc"],50);
		$this->template->content->view("admin/logs", $data);
		$this->template->publish();

	}

	public function processingLogs() {
		$data['lastErrors'] = $this->doctrine->em->getRepository("Entity\JobLog")->findBy([], ["id"=>"desc"],30);
		$this->template->content->view("admin/jobLogs", $data);
		$this->template->publish();
	}

	public function beanstalk() {
		$this->template->javascript->add("assets/js/beanstalk.js");
		$console = new Console($this->config->item("beanstalkd") . ":11300");
		$errors = $console->getErrors();
		$tplVars = $console->getTplVars();
		$tplVars['servers'] = [$tplVars['server']];
		$tplVars['console'] = $console;
		$this->template->content->view('beanstalk/index', $tplVars);
		$this->template->publish();

	}


	/**
	 * find files that have been flagged for deletion so we can delete them with MFA
	 * @return html
	 */
	public function showPendingDeletes() {

		$this->load->model("filehandlerbase");
		$fileList = $this->filehandlerbase->findDeletedItems();

		$deletionArray = array();
		foreach($fileList as $fileEntry) {
			$fileHandler = $this->filehandler_router->getHandlerForObject($fileEntry->getFileObjectId());
			if($fileHandler) {

				$fileHandler->loadFromObject($fileEntry);
				$deletionArray[] = ["objectId"=>$fileHandler->getObjectId(), "filename"=>$fileHandler->sourceFile->originalFilename];	
			}
		}

		$this->template->content->view('admin/purgeDeletions', ["objectArray"=>$deletionArray]);
		$this->template->publish();
	}

	/**
	 * This can only be used on a single s3 bucket (potentially just one collection) at a time
	 * the UI can be smart about this I guess?
	 */
	public function purgeAll() {
		set_time_limit(300);
		$this->load->model("filehandlerbase");
		$fileList = $this->filehandlerbase->findDeletedItems();
		$mfa = $this->input->post("mfa");
		$serial = $this->input->post("arn");

		/**
		 * we need to cache this because MFA is only good once
		 */
		$lastUsedToken = null;
		foreach($fileList as $fileEntry) {
			$fileHandler = $this->filehandler_router->getHandlerForObject($fileEntry->getFileObjectId());
			if(!$fileHandler) { 
				continue;
			}
			
			if(!$fileHandler->loadFromObject($fileEntry)) {
				continue;
			}
			
			if(isset($lastUsedToken)) {
				$fileHandler->s3model->sessionToken = $lastUsedToken;
			}
			try {
				if(!$fileHandler->deleteSource($serial,$mfa)) {
					$this->logging->logError("purgeAll","Could not delete asset with key" . $fileEntry->getFileObjectId());
				}
				$lastUsedToken = $fileHandler->s3model->sessionToken;
			}
			catch (Exception $e) {
				echo $e;
				echo "Deletion fail";
			}

		}

		instance_redirect("/admin");
	}

	/*
	 * Find any assets that had a date hold, see if they should be available now
	 * @return [type] [description]
	 */
	public function updateDateHolds() {
		$now = time();
		$this->load->model("asset_model");
		$this->load->model("search_model");

		$qb = $this->doctrine->em->createQueryBuilder();
		$qb->select("a")->from("Entity\Asset", "a")
			->where("a.availableAfter <= CURRENT_DATE()")
			->andWhere("a.assetId IS NOT NULL")
			->andWhere("a.deleted = FALSE");

		$assets = $qb->getQuery()->execute();
		foreach($assets as $entry) {
			$this->asset_model->loadAssetFromRecord($entry);
			$this->search_model->addOrUpdate($this->asset_model);
			echo $this->asset_model->getAssetTitle(true) . "\n";
		}

	}

	public function hiddenAssets() {
		foreach($this->instance->getCollections() as $collection) {
			$collections[] = $collection->getId();
		}
		$qb = $this->doctrine->em->createQueryBuilder();
		$qb->select("a")->from("Entity\Asset", "a")
			->where("a.readyForDisplay = FALSE")
			->andWhere("a.assetId IS NOT NULL")
			->andWhere("a.collectionId IN(:collections)")
			->setParameter(":collections", $collections)
			->orderby("a.modifiedAt", "desc");

		$assets = $qb->getQuery()->execute();

		$this->load->model("asset_model");
		$hiddenAssetArray = array();
		foreach($assets as $entry) {
			$this->asset_model->loadAssetFromRecord($entry);
			$hiddenAssetArray[] = ["objectId"=>$this->asset_model->getObjectId(), "title"=>$this->asset_model->getAssetTitle(true), "readyForDisplay"=>$this->asset_model->getGlobalValue("readyForDisplay"), "templateId"=>$this->asset_model->getGlobalValue("templateId"), "modifiedDate"=>$this->asset_model->getGlobalValue("modified")];

		}

		$this->template->content->view('user/hiddenAssets', ["isOffset"=>false, "hiddenAssets"=>$hiddenAssetArray]);
		$this->template->publish();
	}


	public function recentAssets() {
		foreach($this->instance->getCollections() as $collection) {
			$collections[] = $collection->getId();
		}

		$qb = $this->doctrine->em->createQueryBuilder();
		$qb->select("a")->from("Entity\Asset", "a")
			->andWhere("a.assetId IS NOT NULL")
			->andWhere("a.collectionId IN(:collections)")
			->setParameter(":collections", $collections)
			->setMaxResults(200)
			->orderby("a.modifiedAt", "desc");

		$assets = $qb->getQuery()->execute();

		$this->load->model("asset_model");
		$hiddenAssetArray = array();
		foreach($assets as $entry) {
			$this->asset_model->loadAssetFromRecord($entry);
			$hiddenAssetArray[] = ["objectId"=>$this->asset_model->getObjectId(), "title"=>$this->asset_model->getAssetTitle(true), "readyForDisplay"=>$this->asset_model->getGlobalValue("readyForDisplay"), "templateId"=>$this->asset_model->getGlobalValue("templateId"), "modifiedDate"=>$this->asset_model->getGlobalValue("modified")];

		}

		$this->template->content->view('user/hiddenAssets', ["isOffset"=>false, "hiddenAssets"=>$hiddenAssetArray]);
		$this->template->publish();
	}

	
	public function deleteFilesFromCollection($collectionId, $skip=0) {
		$saveArray["collectionId"] = $collectionId;
		if($templateId) {
			$saveArray["templateId"] = $templateId;
		}
		$qb = $this->doctrine->em->createQueryBuilder();
		$qb->from("Entity\Asset", 'a')
		->select("a")
		->where("a.collectionId = ?1")
		->setParameter(1, $collectionId);

		if($skip>0) {
			$qb->setFirstResult($skip);
		}

		$assets = $qb->getQuery()->iterate();
		// $assets = $this->doctrine->em->getRepository("Entity\Asset")->findBy($saveArray);
		$this->load->model("asset_model");
		$this->load->model("asset_template");
		$this->load->model("search_model");
		$countStart = $skip;
		foreach($assets as $assetRecord) {
			if(!$assetRecord[0]->getAssetId()) {
				continue;
			}
			$asset = new Asset_model();
			echo "Loading Asset: " . $assetRecord[0]->getAssetId() . "\n";
			$asset->loadAssetFromRecord($assetRecord[0]);
			$asset->delete();
			$this->search_model->remove($asset);
			$this->doctrine->em->clear();
			echo "count: " . $countStart . "\n";
			$countStart++;
		}
		echo "done.\n";

	}

	public function deletedAssets() {
		foreach($this->instance->getCollections() as $collection) {
			$collections[] = $collection->getId();
		}

		$qb = $this->doctrine->em->createQueryBuilder();
		$qb->from("Entity\Asset", 'a')
			->select("a")
			->where("a.deleted = true")
			->orderBy("a.deletedAt", "DESC")
			->setMaxResults(200);

		$assets = $qb->getQuery()->execute();

		$this->load->model("asset_model");
		$hiddenAssetArray = array();
		foreach($assets as $entry) {
			$this->asset_model->loadAssetFromRecord($entry);
			$hiddenAssetArray[] = ["objectId"=>$this->asset_model->getObjectId(), "title"=>$this->asset_model->getAssetTitle(true), "readyForDisplay"=>$this->asset_model->getGlobalValue("readyForDisplay"), "templateId"=>$this->asset_model->getGlobalValue("templateId"), "modifiedDate"=>$this->asset_model->getGlobalValue("modified")];

		}

		$this->template->content->view('user/hiddenAssets', ["isOffset"=>false, "hiddenAssets"=>$hiddenAssetArray]);
		$this->template->publish();
	}

	public function listAPIkeys() {
		$keys = $this->doctrine->em->getRepository("Entity\ApiKey")->findAll();
		$this->template->content->view("admin/listAPIkeys", ["keys"=>$keys]);
		$this->template->publish();
	}

	public function removeAPIkey($apiKey=null) {
		if(!$apiKey) {
			instance_redirect("admin/listAPIkeys");
		}

		$page = $this->doctrine->em->find("Entity\ApiKey", $apiKey);
		$this->doctrine->em->remove($page);
		$this->doctrine->em->flush();
		instance_redirect("admin/listAPIkeys");
	}

	public function editAPIkey($apiKey=null) {

		if($apiKey) {
			$key = $this->doctrine->em->getRepository("Entity\ApiKey")->find($apiKey);
		}
		else {
			$key = new Entity\ApiKey;
		}
		$this->template->content->view("admin/editKey", ["key"=>$key]);
		$this->template->publish();

	}

	public function saveKey() {
		if($this->input->post("keyId")) {
			$key = $this->doctrine->em->getRepository("Entity\ApiKey")->find($this->input->post("keyId"));
		}
		else {
			$key = new Entity\ApiKey();
		}

		$key->setApiKey($this->input->post("apiKey"));
		$key->setApiSecret($this->input->post("apiSecret"));
		$key->setLabel($this->input->post("label"));
		$key->setAllowsRead($this->input->post("read")?1:0);
		$key->setAllowsWrite($this->input->post("write")?1:0);
		$key->setOwner($this->user_model->user);
		$this->doctrine->em->persist($key);
		$this->doctrine->em->flush();
		instance_redirect("admin/listAPIkeys");

	}

	public function importAsset($instanceId, $collectionId, $templateId, $file) {

		$filecontents = file_get_contents($file);
		$decoded = json_decode($filecontents, true);
		$assetArray = $decoded["asset"];

		$files = $decoded["files"];
		foreach($files as $file) {

			$fileHandler = new Entity\FileHandler;
			$fileHandler->setFileObjectId($file["_id"]["\$id"]);
			$fileHandler->setSourceFile($file["sourceFile"]);
			$fileHandler->setDerivatives($file["derivatives"]);
			$fileHandler->setFileType($file["type"]);
			$fileHandler->setHandler($file["handler"]);
			$fileHandler->setCollectionId($collectionId);
			$fileHandler->setDeleted($file["deleted"]);
			$fileHandler->setParentObjectId(null);
			$fileHandler->setGlobalMetadata($file["globalMetadata"]);
			$fileHandler->setJobIdArray([]);
			$this->doctrine->em->persist($fileHandler);
			$this->doctrine->em->flush();
		}

		$this->instance = $this->doctrine->em->find("Entity\Instance", $instanceId);

		$this->load->model("Asset_model");
		$asset = new Asset_model();
		$assetArray["templateId"] = $templateId;
		$assetArray["collectionId"] = $collectionId;
		$assetArray["readyForDisplay"] = true;

		$asset->createObjectFromJSON($assetArray);

		$asset->setGlobalValue("templateId", $templateId);
		$asset->setGlobalValue("collectionId", $collectionId);
		$asset->setGlobalValue("readyForDisplay", true);

		$asset->save(true,false);
		var_dump($asset->getObjectId());


	}

	public function fixACL($offset=0)  {
		$qb = $this->doctrine->em->createQueryBuilder();
		$qb->from("Entity\FileHandler", 'f')
		->select("f.id", 'f.fileObjectId')
		->where("f.deleted != TRUE")
		->orderby("f.id", "desc");

		if($offset > 0) {
			$qb->setFirstResult($offset);
		}
		// $qb->setMaxResults(10000);
		$result = $qb->getQuery()->iterate();
		$count = 0;
		$this->load->model("filehandlerbase");
		foreach($result as $key=>$entry) {
			if($entry[$key]["fileObjectId"] === NULL) {
				continue;
			}
			$asset = new Filehandlerbase();
			try {
				$asset->loadByObjectId($entry[$key]["fileObjectId"]);	
			}
			catch (Exception $e) {
				echo "Error loading record\n";
				continue;
			}
			try {
				$asset->s3model->fixACL($asset->getObjectId());
			}
			catch (Exception $e) {
				echo "ERROR: " . $count .": " . $asset->getObjectId() . ", " . $asset->s3model->bucket . "\n";
			}
			$this->doctrine->em->clear();
			if($count % 100 == 0) {
				gc_collect_cycles();
			}
			echo $count .": " . $asset->getObjectId() . ", " . $asset->s3model->bucket . "\n";
			$count++;
		}
	}


}

/* End of file  */
/* Location: ./application/controllers/ */
