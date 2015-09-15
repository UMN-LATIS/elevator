<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class asset extends Instance_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->template->javascript->add("assets/js/handlebars-v1.1.2.min.js");
		if(defined('ENVIRONMENT') && ENVIRONMENT == "development") {
			$this->template->javascript->add("assets/js/assetView.js");
			$this->template->javascript->add("assets/js/drawers.js");
		}
		else {
			$this->template->javascript->add("assets/js/assetMaster.min.js");
		}


		$this->template->javascript->add("assets/js/jquery.fullscreen-0.4.1.js");
		$this->template->javascript->add("//maps.google.com/maps/api/js?libraries=geometry&sensor=false");
		$this->template->javascript->add("assets/js/jquery.gomap-1.3.2.min.js");
		$this->template->javascript->add("assets/js/markerclusterer.min.js");
		$this->template->javascript->add("assets/js/mapWidget.js");
		$this->template->javascript->add("assets/js/panzoom.min.js");
		$this->template->javascript->add("assets/js/jquery.expander.min.js");

		$this->template->addToDrawer->view("drawers/add_to_drawer");
		$this->template->content->view("drawers/drawerModal");

		$this->load->helper('number');
		$this->load->model("asset_model");
	}

	function viewAsset($objectId=null) {
		$assetModel = new Asset_model;
		if(!$objectId) {
			show_404();
		}

		$assetModel->loadAssetById($objectId);
		$this->accessLevel = $this->user_model->getAccessLevel("asset", $assetModel);

		if($this->accessLevel == PERM_NOPERM) {
			$this->errorhandler_helper->callError("noPermission");
		}


		// Try to find the primary file handler, which might be another asset.  Return the hosting asset, not the filehandler directly

		$targetObject = null;
		try {
			$fileHandler = $assetModel->getPrimaryFilehandler();

			if($fileHandler->parentObjectId != $objectId) {
				$targetObject = $fileHandler->parentObjectId;
			}
			else {
				$targetObject = $fileHandler->getObjectId();
			}
		}
		catch (Exception $e) {

		}

		// for subclipping movies
		$this->template->javascript->add("assets/js/excerpt.js");

		$this->template->content->view('asset/fullPage', ['assetModel'=>$assetModel, "firstAsset"=>$targetObject]);
		$this->template->publish();


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
		$this->template->javascript->add("assets/js/excerpt.js");
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


		// rather than checking that they have access to the asset (which we'd normally do) we just
		// check that they have access to the drawer for this asset, and then let them view it.
		// Luckily, the rest of the auth process will be ok with this, since we don't check again further down the generation.
		$this->accessLevel = $this->user_model->getAccessLevel("drawer", $excerpt->getDrawer());
		if($this->accessLevel < PERM_VIEWDERIVATIVES) {
			$this->errorhandler_helper->callError("noPermission");
		}

		$fileHandler = $this->filehandler_router->getHandlerForObject($excerpt->getExcerptAsset());

		if(!$fileHandler) {
			instance_redirect("errorHandler/error/badExcerpt");
			return;
		}

		$fileHandler->loadByObjectId($excerpt->getExcerptAsset());

		$embed = $this->loadAssetView($assetModel, $fileHandler, $embedLink);

		if($embedLink) {
			$this->template->set_template("noTemplate");
		}

		$this->template->javascript->add("assets/js/excerpt.js");
		$this->template->content->view("asset/excerpt", ["isEmbedded"=>$embedLink, "asset"=>$assetModel, "embed"=>$embed, "startTime"=>$excerpt->getExcerptStart(), "endTime"=>$excerpt->getExcerptEnd(),"excerptId"=>$excerpt->getId(), "label"=>$excerpt->getExcerptLabel()]);
		$this->template->publish();

	}

	// this is used for inline display of objects
	// We pass in the parentObject and the targetObject so we can resolve perms properly in cases of things like
	// objects in drawers, which the user wouldn't otherwise have access to.

	function viewAssetMetadataOnly($parentObject, $targetObject) {

		$assetModel = new Asset_model();
		$assetModel->loadAssetById($parentObject);

		$this->accessLevel = $this->user_model->getAccessLevel("asset", $assetModel);

		if($this->accessLevel == PERM_NOPERM) {
			$this->errorhandler_helper->callError("noPermission");
		}

		$relatedAssets = $assetModel->getAllWithinAsset("Related_asset");
		foreach($relatedAssets as $asset) {

			foreach($asset->fieldContentsArray as $contents) {

				if($contents->targetAssetId == $targetObject) {
					$this->load->view("asset/sidebar", ["sidebarAssetModel"=>$contents->getRelatedAsset()]);
				}
			}
		}




	}

	public function getEmbed($fileObjectId, $parentObject=null, $embedded = false) {

		$fileHandler = $this->filehandler_router->getHandlerForObject($fileObjectId);
		if(!$fileHandler) {
			$embed = $this->load->view("fileHandlers/filenotfound", true);
			if($embedded) {
				$this->template->set_template("noTemplate");
				$this->template->javascript->add("assets/js/excerpt.js");
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


		// We need to see if the user has access to this file's parent object, so we can resolve permissions for drawers/collections.
		// This is a brute force sort of thing.
		// Note, this could fail is they somehow have less access to a parent than to a child.
		$this->accessLevel = PERM_NOPERM;
		if($parentObject && $parentObject != "null") {
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
				$this->accessLevel = $this->user_model->getAccessLevel("asset", $tempAsset);
			}
		}
		else {
			$this->accessLevel = $this->user_model->getAccessLevel("asset", $assetModel);
		}

		if($this->accessLevel == PERM_NOPERM) {
			$this->errorhandler_helper->callError("noPermission");
		}

		$embed = $this->loadAssetView($assetModel, $fileHandler, $embedded);
		if($embedded) {
			$this->template->set_template("noTemplate");
			$this->template->javascript->add("assets/js/excerpt.js");
			$this->template->javascript->add("assets/js/embedTriggers.js");
			$this->template->content = $embed;
			$this->template->publish();
		}
		else {
			echo $embed;
		}
	}


	public function loadAssetView($assetModel, $fileHandler=null, $embedded=false) {

		$requiredAccessLevel = PERM_NOPERM;

		try {
			if($fileHandler == null) {
				$fileHandler = $assetModel->getPrimaryFilehandler();
			}
			if($fileHandler->noDerivatives()) {  // this method implies this *type* doesn't have derivatives, not this specific asset
				$requiredAccessLevel = $fileHandler->getPermission();
			}
			else {
				$requiredAccessLevel = PERM_ORIGINALS;
			}
		}
		catch (Exception $e) {
			$embed = $this->load->view("fileHandlers/filenotfound", null, true);
		}



		// This is hacky and should be refactored - if the user can view the originals, we need to let the view know.
		if($this->accessLevel>= $requiredAccessLevel) {
			$includeOriginal=true;
		}
		else {
			$includeOriginal=false;
		}

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