<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class AssetManager extends Admin_Controller {

	public function __construct()
	{
		parent::__construct();

		$this->template->javascript->add("//maps.google.com/maps/api/js?sensor=false");
		$jsLoadArray = ["handlebars-v1.1.2", "formSubmission", "serializeForm", "interWindow", "jquery.gomap-1.3.2",
		"markerclusterer", "mapWidget","dateWidget","mule2", "uploadWidget","multiselectWidget", "parsley", "bootstrap-datepicker", "bootstrap-tagsinput", "typeahead.jquery", "assetAutocompleter"];
		$this->template->loadJavascript($jsLoadArray);

		$this->load->helper("multiselect");

		$cssLoadArray = ["datepicker", "bootstrap-tagsinput"];
		$this->template->loadCSS($cssLoadArray);
		$this->template->javascript->add("assets/tinymce/tinymce.min.js");

		$this->load->model("asset_model");
	}

	public function addAssetModal() {
		if(isset($this->instance) && $this->user_model->userLoaded) {
			if($this->user_model->getIsSuperAdmin() || $this->user_model->getAccessLevel("instance",$this->instance)>=PERM_ADDASSETS || $this->user_model->getMaxCollectionPermission() >= PERM_ADDASSETS) {
				$this->load->view("addAsset_modal");
			}
		}
	}

	/**
	 * AJAX call to add a new widget to an asset entry form.
	 * gets the current count so we can build the fields with the right ID
	 * Dear god, why isn't this all done client side?  Please rebuild this in React and a proper design.
	 */
	public function getWidget($widgetId, $widgetCount) {
		$widgetObject = $this->doctrine->em->find('Entity\Widget', $widgetId);
		if(!$widgetObject) {
			$this->output->set_status_header(500, 'error loading widget');
			$this->logging->logError("getWidget", "could not load widget" . $widgetId . "with count " . $widgetCount);
			return;
		}
		$this->load->library("widget_router");
		$widget = $this->widget_router->getWidget($widgetObject);
		$widget->offsetCount = $widgetCount;
		echo $widget->getForm();
	}


	/**
	 * This is the main page for creating a new asset
	 */
	public function addAsset($templateId=null, $collectionId = null, $inlineForm = false)
	{

		$accessLevel = max($this->user_model->getAccessLevel("instance",$this->instance), $this->user_model->getMaxCollectionPermission());

		if($accessLevel < PERM_ADDASSETS) {
			$this->errorhandler_helper->callError("noPermission");
		}


		if($this->input->post("templateId")) {
			$templateId = $this->input->post("templateId");
		}

		if(!$templateId) {
			$this->logging->logError("no template", $_SERVER);
			instance_redirect("/errorHandler/error/unknownTemplate");
		}
		$this->template->javascript->add("assets/js/widgetHelpers.js");


		$template = $this->asset_template->getTemplate($templateId);

		$this->template->title = "Add Asset | ". $template->getName() . "";

		// if this is a nested form, we might be trying to force a collectionId
		if(is_numeric($collectionId)) {
			$this->template->collectionId = $collectionId;
		}

		if($inlineForm) {
			$template->displayInline = true;
			$this->template->set_template("chromelessTemplate");
		}

		$templateContents = $template->templateForm();

		$this->template->content->set($templateContents);

		$this->template->publish();

	}


	function editAsset($objectId, $inlineForm=false) {

		$accessLevel = $this->user_model->getAccessLevel("instance",$this->instance);

		$this->asset_model->loadAssetById($objectId);

		if($accessLevel < PERM_ADDASSETS) {
			if($this->user_model->getAccessLevel("collection",$this->collection_model->getCollection($this->asset_model->getGlobalValue("collectionId"))) < PERM_ADDASSETS) {
				$this->errorhandler_helper->callError("noPermission");
			}
		}

		$this->load->model("asset_model");
		$this->asset_model->loadAssetById($objectId);
		$this->template->javascript->add("assets/js/widgetHelpers.js");
		$assetTemplate = $this->asset_template->getTemplate($this->asset_model->templateId);

		$this->template->title = "Edit Asset | ". $assetTemplate->getName() . "";

		if($inlineForm) {
			$assetTemplate->displayInline = true;
			$this->template->set_template("chromelessTemplate");
		}

		$this->template->content->set($assetTemplate->templateForm($this->asset_model));

		$this->template->publish();

	}

	// Used for restoring an asset state from our history table.  We copy it out of the history and save it with the
	// existing object id
	function restoreAsset($objectId) {
		$accessLevel = max($this->user_model->getAccessLevel("instance",$this->instance), $this->user_model->getMaxCollectionPermission());

		if($accessLevel < PERM_ADDASSETS) {
			$this->errorhandler_helper->callError("noPermission");
		}

		$asset = new Asset_model();
		$asset->loadAssetById($objectId);

		$entries = $asset->assetObject->getRevisions();

		$assetArray = array();
		$this->load->model("asset_model");
		foreach($entries as $entry) {
			$tempAsset = new Asset_model();
			$tempAsset->loadAssetFromRecord($entry);
			$assetArray[] = $tempAsset;
		}

		$this->template->content->view('assetManager/restore', ['assetArray'=>$assetArray]);
		$this->template->publish();

	}

	function restore($objectId) {

		$accessLevel = max($this->user_model->getAccessLevel("instance",$this->instance), $this->user_model->getMaxCollectionPermission());

		if($accessLevel < PERM_ADDASSETS) {
			$this->errorhandler_helper->callError("noPermission");
		}

		$restoreObject = $this->doctrine->em->find("Entity\Asset", $objectId);
		$currentParent = $restoreObject->getRevisionSource();

		$restoreObject->setAssetId($currentParent->getAssetId());
		$restoreObject->setRevisionSource(null);
		$restoreObject->setDeleted(false);
		if($currentParent) {
			$currentParent->setAssetId(null);
			$currentParent->setRevisionSource($restoreObject);
			$currentParent->setDeleted(false);
		}

		$qb = $this->doctrine->em->createQueryBuilder();
		$q = $qb->update('Entity\Asset', 'a')
        ->set('a.revisionSource', $restoreObject->getId())
        ->where('a.revisionSource = ?1')
        ->setParameter(1, $currentParent->getId())
        ->getQuery();
		$p = $q->execute();


		$this->doctrine->em->flush();

		$this->asset_model->loadAssetById($restoreObject->getAssetId());
		$files = $this->asset_model->getAllWithinAsset("Upload");
		foreach($files as $file) {
			foreach($file->fieldContentsArray as $entry) {
				if($entry->fileHandler->deleted == true) {
					$entry->fileHandler->regenerate = true;
					$entry->fileHandler->undeleteFile();
				}

			}
		}

		$this->asset_model->save(true,false);


		instance_redirect("asset/viewAsset/" .$restoreObject->getAssetId());
	}

	// save an asset
	public function submission() {

		$accessLevel = max($this->user_model->getAccessLevel("instance",$this->instance), $this->user_model->getMaxCollectionPermission());

		if($accessLevel < PERM_ADDASSETS) {
			$this->errorhandler_helper->callError("noPermission");
		}

		$data = $this->input->post("formData");
		$objectArray = json_decode($data, true);
		unset($data);


		$asset = new Asset_model;
		$firstSave = true;

		if(isset($objectArray["objectId"]) && strlen(($objectArray["objectId"])) == 24) {
			$asset->loadAssetById($objectArray["objectId"]);
			$firstSave = FALSE;
		}

		$asset->templateId = $objectArray["templateId"];
		$asset->createObjectFromJSON($objectArray);

		unset($objectArray);

		if($firstSave) {
			$objectId = $asset->save(false);
		}
		else {
			$objectId = $asset->save(true);
		}


		echo json_encode(["objectId"=>(string)$objectId, "success"=>true]);


		if($firstSave) {
			/**
			 * BEWARE
			 * SOME REALLY HACKY STUFF HERE!
			 * Because we need files to know about their parent, we need to reload the asset and resave so that all the
			 * children get the parent id (because we don't know it at the time of the first save)
			 * Wish we had futures.
			 */

			$asset = new Asset_model();
			if($asset->loadAssetById($objectId)) {
				$asset->save(true,false);
			}


			/**
			 * END HACKY STUFF
			 */

		}
	}


	public function processingLogsForAsset($fileObjectId=null) {
		if(!$fileObjectId) {
			$this->errorhandler_helper->callError("noPermission");
		}
		$accessLevel = max($this->user_model->getAccessLevel("instance",$this->instance), $this->user_model->getMaxCollectionPermission());
		if($accessLevel < PERM_ADDASSETS) {
			$this->errorhandler_helper->callError("noPermission");
		}

		$data['lastErrors'] = $this->doctrine->em->getRepository("Entity\JobLog")->findBy(["asset"=>$fileObjectId], ["id"=>"desc"]);
		$this->template->content->view("admin/jobLogs", $data);
		$this->template->publish();
	}


	/**
	 * Because the uploader goes directly to S3, we need to pre-create a place for the asset to live on our end.  That way
	 * we can give it an appropriate objectId on S3.
	 * This can result in stray file containers sitting in the DB - eventually we should probably have some code to purge these or reconnect them
	 */
	public function getFileContainer() {
		$collectionId = $this->input->post("collectionId");
		$filename = $this->input->post("filename");
		// TODO: check that we can actually write to this bucket
		// right now, it'll fail silently (though if you look at the browser debugger it's obvious)
		$collection = $this->collection_model->getCollection($collectionId);
		$fileObjectId = $this->input->post("fileObjectId");
		$fileContainer = new fileContainerS3();
		$fileContainer->originalFilename = $filename;
		$fileContainer->path = "original";

		if(!$fileObjectId) {
			$fileContainer->ready = false;

			// this handler type may get overwritten later - for example, once we identify the contents of a zip
			$fileHandler = $this->filehandler_router->getHandlerForType($fileContainer->getType());

			$fileHandler->sourceFile = $fileContainer;
			$fileHandler->collectionId = $collectionId;
			$fileId = $fileHandler->save();

		}
		else {
			$fileId = $fileObjectId;
		}

		echo json_encode(["fileObjectId"=>$fileId, "collectionId"=>$collectionId, "bucket"=>$collection->getBucket(), "bucketKey"=>$collection->getS3Key(), "path"=>$fileContainer->path]);

	}


	public function cancelSourceFile($fileObjectId) {
		$fileHandler = $this->filehandler_router->getHandlerForObject($fileObjectId);

		if($this->user_model->getAccessLevel("instance",$this->instance)<PERM_ADDASSETS) {
			$this->errorhandler_helper->callError("noPermission");
		}

		$fileHandler->loadByObjectId($fileObjectId);
		$fileHandler->deleteFile();
	}

	public function deleteAsset($objectId) {


		$accessLevel = $this->user_model->getAccessLevel("instance",$this->instance);

		$this->load->model("search_model");
		$this->asset_model->loadAssetById($objectId);

		if($accessLevel < PERM_ADDASSETS) {
			if($this->user_model->getAccessLevel("collection",$this->collection_model->getCollection($this->asset_model->getGlobalValue("collectionId"))) < PERM_ADDASSETS) {
				$this->errorhandler_helper->callError("noPermission");
			}

		}

		$this->accessLevel = $this->user_model->getAccessLevel("asset", $this->asset_model);
		if($this->accessLevel < PERM_ADDASSETS) {
			$this->errorhandler_helper->callError("noPermission");
		}
		$this->search_model->remove($this->asset_model);

		$this->asset_model->delete();

		$qb = $this->doctrine->em->createQueryBuilder();
		$qb->delete('Entity\DrawerItem', 's');
		$qb->andWhere($qb->expr()->eq('s.asset', ':assetId'));
		$qb->setParameter(':assetId', $objectId);
		$qb->getQuery()->execute();

		instance_redirect("/");
	}

	// List all of the assets touched by the user.  Offset specifies page number essentially.
	public function userAssets($offset=0) {

		$qb = $this->doctrine->em->createQueryBuilder();
		$qb->from("Entity\Asset", 'a')
			->select("a")
			->where("a.createdBy = :userId")
			->setParameter(":userId", (int)$this->user_model->userId)
			->andWhere("a.assetId IS NOT NULL")
			->andWhere("a.deleted = false")
			->orderBy("a.modifiedAt", "DESC")
			->setMaxResults(50)
			->setFirstResult($offset);
		$assets = $qb->getQuery()->execute();

		$hiddenAssetArray = array();
		foreach($assets as $entry) {
			$this->asset_model->loadAssetFromRecord($entry);
			$hiddenAssetArray[] = ["objectId"=>$this->asset_model->getObjectId(), "title"=>$this->asset_model->getAssetTitle(true), "readyForDisplay"=>$this->asset_model->getGlobalValue("readyForDisplay"), "templateId"=>$this->asset_model->getGlobalValue("templateId"), "modifiedDate"=>$this->asset_model->getGlobalValue("modified")];
		}

		if($offset>0) {
			$this->load->view('user/hiddenAssets', ["isOffset"=>true, "hiddenAssets"=>$hiddenAssetArray]);
		}
		else {
			$this->template->content->view('user/hiddenAssets', ["isOffset"=>false, "hiddenAssets"=>$hiddenAssetArray]);
			$this->template->javascript->add("assets/js/scrollAssets.js");
			$this->template->publish();

		}

	}


	/**
	 * Fired when a file has finished uploading
	 */
	public function completeSourceFile($fileObjectId) {

		$fileHandler = $this->filehandler_router->getHandlerForObject($fileObjectId);
		$fileHandler->loadByObjectId($fileObjectId);
		$fileHandler->sourceFile->ready = true;
		$fileHandler->regenerate = true;
		$fileHandler->save();
		echo "success";
	}

	/**
	 * compareTemplates is called via AJAX when a user wants to switch from one metadata template
	 * to another.  It allows us to warn them if data will be lost in the process.
	 */
	public function compareTemplates($sourceTemplate, $destinationTemplate) {

		$sourceTemplate = $this->asset_template->getTemplate($sourceTemplate);
		$destinationTemplate = $this->asset_template->getTemplate($destinationTemplate);

		$missingFields = array();

		foreach($sourceTemplate->widgetArray as $widgetTitle=>$sourceWidget) {
			if(!array_key_exists($widgetTitle, $destinationTemplate->widgetArray)) {
				$missingFields[$widgetTitle] = "missing";
				continue;
			}

			if(get_class($sourceWidget) != get_class($destinationTemplate->widgetArray[$widgetTitle])) {
				$missingFields[$widgetTitle] = "type mismatch";
			}

		}


		$outputArray = array();
		foreach($missingFields as $key=>$failure) {
			$outputArray[$key] = ["type"=>$failure, "label"=>$sourceTemplate->widgetArray[$key]->getLabel()];

		}

		echo json_encode($outputArray);

	}

	public function importFromCSV() {

		$accessLevel = max($this->user_model->getAccessLevel("instance",$this->instance), $this->user_model->getMaxCollectionPermission());

		if($accessLevel < PERM_ADMIN) {
			$this->errorhandler_helper->callError("noPermission");
		}

		if(!isset($_POST["templateId"])) {
			$this->template->content->view("assetManager/importCSV", array());
			$this->template->publish();
			return;
		}

		$targetTemplate = $this->input->post("templateId");

		$config['upload_path'] = '/tmp/';
		$config['max_size']	= '0';
		$config['allowed_types']	= '*';

		$this->load->library('upload', $config);
		if ( ! $this->upload->do_upload())
		{
			$error = array('error' => $this->upload->display_errors());
			var_dump($error); // TODO: draw this in a view 
			return;
		}
		// TODO: more security here
		$data = array('upload_data' => $this->upload->data());
		$filename = $data["upload_data"]["full_path"];

		if(!$fp = fopen($filename, "r")) {
			$this->logging->logError("error reading file", $filename);
			$this->errorhandler_helper->callError("genericError");
			return;
		}

		$header = fgetcsv($fp, 0, ",");
		$data["filename"]  = $filename;
		$data["headerRows"] = $header;

		$template = new Asset_template($targetTemplate);
		$data["template"] = $template;


		$this->template->content->view("assetManager/matchCSVrows", $data);
		$this->template->publish();
	}

	public function exportCSV() {
		
		$accessLevel = max($this->user_model->getAccessLevel("instance",$this->instance), $this->user_model->getMaxCollectionPermission());

		if($accessLevel < PERM_ADMIN) {
			$this->errorhandler_helper->callError("noPermission");
		}

		$searchId = $this->input->post("searchId");
		$templateId = $this->input->post("templateId");
		if(!$searchId) {
			$this->template->content->view("assetManager/exportCSV");
			$this->template->publish();	
		}
		else {

			$assetTemplate = new Asset_template($templateId);
			$searchArchiveEntry = $this->doctrine->em->find('Entity\SearchEntry', $searchId);
			$searchArray = $searchArchiveEntry->getSearchData();
			$this->load->model("search_model");
			$matchArray = $this->search_model->find($searchArray, false, null, TRUE);
			$i=0;

			$out = fopen('php://output', 'w');
			$widgetArray = array();
			$widgetArray[] = "objectId";
			foreach($assetTemplate->widgetArray as $widgets) {
				$widgetArray[] = $widgets->getLabel();
				if(get_class($widgets) == "Upload") {
					$widgetArray[] = $widgets->getLabel() . " URL";
				}
				if(get_class($widgets) == "Related_asset") {
					$widgetArray[] = $widgets->getLabel() . " ObjectID";
				}
			}
			header('Content-Type: application/csv');
    		// tell the browser we want to save it instead of displaying it
    		header('Content-Disposition: attachment; filename="csvExport-' . $searchId . '.csv";');
			fputcsv($out, $widgetArray);

			foreach($matchArray['searchResults'] as $match) {

				$assetModel = new Asset_model($match);
				if($assetModel->templateId != $templateId) {
					continue;
				}
				$outputRow = [];
				$outputRow[] = $assetModel->getObjectId();
				foreach($assetTemplate->widgetArray as $key => $widgets) {
					if(isset($assetModel->assetObjects[$key])) {
						$object = $assetModel->assetObjects[$key];
						$outputRow[] = join("|",$object->getAsText(0));
						if(get_class($object) == "Upload") {
							$outputURLs = array();
							foreach($object->fieldContentsArray as $entry) {
								$handler = $entry->getFileHandler();
								$outputURLs[] = $handler->sourceFile->getProtectedURLForFile(null, "+240 minutes");
							}
							$outputRow[] = join($outputURLs, "|");
						}
						if(get_class($object) == "Related_asset") {
							$outputObjects = array();
							foreach($object->fieldContentsArray as $entry) {
								$objectId = $entry->getRelatedObjectId();
								$outputObjects[] = $objectId;
							}
							$outputRow[] = join($outputObjects, "|");
						}
					}
					else {

						$outputRow[] = "";
						if(get_class($widgets) == "Upload" || get_class($widgets) == "Related_asset") {
							$outputRow[] = "";
						}
					}

				}

				$assetModel = null;
				$i++;
				if($i == 50) {
					gc_collect_cycles();
					$i = 0;
				}
				fputcsv($out, $outputRow);
			}
			
			
			fclose($out);

		}
		
	}

	public function parseTime($timeString) {
		if(strlen($timeString) == 4) {
			$timeString = $timeString . "-01-01";
		}
		
		if(strtotime($timeString)) {
			return strtotime($timeString);
		}
		else {
			if(stristr($timeString, "bc")) {
				$timeString = str_replace(",", "", $timeString);
				$pattern = "/[0-9]+/";
				$matches = array();
				preg_match($pattern, $timeString, $matches);
				if(count($matches) > 0) {
					$yearsAgo = $matches[0];
					if(stristr($timeString, "century")) {
						$yearsAgo = $yearsAgo * 100;
					}
					$bceDate = (-1 * $yearsAgo * 31556952) - (1970*31556952);
					return $bceDate;
				}
			}
		}
		return FALSE;
	}


	public function processCSV($hash=null, $offset=null) {
		set_time_limit(120);

		if($this->input->post("filename")) {
			$cacheArray['filename'] = $this->input->post("filename");
			$cacheArray['templateId'] = $this->input->post("templateId");
			$cacheArray['collectionId'] = $this->input->post("collectionId");
			$cacheArray['mapping'] = $this->input->post("targetField");
			$cacheArray['delimiter'] = $this->input->post("delimiter");
			$hash = sha1(rand());
		}
		else {

			if($hash) {
				$this->doctrineCache->setNamespace('importCache_');
				$cacheArray = $this->doctrineCache->fetch($hash);
			}
			else {
				$this->logging->logError("Cachine Error");
				$this->errorhandler_helper->callError("genericError");
				return;
			}

		}
		


		if(!$this->collection_model->getCollection($cacheArray['collectionId'])) {
			$this->template->content->set("Invalid Collection");
			$this->template->publish();
			return;
		}

		$template = new Asset_template($cacheArray['templateId']);

		if(!$fp = fopen($cacheArray['filename'], "r")) {
			$this->logging->logError("error reading file", $cacheArray['filename']);
			$this->errorhandler_helper->callError("genericError");
			return;
		}

		$pheanstalk = new Pheanstalk\Pheanstalk($this->config->item("beanstalkd"));
				
		$targetArray = null;
		$parentObject = null;
		$targetField = null;

		if($this->input->post("parentObject") && strlen($this->input->post("parentObject")) == 24 && $this->input->post("targetFieldSelect") && strlen($this->input->post("targetFieldSelect")) > 0) {
			$cacheArray['parentObject'] = $this->input->post("parentObject");
			$cacheArray['targetField'] = $this->input->post("targetFieldSelect");
		}
		if($cacheArray['parentObject'] && $cacheArray['targetField']) {
			$parentObject = new Asset_model;
			$targetField =  $cacheArray['targetField'];
			$parentObject->loadAssetById($cacheArray['parentObject']);


			$objectArray = $parentObject->getAsArray();
			if(isset($objectArray[$targetField])) {
				$targetArray = $objectArray[$targetField];
			}
			else {
				$targetArray = array();
			}

		}


		$rowCount = 0;
		$totalLines = intval(exec("wc -l '" . $cacheArray['filename'] . "'"));

		$convertLines = true;
		if (mb_check_encoding(file_get_contents($cacheArray['filename']), 'UTF-8')) {
			$convertLines = false;
		}

		$header = fgetcsv($fp, 0, ",");
		$successArray = [];
		while($row = fgetcsv($fp, 0, ",")) {
			if($offset > 0) {
				if($rowCount < $offset) {
					$rowCount++;
					continue;
				}
			}
			$newEntry = array();
			$newEntry["readyForDisplay"] = true;
			$newEntry["templateId"] = $cacheArray['templateId'];
			$newEntry["collectionId"] = $cacheArray['collectionId'];
			$uploadItems = array();
			foreach($row as $key=>$cell) {
				if($convertLines)  {
					$cell = mb_convert_encoding( $cell, 'UTF-8', 'Windows-1252');	
				}
				
				$rowArray = array();
				if(strlen($cacheArray['delimiter'][$key]) > 0 && strpos($cell, $cacheArray['delimiter'][$key])) {
					$rowArray = explode($cacheArray['delimiter'][$key], $cell);
				}
				else {
					$rowArray[] = $cell;
				}

				foreach($rowArray as $rowEntry) {
					if($cacheArray['mapping'][$key] !== "ignore") {
						$widget = clone $template->widgetArray[$cacheArray['mapping'][$key]];
						$widgetContainer = $widget->getContentContainer();
					
						if(get_class($widget) == "Upload" && strlen(trim($rowEntry))>0) {
							$uploadItems[] = ["field"=>$widget->getfieldTitle(), "url"=>trim($rowEntry)];
							continue;
						}
						else if(get_class($widget) == "Date") {
							if(strpos($rowEntry, "-")) { 
								$exploded = explode("-", $rowEntry);
								if($this->parseTime($exploded[0])) {
									$widgetContainer->start = ["text"=>trim($exploded[0]), "numeric"=>$this->parseTime($exploded[0])];
								}
								if($this->parseTime($exploded[1])) {
									$widgetContainer->end = ["text"=>trim($exploded[1]), "numeric"=>$this->parseTime($exploded[1])];	
								}
							}
							else {
								if($this->parseTime($rowEntry)) {
									$widgetContainer->start = ["text"=>trim($rowEntry), "numeric"=>$this->parseTime($rowEntry)];
									$widgetContainer->end = ["text"=>"", "numeric"=>""];
								}	
							}
							
							
						}
						else if(get_class($widget) == "Location") {
							if(strpos($rowEntry, ",")) {
								$exploded = explode(",", $rowEntry);
								$widgetContainer->latitude = $exploded[0];
								$widgetContainer->longitude = $exploded[1];
								if(isset($exploded[2])) {
									$widgetcontainer->locationLabel = $exploded[2];
								}
							}
						}
						else if(get_class($widget) == "Tags") {
							if(strpos($rowEntry, ",")) {
								$exploded = explode(",", $rowEntry);
								$widgetContainer->tags = $exploded;
							}	
						}
						else if(get_class($widget) == "Related_asset") {
							if(strlen($rowEntry)> 15) {
								$widgetContainer->targetAssetId = $rowEntry;	
							}	
						}
						else if(get_class($widget) == "Multiselect") {
							// let's split and rematch the entry
							$splitEntry = explode("/", $rowEntry);
							$topLevels = getTopLevels($widget->getFieldData());
							$mappedArray = array();
							for($i=0; $i<count($splitEntry); $i++) {
								if(isset($topLevels[$i])) {
									$mappedArray[makeSafeForTitle($topLevels[$i])] = trim($splitEntry[$i]);
								}
								else {
									$mappedArray[] = $splitEntry[$i];
								}
								
							}
							$widgetContainer->fieldContents = $mappedArray;
						}
						else {
							$widgetContainer->fieldContents = trim($rowEntry);
						}

						$newEntry[$widget->getFieldTitle()][] = $widgetContainer->getAsArray();
					}
				}
				
			}


			$assetModel = new Asset_model();
			$assetModel->templateId = $cacheArray['templateId'];
			$assetModel->createObjectFromJSON($newEntry);
			$assetModel->save();

			if(isset($targetArray)) {
				$targetArray[]["targetAssetId"] = $assetModel->getObjectId();
			}

			if(count($uploadItems)>0) {
				$newTask = json_encode(["objectId"=>$assetModel->getObjectId(),"instance"=>$this->instance->getId(), "importItems"=>$uploadItems]);
				$jobId= $pheanstalk->useTube('urlImport')->put($newTask, NULL, 1, 900);
			}

			$cacheArray['successArray'][] = "Imported asset: " . $assetModel->getAssetTitle(true) . " (<a href=\"" . instance_url("/asset/viewAsset/" . $assetModel->getObjectId()) ."\">" . $assetModel->getObjectId() . "</A>)";
			
			$rowCount++;

			if($rowCount % 50 == 0) {
				$this->doctrineCache->setNamespace('importCache_');
				$this->doctrineCache->save($hash, $cacheArray, 900);

				if(isset($parentObject) && isset($targetArray)) {
					$objectArray = $parentObject->getAsArray();
					$objectArray[$targetField] = $targetArray;

					$parentObject->createObjectFromJSON($objectArray);
					$parentObject->save();
				}
				$offset = $rowCount;
				instance_redirect("assetManager/processCSV/" . $hash . "/" . $offset);
				return;
			}

		}

		if(isset($parentObject) && isset($targetArray)) {
			$objectArray = $parentObject->getAsArray();
			$objectArray[$targetField] = $targetArray;
			$parentObject->createObjectFromJSON($objectArray);
			$parentObject->save();
		}

		if(isset($parentObject)) {
			array_unshift($cacheArray['successArray'],  "Updated parent: " . $parentObject->getAssetTitle(true) . " (<a href=\"" . instance_url("/asset/viewAsset/" . $parentObject->getObjectId()) ."\">" . $parentObject->getObjectId() . "</A>)<br>");
		}

		$this->template->content->set("CSV Imported Successfully<hr>" . implode("<br>", $cacheArray['successArray']));
		$this->template->publish();

	}



	/**
	 * If changing collections will result in the file being migrated from one bucket to another, we
	 * need to alert the user.  This will lock the record from updating until the migration is complete.
	 * That migration is actually handled by the beltdrive backend.
	 */
	public function compareCollections($sourceCollection, $destinationCollection) {

		$sourceCollection = $this->collection_model->getCollection($sourceCollection);
		$destinationCollection = $this->collection_model->getCollection($destinationCollection);

		if($sourceCollection->getBucket() == $destinationCollection->getBucket()) {
			echo json_encode(["migration"=>false]);
		}
		else {
			echo json_encode(["migration"=>true]);
		}


	}


}

/* End of file  */
/* Location: ./application/controllers/ */