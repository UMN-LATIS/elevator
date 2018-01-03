<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * dcl_works X
 * dcl_agent X
 * wk_title X
 * wk_event X
 * wk_measure X
 * src_pub
 * orders
 * ag_work
 * dcl_Views
 * mb_files
 *
 */

class dclImporter extends Instance_Controller {

	public $wkid = null;
	public $vwid = null;
	public $agid = null;
	public $srcid =null;
	public $ordid =null;
	public $digitalid = null;
	public $primaryViewId = null;
	public $targetCollection = 35;

	public $rootPathToMedia = "/export/A24FLUN0P1/archive_root/dcl/";

	public $agentObject = array();
	public $workObject = array();

	public function __construct()
	{
		parent::__construct();
		ini_set("memory_limit","4096M");
		// $this->dcl = $this->load->database('old', TRUE);
		$this->instance = $this->doctrine->em->find("Entity\Instance", 7);
		$this->load->model("asset_model");
		$this->user_model->loadUser(1);
		echo "\n";

	}

	public function buildDrawersFromFile($filePath, $targetOwner, $targetInstance) {
		$fp = fopen($filePath, "r");
		$header = fgetcsv($fp);

		$this->instance = $this->doctrine->em->find("Entity\Instance", $targetInstance);
	 	$this->user_model->loadUser($targetOwner);
	 	$drawerAssets = array();
		while($line = fgetcsv($fp)) {

			$drawerTitle = $line[7];
			if(!isset($drawerAssets[$drawerTitle])) {
				$drawerAssets[$drawerTitle] = array();
			}
			$asset = $line[2];
			echo "Adding " . $asset . " to " . $drawerTitle . "\n";
			$drawer = $this->doctrine->em->getRepository("Entity\Drawer")->findOneBy(["title"=>$drawerTitle]);
			if(!$drawer) {
				echo "making new drawer\n";
				$drawer = new Entity\Drawer;
				$drawer->setTitle($drawerTitle);
				$drawer->setInstance($this->instance);
				// \Doctrine\Common\Util\Debug::dump($drawer);
				$this->doctrine->em->persist($drawer);
				$groupObject = $this->user_model->getPermissions("DrawerGroup", "User", $this->user_model->getId(), $this->user_model->user);
				if(count($groupObject)) {
					$groupObject = array_shift($groupObject);
				}
				$groupObject->addDrawer($drawer);

				$permission = new Entity\DrawerPermission;
				$permission->setDrawer($drawer);
				$permission->setGroup($groupObject);
				$permissionType = $this->doctrine->em->getRepository("Entity\Permission")->findOneBy(["level"=>PERM_CREATEDRAWERS]);
				$permission->setPermission($permissionType);
				// \Doctrine\Common\Util\Debug::dump($permission);
				$this->doctrine->em->persist($permission);


				if($line[33] && $line[34] && $line[33] != "NULL") {
					$courseCombo = $line[33] . "." . $line[34];
					if(strlen($courseCombo)>3){
						$courseComboWithPercent = $courseCombo .  "%";
						$groupObject = $this->user_model->getPermissions("DrawerGroup", "Dept/Course Number", $courseCombo, $this->user_model->user);
						if(count($groupObject)) {
							$groupObject = array_shift($groupObject);
						}
						else {
							$groupObject = new Entity\DrawerGroup;
							$groupObject->setUser($this->user_model->user);
							$groupObject->setGroupType("Dept/Course Number");
							$groupValueObject = new Entity\GroupEntry();

							$groupValueObject->setGroupValue($courseComboWithPercent);
							$this->doctrine->em->persist($groupValueObject);
							$groupObject->addGroupValue($groupValueObject);
							$groupObject->setGroupLabel($courseCombo);
							$this->doctrine->em->persist($groupObject);
						}
						$groupObject->addDrawer($drawer);

						$permission = new Entity\DrawerPermission;
						$permission->setDrawer($drawer);
						$permission->setGroup($groupObject);
						$permissionType = $this->doctrine->em->getRepository("Entity\Permission")->findOneBy(["level"=>PERM_DERIVATIVES_GROUP_2]);
						$permission->setPermission($permissionType);
						$this->doctrine->em->persist($permission);
	

					}

				}
				$this->doctrine->em->flush();


			}

			$foundRecord = $this->getExistingRecord("Old DCL Views", "viewid_7", "fieldContents", $asset);
			$assetId = null;
			if($foundRecord) {
				$viewAssetId = $foundRecord['assetid'];
				$asset = new Asset_model($viewAssetId);
				$assetArray = $asset->getAsArray();
				if($assetArray["workid_7"][0]["fieldContents"]) {
					$foundWorkRecord = $this->getExistingRecord("Old DCL Works", "workid_7", "fieldContents", $assetArray["workid_7"][0]["fieldContents"]);	
					if($foundWorkRecord) {
						$assetId = $foundWorkRecord["assetid"];
					}
				}
				
			}
			if(!$assetId) {
				continue;
			}
			if(in_array($assetId, $drawerAssets[$drawerTitle])) {
				echo "skipping\n";
				continue;
			}
			echo "Adding " . $assetId . "\n";
			$drawerAssets[$drawerTitle][] = $assetId;

			$drawerItem = new Entity\DrawerItem;
			$drawerItem->setAsset($assetId);
			$drawerItem->setDrawer($drawer);
			$this->doctrine->em->persist($drawerItem);
			$this->doctrine->em->flush();
			gc_collect_cycles();

		}


	}


	public function fixOrders() {
		set_time_limit(0);
		ini_set('max_execution_time', 0);
		$this->dcl->query("SET SESSION wait_timeout = 28800 ");
		$qb = $this->doctrine->em->createQueryBuilder();
		$qb->from("Entity\Asset", 'a')
		->select("a")
		->where("a.templateId = 23")
		->orderBy("a.id");

		$assets = $qb->getQuery()->iterate();
		$count = 0;
		foreach($assets as $asset) {
			$widgets = $asset->getWidgets();
			if(!isset($widgets["orderid_7"][0]["fieldContents"])) {
				continue;
			}
			$orderId = $widgets["orderid_7"][0]["fieldContents"];


			$this->dcl->where("ord_id", $orderId);
			$result = $this->dcl->get("orders");
			$assetObject = new Asset_model();
			$assetObject->loadAssetFromRecord($asset);
			$jsonContents = $asset->getAsArray();

			foreach($result->result_array() as $entry) {

				if($entry['order_date'] != null && !array_key_exists("order_date", $jsonContents)) {
					$jsonContents["orderdate_7"][]["fieldContents"] = $entry["order_date"];
				}
				if($entry['requester_id'] != null && !array_key_exists("requester_id", $jsonContents)) {
					$jsonContents["requesterinternetid_7"][]["fieldContents"] = $entry["requester_id"];
				}
				if($entry['contributor'] != null && !array_key_exists("contributor", $jsonContents)) {
					$jsonContents["contributordonor_7"][]["fieldContents"] = $entry["contributor"];
				}
				if($entry['deadline'] != null && !array_key_exists("deadline", $jsonContents)) {
					$jsonContents["deadline_7"][]["fieldContents"] = $entry["deadline"];
				}
				if($entry['photographer'] != null && !array_key_exists("photographer", $jsonContents)) {
					$jsonContents["photographer_7"][]["fieldContents"] = $entry["photographer"];
				}
				if($entry['completion'] != null && !array_key_exists("completion", $jsonContents)) {
					$jsonContents["completiondate_7"][]["fieldContents"] = $entry["completion"];
				}
				if($entry['source_type'] != null && !array_key_exists("source_type", $jsonContents)) {
					$jsonContents["sourcetype_7"][]["fieldContents"] = $entry["source_type"];
				}
				if($entry['number_of_items'] != null && !array_key_exists("number_of_items", $jsonContents)) {
					$jsonContents["numberofitems_7"][]["fieldContents"] = $entry["number_of_items"];
				}
				if($entry['subject'] != null && !array_key_exists("subject", $jsonContents)) {
					$jsonContents["subject_7"][]["fieldContents"] = $entry["subject"];
				}
				if($entry['order_number'] != null && !array_key_exists("order_number", $jsonContents)) {
					$jsonContents["ordernumber_7"][]["fieldContents"] = $entry["order_number"];
				}
				if($entry['accession_range'] != null && !array_key_exists("accession_range", $jsonContents)) {
					$jsonContents["accessionrange_7"][]["fieldContents"] = $entry["accession_range"];
				}
				if($entry['instructions'] != null && !array_key_exists("instructions", $jsonContents)) {
					$jsonContents["instructionscommentsnotes_7"][]["fieldContents"] = $entry["instructions"];
				}
				if($entry['admin_notes'] != null && !array_key_exists("admin_notes", $jsonContents)) {
					$jsonContents["adminnotes_7"][]["fieldContents"] = $entry["admin_notes"];
				}

			}

			$assetObject->createObjectFromJSON($jsonContents);
			echo "Updating Record for " . $assetObject->getObjectId() . "(" . $asset->getId() . ")\n";
			$assetObject->save(true,false,false);
			$this->doctrine->em->clear();
			$count++;
			if($count % 10 == 0) {
				gc_collect_cycles();
			}
		}
	}

