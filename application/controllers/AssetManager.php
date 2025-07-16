<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class AssetManager extends Admin_Controller {

	public function __construct()
	{
		parent::__construct();

		$this->template->javascript->add("//maps.google.com/maps/api/js?key=". $this->config->item("googleApi") ."&sensor=false");
		$jsLoadArray = ["handlebars-v1.1.2", "formSubmission", "serializeForm", "interWindow", "mapWidget","dateWidget","mule2", "uploadWidget","multiselectWidget", "parsley", "bootstrap-datepicker", "bootstrap-tagsinput", "typeahead.jquery", "assetAutocompleter"];
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
				$this->load->view("modals/addAsset_modal");
			}
		}
	}

	/**
	 * AJAX call to add a new widget to an asset entry form.
	 * gets the current count so we can build the fields with the right ID
	 * Dear god, why isn't this all done client side?  Please rebuild this in React and a proper design.
	 */
	public function getWidget($widgetId, $widgetCount, $totalNeeded=1) {
		$widgetObject = $this->doctrine->em->find('Entity\Widget', $widgetId);
		if(!$widgetObject) {
			$this->output->set_status_header(500, 'error loading widget');
			$this->logging->logError("getWidget", "could not load widget" . $widgetId . "with count " . $widgetCount);
			return;
		}
		$this->load->library("widget_router");
		$widget = $this->widget_router->getWidget($widgetObject);
		$return = "";
		for($i=0; $i<$totalNeeded; $i++) {
			$widget->offsetCount = $widgetCount;
			$return .= $widget->getForm();
			$widgetCount++;
		}
		echo $return;
	}


	/**
	 * This is the main page for creating a new asset
	 */
	public function addAsset($templateId=null, $collectionId = null, $inlineForm = false)
	{
		if ($this->isUsingVueUI()) {
			return $this->template->publish('vueTemplate');
		}

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
		$this->template->loadJavascript(["widgetHelpers"]);


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

	public function getTemplate($templateId) {
	
	
		if(!$templateId) {
			$this->logging->logError("no template", $_SERVER);
			instance_redirect("/errorHandler/error/unknownTemplate");
		}

		$template = $this->asset_template->getTemplate($templateId);

		$templateArray = $template->getAsArray();

		$allowedCollectionsObject = $this->user_model->getAllowedCollections(PERM_ADDASSETS);
		$allowedCollections = array();
		foreach($allowedCollectionsObject as $collection) {
			$allowedCollections[$collection->getId()] = $collection->getTitle();
		}

		if(strlen($this->template->collectionId)>0) {
			$collectionId = intval($this->template->collectionId->__toString());
		}

		$collections = $this->buildCollectionArray($this->instance->getCollectionsWithoutParent());


		$templateArray["collections"] = $collections;
		$templateArray["allowedCollections"] = $allowedCollections;
		// $templateArray["templates"] = $templates;

		return render_json($templateArray);
	}


	function editAsset($objectId, $inlineForm=false) {
		if ($this->isUsingVueUI()) {
			return $this->template->publish('vueTemplate');
		}

		$accessLevel = $this->user_model->getAccessLevel("instance",$this->instance);

		$this->asset_model->loadAssetById($objectId);

		if($accessLevel < PERM_ADDASSETS) {
			if($this->user_model->getAccessLevel("collection",$this->collection_model->getCollection($this->asset_model->getGlobalValue("collectionId"))) < PERM_ADDASSETS) {
				$this->errorhandler_helper->callError("noPermission");
			}
		}

		$this->load->model("asset_model");
		$this->asset_model->loadAssetById($objectId);
		$this->template->loadJavascript(["widgetHelpers"]);
		$assetTemplate = $this->asset_template->getTemplate($this->asset_model->templateId);




		$this->template->title = "Edit Asset | ". $assetTemplate->getName() . "";

		if($inlineForm) {
			$assetTemplate->displayInline = true;
			$this->template->set_template("chromelessTemplate");
		}

		$this->template->content->set($assetTemplate->templateForm($this->asset_model));

		$this->template->publish();
		
	}


	public function buildCollectionArray($collections) {

		$collectionReturn = array();
		foreach($collections as $collection) {
			if(!$collection->hasChildren()) {
				$collectionReturn[$collection->getId()] = $collection->getTitle();
			}
			else {
				$collectionReturn[$collection->getId()] = [$collection->getTitle() => $this->buildCollectionArray($collection->getChildren())];
			}

			
		}
		return $collectionReturn;

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

		$restoreObject = $this->internalRestore($objectId);
		

		instance_redirect("asset/viewAsset/" .$restoreObject);
	}

	private function internalRestore(string $objectId, bool $createCheckpoint = true) {
		$restoreObject = $this->doctrine->em->find("Entity\Asset", $objectId);
		$currentParent = $restoreObject->getRevisionSource();
		if(!$currentParent) {
			return false;
		}
		$restoreObject->setAssetId($currentParent->getAssetId());
		$restoreObject->setRevisionSource(null);
		$restoreObject->setDeleted(false);
		$restoreObject->setDeletedBy(null);
		
		if($createCheckpoint) {
			$currentParent->setAssetId(null);
			$currentParent->setRevisionSource($restoreObject);
			$currentParent->setDeleted(false);
		}
		else {
			$currentParent->setDeleted(true);
			$currentParent->setDeletedBy($this->user_model->user->getId());
			$currentParent->setRevisionSource(null);
			$currentParent->setAssetId(null);
		}
		

		$qb = $this->doctrine->em->createQueryBuilder();
		$q = $qb->update('Entity\Asset', 'a')
        ->set('a.revisionSource', $restoreObject->getId())
        ->where('a.revisionSource = ?1')
        ->setParameter(1, $currentParent->getId())
        ->getQuery();
		$p = $q->execute();


		$this->doctrine->em->flush();
		$assetObject = new Asset_model;
		$assetObject->loadAssetById($restoreObject->getAssetId());
		$files = $assetObject->getAllWithinAsset("Upload");
		foreach($files as $file) {
			foreach($file->fieldContentsArray as $entry) {
				if($entry->fileHandler->deleted == true) {
					$entry->fileHandler->regenerate = true;
					$entry->fileHandler->undeleteFile();
				}

			}
		}

		$assetObject->save(true,false, false);
		return $restoreObject->getAssetId();
	}

	// save an asset
	public function submission($returnJson = false) {

		$accessLevel = max($this->user_model->getAccessLevel("instance",$this->instance), $this->user_model->getMaxCollectionPermission());

		if($accessLevel < PERM_ADDASSETS) {
			// log this
			$this->logging->logError("no permission to add asset", $_SERVER);
			if ($returnJson) {
				return render_json(["error" => "No permission"], 403);
			}

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

		if ($returnJson) {
			return render_json(["objectId" => $objectId, "success" => true], 200);
		} else {
			echo json_encode(["objectId"=>(string)$objectId, "success"=>true]);
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
		$containers = $this->input->post("containers");
		$containersArray = json_decode($containers, true);
		$returnArray = [];
		foreach($containersArray as $container) {
			$collectionId = $container["collectionId"];
			$index = $container["index"];
			$filename = $container["filename"];
			$fileObjectId = $container["fileObjectId"];
			$collection = $this->collection_model->getCollection($collectionId);
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

			$returnArray[] = ["fileObjectId"=>$fileId, "collectionId"=>$collectionId, "bucket"=>$collection->getBucket(), "bucketKey"=>$collection->getS3Key(), "path"=>$fileContainer->path,  "index"=>$index];
			if(isset($fileHandler)) {
				$this->doctrine->em->detach($fileHandler->asset);
			}
			
			$fileHandler = null;
			$fileContainer = null;

			$this->doctrine->em->clear();
			gc_collect_cycles();

		}

		// TODO: check that we can actually write to this bucket
		// right now, it'll fail silently (though if you look at the browser debugger it's obvious)

		echo json_encode($returnArray);

	}


	public function cancelSourceFile($fileObjectId) {
		$fileHandler = $this->filehandler_router->getHandlerForObject($fileObjectId);

		if($this->user_model->getAccessLevel("instance",$this->instance)<PERM_ADDASSETS) {
			$this->errorhandler_helper->callError("noPermission");
		}

		$fileHandler->loadByObjectId($fileObjectId);
		$fileHandler->deleteFile();
	}

	public function deleteAsset($objectId, $returnJson = false) {


		$accessLevel = $this->user_model->getAccessLevel("instance",$this->instance);

		$this->load->model("search_model");
		$this->asset_model->loadAssetById($objectId);

		if($accessLevel < PERM_ADDASSETS) {
			if($this->user_model->getAccessLevel("collection",$this->collection_model->getCollection($this->asset_model->getGlobalValue("collectionId"))) < PERM_ADDASSETS) {
				if ($returnJson) {
					return render_json(["error" => "No permission"], 403);
				}

				$this->errorhandler_helper->callError("noPermission");
			}

		}

		$this->accessLevel = $this->user_model->getAccessLevel("asset", $this->asset_model);
		if($this->accessLevel < PERM_ADDASSETS) {
			if ($returnJson) {
				return render_json(["error" => "No permission"], 403);
			}
			$this->errorhandler_helper->callError("noPermission");
		}
		$this->search_model->remove($this->asset_model);

		$this->asset_model->delete();

		$qb = $this->doctrine->em->createQueryBuilder();
		$qb->delete('Entity\DrawerItem', 's');
		$qb->andWhere($qb->expr()->eq('s.asset', ':assetId'));
		$qb->setParameter(':assetId', $objectId);
		$qb->getQuery()->execute();

		if ($returnJson) {
			return render_json(null, 204);
		}

		instance_redirect("/");
	}

	// List all of the assets touched by the user.  Offset specifies page number essentially.
	public function userAssets($offset=0, $returnJson = false) {
		if ($this->isUsingVueUI() && !$returnJson) {
			return $this->template->publish('vueTemplate');
		}

		if(!isset($this->instance) || !$this->user_model->userLoaded) { 
			if ($returnJson) {
				return render_json(["error" => "No permission"], 403);
			}
			instance_redirect("errorHandler/error/noPermission");
		}


		$qb = $this->doctrine->em->createQueryBuilder();
		$qb->from("Entity\Asset", 'a')
			->select("a")
			->where("a.createdBy = :userId")
			->setParameter(":userId", (int)$this->user_model->userId)
			->andWhere("a.assetId IS NOT NULL")
			->andWhere("a.deleted = false")
			->orderBy("a.modifiedAt", "DESC")
			->setMaxResults(100)
			->setFirstResult($offset);
		$assets = $qb->getQuery()->execute();

		$hiddenAssetArray = array();
		foreach($assets as $entry) {

			$this->asset_model->loadAssetFromRecord($entry, true);
			$resultCache = $this->asset_model->getSearchResultEntry();

			$hiddenAssetArray[] = ["objectId"=>$this->asset_model->getObjectId(), "title"=>$resultCache['title']??"", "readyForDisplay"=>$this->asset_model->getGlobalValue("readyForDisplay"), "templateId"=>$this->asset_model->getGlobalValue("templateId"), "modifiedDate"=>$this->asset_model->getGlobalValue("modified")];
		}

		if ($returnJson) {
			return render_json($hiddenAssetArray);
		}

		$this->template->javascript->add("assets/datatables/datatables.min.js");
		$this->template->stylesheet->add("assets/datatables/datatables.min.css");
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
		
		if(!$destinationTemplate) {
			render_json("error", 500);
		}

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

			$csvBatches = $this->doctrine->em->getRepository("Entity\CSVBatch")->findBy(["createdBy"=>$this->user_model->user], ["id"=>"desc"]);

			$this->template->content->view("assetManager/importCSV", ["csvBatches" => $csvBatches]);
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

		$header = fgetcsv($fp, 0, ",", '"', '\\');
		$data["filename"]  = $filename;
		$data["headerRows"] = $header;

		$template = new Asset_template($targetTemplate);
		$data["template"] = $template;


		$this->template->content->view("assetManager/matchCSVrows", $data);
		$this->template->publish();
	}

	public function exportCSV() {
		function collapseParents($collection) { 
			$parent = $collection->getParent();
			if($parent) {
				return collapseParents($parent)  . $parent->getTitle() . "/";
			}
			else {
				return "";
			}
		}
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
			$showHidden = false;
			if(isset($searchArray['showHidden']) && $searchArray['showHidden'] == true) {
				// This will include items that are not yet flagged "Ready for display"
				$showHidden = true;
			}
			$this->load->model("search_model");
			$this->search_model->loadAllLength = 10000;
			$matchArray = $this->search_model->find($searchArray, !$showHidden, null, TRUE);
			$i=0;

			$out = fopen('php://output', 'w');
			fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
			$widgetArray = array();
			$widgetArray[] = "objectId";
			$widgetArray[] = "collection";
			$widgetArray[] = "objectURL";
			foreach($assetTemplate->widgetArray as $widgets) {
				$widgetArray[] = $widgets->getLabel();
				if(get_class($widgets) == "Upload") {
					$widgetArray[] = $widgets->getLabel() . " URL";
					$widgetArray[] = $widgets->getLabel() . " Derivative";
				}
				if(get_class($widgets) == "Related_asset") {
					$widgetArray[] = $widgets->getLabel() . " ObjectID";
				}
				if(get_class($widgets) == "Location") {
					$widgetArray[] = $widgets->getLabel() . " Latitude";
					$widgetArray[] = $widgets->getLabel() . " Longitude";
					$widgetArray[] = $widgets->getLabel() . " Label";
					$widgetArray[] = $widgets->getLabel() . " Address";
				}
			}
			$widgetArray[] = "last modified by";
			$widgetArray[] = "last modified date";

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
				$collection =  $this->collection_model->getCollection($assetModel->getGlobalValue("collectionId"));
				
				
				
				$parents = collapseParents($collection) . $collection->getTitle();

				$collection = $this->collection_model->getCollection($assetModel->getGlobalValue("collectionId"));
				$outputRow[] = $parents;
				$outputRow[] = instance_url("asset/viewAsset/" . $assetModel->getObjectId());
				foreach($assetTemplate->widgetArray as $key => $widgets) {
					if(isset($assetModel->assetObjects[$key])) {
						$object = $assetModel->assetObjects[$key];

						// special case textarea to get the html out (for St. Olaf)
						// should fix this in a more real way at some point
						if(get_class($object) == "Textarea") { 
							$outputObjects = array();
							foreach($object->fieldContentsArray as $entry) {
								$outputObjects[] = $entry->fieldContents;
							}
							$outputRow[] = implode("|", $outputObjects);
						}
						elseif(get_class($object) == "Date") {
							$outputObjects = array();
							foreach($object->fieldContentsArray as $entry) {
								$outputString = "";								
								if($entry->start["text"]) {
									$outputString .= $entry->start["text"];
								}
								
								if($entry->range) {
									$outputString .= " - " . $entry->end["text"];
								}
								
								if($entry->label) {
									if(strlen($outputString) > 0) {
										$outputString .= ",";
									}
									$outputString .= $entry->label;
								}
								$outputObjects[] = $outputString;
							}
							$outputRow[] = implode("|", $outputObjects);
						}
						else {
							$outputRow[] = implode("|",$object->getAsText(0));	
						}
						
						if(get_class($object) == "Upload") {
							$outputURLs = array();
							$outputDerivatives = array();
							foreach($object->fieldContentsArray as $entry) {
								$handler = $entry->getFileHandler();
								if($handler->sourceFile) {
									$outputURLs[] = $handler->sourceFile->getProtectedURLForFile(null, "+240 minutes");	
									$outputDerivatives[] = instance_url("/fileManager/bestDerivativeByFileId/" . $handler->getObjectId());
								}
								
							}
							$outputRow[] = implode("|", $outputURLs);
							$outputRow[] = implode("|", $outputDerivatives);
						}
						if(get_class($object) == "Related_asset") {
							$outputObjects = array();
							foreach($object->fieldContentsArray as $entry) {
								$objectId = $entry->getRelatedObjectId();
								$outputObjects[] = $objectId;
							}
							$outputRow[] = implode("|", $outputObjects);
						}
						if(get_class($widgets) == "Location") {
							$outputLatitude = array();
							$outputLongitude = array();
							$outputLabel = array();
							$outputAddress = array();
							foreach($object->fieldContentsArray as $entry) {
								$outputLatitude[] = $entry->latitude;
								$outputLongitude[] = $entry->longitude;
								$outputLabel[] = $entry->locationLabel;
								$outputAddress[] = $entry->address;
							}
							$outputRow[] = implode("|", $outputLatitude);
							$outputRow[] = implode("|", $outputLongitude);
							$outputRow[] = implode("|", $outputLabel);
							$outputRow[] = implode("|", $outputAddress);
						}
					}
					else {

						$outputRow[] = "";
						if(get_class($widgets) == "Related_asset") {
							$outputRow[] = "";
						}
						if(get_class($widgets) == "Upload") {
							$outputRow[] = "";
							$outputRow[] = "";
						}
						if(get_class($widgets) == "Location") {
							$outputRow[] = "";
							$outputRow[] = "";
							$outputRow[] = "";
							$outputRow[] = "";
						}
					}

				}
				$outputRow[] = $assetModel->getLastModifiedName();
				$outputRow[] = $assetModel->getGlobalValue("modified")->format('Y-m-d H:i:s');

				unset($assetModel);
				$this->doctrine->em->clear();
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
		$timeString = trim($timeString);
		// anything less than 2 chars is probably just noise
		if(strlen($timeString) < 4) {
			return false;
		}

		// if we've got just a four digit year, we do our best to guess a date.
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

			if(!$this->collection_model->getCollection($cacheArray['collectionId'])) {
				$this->template->content->set("Invalid Collection");
				$this->template->publish();
				return;
			}

			$csvBatch = new Entity\CSVBatch();
			$csvBatch->setCollection($this->collection_model->getCollection($cacheArray['collectionId']));
			$templateModel = $this->doctrine->em->find('Entity\Template', $cacheArray['templateId']);
			$csvBatch->setTemplate($templateModel);
			$csvBatch->setCreatedBy($this->user_model->user);
			$csvBatch->setFilename($cacheArray['filename']);
			$csvBatch->setCreatedAt(new DateTime());
			$this->doctrine->em->persist($csvBatch);
			$this->doctrine->em->flush();
			$cacheArray['importId'] = $csvBatch->getId();
			$hash = sha1(rand());
		}
		else {

			if($hash) {
				$this->doctrineCache->setNamespace('importCache_');
				$cacheArray = $this->doctrineCache->fetch($hash);
				$csvBatch = $this->doctrine->em->find('Entity\CSVBatch', $cacheArray['importId']);
			}
			else {
				$this->logging->logError("Cachine Error");
				$this->errorhandler_helper->callError("genericError");
				return;
			}

		}
		

		$isUpdate = false;
		$updateField = null;
		$readyForDisplayField = null;
		foreach($cacheArray['mapping'] as $key=>$value) {
			if($value == "objectId") {
				$isUpdate = true;
				$updateField = $key;
			}
			if($value == "readyForDisplay") {
				$readyForDisplayField = $key;
			}
		}

		
		$template = new Asset_template($cacheArray['templateId']);

		if(!$fp = fopen($cacheArray['filename'], "r")) {
			$this->logging->logError("error reading file", $cacheArray['filename']);
			$this->errorhandler_helper->callError("genericError");
			return;
		}
				
		$targetArray = null;
		$parentObject = null;
		$targetField = null;

		if($this->input->post("parentObject") && strlen($this->input->post("parentObject")) == 24 && $this->input->post("targetFieldSelect") && strlen($this->input->post("targetFieldSelect")) > 0) {
			$cacheArray['parentObject'] = $this->input->post("parentObject");
			$cacheArray['targetField'] = $this->input->post("targetFieldSelect");
		}
		if(isset($cacheArray['parentObject']) && isset($cacheArray['targetField'])) {
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

		$header = fgetcsv($fp, 0, ",", escape: "\\");
		$successArray = [];
		while($row = fgetcsv($fp, 0, ",", escape: '\\')) {
			if($offset > 0) {
				if($rowCount < $offset) {
					$rowCount++;
					continue;
				}
			}
			if(max(array_map('strlen', $row)) == 0) {
				// every item in this row is blank. Thanks Excel! Let's skip it.
				$rowCount++;
				continue;
			}
			$assetModel = null;
			if($isUpdate) {
				$assetModel = new asset_model();
				if(!$assetModel->loadAssetById($row[$updateField])) {
					$cacheArray['successArray'][] = "could not find asset: " . $row[$updateField];
					continue;
				}

				// confirm that this asset is in the current instance
				$collection = $assetModel->getGlobalValue("collectionId");
				$loadedCollection = $this->collection_model->getCollection($collection);
				$correctInstance = false;
				foreach($loadedCollection->getInstances() as $instance) {
					if($instance->getId() == $this->instance->getId()) {
						$correctInstance = true;
					}
				}

				if(!$correctInstance) {
					return $this->errorhandler_helper->callError("noPermission");
				}


				$newEntry = $assetModel->getAsArray();
			}
			else {
				$newEntry = array();
			}

			$newEntry["csvBatch"] = $csvBatch->getId();

			if($readyForDisplayField) {
				 if(isset($row[$readyForDisplayField]) && ($row[$readyForDisplayField] == 1 || strtolower($row[$readyForDisplayField]) == "on" || strtolower($row[$readyForDisplayField]) == "true")){
					$newEntry["readyForDisplay"] = true;
				 }
				 else {
					$newEntry["readyForDisplay"] = false;
				 }
			}
			else {
				$newEntry["readyForDisplay"] = true;
			}
			
			
			if(!$isUpdate) {
				$newEntry["templateId"] = $cacheArray['templateId'];
				$newEntry["collectionId"] = $cacheArray['collectionId'];	
			}
			
			$uploadItems = array();
			foreach($row as $key=>$cell) {
				if($convertLines)  {
					$cell = mb_convert_encoding( $cell, 'UTF-8', 'Windows-1252');	
				}
				
				$rowArray = array();
				if(strlen($cacheArray['delimiter'][$key]) > 0 && strpos($cell, $cacheArray['delimiter'][$key])) {
					$rowArray = array_filter(explode($cacheArray['delimiter'][$key], $cell));
				}
				else {
					$rowArray[] = $cell;
				}
				$firstLoop = true;
				foreach($rowArray as $rowEntry) {
					if($cacheArray['mapping'][$key] !== "ignore" && $cacheArray['mapping'][$key] !== "objectId" && $cacheArray['mapping'][$key] !== "readyForDisplay") {
						$widget = clone $template->widgetArray[$cacheArray['mapping'][$key]];
						$widgetContainer = $widget->getContentContainer();

						if($isUpdate && $firstLoop) {
							// for updates, clear existing content
							$newEntry[$widget->getFieldTitle()] = array();
						}
						$firstLoop = false;

						if(get_class($widget) == "Upload" && strlen(trim($rowEntry))>0) {
							$description = null;
							$captions = null;
							$chapters = null;
							if(strpos($rowEntry, ",")) {
								$fileUploadExploded = str_getcsv($rowEntry);
								$url = $fileUploadExploded[0];
								if(isset($fileUploadExploded[1])) {
									$description = $fileUploadExploded[1];
								}
								if(isset($fileUploadExploded[2])) { 
									$captions = $fileUploadExploded[2];
								}
								if(isset($fileUploadExploded[3])) { 
									$chapters = $fileUploadExploded[3];
								}
							}
							else {
								$url = $rowEntry;
							}
							$uploadItems[] = ["field"=>$widget->getfieldTitle(), "url"=>trim($url), "description"=>$description, "captions"=>trim($captions??""),"chapters"=>trim($chapters??"")];
							continue;
						}
						else if(get_class($widget) == "Date") {
							if(strpos($rowEntry, ",")) {
								$dateExploded = $exploded = str_getcsv($rowEntry, escape: "\\");
								$widgetContainer->label = $dateExploded[1];
								$dateString = $dateExploded[0];
							}
							else {
								$dateString = $rowEntry;
							}
							if(strpos($dateString, "-")) { 
								$exploded = explode("-", $dateString);
								if($this->parseTime($exploded[0])) {
									$widgetContainer->start = ["text"=>trim($exploded[0]), "numeric"=>$this->parseTime($exploded[0])];
								}
								if($this->parseTime($exploded[1])) {
									$widgetContainer->end = ["text"=>trim($exploded[1]), "numeric"=>$this->parseTime($exploded[1])];	
								}
							}
							else {
								if($this->parseTime($dateString)) {
									$widgetContainer->start = ["text"=>trim($dateString), "numeric"=>$this->parseTime($dateString)];
									$widgetContainer->end = ["text"=>"", "numeric"=>""];
								}	
							}
							
							
						}
						else if(get_class($widget) == "Location") {
							if(strpos($rowEntry, ",")) {
								$exploded = preg_split("/,/", $rowEntry, 3);
								$widgetContainer->latitude = $exploded[0];
								$widgetContainer->longitude = $exploded[1];
								if(isset($exploded[2])) {
									$widgetContainer->locationLabel = $exploded[2];
								}
							}
						}
						else if(get_class($widget) == "Tags") {
							if(strpos($rowEntry, ",")) {
								$exploded = str_getcsv($rowEntry);
								$widgetContainer->tags = $exploded;
							}
							else {
								$widgetContainer->tags = $rowEntry;
							}
						}
						else if(get_class($widget) == "Related_asset") {
							$targetId = $rowEntry;
							$label = null;
							if(strpos($rowEntry, ",")) {
								$exploded = preg_split("/,/", $rowEntry, 3);
								$targetId = $exploded[0];
								$label = $exploded[1];
							}
							
							if(strlen($targetId)> 15) {
								$widgetContainer->targetAssetId = $targetId;	
								if($label) {
									$widgetContainer->label = $label;
								}
							}		
						}
						else if(get_class($widget) == "Checkbox") {
							if(strlen($rowEntry)>0 && $rowEntry != "0" && strtolower($rowEntry) != "off" && strtolower($rowEntry) != "false") {
								$widgetContainer->fieldContents = "on";	
							}
						}
						else if(get_class($widget) == "Multiselect") {
							// let's split and rematch the entry
							$splitEntry = explode("/", $rowEntry);
							$topLevels = array_values(getTopLevels($widget->getFieldData())); // rekey so we get the headers properly
							$mappedArray = array();
							for($i=0; $i<count($topLevels); $i++) {
								if(isset($splitEntry[$i])) {
									$mappedArray[makeSafeForTitle($topLevels[$i])] = trim($splitEntry[$i]);
								}
								else {
									$mappedArray[makeSafeForTitle($topLevels[$i])] ="";
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

			if(!$isUpdate) {
				$assetModel = new Asset_model();
				$assetModel->templateId = $cacheArray['templateId'];
				$assetModel->createObjectFromJSON($newEntry);
			}
			else {
				$assetModel->loadWidgetsFromArray($newEntry);
				$assetModel->setGlobalValue("csvBatch", $newEntry["csvBatch"]);
			}
			
			
			$assetModel->save(reindex: true, saveRevision:true, noCache:true);

			if(isset($targetArray)) {
				$targetArray[]["targetAssetId"] = $assetModel->getObjectId();
			}

			if(count($uploadItems)>0) {
				$newTask = ["objectId"=>$assetModel->getObjectId(),"instance"=>$this->instance->getId(), "importItems"=>$uploadItems];
				$pheanstalk =  Pheanstalk\Pheanstalk::create($this->config->item("beanstalkd"));
				$tube = new Pheanstalk\Values\TubeName('urlImport');
				$pheanstalk->useTube($tube);
				$jobId = $pheanstalk->put(json_encode($newTask), Pheanstalk\Pheanstalk::DEFAULT_PRIORITY, 0,900);
			}

			$cacheArray['successArray'][] = "Imported asset: " . $assetModel->getAssetTitle(true) . " (<a href=\"" . instance_url("/asset/viewAsset/" . $assetModel->getObjectId()) ."\">" . $assetModel->getObjectId() . "</A>)";
			
			$rowCount++;

			if($rowCount % 400 == 0) {
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
	 * delete all of the records that were imported with a batch
	 */
	public function purgeCSVImport($importId) {
		$accessLevel = max($this->user_model->getAccessLevel("instance",$this->instance), $this->user_model->getMaxCollectionPermission());
		if($accessLevel < PERM_ADMIN) {
			$this->errorhandler_helper->callError("noPermission");
		}

		$csvBatch = $this->doctrine->em->find("Entity\CSVBatch", $importId);
		if(!$csvBatch) {
			$this->errorhandler_helper->callError("unknownAsset");
		}
		$this->load->model("asset_model");
		$this->load->model("asset_template");
		$this->load->model("search_model");

		$assets = $csvBatch->getAssets();
		$output = "<ul>";
		$countStart = 0;
		foreach($assets as $assetRecord) {
			$asset = new Asset_model();
			if(!$assetRecord->getAssetId()) {
				$output .= "<li>Asset not found: " . $assetRecord->getAssetId() . "</li>";
				continue;
			}
			$output .= "<li>Loading Asset: " . $assetRecord->getAssetId() . "</li>";
			$asset->loadAssetFromRecord($assetRecord);

			$revisions = $asset->assetObject->getRevisions();
			if(count($revisions) > 0) {
				$mostRecentRevision = $revisions->last();
				$this->internalRestore(objectId:$mostRecentRevision->getId(), createCheckpoint:false);
			}
			else {
				$asset->delete();
				$this->search_model->remove($asset);
				$this->doctrine->em->clear();
			}

			
			$countStart++;
		}
		$output .="</ul>";
		$output .="Count: " . $countStart . "\n";
		$csvBatch = $this->doctrine->em->find("Entity\CSVBatch", $importId);
		$this->doctrine->em->remove($csvBatch);
		$this->doctrine->em->flush();
		$this->template->content->set("CSV Content Purged<hr>" . $output);
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

	/**
	 * Allow sidecars to be updated independently of the asset
	 */
	public function setSidecarForFile($fileId, $sidecarType) {
		$accessLevel = max($this->user_model->getAccessLevel("instance",$this->instance), $this->user_model->getMaxCollectionPermission());

		if($accessLevel < PERM_ADDASSETS) {
			$this->errorhandler_helper->callError("noPermission");
		}

		$fileHandler = $this->filehandler_router->getHandlerForObject($fileId);
		$fileHandler->loadByObjectId($fileId);

		if(!($fileHandler)) {
			instance_redirect("errorHandler/error/unknownFile");
			return;
		}

		if(!$this->asset_model->loadAssetById($fileHandler->parentObjectId)) {
			$this->logging->logError("getOriginal", "could not load asset from fileHandler" . $fileId);
			instance_redirect("errorHandler/error/unknownFile");
			return;
		}

		$uploadHandlers = $this->asset_model->getAllWithinAsset("Upload", null, 0);

		foreach($uploadHandlers as $uploadHandler) {

			foreach($uploadHandler->fieldContentsArray as $uploadHandlerContent) {

				if($uploadHandlerContent->fileId == $fileId) {
					if($sidecarContent = json_decode($this->input->post("sidecarContent"))) {
						$uploadHandlerContent->sidecars[$sidecarType] = $sidecarContent;
						$this->asset_model->save();
						echo json_encode(["status"=>"success"]);
						return;
					}
					
				}
			}
		}
		echo json_encode(["status"=>"failure"]);
	}


}

/* End of file  */
/* Location: ./application/controllers/ */
