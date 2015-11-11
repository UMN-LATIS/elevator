<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Upload_contents extends Widget_contents_base {

	public $fileId;
	public $searchData = null;
	public $locationData = null;
	public $dateData = null;
	public $fileHandler = null;

	public $fileType = null;
	public $fileDescription = null;
	public $collectionId = null;

	public $extractLocation;
	public $extractDate;
	public $sidecars = array();

	public function __construct()
		{
			parent::__construct();
			//Do your magic here
		}

	public function getAsArray($serializeNestedObjects=false) {
		if(isset($this->fileHandler)) {
			if($this->extractLocation && $this->locationData) {
				$location = array("type"=>"Point","coordinates"=>[(float)$this->locationData[0],(float)$this->locationData[1]]);
			}
			else {
				$location = null;
			}
			if($this->extractDate && $this->dateData) {
				$dateData = ["label"=>"File Creation", "start"=>["text"=>$this->dateData, "numeric"=>(string)strtotime($this->dateData)], "end"=>["text"=>"", "numeric"=>""]];
			}
			else {
				$dateData = array();
			}

			return array_merge($dateData, ["fileId"=>new MongoId($this->fileHandler->getObjectId()), "fileDescription"=>$this->fileDescription, "fileType"=>$this->fileHandler->sourceFile->getType(), "searchData"=>$this->searchData, "loc"=>$location, "sidecars"=>$this->sidecars, "isPrimary"=>$this->isPrimary]);
		}
		else {
			return array();
		}

	}

	public function getAsText($serializeNestedObjects=false) {
		return implode(" " , [$this->fileHandler->getObjectId(), $this->fileDescription, substr($this->searchData, 0, 100)]);
	}

	public function loadContentFromArray($value) {
		if(isset($value["fileId"]) && isset($value["fileType"]) && strlen($value["fileType"])>0) {
			// we're loading an object we've already seen
			parent::loadContentFromArray($value);
			$this->fileHandler = $this->filehandler_router->getHandlerForObject($value["fileId"]);
			if($this->fileHandler) {
				$this->fileHandler->loadByObjectId($value["fileId"]);
				if(isset($this->fileHandler->globalMetadata["text"])) {
					$this->searchData = $this->fileHandler->globalMetadata["text"];
				}

				if(isset($this->fileHandler->sourceFile->metadata["coordinates"])) {
					$this->locationData = $this->fileHandler->sourceFile->metadata["coordinates"];
				}
				if(isset($this->fileHandler->sourceFile->metadata["creationDate"])) {
					$this->dateData = $this->fileHandler->sourceFile->metadata["creationDate"];
				}

				if($this->parentObjectId != NULL && $this->fileHandler->parentObjectId == NULL) {
					$this->fileHandler->parentObjectId = $this->parentObjectId;
					$this->fileHandler->save();
				}

				if(isset($value["regenerate"]) && $value["regenerate"] == "On") {
					$this->fileHandler->regenerate = true;
					$this->fileHandler->save();
				}
				return TRUE;
			}
			else {
				return FALSE;
			}
		}
		else {
			parent::loadContentFromArray($value);
			if(isset($value["fileId"]) && strlen($value["fileId"])>0) {
				$this->fileHandler = $this->filehandler_router->getHandlerForObject($value["fileId"]);
				$this->fileHandler->loadByObjectId($value["fileId"]);
				if($this->parentObjectId != NULL && $this->fileHandler->parentObjectId == NULL) {
					$this->fileHandler->parentObjectId = $this->parentObjectId;
					$this->fileHandler->save();
				}
				$this->fileHandler->save();
				if(isset($this->fileHandler->globalMetadata["text"])) {
					$this->searchData = $this->fileHandler->globalMetadata["text"];
				}
				if(isset($value["regenerate"]) && $value["regenerate"] == "On") {
					$this->fileHandler->regenerate = true;
					$this->fileHandler->save();
				}
			}
			else {
				return FALSE;
			}

		}
	}

	public function hasContents() {
		if($this->fileHandler != NULL) {
			return true;
		}
		else {
			return false;
		}
	}


	public function getContent() {
		return $this->fileId;
	}

}

/* End of upload widget_contents.php */
/* Location: ./application/models/widget_contents.php */