	public function fixSourcePublications() {
		set_time_limit(0);
		ini_set('max_execution_time', 0);
		$this->dcl->query("SET SESSION wait_timeout = 28800 ");
		$qb = $this->doctrine->em->createQueryBuilder();
		$qb->from("Entity\Asset", 'a')
		->select("a")
		->where("a.templateId = 22")
		->orderBy("a.id");

		$assets = $qb->getQuery()->iterate();
		$count = 0;
		foreach($assets as $asset) {
			$widgets = $asset->getWidgets();
			if(!isset($widgets["sourceid_7"][0]["fieldContents"])) {
				continue;
			}
			$sourceId = $widgets["sourceid_7"][0]["fieldContents"];


			$this->dcl->where("src_id", $sourceId);
			$result = $this->dcl->get("source_publications");
			$assetObject = new Asset_model();
			$assetObject->loadAssetFromRecord($asset);
			$jsonContents = $asset->getAsArray();

			foreach($result->result_array() as $entry) {

				if($entry['publisher'] != null && !array_key_exists("publisher_7", $jsonContents)) {
					$jsonContents["publisher_7"][]["fieldContents"] = $entry["publisher"];
				}
				if($entry['month'] != null && !array_key_exists("month_7", $jsonContents)) {
					$jsonContents["month_7"][]["fieldContents"] = $entry["month"];
				}
				if($entry['source_number'] != null && !array_key_exists("sourcenumber_7", $jsonContents)) {
					$jsonContents["sourcenumber_7"][]["fieldContents"] = $entry["source_number"];
				}
				if($entry['call_number'] != null && !array_key_exists("callnumber_7", $jsonContents)) {
					$jsonContents["callnumber_7"][]["fieldContents"] = $entry["call_number"];
				}
				if($entry['source_format'] != null && !array_key_exists("sourceformat_7", $jsonContents)) {
					$jsonContents["sourceformat_7"][]["fieldContents"] = $entry["source_format"];
				}
				if($entry['admin_notes'] != null && !array_key_exists("adminnotes_7", $jsonContents)) {
					$jsonContents["adminnotes_7"][]["fieldContents"] = $entry["admin_notes"];
				}

			}

			$assetObject->createObjectFromJSON($jsonContents);
			echo "Updating Record for " . $assetObject->getObjectId() . "(" . $asset->getId() . ")\n";
			$assetObject->save(false,false,true);
			$this->doctrine->em->clear();
			$count++;
			if($count % 10 == 0) {
				gc_collect_cycles();
			}
		}
	}

	public function fixWorks() {
		set_time_limit(0);
		ini_set('max_execution_time', 0);
		$this->dcl->query("SET SESSION wait_timeout = 28800 ");
		$qb = $this->doctrine->em->createQueryBuilder();
		$qb->from("Entity\Asset", 'a')
		->select("a")
		->where("a.templateId = 26")
		->orderBy("a.id");

		$assets = $qb->getQuery()->iterate();
		$count = 0;
		foreach($assets as $asset) {
			$widgets = $asset->getWidgets();
			if(!isset($widgets["workid_7"][0]["fieldContents"])) {
				continue;
			}
			$workId = $widgets["workid_7"][0]["fieldContents"];


			$this->dcl->where("wk_id", $sourceId);
			$result = $this->dcl->get("dcl_works");
			$assetObject = new Asset_model();
			$assetObject->loadAssetFromRecord($asset);
			$jsonContents = $asset->getAsArray();

			foreach($result->result_array() as $entry) {

				if($entry['admin_notes'] != null && !array_key_exists("adminnotes_7", $jsonContents)) {
					$jsonContents["adminnotes_7"][]["fieldContents"] = $entry["admin_notes"];
				}

			}

			$assetObject->createObjectFromJSON($jsonContents);
			echo "Updating Record for " . $assetObject->getObjectId() . "(" . $asset->getId() . ")\n";
			$assetObject->save(false,false,true);
			$this->doctrine->em->clear();
			$count++;
			if($count % 10 == 0) {
				gc_collect_cycles();
			}
		}
	}

	public function fixViews() {
		set_time_limit(0);
		ini_set('max_execution_time', 0);
		$this->dcl->query("SET SESSION wait_timeout = 28800 ");
		$qb = $this->doctrine->em->createQueryBuilder();
		$qb->from("Entity\Asset", 'a')
		->select("a")
		->where("a.templateId = 25")
		->orderBy("a.id");

		$assets = $qb->getQuery()->iterate();
		$count = 0;
		foreach($assets as $asset) {
			$widgets = $asset->getWidgets();
			if(!isset($widgets["viewid_7"][0]["fieldContents"])) {
				continue;
			}
			$viewId = $widgets["viewid_7"][0]["fieldContents"];


			$this->dcl->where("vw_id", $viewId);
			$result = $this->dcl->get("dcl_views");
			$assetObject = new Asset_model();
			$assetObject->loadAssetFromRecord($asset);
			$jsonContents = $asset->getAsArray();

			foreach($result->result_array() as $entry) {

				if($entry['accession_number'] != null && !array_key_exists("accession_number", $jsonContents)) {
					$jsonContents["accessionnumber_7"][]["fieldContents"] = $entry["accession_number"];
				}
				if($entry['photographer'] != null && !array_key_exists("photographer", $jsonContents)) {
					$jsonContents["photographer_7"][]["fieldContents"] = $entry["photographer"];
				}
				if($entry['copyright_holder'] != null && !array_key_exists("copyright_holder", $jsonContents)) {
					$jsonContents["copyrightholder_7"][]["fieldContents"] = $entry["copyright_holder"];
				}
				if($entry['copy_history'] != null && !array_key_exists("copy_history", $jsonContents)) {
					$jsonContents["copyhistory_7"][]["fieldContents"] = $entry["copy_history"];
				}
				if($entry['admin_notes'] != null && !array_key_exists("admin_notes", $jsonContents)) {
					$jsonContents["adminnotes_7"][]["fieldContents"] = $entry["admin_notes"];
				}
				if($entry["digitized"] != "Y") {
					$jsonContents["readyForDisplay"] = false;
				}
				else {
					$jsonContents["readyForDisplay"] = true;
				}

			}

			$assetObject->createObjectFromJSON($jsonContents);
			echo "Updating Record for " . $assetObject->getObjectId() . "(" . $asset->getId() . ")\n";
			$assetObject->save(true,false,true);
			$this->doctrine->em->clear();
			$count++;
			if($count % 10 == 0) {
				gc_collect_cycles();
			}
		}
	}


