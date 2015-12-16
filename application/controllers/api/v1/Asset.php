<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class asset extends API_Controller {

	public function __construct()
	{
		parent::__construct();

	}

	public function fileLookup($assetId)
	{
		$this->load->model("asset_model");
		$isExcerpt = false;
		if(stristr($assetId, "excerpt")) {
			$assetId = str_replace("excerpt", "", $assetId);
			$this->load->model("asset_model");
			$excerpt = $this->doctrine->em->getRepository("Entity\DrawerItem")->find($assetId);
			$assetModel = new Asset_model;
			if(!$assetModel->loadAssetById($excerpt->getAsset())) {
				$this->logging->logError("getEmbed", "could not load asset for fileHandler" . $fileHandler->getObjectId());
				return;
			}
			$fileHandler = $this->filehandler_router->getHandlerForObject($excerpt->getExcerptAsset());
			$fileHandler->loadByObjectId($excerpt->getExcerptAsset());
			$isExcerpt = true;

		}
		else {
			$fileHandler = $this->filehandler_router->getHandlerForObject($assetId);
			$fileHandler->loadByObjectId($assetId);

		}

		$accessLevel = PERM_NOPERM;
		if($fileHandler->parentObjectId) {
			$this->asset_model->loadAssetById($fileHandler->parentObjectId);
			$accessLevel = $this->user_model->getAccessLevel("asset", $this->asset_model);
		}

		$bestURL = null;
		try {
			$bestDerivative = $fileHandler->highestQualityDerivativeForAccessLevel($accessLevel, false);
			if(!$bestDerivative) {
				$this->logging->logError("API", "Missing Best Derivative");
				echo json_encode([]);
				return;
			}
			$bestURL = $bestDerivative->getProtectedURLForFile();
		}
		catch (Exception $e) {

		}



		$original = null;
		if($accessLevel >= PERM_ORIGINALS) {
			$original = $fileHandler->sourceFile->getProtectedURLForFile();
		}


		try {
			$sourceLink = $fileHandler->getPreviewThumbnail(true)->getURLForFile(true) . "?placeholderStringForElevatorDoNotRemove=" . $assetId . "&instance=".$this->instance->getId();

			if($isExcerpt) {
				$sourceLink .= "&excerptId=" . $assetId;
			}
		}
		catch (Exception $e) {
			$sourceLink = "";
		}

		echo json_encode([
			"title"=>"Asset",
			"source"=>$sourceLink,
			"best"=>$bestURL,
			"original"=>$original
			]);

	}

	public function assetLookup($assetId) {

		$isExcerpt = false;
		$assetModel = null;
		$this->load->model("asset_model");
		if(stristr($assetId, "excerpt")) {
			$assetId = str_replace("excerpt", "", $assetId);
			$this->load->model("asset_model");
			$excerpt = $this->doctrine->em->getRepository("Entity\DrawerItem")->find($assetId);
			$assetModel = new Asset_model;
			if(!$assetModel->loadAssetById($excerpt->getAsset())) {
				$this->logging->logError("assetLookup", "could not load asset " . $assetId);
				return;
			}
		}
		else {
			$assetModel = new Asset_model;
			$assetModel->loadAssetById($assetId);
		}

		$assetText = $assetModel->getAsArray(1, true);

		echo json_encode($assetText);



	}

	public function getEmbedLink($objectId, $instance) {
		$this->instance = $this->doctrine->em->getRepository("Entity\Instance")->find($instance);

		echo site_url("/" . $this->instance->getDomain() . "/asset/getEmbed/" . $objectId.  "/null/true");

	}

	public function getExcerptLink($excerptId, $instance) {
		$this->instance = $this->doctrine->em->getRepository("Entity\Instance")->find($instance);

		echo site_url("/" . $this->instance->getDomain() . "/asset/viewExcerpt/" . $excerptId.  "/true");

	}


	public function getAssetChildren($objectId, $mimeType=null) {

		$this->load->model("asset_model");
		$this->asset_model->loadAssetById($objectId);

		$related = $this->asset_model->getAllWithinAsset("Related_asset");
		$upload = $this->asset_model->getAllWithinAsset("Upload");

		$title = $this->asset_model->getAssetTitle(true);

		$outputArray = array();
		foreach($related as $entry) {
			foreach($entry->fieldContentsArray as $relatedAssetContent) {
				$result = $relatedAssetContent->getRelatedAsset()->getAllWithinAsset("Upload");
				$resultTitle = $relatedAssetContent->getRelatedAsset()->getAssetTitle(true);
				if(count($result)> 0) {
					foreach($result as $resultEntry) {
						$upload[$resultTitle] = $resultEntry;
					}
				}
			}

		}


		foreach($upload as $entryTitle=>$entry) {
			foreach($entry->fieldContentsArray as $contents) {
				if(!isset($contents->fileHandler)) {
					continue;
				}
				$targetURL = "";
				try {
					$targetURL = $contents->fileHandler->getPreviewTiny()->getURLForFile(true);
					if($entryTitle) {
						$outputTitle = $entryTitle;
					}
					else {
						$outputTitle = $title;
					}

					if(!$mimeType || stristr(get_class($contents->fileHandler), $mimeType)) {

						$outputArray[] = array("title"=>$outputTitle, "primaryHandlerId"=>$contents->fileHandler->getObjectId(), "primaryHandlerTiny"=>$targetURL);
					}
				}
				catch (Exception $e) {
					if($entryTitle) {
						$outputTitle = $entryTitle;
					}
					else {
						$outputTitle = $title;
					}

					if(!$mimeType || stristr(get_class($contents->fileHandler), $mimeType)) {

						$outputArray[] = array("title"=>$outputTitle, "primaryHandlerId"=>$contents->fileHandler->getObjectId(), "primaryHandlerTiny"=>site_url("/assets/icons/512px/".$contents->fileHandler->getIcon(), 307));
					}
					// it's ok not to do anytihng, might not have an entry
				}

			}


		}
		$returnArray["matches"] = $outputArray;
		echo json_encode($returnArray);


	}

}

/* End of file  */
/* Location: ./application/controllers/ */