<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Related_asset_contents extends Widget_contents_base {

	public $relatedAsset = NULL;
	public $targetAssetId = null;
	public $nestData = false;
	public $label = null;
	public $displayInline = false;
	public $relatedObjectId = null;
	public $relatedObjectTitle = null;
	public $cachedPrimaryHandler = null;

	public function __construct()
		{
			parent::__construct();
			//Do your magic here
		}


	public function getAsArray($nestedObjectDepth=0) {
		return ["targetAssetId"=>$this->targetAssetId, "label"=>$this->label, "isPrimary"=>$this->isPrimary];
	}

	public function getRelatedObjectId() {
		if($this->targetAssetId) {
			return $this->targetAssetId;
		}
		else {
			return FALSE;
		}

	}

	public function getRelatedObjectTitle($collapse = false) {

		if(!$this->relatedObjectTitle && $assetCache = $this->parentObject->assetObject->getAssetCache()) {
			if($this->parentObject->useStaleCaches || !$assetCache->getNeedsRebuild()) {
				$relatedAssetCache = $assetCache->getRelatedAssetCache();
				if(isset($relatedAssetCache[$this->getRelatedObjectId()])) {
					$this->relatedObjectTitle = $relatedAssetCache[$this->getRelatedObjectId()]["relatedAssetTitle"];
				}
			}
		}

		if($this->relatedObjectTitle) {
			if($collapse) {
				return implode(" ", $this->relatedObjectTitle);
			}
			else {
				return $this->relatedObjectTitle;
			}
		}
		else {
			if($relatedAsset = $this->getRelatedAsset()) {
				return $relatedAsset->getAssetTitle($collapse);
			}
			if($collapse) {
				return "";
			}
			else {
				return array();
			}
		}
	}

	public function getPrimaryFileHandler() {
		if(!$this->cachedPrimaryHandler && $assetCache = $this->parentObject->assetObject->getAssetCache()) {
			if($this->parentObject->useStaleCaches || !$assetCache->getNeedsRebuild()) {
				$relatedAssetCache = $assetCache->getRelatedAssetCache();
				if(isset($relatedAssetCache[$this->getRelatedObjectId()])) {
					$this->cachedPrimaryHandler = $relatedAssetCache[$this->getRelatedObjectId()]["primaryHandler"];
				}
			}
		}


		if($this->cachedPrimaryHandler) {
			return $this->filehandler_router->getHandledObject($this->cachedPrimaryHandler);
		}
		else {
			try {
				$relatedAsset = $this->getRelatedAsset();
				if($relatedAsset) {
					$fileHandler = $relatedAsset->getPrimaryFilehandler();
				}
				else {
					throw new Exception('Primary File Handler Not Found');
					return;
				}
			}
			catch (Exception $e) {
				throw new Exception('Primary File Handler Not Found');
				return;
			}
			return $fileHandler;
		}

	}

	public function getAsText($nestedObjectDepth=0) {

		if($nestedObjectDepth < 0) {
			return $this->getRelatedObjectTitle(true);
		}

		// TODO: check this.  nestDAta is used for two things - drawing things with nesting on the display page,
		// as well as making sure the search engine "deep indexes" content.
		if(($nestedObjectDepth>0 || $this->nestData == true) && !($nestedObjectDepth<0)) {

			// decrement to prevent recusion.
			foreach($this->getRelatedAsset()->assetObjects as $object) {
				$assetText[] = implode(" ", $object->getAsText($nestedObjectDepth-1));
			}
			if($this->getRelatedAsset()) {
				$assetText["objectId"] = $this->getRelatedAsset()->getObjectId();
			}


			return implode(" ", $assetText);
		}
		else {
			$assetText = $this->getRelatedAsset()->getAssetTitle($nestedObjectDepth-1);
		}

		return $assetText;
	}

	public function loadContentFromArray($value) {
		foreach($value as $key=>$entry) {
			$this->$key = $entry;
			if($key == "isPrimary" && ($entry == true || $entry == "on")) {
				$this->isPrimary = true;
			}
		}
	}

	public function hasContents() {
		if($this->targetAssetId != null ) {
			return true;
		}
		else {
			return false;
		}
	}


	public function getContent() {
		return $this->targetAssetId;
	}

	/**
	 * IMPORTANT
	 *
	 *
	 * Be very careful when hitting this function
	 * it can be very expensive if you're calling getRelatedAsset() on everything on a complicated record.
	 * We do our best to cache things so we're not dealing with this.
	 *
	 */
	public function getRelatedAsset() {
		if(!$this->relatedAsset) {
			if(!($this->relatedAsset = $this->asset_model->getCachedAsset($this->targetAssetId))) {
				$this->relatedAsset = new Asset_model;
				if(!$this->relatedAsset->loadAssetById($this->targetAssetId)) {
					$this->relatedAsset = null;
					return FALSE;
				}
				$this->relatedAsset->useStaleCaches = $this->parentObject->useStaleCaches;

			}
			return $this->relatedAsset;
		}
		else {
			return $this->relatedAsset ;
		}
	}


}

/* End of file widget_contents.php */
/* Location: ./application/models/widget_contents.php */