	public function fixWorkTitles($file) {
		set_time_limit(0);
		ini_set('max_execution_time', 0);
		$this->dcl->query("SET SESSION wait_timeout = 28800 ");
		$contents = file_get_contents($file);
		$lines = explode("\n", $contents);

		$count = 0;

		$count = 0;
		foreach($lines as $key=>$entry) {
			$splitLine = explode("\t", $entry);
			$wkId = $splitLine[0];
			$wkTitle = $splitLine[1];
			$record = $this->getExistingRecord("Old DCL Works",  "workid_7", "fieldContents", trim($wkId));

			if(!$record) {
				continue;
			}
			$titleRecord = $this->getExistingRecord("Old Work Title",  "worktitleid_7", "fieldContents", trim($wkTitle));

			$asset = new Asset_model();
			$asset->loadAssetById($record["assetid"]);
			$assetRecord= $asset->getAsArray();
			if(!array_key_exists("worktitle_7", $assetRecord)) {
				continue;
			}

			foreach($assetRecord['worktitle_7'] as $widgetKey=>$widgetEntry) {

				if($widgetEntry['targetAssetId'] == $titleRecord['assetid']) {
					$widgetEntry['isPrimary'] = true;
				}
				else {
					$widgetEntry['isPrimary'] = false;
				}
				$assetRecord['worktitle_7'][$widgetKey] = $widgetEntry;

			}
			$asset->createObjectFromJSON($assetRecord);
			echo "Updating Record for " . $asset->getObjectId() . "\n";
			$assetObject->save(false,false,true);
			$this->doctrine->em->clear();
			$count++;
			unset($lines[$key]);
			if($count % 10 == 0) {
				file_put_contents($file, implode(PHP_EOL, $lines));
				gc_collect_cycles();
			}
		}
	}

	public function importFromFile($file) {
		set_time_limit(0);
		ini_set('max_execution_time', 0);
		$this->dcl->query("SET SESSION wait_timeout = 28800 ");
		$contents = file_get_contents($file);
		$lines = explode("\n", $contents);

		$count = 0;

		foreach($lines as $key=>$entry) {
			if(strlen($entry) < 5) {
				continue;
			}
			$this->wkid = null;
			$this->vwid = null;
			$this->agid = null;
			$this->srcid =null;
			$this->ordid =null;
			$this->digitalid = null;
			echo "Importing " . $entry . "\n";
			$this->importByWork($entry);
			$this->doctrine->em->clear();
			$count++;
			unset($lines[$key]);
			if($count % 10 == 0) {
				file_put_contents($file, implode(PHP_EOL, $lines));
				gc_collect_cycles();
			}
		}
		echo "Finished!\n";

	}




	public function regenerate($parentId) {

		$this->asset_model->loadAssetById($parentId);
		$related = $this->asset_model->getAllWithinAsset("Related_asset", null,0);
		foreach($related as $relate) {
			foreach($relate->fieldContentsArray as $newAsset) {
				$uploads = $newAsset->getRelatedAsset()->getAllWithinAsset("Upload");
				foreach($uploads as $upload) {
					foreach($upload->fieldContentsArray as $uploadContents) {

						$uploadContents->fileHandler->regenerate = true;
						$uploadContents->fileHandler->save();

					}
				}
			}
		}
		$uploads = $this->asset_model->getAllWithinAsset("Upload", null,0);
		foreach($uploads as $upload) {
			foreach($upload->fieldContentsArray as $uploadContents) {
				$uploadContents->fileHandler->regenerate = true;
				$uploadContents->fileHandler->save();
			}
		}


	}

	public function findCollection($collectionId) {
		return 125;
		$this->dcl->where("col_id", $collectionId);
		$collection = $this->dcl->get("collections");
		if($collection->num_rows() > 0) {
			$collectionResult = $collection->row();


			$result = $this->doctrine->em->getRepository("Entity\Collection")->findOneBy(["title"=>$collectionResult->name]);
			if($result) {
				return $result->getId();
			}



		}
		return 35;

	}


	public function importByWork($workId) {
		$this->wkid = $workId;
		$this->dcl->where("wk_id", $this->wkid);
		$wkresult = $this->dcl->get("dcl_works");
		if($wkresult->num_rows()>0) {
			$work = $wkresult->row();
			$this->targetCollection = $this->findCollection($work->col_id);
		}

		if($this->wkid) {
			$this->importWork();
			$this->importWorkTitle();
			$this->importWorkEvent();
			$this->importWorkMeasure();
		}

		$this->importAgent();

		if($this->wkid) {
			$this->dcl->where("wk_id", $this->wkid);
			$result = $this->dcl->get("agents_works");
			foreach($result->result() as $entry) {
				$this->agid = $entry->ag_id;
				$this->importAgent();
			}
			$this->importAgentWork();
		}

		$foundRecord = $this->getExistingRecord("Old DCL Works", "workid_7", "fieldContents", $this->wkid);

		if($foundRecord) {
			$tempAsset = new Asset_model();
			$start = microtime(true);
			$tempAsset->loadAssetById($foundRecord);
			$assetArray = $tempAsset->getAsArray();
			$insert = array();
		}

		$this->dcl->where("wk_id", $this->wkid);
		$views = $this->dcl->get("dcl_views");
		foreach($views->result() as $view) {
			$this->ordid = $view->ord_id;
			$this->agid = $view->view_agent_id;
			$this->vwid = $view->vw_id;
			$this->digitalid = $view->digital_id;
			$this->importAgent();
			if($this->ordid) {
				$this->dcl->where("ord_id", $this->ordid);
				$orderRow = $this->dcl->get("orders")->row();
				$this->srcid = $orderRow->src_id;
				$this->importSourcePublication();
				$this->importOrder();
			}


			if($this->vwid) {
				$insert = $this->importViews(true);
				if(strcasecmp($this->primaryViewId, $view->digital_id) == 0 || ($this->primaryViewId == NULL && !array_key_exists("views_7", $assetArray))) {
					$insert["isPrimary"] = true;
				}
				$skip = false;
				if(array_key_exists("views_7", $assetArray)) {
					foreach($assetArray["views_7"] as $entry) {
						if($entry["targetAssetId"] == $insert["targetAssetId"]) {
							$skip = true;
						}
					}
				}

				if(!$skip) {
					$assetArray["views_7"][] = $insert;
				}
				else {
					echo "Skipping\n";
				}


				// $this->importMediaBank();
			}

		}
		$tempAsset->createObjectFromJSON($assetArray);
		$tempAsset->save(true,false);


		echo "done!\n";

	}

	public function importId($digitalId) {

		$this->dcl->where("digital_id", $digitalId);
		$viewQuery = $this->dcl->get("dcl_views");

		$this->primaryViewId = NULL;
		$this->digitalid = NULL;

		if(!$viewQuery) {
			$this->logging->logError("import failed", "importing " . $digitalId . " failed");
			return;
		}

		foreach($viewQuery->result() as $viewRow) {
			$this->vwid = $viewRow->vw_id;
			$this->wkid = $viewRow->wk_id;
			$this->digitalid = $digitalId;
			$this->agid = $viewRow->view_agent_id;
			$this->ordid = $viewRow->ord_id;

			$this->dcl->where("wk_id", $this->wkid);
			$wkresult = $this->dcl->get("dcl_works");
			if($wkresult->num_rows()>0) {
				$work = $wkresult->row();
				$this->targetCollection = $this->findCollection($work->col_id);
			}

			if($this->wkid) {
				$this->importWork();
				$this->importWorkTitle();
				$this->importWorkEvent();
				$this->importWorkMeasure();
			}

			$this->importAgent();

			if($this->wkid) {
				$this->dcl->where("wk_id", $this->wkid);
				$result = $this->dcl->get("agents_works");
				foreach($result->result() as $entry) {
					$this->agid = $entry->ag_id;
					$this->importAgent();
				}
				$this->importAgentWork();
			}


			if($this->ordid) {
				$this->dcl->where("ord_id", $this->ordid);
				$orderRow = $this->dcl->get("orders")->row();
				$this->srcid = $orderRow->src_id;
				$this->importSourcePublication();
				$this->importOrder();
			}
			if($this->vwid) {
				$this->importViews();
				$this->importMediaBank();
			}

			echo "done!\n";


		}

	}

