<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class asset extends Instance_Controller {

	public function __construct()
	{
		parent::__construct();

		$jsLoadArray = ["handlebars-v1.1.2"];

		if(defined('ENVIRONMENT') && ENVIRONMENT == "development") {
			$jsLoadArray = array_merge($jsLoadArray, ["assetView", "drawers"]);
		}
		else {
			$jsLoadArray[] = "assetMaster";
		}


		$jsLoadArray = array_merge($jsLoadArray, ["jquery.fullscreen-0.4.1",  "mapWidget","panzoom","jquery.expander"]);
		$this->template->loadJavascript($jsLoadArray);
		$this->template->addToDrawer->view("drawers/add_to_drawer");
		$this->template->content->view("drawers/drawerModal");

		$this->load->helper('number');
		$this->load->helper('fileview');
		$this->load->model("asset_model");
	}

	function getAsset($objectId) {
		$assetModel = new Asset_model;
		if(!$objectId) {
			show_404();
		}

		
		if(!$assetModel->loadAssetById($objectId, $noHydrate = true)) {
			show_404();
		}

		if(!$this->collection_model->getCollection($assetModel->assetObject->getCollectionId())) {
			show_404();
		}
		
		$this->accessLevel = $this->user_model->getAccessLevel("asset", $assetModel);

		if($this->accessLevel == PERM_NOPERM) {
			$this->errorhandler_helper->callJsonError("noPermission");
		}
		if($this->config->item('restrict_hidden_assets') == "TRUE" && $this->accessLevel < PERM_ADDASSETS && 	$assetModel->getGlobalValue("readyForDisplay") == false) {
			$this->errorhandler_helper->callJsonError("noPermission");
		}

		return render_json($assetModel->assetObject->getWidgets());

	}

	function viewAsset($objectId=null, $returnJson=false) {
		$assetModel = new Asset_model;
		if(!$objectId) {
			show_404();
		}

		
		if(!$assetModel->loadAssetById($objectId)) {
			show_404();
		}

		if(!$this->collection_model->getCollection($assetModel->getGlobalValue("collectionId"))) {
			show_404();
		}
		
		$this->accessLevel = $this->user_model->getAccessLevel("asset", $assetModel);

		if($this->accessLevel == PERM_NOPERM) {
			$this->errorhandler_helper->callError("noPermission");
		}
		if($this->config->item('restrict_hidden_assets') == "TRUE" && $this->accessLevel < PERM_ADDASSETS && $assetModel->getGlobalValue("readyForDisplay") == false) {
			$this->errorhandler_helper->callError("noPermission");
		}


		// Try to find the primary file handler, which might be another asset.  Return the hosting asset, not the filehandler directly

		$targetObject = null;
		try {
			$fileHandler = $assetModel->getPrimaryFilehandler();

			if($fileHandler->parentObjectId != $objectId) {
				// So in this case, the file handler has a parent that isn't our object.
				// it might be a related asset which appears on our page, but in the case of multiple levels
				// of depth, we might not actually know what this asset is.  let's check to see if we have record of this object
				$json = json_encode($assetModel->getAsArray(null,false, false)); 
				if(!strstr($json, $fileHandler->parentObjectId)) {
					$targetObject = $fileHandler->getObjectId();
				}
				else {
					$targetObject = $fileHandler->parentObjectId;
				}
				
			}
			else {
				$targetObject = $fileHandler->getObjectId();
			}
		}
		catch (Exception $e) {

		}

		if($returnJson == "true") {
			$json = $assetModel->getAsArray(null,false, $includeRelatedAssetCache= true); // include related assets
			header('Content-type: application/json');
			echo json_encode($json);
			return;
		}
		else {
		// for subclipping movies

			$assetTitle = $assetModel->getAssetTitle();
			$this->template->title = reset($assetTitle);
			$this->template->content->view('asset/fullPage', ['assetModel'=>$assetModel, "firstAsset"=>$targetObject]);
			$this->template->publish();
	
		}


	}

	function viewRestore($objectId=null) {
		if(!$objectId) {
			show_404();
		}

		$object = $this->doctrine->em->find("Entity\Asset", $objectId);
		$assetModel = new Asset_model;
		$assetModel->loadAssetFromRecord($object);

		$this->accessLevel = $this->user_model->getAccessLevel("asset", $assetModel);

		if($this->accessLevel == PERM_NOPERM) {
			$this->errorhandler_helper->callError("noPermission");
		}

		$embed = $this->loadAssetView($assetModel);
		$this->template->content->view('asset/fullPage', ['assetModel'=>$assetModel, "embed"=>$embed, 'firstAsset'=>null]);
		$this->template->publish();

	}


	function viewExcerpt($excerptId, $embedLink=false) {

		$excerpt = $this->doctrine->em->getRepository("Entity\DrawerItem")->find($excerptId);
		$assetModel = new Asset_model;
		if(!$assetModel->loadAssetById($excerpt->getAsset())) {
			$this->logging->logError("getEmbed", "could not load asset for fileHandler" . $fileHandler->getObjectId());
			return;
		}

		$fileHandler = $this->filehandler_router->getHandlerForObject($excerpt->getExcerptAsset());

		// we check that they have access to the drawer or the specific asset (for API Calls)
		// if they have access to the drawer for this asset, and then let them view it.
		// Luckily, the rest of the auth process will be ok with this, since we don't check again further down the generation.
		$this->accessLevel = $this->user_model->getAccessLevel("drawer", $excerpt->getDrawer());
		$this->assetAccessLevel = $this->user_model->getAccessLevel("asset", $assetModel);
		if($this->accessLevel < PERM_VIEWDERIVATIVES && $this->assetAccessLevel < PERM_VIEWDERIVATIVES) {

			// let's check if the excerpt file's parent is different. This can happen with nested views
			if($fileHandler->parentObjectId !== $excerpt->getAsset()) {
				$assetModel = new Asset_model;
				$assetModel->loadAssetById($fileHandler->parentObjectId);
				$this->assetAccessLevel = $this->user_model->getAccessLevel("asset", $assetModel);
				if($this->accessLevel < PERM_VIEWDERIVATIVES && $this->assetAccessLevel < PERM_VIEWDERIVATIVES) {
					$this->errorhandler_helper->callError("noPermission");
				}
				
			}
			else {
				$this->errorhandler_helper->callError("noPermission");
			}
			
		}

		$this->accessLevel = max($this->accessLevel, $this->assetAccessLevel);

		

		if(!$fileHandler) {
			instance_redirect("errorHandler/error/badExcerpt");
			return;
		}

		$fileHandler->loadByObjectId($excerpt->getExcerptAsset());

		

		if($embedLink) {
			$this->template->set_template("noTemplate");
			$embed = "<iframe class='videoEmbedFrame' src='" . $fileHandler->getEmbedURL() . "' width=100% height=100%></iframe>";
		}
		else {
			$embed = $this->loadAssetView($assetModel, $fileHandler, $embedLink);
		}

		$this->template->content->view("asset/excerpt", ["isEmbedded"=>$embedLink, "asset"=>$assetModel,"fileObjectId"=>$fileHandler->getObjectId(), "embed"=>$embed, "startTime"=>$excerpt->getExcerptStart(), "endTime"=>$excerpt->getExcerptEnd(),"excerptId"=>$excerpt->getId(), "label"=>$excerpt->getExcerptLabel()]);
		$this->template->publish();

	}

	// this is used for inline display of objects
	// We pass in the parentObject and the targetObject so we can resolve perms properly in cases of things like
	// objects in drawers, which the user wouldn't otherwise have access to.

	function viewAssetMetadataOnly($parentObject, $targetObject) {

		$assetModel = new Asset_model();

		if(!$assetModel->loadAssetById($parentObject)) {
			$this->errorhandler_helper->callError("unknownAsset");
		}

		$this->accessLevel = $this->user_model->getAccessLevel("asset", $assetModel);

		if($this->accessLevel == PERM_NOPERM) {
			$this->errorhandler_helper->callError("noPermission");
		}

		$foundAsset = false;
		$relatedAssets = $assetModel->getAllWithinAsset("Related_asset");
		foreach($relatedAssets as $asset) {

			foreach($asset->fieldContentsArray as $contents) {

				if($contents->targetAssetId == $targetObject) {
					if(!$contents->getRelatedAsset()) {
						$this->errorhandler_helper->callError("unknownAsset", $inline=true);
						$foundAsset = true;
						return;
					}
					else {
						$this->load->view("asset/sidebar", ["sidebarAssetModel"=>$contents->getRelatedAsset()]);
						$foundAsset = true;
						return;
					}

				}
			}
		}

		if(!$foundAsset) {
			// see if we can show them the asset anyways
			$assetModel = new Asset_model();

			if(!$assetModel->loadAssetById($targetObject)) {
				$this->errorhandler_helper->callError("unknownAsset");
			}

			$this->accessLevel = $this->user_model->getAccessLevel("asset", $assetModel);

			if($this->accessLevel == PERM_NOPERM) {
				$this->errorhandler_helper->callError("noPermission");
			}

			$this->load->view("asset/sidebar", ["sidebarAssetModel"=>$assetModel]);

		}




	}

	public function getEmbedWithChrome($fileObjectId, $parentObject=null) {
		
		list($assetModel, $fileHandler) = $this->getComputedAsset($fileObjectId, $parentObject);
		if($parentObject) {
			$fileHandler->parentObjectId = $parentObject;
		}
		$embed = $this->loadAssetView($assetModel, $fileHandler);
		echo $embed;
		return;
	}

	public function getEmbed($fileObjectId, $parentObject=null, $embedded = false) {

		list($assetModel, $fileHandler) = $this->getComputedAsset($fileObjectId, $parentObject, $embedded);
		if(!$fileHandler) {
			return;
		}
		try {
			$embedAssets = $fileHandler->allDerivativesForAccessLevel($this->accessLevel);
		}
		catch (exception $e) {

		}
		
		$includeOriginal = $this->getAllowOriginal($fileHandler);
		if(isset($embedAssets)) {
			$embed = $fileHandler->getEmbedView($embedAssets, $includeOriginal);
		}
		else {
			$embed = $this->load->view("fileHandlers/filenotfound", null, true);
		}
		
		$this->template->set_template("noTemplate");
		$this->template->loadJavascript(["embedTriggers"]);
		$this->template->content = $embed;
		$this->template->title = $assetModel->getAssetTitle(true);
		$this->template->publish();
	}

	private function getComputedAsset($fileObjectId, $parentObject, $embedded = false) {
		$fileHandler = $this->filehandler_router->getHandlerForObject($fileObjectId);
		if(!$fileHandler) {
			$embed = $this->load->view("fileHandlers/filenotfound", null, true);
			if($embedded) {
				$this->template->set_template("noTemplate");
				$this->template->content = $embed;
				$this->template->publish();
			}
			else {
				echo $embed;
			}
			return;
		}

		$fileHandler->loadByObjectId($fileObjectId);
		
		$assetModel = new Asset_model();
		if(!$assetModel->loadAssetById($fileHandler->parentObjectId)) {
			$this->logging->logError("getEmbed", "could not load asset for fileHandler" . $fileHandler->getObjectId());
			return;
		}

		// TODO: merging this in, but do we need it?
		$fileHandler->parentObject = $assetModel;

		// We need to see if the user has access to this file's parent object, so we can resolve permissions for drawers/collections.
		// This is a brute force sort of thing.
		// Note, this could fail is they somehow have less access to a parent than to a child.
		$this->accessLevel = PERM_NOPERM;
		if($parentObject && $parentObject != "null" && $parentObject != $fileHandler->parentObjectId) {
			$tempAsset = new Asset_model;
			$tempAsset->loadAssetById($parentObject);

			$parentMatch = FALSE;

			if($fileHandler->parentObjectId == $parentObject) {
				$parentMatch = true;
			}
			else {
				$relatedAssets = $tempAsset->getAllWithinAsset("Related_asset");
				foreach($relatedAssets as $asset) {
					foreach($asset->fieldContentsArray as $contents) {
						if($contents->targetAssetId == $fileHandler->parentObjectId) {
							$parentMatch = true;
						}
					}
				}
			}

			if($parentMatch) {
				$assetPerms = $this->user_model->getAccessLevel("asset", $assetModel);
				$parentPerms = $this->user_model->getAccessLevel("asset", $tempAsset);
				$this->accessLevel = max($assetPerms, $parentPerms);
			}
			else {
				// we've got a mismatch, but see if they've got access to the file without looking at the parent.
				$this->accessLevel = $this->user_model->getAccessLevel("asset", $assetModel);
			}
		}
		else {
			$this->accessLevel = $this->user_model->getAccessLevel("asset", $assetModel);
		}

		if($this->accessLevel == PERM_NOPERM) {
			$this->errorhandler_helper->callError("noPermission");
		}
		return [$assetModel, $fileHandler];
	}

	public function getAllowOriginal($fileHandler) {
		$requiredAccessLevel = PERM_NOPERM;
		if($fileHandler->noDerivatives()) {  // this method implies this *type* doesn't have derivatives, not this specific asset
			$requiredAccessLevel = $fileHandler->getPermission();
		}
		else {
			$requiredAccessLevel = PERM_ORIGINALS;
		}
		// This is hacky and should be refactored - if the user can view the originals, we need to let the view know.
		if($this->accessLevel>= $requiredAccessLevel) {
			$includeOriginal=true;
		}
		else {
			$includeOriginal=false;
		}
		return $includeOriginal;
	}

	public function loadAssetView($assetModel, $fileHandler=null, $embedded=false) {

	

		try {
			if($fileHandler == null) {
				$fileHandler = $assetModel->getPrimaryFilehandler();
			}
		}
		catch (Exception $e) {
			$embed = $this->load->view("fileHandlers/filenotfound", null, true);
		}

		$includeOriginal = $this->getAllowOriginal($fileHandler);

		

		if($fileHandler) {
			try {
				$embedAssets = $fileHandler->allDerivativesForAccessLevel($this->accessLevel);
				$embed = $fileHandler->getEmbedViewWithFiles($embedAssets, $includeOriginal, $embedded);
			}
			catch (Exception $e) {
				$embed = $fileHandler->getEmbedViewWithFiles(array(), $includeOriginal, $embedded);
			}

		}

		return $embed;
	}

	public function getAssetPreview($objectId) {
		$assetModel = new Asset_model();
		$assetModel->loadAssetById($objectId);
		$result = $assetModel->getSearchResultEntry();
		echo json_encode($result);
	}

}

/* End of file  */
/* Location: ./application/controllers/ */
