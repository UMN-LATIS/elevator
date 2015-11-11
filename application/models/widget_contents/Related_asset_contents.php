<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Related_asset_contents extends Widget_contents_base {

	public $relatedAsset = NULL;
	public $targetAssetId = null;
	public $nestData = false;
	public $label = null;
	public $displayInline = false;

	public function __construct()
		{
			parent::__construct();
			//Do your magic here
		}


	public function getAsArray($nestedObjectDepth=0) {
		if(($nestedObjectDepth>0 || $this->nestData == true) && !($nestedObjectDepth<0)) {
			// decrement to prevent recusion.
			//return ["targetAssetId"=>new MongoId($this->targetAssetId), "label"=>$this->label, "isPrimary"=>$this->isPrimary];
			return ["targetAssetId"=>new MongoId($this->targetAssetId), "label"=>$this->label, "targetAsset"=>$this->getRelatedAsset()->getAsArray($nestedObjectDepth-1), "isPrimary"=>$this->isPrimary];
		}
		else {
			return ["targetAssetId"=>new MongoId($this->targetAssetId), "label"=>$this->label, "isPrimary"=>$this->isPrimary];
		}
	}

	public function getAsText($nestedObjectDepth=0) {
		if(!$this->getRelatedAsset()->templateId) {
			return "";
		}

		if(($nestedObjectDepth>0 || $this->nestData == true) && !($nestedObjectDepth<0)) {
			// decrement to prevent recusion.
			foreach($this->getRelatedAsset()->assetObjects as $object) {
				$assetText[] = implode(" ", $object->getAsText($nestedObjectDepth-1));
			}

			$assetText["objectId"] = $this->getRelatedAsset()->getObjectId();

			return implode(" ", $assetText);
		}
		else {
			$assetText = $this->getRelatedAsset()->getAssetTitle(true);
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

	public function getRelatedAsset() {
		if(!$this->relatedAsset) {
			$this->relatedAsset = new Asset_model;
			$this->relatedAsset->loadAssetById($this->targetAssetId);
			return $this->relatedAsset;
		}
		else {
			return $this->relatedAsset ;
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

}

/* End of file widget_contents.php */
/* Location: ./application/models/widget_contents.php */