	public function findFileRecord($keyPath) {

		$manager = $this->doctrine->em->getConnection();

		$results = $manager->query('select fileobjectid from filehandlers where deleted = FALSE AND sourcefile @> \'{"originalFilename": "' . $keyPath . '"}\'');
		if($results) {
			$records = $results->fetchAll();
			if(count($records)>0) {
				foreach($records as $record) {
					if($record['fileobjectid'] != null) {
						return $record;
					}
				}
			}
		}

		return false;


	}



	public function getExistingRecord($templateTitle, $baseField, $keyPath, $searchValue) {
		$template = $this->doctrine->em->getRepository("Entity\Template")->findOneBy(["name"=>$templateTitle]);
		$templateId = $template->getId();

		$manager = $this->doctrine->em->getConnection();

		$results = $manager->query('select assetid from assets where assetid IS NOT NULL and templateid = ' . $templateId . ' AND widgets @> \'{"' . $baseField. '": [{"' . $keyPath .'":"' . $searchValue . '"}]}\'');
		if($results) {
			$records = $results->fetchAll();
			if(count($records)>0) {
				foreach($records as $record) {
					if($record['assetid'] != null) {
						return $record;
					}
				}
			}
		}

		return false;


	}

	public function testWork() {
		$this->wkid = "WK1116853";
		$this->targetCollection = 1;
		$this->importWork();

	}

	public function getTemplateId($templateName) {

		$result = $this->doctrine->em->getRepository("Entity\Template")->findOneBy(["name"=>$templateName]);
		if($result) {
			return $result->getId();
		}
		else {
			return false;
		}

	}


	public function importWork() {

		$foundRecord = $this->getExistingRecord("Old DCL Works", "workid_7", "fieldContents", $this->wkid);
		if($foundRecord) {
			return;
		}

		$this->dcl->where("wk_id", $this->wkid);
		$result = $this->dcl->get("dcl_works", 1);

		foreach($result->result_array() as $entry) {
			$newEntry = array();
			$entry = array_map(function($source){$trimmed = trim($source); if($trimmed=="") {return null;} return $trimmed;}, $entry);


			$newEntry["workid_7"][]["fieldContents"] = $entry["wk_id"];
			$newEntry["styleperiod_7"][]["fieldContents"] = $entry["style_period1"];
			$newEntry["styleperiod_7"][]["fieldContents"] = $entry["style_period2"];
			$newEntry["styleperiod_7"][]["fieldContents"] = $entry["style_period3"];
			$newEntry["styleperiod_7"][]["fieldContents"] = $entry["style_period4"];
			if($entry["type1"]) {
				$newEntry["classification_7"][]["fieldContents"] = $entry["type1"];
			}
			if($entry["type2"]) {
				$newEntry["classification_7"][]["fieldContents"] = $entry["type2"];
			}
			if($entry["type3"]) {
				$newEntry["classification_7"][]["fieldContents"] = $entry["type3"];
			}
			if($entry["type4"]) {
				$newEntry["classification_7"][]["fieldContents"] = $entry["type4"];
			}

			$newEntry["culture_7"][]["fieldContents"] = $entry["culture1"];
			$newEntry["culture_7"][]["fieldContents"] = $entry["culture2"];
			$newEntry["technique_7"][]["fieldContents"] = $entry["technique1"];
			$newEntry["materials_7"][]["fieldContents"] = $entry["materials"];
			$newEntry["language_7"][]["fieldContents"] = $entry["language"];
			$newEntry["stateedition_7"][]["fieldContents"] = $entry["state_edition"];
			$newEntry["inscription_7"][]["fieldContents"] = $entry["inscription"];
			$newEntry["repositoryobjectid_7"][]["fieldContents"] = $entry["repository_object_id"];
			$newEntry["comments_7"][]["fieldContents"] = $entry["comments"];
			$newEntry["adminnotes_7"][]["fieldContents"] = $entry["admin_notes"];
			if($entry['primary_view_digital_id']) {
				$this->primaryViewId = $entry['primary_view_digital_id'];
			}

			$newEntry["collectionId"] = $this->targetCollection;
			$newEntry["templateId"] = $this->getTemplateId("Old DCL Works");
			$newEntry["readyForDisplay"] = true;

			$asset = new Asset_model();
			$asset->templateId = $newEntry["templateId"];
			$asset->createObjectFromJSON($newEntry);
			$objectId = $asset->save(false,false);
			$this->workObject[$entry['wk_id']] = $objectId;
			echo "Work:" . $objectId. "\n";


		}
	}

	function isArrayEmpty($sourceArray, $targetKeys) {
		foreach($sourceArray as $key=>$value) {

			if(in_array($key, $targetKeys)) {
				if(!empty($value)) {
					return false;
				}
			}
		}
		return true;
	}


	public function importWorkTitle() {

		$foundRecord = $this->getExistingRecord("Old Work Title", "workid_7", "fieldContents", $this->wkid);
		if($foundRecord) {
			return;
		}


		$this->dcl->where("wk_id", $this->wkid);
		$result = $this->dcl->get("work_titles");
		foreach($result->result_array() as $entry) {
			$newEntry = array();
			$entry = array_map(function($source){$trimmed = trim($source); if($trimmed=="") {return null;} return $trimmed;}, $entry);

			if($this->isArrayEmpty($entry, ["title", "type"])) {
				echo "Work title Empty\n";
				return;
			}

			$newEntry["worktitleid_7"][]["fieldContents"] = $entry["wkt_id"];
			$newEntry["workid_7"][]["fieldContents"] = $entry["wk_id"];
			$newEntry["title_7"][]["fieldContents"] = $entry["title"];
			$newEntry["type_7"][]["fieldContents"] = $entry["type"];
			$newEntry["markpreferred_7"][]["fieldContents"] = $entry["mark_preferred"];
			$newEntry["collectionId"] = $this->targetCollection;
			$newEntry["templateId"] = $this->getTemplateId("Old Work Title");
			$newEntry["readyForDisplay"] = true;

			if(!$entry["title"] && !$entry["type"]) {
				echo "continuig\n";
				continue;
			}

			$asset = new Asset_model();
			$asset->templateId = $newEntry["templateId"];
			$asset->createObjectFromJSON($newEntry);
			$objectId = $asset->save(false,false);
			echo "Work Title:" . $objectId. "\n";


			$foundRecord = $this->getExistingRecord("Old DCL Works", "workid_7", "fieldContents", $this->wkid);
			if($foundRecord) {
				$tempAsset = new Asset_model();
				$tempAsset->loadAssetById($foundRecord);
				$assetArray = $tempAsset->getAsArray();
				$insert = array();
				$insert["targetAssetId"] = $objectId;
				if($entry["mark_preferred"] == "Y") {
					$insert["isPrimary"] = true;
				}
				$assetArray["worktitle_7"][] = $insert;
				$tempAsset->createObjectFromJSON($assetArray);
				$tempAsset->save(false,false);
			}


		}
	}

