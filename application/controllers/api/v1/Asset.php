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
			if($fileHandler = $this->filehandler_router->getHandlerForObject($assetId)) {
				$fileHandler->loadByObjectId($assetId);	
			}
			else {
				echo json_encode([]);
				return;	
			}
			

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
			"original"=>$original,
			"metadata"=>$fileHandler->sourceFile->metadata
			]);

	}

	public function restoreFile($assetId)
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
			if($fileHandler = $this->filehandler_router->getHandlerForObject($assetId)) {
				$fileHandler->loadByObjectId($assetId);	
			}
			else {
				echo json_encode([]);
				return;	
			}
			

		}

		$accessLevel = PERM_NOPERM;
		if($fileHandler->parentObjectId) {
			$this->asset_model->loadAssetById($fileHandler->parentObjectId);
			$accessLevel = $this->user_model->getAccessLevel("asset", $this->asset_model);
		}

	
		$original = false;
		if($accessLevel >= PERM_ORIGINALS) {
			$original = $fileHandler->sourceFile->restoreFromArchive();
		}

		if($original) {
			
		}

		echo json_encode([
			"restoring"=>$original
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

	public function assetPreview($assetId) {

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

		$assetText = $assetModel->getSearchResultEntry();

		echo json_encode($assetText);

	}

	public function getEmbedLink($objectId, $instance=null, $returnJSON=false) {
		if($instance && $instance !== "false") {
			$this->instance = $this->doctrine->em->getRepository("Entity\Instance")->find($instance);	
		}
		
		$fileHandler = $this->filehandler_router->getHandlerForObject($objectId);
		if(!$fileHandler) {
			return "";
		}

		$fileHandler->loadByObjectId($objectId);
		if(!$fileHandler->parentObjectId) {
			return "";
		}
		
		$timestamp = time();
		$signedString = sha1($timestamp . $fileHandler->parentObjectId . $this->apiKey->getApiSecret());

		$targetQuery = ["apiHandoff"=>$signedString, "authKey"=>$this->apiKey->getApiKey(), "timestamp"=>$timestamp, "targetObject"=>$fileHandler->parentObjectId];

		$embedURL = site_url("/" . $this->instance->getDomain() . "/asset/getEmbed/" . $objectId.  "/null/true?" . http_build_query($targetQuery));
		if($returnJSON) {
			render_json($embedURL);
		}
		else {
			echo $embedURL;
		}

	}

	public function getExcerptLink($excerptId, $instance) {

		$this->instance = $this->doctrine->em->getRepository("Entity\Instance")->find($instance);

		$excerpt = $this->doctrine->em->getRepository("Entity\DrawerItem")->find($excerptId);
		$this->load->model("Asset_model");
		$assetModel = new Asset_model;
		if(!$assetModel->loadAssetById($excerpt->getAsset())) {
			// todo
		}

		$timestamp = time();
		$signedString = sha1($timestamp . $assetModel->getObjectId() . $this->apiKey->getApiSecret());

		$targetQuery = ["apiHandoff"=>$signedString, "authKey"=>$this->apiKey->getApiKey(), "timestamp"=>$timestamp, "targetObject"=>$assetModel->getObjectId()];
		echo site_url("/" . $this->instance->getDomain() . "/asset/viewExcerpt/" . $excerptId.  "/true?" . http_build_query($targetQuery));

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
				if(!$contents->getFileHandler()) {
					continue;
				}
				$targetURL = "";
				try {
					$tiny = $contents->getFileHandler()->getPreviewTiny()->getURLForFile(true);
					$tiny2x = $contents->getFileHandler()->getPreviewTiny(true)->getURLForFile(true);
					$thumbnail = $contents->getFileHandler()->getPreviewThumbnail(true)->getURLForFile(true);
					$thumbnail2x = $contents->getFileHandler()->getPreviewThumbnail(true)->getURLForFile(true);

					if($entryTitle) {
						$outputTitle = $entryTitle;
					}
					else {
						$outputTitle = $title;
					}

					if(isset($contents->getFileHandler()->sourceFile) && isset($contents->getFileHandler()->sourceFile->originalFilename)) {
						$outputTitle = $outputTitle . ": " . $contents->getFileHandler()->sourceFile->originalFilename;
					}

					if(!$mimeType || stristr(get_class($contents->getFileHandler()), $mimeType)) {

						$outputArray[] = array("title"=>$outputTitle, "primaryHandlerId"=>$contents->getFileHandler()->getObjectId(), "primaryHandlerTiny"=>$tiny, "primaryHandlerTiny2x"=>$tiny2x, "primaryHandlerThumbnail"=>$thumbnail, "primaryHandlerThumbnail2x"=>$thumbnail2x);
					}
				}
				catch (Exception $e) {
					if($entryTitle) {
						$outputTitle = $entryTitle;
					}
					else {
						$outputTitle = $title;
					}

					if(isset($contents->getFileHandler()->sourceFile) && isset($contents->getFileHandler()->sourceFile->originalFilename)) {
						$outputTitle = $outputTitle . ": " . $contents->getFileHandler()->sourceFile->originalFilename;
					}

					if (!$mimeType || stristr(get_class($contents->getFileHandler()), $mimeType)) {
						$targetURL = site_url(
							getIconPath() . $contents->fileHandler->getIcon(),
							307
						);

						$outputArray[] = [
							"title" => $outputTitle,
							"primaryHandlerId" => $contents->getFileHandler()->getObjectId(), "primaryHandlerTiny" => $targetURL,
							"primaryHandlerTiny2x" => $targetURL,
							"primaryHandlerThumbnail" => $targetURL, "primaryHandlerThumbnail2x" => $targetURL
						];
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