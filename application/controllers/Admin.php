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

	public function reindex($searchKey = null, $searchValue = null) {
		set_time_limit(0);
		ini_set('max_execution_time', 0);
		$this->doctrine->extendTimeout();

		$this->load->model("asset_model");
		$this->load->model("search_model");

		if($searchKey && $searchValue) {
			$result = $this->qb->where($searchKey, (int)$searchValue)->get($this->config->item('mongoCollection'), true);
		}
		else {
			$result = $this->qb->get($this->config->item('mongoCollection'), true);
			$this->search_model->wipeIndex();
		}


		$count = 0;
		while ($result->hasNext()) {
			$assetModel = new asset_model();
			$searchModel = new search_model();
			// $before = microtime(true);
			if($assetModel->loadAssetFromRecord($result->getNext())) {
				// $after = microtime(true);
				// echo "Load Took:" . ($after - $before) . "\n";
				if($assetModel->asset_template) {
					echo "updating" . $assetModel->getObjectId(). "\n";
					$searchModel->addOrUpdate($assetModel);
					$count++;
				}

			}
			unset($searchModel);
			unset($assetModel);
			if($count % 100 == 0) {
				gc_collect_cycles();
			}
			if($count % 10000 == 0) {
				$this->doctrine->reset();
				$this->doctrine->extendTimeout();
			}
		}

		echo "Completed reindexing " . $count . "\n";

		instance_redirect("admin");

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
		$console = new Console;
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
			$fileHandler = $this->filehandler_router->getHandlerForObject($fileEntry["_id"]);
			$fileHandler->loadFromObject($fileEntry);
			$deletionArray[] = ["objectId"=>$fileHandler->getObjectId(), "filename"=>$fileHandler->sourceFile->originalFilename];
		}

		$this->template->content->view('admin/purgeDeletions', ["objectArray"=>$deletionArray]);
		$this->template->publish();
	}

	/**
	 * This can only be used on a single s3 bucket (potentially just one collection) at a time
	 * the UI can be smart about this I guess?
	 */
	public function purgeAll() {
		$this->load->model("filehandlerbase");
		$fileList = $this->filehandlerbase->findDeletedItems();
		$mfa = $this->input->post("mfa");
		$serial = $this->input->post("arn");

		/**
		 * we need to cache this because MFA is only good once
		 */
		$lastUsedToken = null;
		foreach($fileList as $fileEntry) {
			$fileHandler = $this->filehandler_router->getHandlerForObject($fileEntry["_id"]);
			$fileHandler->loadFromObject($fileEntry);
			if(isset($lastUsedToken)) {
				$fileHandler->s3model->sessionToken = $lastUsedToken;
			}
			try {
				if(!$fileHandler->deleteSource($serial,$mfa)) {
					$this->logging->logError("purgeAll","Could not delete asset with key" . $serial);
				}
				$lastUsedToken = $fileHandler->s3model->sessionToken;
			}
			catch (Exception $e) {
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
		$assets = $this->qb->whereLte('availableAfter', new MongoDate($now))->get($this->config->item('mongoCollection'));
		foreach($assets as $entry) {
			$this->asset_model->loadAssetFromRecord($entry);
			$this->search_model->addOrUpdate($this->asset_model);
			echo $this->asset_model->getAssetTitle(true) . "<br />";
		}

	}

	public function hiddenAssets() {
		foreach($this->instance->getCollections() as $collection) {
			$collections[] = $collection->getId();
		}

		$assets = $this->qb->where("readyForDisplay", null)->whereIn("collectionId", $collections)->orderBy(["modified"=>"DESC"])->get($this->config->item('mongoCollection'));
		$this->load->model("asset_model");
		$hiddenAssetArray = array();
		foreach($assets as $entry) {
			$this->asset_model->loadAssetFromRecord($entry);
			$hiddenAssetArray[] = ["objectId"=>$this->asset_model->getObjectId(), "title"=>$this->asset_model->getAssetTitle(true), "readyForDisplay"=>$this->asset_model->getGlobalValue("readyForDisplay"), "modifiedDate"=>$this->asset_model->getGlobalValue("modified")];

		}

		$this->template->content->view('user/hiddenAssets', ["isOffset"=>false, "hiddenAssets"=>$hiddenAssetArray]);
		$this->template->publish();
	}


	public function recentAssets() {
		foreach($this->instance->getCollections() as $collection) {
			$collections[] = $collection->getId();
		}

		$assets = $this->qb->whereIn("collectionId", $collections)->orderBy(["modified"=>"DESC"])->limit(200)->get($this->config->item('mongoCollection'));
		$this->load->model("asset_model");
		$hiddenAssetArray = array();
		foreach($assets as $entry) {
			$this->asset_model->loadAssetFromRecord($entry);
			$hiddenAssetArray[] = ["objectId"=>$this->asset_model->getObjectId(), "title"=>$this->asset_model->getAssetTitle(true), "readyForDisplay"=>$this->asset_model->getGlobalValue("readyForDisplay"), "modifiedDate"=>$this->asset_model->getGlobalValue("modified")];

		}

		$this->template->content->view('user/hiddenAssets', ["isOffset"=>false, "hiddenAssets"=>$hiddenAssetArray]);
		$this->template->publish();
	}

	public function deletedAssets() {
		foreach($this->instance->getCollections() as $collection) {
			$collections[] = $collection->getId();
		}

		$assets = $this->qb->whereIn("collectionId", $collections)->where("deleted",true)->orderBy(["deletedDate"=>"DESC"])->limit(200)->get($this->config->item('historyCollection'));
		$this->load->model("asset_model");
		$hiddenAssetArray = array();
		foreach($assets as $entry) {
			$this->asset_model->loadAssetFromRecord($entry);
			$hiddenAssetArray[] = ["objectId"=>$entry["sourceId"], "deleted"=>true, "title"=>$this->asset_model->getAssetTitle(true), "readyForDisplay"=>$this->asset_model->getGlobalValue("readyForDisplay"), "modifiedDate"=>$this->asset_model->getGlobalValue("modified")];

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


}

/* End of file  */
/* Location: ./application/controllers/ */