	public function importWorkEvent() {
		$foundRecord = $this->getExistingRecord("Old Work Event", "workid_7", "fieldContents", $this->wkid);
		if($foundRecord) {
			return;
		}
		$this->dcl->where("wk_id", $this->wkid);
		$result = $this->dcl->get("work_events");
		foreach($result->result_array() as $entry) {
			$newEntry = array();
			$entry = array_map(function($source){$trimmed = trim($source); if($trimmed=="") {return null;} return $trimmed;}, $entry);

			if($this->isArrayEmpty($entry, ["earliest_date", "latest_date", "begin_century", "end_century", "decade", "location_name", "continent", "country", "state", "region", "type", "city_site"])) {
				echo "Work Event Empty\n";
				return;
			}

			$newEntry["workeventid_7"][]["fieldContents"] = $entry["wke_id"];
			$newEntry["workid_7"][]["fieldContents"] = $entry["wk_id"];
			$newEntry["earliestdate_7"][]["fieldContents"] = $entry["earliest_date"];
			$newEntry["latestdate_7"][]["fieldContents"] = $entry["latest_date"];
			$newEntry["begincentury_7"][]["fieldContents"] = $entry["begin_century"];
			$newEntry["endcentury_7"][]["fieldContents"] = $entry["end_century"];
			$newEntry["decade_7"][]["fieldContents"] = $entry["decade"];
			$newEntry["locationname_7"][]["fieldContents"] = $entry["location_name"];
			$newEntry["continent_7"][]["fieldContents"] = $entry["continent"];
			$newEntry["country_7"][]["fieldContents"] = $entry["country"];
			$newEntry["state_7"][]["fieldContents"] = $entry["state"];
			$newEntry["region_7"][]["fieldContents"] = $entry["region"];
			$newEntry["type_7"][]["fieldContents"] = $entry["type"];
			$newEntry["citysite_7"][]["fieldContents"] = $entry["city_site"];
			$newEntry["address_7"][]["fieldContents"] = $entry["address"];
			$newEntry["county_7"][]["fieldContents"] = $entry["county"];
			if($entry['longitude'] && $entry['latitude']) {

				$locArray = ["type"=>"Point", "coordinates"=>[$entry['longitude'], $entry['latitude']]];
				$locationEntry['loc'] = $locArray;
				$locationEntry['locationLabel'] = "";
				$newEntry['locationcoordinates_7'][] = $locationEntry;

			}
			$newEntry["readyForDisplay"] = true;

			$newEntry["collectionId"] = $this->targetCollection;
			$newEntry["templateId"] = $this->getTemplateId("Old Work Event");

			$asset = new Asset_model();
			$asset->templateId = $newEntry["templateId"];
			$asset->createObjectFromJSON($newEntry);
			$objectId = $asset->save(false,false);
			echo "Work Event:" . $objectId. "\n";

			$foundRecord = $this->getExistingRecord("Old DCL Works", "workid_7", "fieldContents", $entry['wk_id']);
			if($foundRecord) {
				$tempAsset = new Asset_model();

				$tempAsset->loadAssetById($foundRecord);
				$assetArray = $tempAsset->getAsArray();
				$assetArray["datelocation_7"][]["targetAssetId"] = $objectId;
				$tempAsset->createObjectFromJSON($assetArray);

				$tempAsset->save(false,false);

			}
		}
	}

	public function importWorkMeasure() {

		$foundRecord = $this->getExistingRecord("Old Work Measurement", "workid_7", "fieldContents", $this->wkid);
		if($foundRecord) {
			return;
		}
		$this->dcl->where("wk_id", $this->wkid);
		$result = $this->dcl->get("work_measures");
		foreach($result->result_array() as $entry) {
			$newEntry = array();
			$entry = array_map(function($source){$trimmed = trim($source); if($trimmed=="") {return null;} return $trimmed;}, $entry);

			if($this->isArrayEmpty($entry, ["measurement", "extent"])) {
				echo "Work measure Empty\n";
				return;
			}


			$newEntry["workmeasurementid_7"][]["fieldContents"] = $entry["wkm_id"];
			$newEntry["workid_7"][]["fieldContents"] = $entry["wk_id"];
			$newEntry["measurement_7"][]["fieldContents"] = $entry["measurement"];
			$newEntry["extent_7"][]["fieldContents"] = $entry["extent"];
			$newEntry["readyForDisplay"] = true;
			$newEntry["collectionId"] = $this->targetCollection;
			$newEntry["templateId"] = $this->getTemplateId("Old Work Measurement");

			$asset = new Asset_model();
			$asset->templateId = $newEntry["templateId"];
			$asset->createObjectFromJSON($newEntry);
			$objectId = $asset->save(false,false);
			echo "Work Measure" . $objectId. "\n";

			$foundRecord = $this->getExistingRecord("Old DCL Works", "workid_7", "fieldContents", $entry['wk_id']);

			if($foundRecord) {
				$tempAsset = new Asset_model();
				$tempAsset->loadAssetById($foundRecord);
				$assetArray = $tempAsset->getAsArray();
				$assetArray["workmeasurement_7"][]["targetAssetId"] = $objectId;
				$tempAsset->createObjectFromJSON($assetArray);
				$tempAsset->save(false,false);

			}
		}
	}

	// get all the values for a key from a multidimensional array
	function array_value_recursive($key, array $arr){
		$val = array();
		array_walk_recursive($arr, function($v, $k) use($key, &$val){
			if($k == $key) array_push($val, $v);
		});
		return count($val) > 1 ? $val : array_pop($val);
	}


	public function importAgent() {
		$foundRecord = $this->getExistingRecord("Old DCL Agents", "agentid_7", "fieldContents", $this->agid);
		if($foundRecord) {
			return;
		}

		$this->dcl->where("ag_id", $this->agid);
		$result = $this->dcl->get("dcl_agents");
		foreach($result->result_array() as $entry) {
			$newEntry = array();
			$entry = array_map(function($source){$trimmed = trim($source); if($trimmed=="") {return null;} return $trimmed;}, $entry);

			$newEntry["firstnamequalifier_7"][]["fieldContents"] = $entry["first_name_qualifier"];
			$newEntry["firstname_7"][]["fieldContents"] = $entry["first_name"];
			$newEntry["lastname_7"][]["fieldContents"] = $entry["last_name"];
			$newEntry["lastnamequalifier_7"][]["fieldContents"] = $entry["last_name_qualifier"];
			$newEntry["altname_7"][]["fieldContents"] = $entry["alt_name"];
			$newEntry["birthdate_7"][]["fieldContents"] = $entry["birth_date"];
			$newEntry["deathdate_7"][]["fieldContents"] = $entry["death_date"];
			$newEntry["nationality_7"][]["fieldContents"] = $entry["nationality1"];
			$newEntry["nationality_7"][]["fieldContents"] = $entry["nationality2"];
			$newEntry["datesactive_7"][]["fieldContents"] = $entry["dates_active"];
			$newEntry["notes_7"][]["fieldContents"] = $entry["notes"];
			$newEntry["agentqualifier_7"][]["fieldContents"] = $entry["agent_qualifier"];
			$newEntry["gender_7"][]["fieldContents"] = $entry["gender"];
			$newEntry["countryofbirth_7"][]["fieldContents"] = $entry["country_birth"];
			$newEntry["century_7"][]["fieldContents"] = $entry["century"];
			$newEntry["countryactive_7"][]["fieldContents"] = $entry["country_active"];
			$newEntry["firmlocation_7"][]["fieldContents"] = $entry["firm_location"];
			$newEntry["authority_7"][]["fieldContents"] = $entry["authority"];
			$newEntry["adminnotes_7"][]["fieldContents"] = $entry["admin_notes"];


			// if we don't have any values up to this point, let's bail.
			if(!array_filter($this->array_value_recursive("fieldContents", $newEntry))) {
				return;
			}

			$newEntry["agentid_7"][]["fieldContents"] = $entry["ag_id"];


			$newEntry["templateId"] = $this->getTemplateId("Old DCL Agents");
			$newEntry["collectionId"] = $this->targetCollection;
			$newEntry["readyForDisplay"] = true;

			$agentNameArray = array($entry["first_name_qualifier"], join($this->removeEmptyElements(array($entry["first_name"], $entry["last_name"])), " "),$entry["last_name_qualifier"]);
			$agentName = join($this->removeEmptyElements($agentNameArray), ", ");
			$newEntry["displayname_7"][]["fieldContents"] = $agentName;


			$asset = new Asset_model();
			$asset->templateId = $newEntry["templateId"];
			$asset->createObjectFromJSON($newEntry);
			$objectId = $asset->save(false,false);
			$this->agentObject[$entry["ag_id"]] = $objectId;

			echo "Agent:" . $objectId. "\n";
		}

	}

	public function removeEmptyElements($myArray) {

		foreach ($myArray as $key => $value) {
			if (is_null($value) || $value=="") {
				unset($myArray[$key]);
			}
		}
		return $myArray;

	}

	public function importSourcePublication() {

		$foundRecord = $this->getExistingRecord("Old Source Publication", "sourceid_7", "fieldContents", $this->srcid);
		if($foundRecord) {
			return;
		}

		$this->dcl->where("src_id", $this->srcid);
		$result = $this->dcl->get("source_publications");
		foreach($result->result_array() as $entry) {
			$newEntry = array();
			$entry = array_map(function($source){$trimmed = trim($source); if($trimmed=="") {return null;} return $trimmed;}, $entry);

			$newEntry["sourceid_7"][]["fieldContents"] = $entry["src_id"];
			$newEntry["author_7"][]["fieldContents"] = $entry["author"];
			$newEntry["articletitle_7"][]["fieldContents"] = $entry["article_title"];
			$newEntry["title_7"][]["fieldContents"] = $entry["title"];
			$newEntry["volume_7"][]["fieldContents"] = $entry["volume"];
			$newEntry["number_7"][]["fieldContents"] = $entry["number"];
			$newEntry["year_7"][]["fieldContents"] = $entry["year"];
			$newEntry["publisher_7"][]["fieldContents"] = $entry["publisher"];
			$newEntry["month_7"][]["fieldContents"] = $entry["month"];
			$newEntry["sourcenumber_7"][]["fieldContents"] = $entry["source_number"];
			$newEntry["callnumber_7"][]["fieldContents"] = $entry["call_number"];
			$newEntry["sourceformat_7"][]["fieldContents"] = $entry["source_format"];
			$newEntry["adminnotes_7"][]["fieldContents"] = $entry["admin_notes"];
			$newEntry["readyForDisplay"] = true;
			$newEntry["templateId"] = $this->getTemplateId("Old Source Publication");
			$newEntry["collectionId"] = $this->targetCollection;

			$asset = new Asset_model();
			$asset->templateId = $newEntry["templateId"];
			$asset->createObjectFromJSON($newEntry);
			$objectId = $asset->save(false,false);
			echo "Source Pub:" . $objectId. "\n";
		}

	}

	public function importOrder() {


		$foundRecord = $this->getExistingRecord("Old Orders", "orderid_7", "fieldContents", $this->ordid);
		if($foundRecord) {
			return;
		}

		$this->dcl->where("ord_id", $this->ordid);
		$result = $this->dcl->get("orders");
		foreach($result->result_array() as $entry) {
			$newEntry = array();
			$entry = array_map(function($source){$trimmed = trim($source); if($trimmed=="") {return null;} return $trimmed;}, $entry);

			$newEntry["orderid_7"][]["fieldContents"] = $entry["ord_id"];
			$newEntry["collectionid_7"][]["fieldContents"] = $entry["col_id"];
			$newEntry["sourceid_7"][]["fieldContents"] = $entry["src_id"];
			$newEntry["orderedby_7"][]["fieldContents"] = $entry["ordered_by"];
			$newEntry["orderdate_7"][]["fieldContents"] = $entry["order_date"];
			$newEntry["requesterinternetid_7"][]["fieldContents"] = $entry["requester_id"];
			$newEntry["contributordonor_7"][]["fieldContents"] = $entry["contributor"];
			$newEntry["deadline_7"][]["fieldContents"] = $entry["deadline"];
			$newEntry["photographer_7"][]["fieldContents"] = $entry["photographer"];
			$newEntry["completiondate_7"][]["fieldContents"] = $entry["completion"];
			$newEntry["sourcetype_7"][]["fieldContents"] = $entry["source_type"];
			$newEntry["numberofitems_7"][]["fieldContents"] = $entry["number_of_items"];
			$newEntry["subject_7"][]["fieldContents"] = $entry["subject"];
			$newEntry["ordernumber_7"][]["fieldContents"] = $entry["order_number"];
			$newEntry["accessionrange_7"][]["fieldContents"] = $entry["accession_range"];
			$newEntry["instructionscommentsnotes_7"][]["fieldContents"] = $entry["instructions"];
			$newEntry["adminnotes_7"][]["fieldContents"] = $entry["admin_notes"];
			$newEntry["readyForDisplay"] = true;
			$newEntry["templateId"] = $this->getTemplateId("Old Orders");
			$newEntry["collectionId"] = $this->targetCollection;

			if($entry['src_id']) {
				$foundRecord = $this->getExistingRecord("Old Source Publication", "sourceid_7", "fieldContents", $entry['src_id']);

				if($foundRecord) {
					$tempAsset = new Asset_model();
					$tempAsset->loadAssetById($foundRecord);

					$newEntry["source_7"][]["targetAssetId"] = $tempAsset->getObjectId();

				}
			}


			$asset = new Asset_model();
			$asset->templateId = $newEntry["templateId"];
			$asset->createObjectFromJSON($newEntry);
			$objectId = $asset->save(false,false);
			echo "Order:" . $objectId. "\n";
		}

	}


	public function importAgentWork() {

		$foundRecord = $this->getExistingRecord("Old Agent Work", "workid_7", "fieldContents", $this->wkid);
		if($foundRecord) {
			return;
		}

		$workObject = null;
		$workObjectArray = null;

		$this->dcl->where("wk_id", $this->wkid);
		$result = $this->dcl->get("agents_works");
		foreach($result->result_array() as $entry) {
			$newEntry = array();
			$entry = array_map(function($source){$trimmed = trim($source); if($trimmed=="") {return null;} return $trimmed;}, $entry);

			$newEntry["agentworkid_7"][]["fieldContents"] = $entry["agwk_id"];
			$newEntry["agentid_7"][]["fieldContents"] = $entry["ag_id"];
			$newEntry["workid_7"][]["fieldContents"] = $entry["wk_id"];
			$newEntry["role_7"][]["fieldContents"] = $entry["role"];
			$newEntry["attribution_7"][]["fieldContents"] = $entry["attribution"];
			$newEntry["extent_7"][]["fieldContents"] = $entry["extent"];
			$newEntry["readyForDisplay"] = true;
			$newEntry["templateId"] = $this->getTemplateId("Old Agent Work");
			$newEntry["collectionId"] = $this->targetCollection;

			$agentObjectId = null;
			if(isset($this->agentObject[$entry["ag_id"]])) {
				$agentObjectId = $this->agentObject[$entry["ag_id"]];
			}
			else {
				$foundRecord = $this->getExistingRecord("Old DCL Agents", "agentid_7", "fieldContents", $entry['ag_id']);

				if($foundRecord) {
					$tempAsset = new Asset_model();
					$tempAsset->loadAssetById($foundRecord);
					$agentObjectId = $tempAsset->getObjectId();
				}
			}
			if($agentObjectId) {
				$newEntry["agent_7"][]["targetAssetId"] = $agentObjectId;
			}
			else {
				return;
			}



			$asset = new Asset_model();
			$asset->templateId = $newEntry["templateId"];
			$asset->createObjectFromJSON($newEntry);
			$objectId = $asset->save(false,false);
			echo "Agentwork:" . $objectId. "\n";


			if(!$workObject || !$workObjectArray) {
				if(isset($this->workObject[$entry['wk_id']])) {
					$foundRecord = $this->workObject[$entry['wk_id']];
				}
				else {
					$foundRecord = $this->getExistingRecord("Old DCL Works", "workid_7", "fieldContents", $entry['wk_id']);
				}

				if($foundRecord) {
					$workObject = new Asset_model();
					$workObject->loadAssetById($foundRecord);
					$workObjectArray = $workObject->getAsArray();
				}
			}
			if($workObject) {
				$insert = array();
				$insert["targetAssetId"] = $objectId;
				if($entry["rank"] == 1) {
					$insert["isPrimary"] = true;
				}
				$workObjectArray["creator_7"][] = $insert;
			}
		}

		if($workObjectArray) {
			$workObject->createObjectFromJSON($workObjectArray);
			$workObject->save(false,false);
		}

	}

	public function timeTest() {
		$tempAsset = new Asset_model();
			$tempAsset->loadAssetById("558c633b81bbd1567c8b4567");
			$start = microtime(true);
				$assetArray = $tempAsset->getAsArray();
				$end = microtime(true);
				echo "took " . ($start - $end);
	}

	public function importViews($return=false)
	{

		$foundRecord = $this->getExistingRecord("Old DCL Views", "viewid_7", "fieldContents", $this->vwid);
		if($foundRecord) {
			if($return) {
				$insert["targetAssetId"] = $foundRecord['assetid'];
				return $insert;
			}
			return;
		}


		$this->dcl->where("vw_id", $this->vwid);
		$result = $this->dcl->get("dcl_views");

		foreach($result->result_array() as $entry) {
			$newEntry = array();
			$entry = array_map(function($source){$trimmed = trim($source); if($trimmed=="") {return null;} return $trimmed;}, $entry);

			$newEntry["alttype_7"][]["fieldContents"] = $entry["alt_type"];
			$newEntry["classification_7"][]["fieldContents"] = $entry["classification"];
			$newEntry["date_7"][]["fieldContents"] = $entry["date"];
			$newEntry["description_7"][]["fieldContents"] = $entry["description"];
			$newEntry["digitalid_7"][]["fieldContents"] = $entry["digital_id"];
			$newEntry["digitized_7"][]["fieldContents"] = $entry["digitized"];
			$newEntry["figurenumber_7"][]["fieldContents"] = $entry["figure_number"];
			$newEntry["folionumber_7"][]["fieldContents"]= $entry["folio_number"];
			$newEntry["keywords_7"][]["tags"] = $entry["keywords"];
			$newEntry["mediatype_7"][]["fieldContents"] = $entry["media_type"];
			$newEntry["orderid_7"][]["fieldContents"] = $entry["ord_id"];
			$newEntry["legacyid_7"][]["fieldContents"] = $entry["legacy_id"];
			$newEntry["pagenumber_7"][]["fieldContents"] = $entry["page_number"];
			$newEntry["publiccopyright_7"][]["fieldContents"] = $entry["copyright_public"];
			$newEntry["scale_7"][]["fieldContents"] = $entry["scale"];
			$newEntry["subtype_7"][]["fieldContents"] = $entry["sub_type"];
			$newEntry["title_7"][]["fieldContents"] = $entry["title"];
			$newEntry["viewtype_7"][]["fieldContents"] = $entry["type"];
			$newEntry["viewagentextent_7"][]["fieldContents"] = $entry["view_agent_extent"];
			$newEntry["viewagentid_7"][]["fieldContents"] = $entry["view_agent_id"];
			$newEntry["viewid_7"][]["fieldContents"] = $entry["vw_id"];
			$newEntry["workid_7"][]["fieldContents"] = $entry["wk_id"];
			$newEntry["accessionnumber_7"][]["fieldContents"] = $entry["accession_number"];
			$newEntry["photographer_7"][]["fieldContents"] = $entry["photographer"];
			$newEntry["copyrightholder_7"][]["fieldContents"] = $entry["copyright_holder"];
			$newEntry["copyhistory_7"][]["fieldContents"] = $entry["copy_history"];
			$newEntry["adminnotes_7"][]["fieldContents"] = $entry["admin_notes"];
			$newEntry["copyrightfullvideo_7"][]["fieldContents"] = $entry["copyright_full_video"];

			if($entry["digitized"] != "Y") {
				$newEntry["readyForDisplay"] = false;
			}
			else {
				$newEntry["readyForDisplay"] = true;
			}

			$newEntry["collectionId"] = $this->targetCollection;
			$newEntry["templateId"] = $this->getTemplateId("Old DCL Views");



			$foundRecord = $this->getExistingRecord("Old DCL Agents", "agentid_7", "fieldContents", $entry['view_agent_id']);

			if($foundRecord) {
				$tempAsset = new Asset_model();
				$tempAsset->loadAssetById($foundRecord);
				$newEntry["agent_7"][]["targetAssetId"] = $tempAsset->getObjectId();
			}

			$foundRecord = $this->getExistingRecord("Old Orders", "orderid_7", "fieldContents", $entry['ord_id']);

			if($foundRecord) {
				$tempAsset = new Asset_model();
				$tempAsset->loadAssetById($foundRecord);
				$newEntry["ordersource_7"][]["targetAssetId"] = $tempAsset->getObjectId();
			}



			$asset = new Asset_model();
			$asset->templateId = $newEntry["templateId"];
			$asset->createObjectFromJSON($newEntry);
			$objectId = $asset->save(false,false);
			echo "View:" . $objectId. "\n";


			if($return) {
				$insert = array();
				$insert["targetAssetId"] = $objectId;
				return $insert;
			}
			else {


				$foundRecord = $this->getExistingRecord("Old DCL Works", "workid_7", "fieldContents", $entry['wk_id']);

				if($foundRecord) {
					$tempAsset = new Asset_model();
					$start = microtime(true);
					$tempAsset->loadAssetById($foundRecord);
					$assetArray = $tempAsset->getAsArray();
					$insert = array();
					$insert["targetAssetId"] = $objectId;
					if(strcasecmp($this->primaryViewId, $entry["digital_id"]) == 0 || ($this->primaryViewId == NULL && !array_key_exists("views_7", $assetArray))) {
						$insert["isPrimary"] = true;
					}
					$assetArray["views_7"][] = $insert;

					$tempAsset->createObjectFromJSON($assetArray);
					$tempAsset->save(false,false, true); // dont builda new cache

				}
			}

		}
	}



	public function importMediaBank() {

		$this->dcl->where("digital_id", $this->digitalid);
		$this->dcl->where("is_active_for_delivery", 1);
		$result = $this->dcl->get("source_medias");
		foreach($result->result_array() as $entry) {


			$mediaId = $entry["id"];
			$digitalId = $entry["digital_id"];
			$originalExtension = str_replace(".", "", $entry["file_extension"]);

			$foundRecord = $this->getExistingRecord("Old DCL Views", "viewid_7", "fieldContents", $this->vwid);


			if($foundRecord) {
				$tempAsset = new Asset_model();
				$tempAsset->loadAssetById($foundRecord);
				try {
					$fileHandler = $tempAsset->getPrimaryFilehandler();
					if($fileHandler) {
						echo "This view, " . $this->vwid . " " . $tempAsset->getObjectId() . "  already has files\n";
						return;
					}
				}
				catch (Exception $e) {
				// don't need to do anything, might not have a handler, that's ok.
				}


			}

			$foundRecord = $this->getExistingRecord("Old DCL Views", "digitalid_7", "fieldContents", $digitalId);

			if($foundRecord) {
				echo "starting\n";
				$tempAsset = new Asset_model();
				$tempAsset->loadAssetById($foundRecord);

				// try {
				// 	$tempAsset->getPrimaryFilehandler();
				// }
				// catch(Exception $e) {
				// 	// have a file handler already;
				// 	return;
				// }

				$fileHandlerId = $this->findFileRecord($digitalId . ".". $originalExtension);
				if($fileHandlerId) {
					$fileHandlerId = $fileHandlerId["fileobjectid"];
					$assetArray = $tempAsset->getAsArray();
					$assetArray["file_7"][] = ["fileId"=>$fileHandlerId];
					$fileHandler = $this->filehandler_router->getHandledObject($fileHandlerId);

					$fileHandler->parentObjectId = $tempAsset->getObjectId();
					$fileHandler->save();
					$objectId = $fileHandlerId;
				}
				else {
					$filename = $mediaId . ".orig";

					$pathToFile = $this->rootPathToMedia . "/" . $this->pathToMedia($mediaId) . "/" . $filename;

					if(!file_exists($pathToFile)) {
						echo "File Not Found: " . $pathToFile . "\n";
						return;
					}

					$fileHandler = $this->filehandler_router->getHandlerForType($originalExtension);

					if(get_class($fileHandler) == "FileHandlerBase") {
						echo "unkown type: " . $originalExtension . "\n";
						die;
					}

					$fileHandler->setCollectionId($tempAsset->getGlobalValue("collectionId"));
					$fileHandler->parentObjectId = $tempAsset->getObjectId();

					$fileContainer = new fileContainerS3();
					$fileHandler->sourceFile = $fileContainer;

					$fileContainer->path = "original";
					$fileContainer->storageType = $this->instance->getS3StorageType();
					$fileContainer->derivativeType = "source";
					$fileContainer->setParent($fileHandler);
					$fileContainer->originalFilename = $digitalId . ".". $originalExtension;
					$fileHandler->save(false,false);

					$objectId = $fileHandler->getObjectId();

					if(!$fileHandler->s3model->putObject($pathToFile, "original" . "/" . $fileHandler->getReversedObjectId() . "-source")) {
						echo "issue with " . $objectId . " " . $digitalId . "\n";
						die;
					}
					$fileHandler->sourceFile->ready = true;
					$fileHandler->save(false,false);

					$assetArray = $tempAsset->getAsArray();
					$assetArray["file_7"][] = ["fileId"=>$objectId, "regenerate"=>"On"];


				}
				$tempAsset->createObjectFromJSON($assetArray);
				echo $tempAsset->getObjectId() . "\n";
				echo $objectId . "\n";

				$tempAsset->save(false,false);

			}
			else {
				// $this->logging->logError("no match", "could not find match for " . $digitalId);
				echo "could not find match for " . $digitalId . "\n";
			}





		}
	}
	public function moveCCItems() {
		$manager = $this->doctrine->em->getConnection();

		$results = $manager->query('select id from assets where collectionid = 156 and templateid = 25 and widgets->>\'description_7\' like \'%commons%\'');
		if($results) {
			$records = $results->fetchAll();
			if(count($records)>0) {
				foreach($records as $record) {
					$asset= null;
					$asset = $this->doctrine->em->getRepository("Entity\Asset")->find($record['id']);
					$asset->setCollectionId(169);
				}
			}
		}
		$this->doctrine->em->flush();
	}

	public function moveWorksBack() {

		$results = $this->doctrine->em->getRepository("Entity\Asset")->findBy(["templateId"=>26, "collectionId"=>172]);
		foreach($results as $entry) {
			if($entry->getAssetId() == NULL) {
				continue;
			}
			$asset = new Asset_model();
			$asset->loadAssetFromRecord($entry);
			$views = $asset->assetObjects["views_7"];
			$needsMove = false;
			foreach($views->fieldContentsArray as $widget) {
				$collection = $widget->getRelatedAsset()->getGlobalValue("collectionId");
				if($collection != 1752) {
					$needsMove = true;
					break;
				}
			}
			if($needsMove) {
				$asset->setGlobalValue("collectionId", 127);
				$asset->save(true,false);
				echo $entry->getAssetId() . " moved\n";	
			}
			
		}

	}

public function moveWorksBack2() {
		$qb = $this->doctrine->em->createQueryBuilder();
		$qb->from("Entity\Asset", 'a')
		->select("a")
		->where("a.templateId = 26")
		->andWhere("a.collectionId= 28")
		->andWhere("a.modifiedAt > '2016-04-07'");

		$assets = $qb->getQuery()->iterate();
		$count = 0;
		foreach($assets as $entry) {
			if($entry[0]->getAssetId() == NULL) {
				continue;
			}
			echo $entry[0]->getAssetId() . "\n";
			$asset = new Asset_model();
			$asset->loadAssetFromRecord($entry[0]);
			$views = $asset->assetObjects["views_7"];
			$needsMove = true;
			foreach($views->fieldContentsArray as $widget) {
				$collection = $widget->getRelatedAsset()->getGlobalValue("collectionId");
				if($collection != 173) {
					$needsMove = false;
					break;
				}
			}
			if($needsMove) {
				$asset->setGlobalValue("collectionId", 173);
				$asset->save(true,false);
				echo $entry[0]->getAssetId() . " moved\n";	
			}
			
		}

	}

	public function fixViewCollection() {
		$contents = file_get_contents("viewFix");
		$lines = explode("\n", $contents);

		$count = 0;

		$count = 0;
		foreach($lines as $key=>$entry) {
			$foundRecord = $this->getExistingRecord("Old DCL Views", "viewid_7", "fieldContents", $entry);
			if($foundRecord) {
				$assetId = $foundRecord['assetid'];
				echo $assetId . "\n";
				$asset = new Asset_model;
				$asset->loadAssetById($assetId);
				$asset->setGlobalValue("collectionId", 140);
				$asset->save(false,false,false);
			}
			$this->doctrine->em->flush();
			$this->doctrine->em->clear();
			$count++;
			unset($lines[$key]);
			if($count % 10 == 0) {
				file_put_contents("viewFix", implode(PHP_EOL, $lines));
				gc_collect_cycles();
				if($count == 200) {
					return;
				}
			}
		}	
		
	}

	public function fixSortDate() {
		$this->dcl->where("sort_date IS NOT NULL", null, false);
		$result = $this->dcl->get("work_events");
		foreach($result->result() as $entry) {
			echo "Looking for: " . $entry->wke_id . "\n";
			$foundRecord = $this->getExistingRecord("Old Work Event", "workeventid_7", "fieldContents", $entry->wke_id);
			if($foundRecord) {
				$tempAsset = new Asset_model();
				$start = microtime(true);
				$tempAsset->loadAssetById($foundRecord);
				$assetArray = $tempAsset->getAsArray();
				if(array_key_exists("sortdate_7", $assetArray)) {
					continue;
				}
				$insert = array();
				$insert["end"] = ["text"=>"", "numeric"=>""];
				$insert["label"] = "";
				$insert["isPrimary"] = false;
				$insert["start"] = ["text"=>$entry->sort_date, "numeric"=>mktime(0, 0, 0, 1, 1, $entry->sort_date)];
				$assetArray["sortdate_7"][] = $insert;
				$tempAsset->createObjectFromJSON($assetArray);
				$tempAsset->save(true,false);
				echo "Updated " . $tempAsset->getObjectId() . "\n";
			}
			$this->doctrine->em->clear();
		}
	}


	function pathToMedia($mediaId) {
		$reversedMediaId = strrev($mediaId);
		$stringLength = strlen($reversedMediaId);


		$newPathArray = array();
		for($i=0; $i<$stringLength/2; $i++) {
			if(1+$i*2 < $stringLength) {
				$newPathArray[] = str_pad(substr($reversedMediaId, 2*$i, 2), 2, 0, STR_PAD_LEFT);
			}
		}

		$newPath = implode("/", $newPathArray);
		return $newPath;

	}

}

/* End of file  */
/* Location: ./application/controllers/ */
