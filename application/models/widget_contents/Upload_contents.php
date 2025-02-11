<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// didn't want to do this but we have legacy objects that create all kinds of properties
#[\AllowDynamicProperties]
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
	public $loc = null;
	public $regenerate = false;

	public function __construct()
		{
			parent::__construct();
			//Do your magic here
		}

	public function getAsArray($serializeNestedObjects=false) {
		if($this->getFileHandler()) {
			if($this->extractLocation && $this->locationData) {
				$location = array("type"=>"Point","coordinates"=>[(float)$this->getLocationData()[0],(float)$this->getLocationData()[1]]);
			}
			else {
				$location = null;
			}
			if($this->extractDate && $this->dateData) {
				$dateData = ["label"=>"File Creation", "start"=>["text"=>$this->getDateData(), "numeric"=>(string)strtotime($this->getDateData())], "end"=>["text"=>"", "numeric"=>""]];
			}
			else {
				$dateData = array();
			}

			foreach($this->sidecars as $key=>$value) {
				if(is_string($value) && $this->isJson($value)) {
					$this->sidecars[$key] = json_decode($value); // we store decoded objects and let them get jsonified
				}
			}


			return array_merge($dateData, ["fileId"=>$this->fileHandler->getObjectId(),"fileDescription"=>$this->fileDescription, "fileType"=>$this->fileHandler->sourceFile->getType(), "searchData"=>$this->getSearchData(), "loc"=>$location, "sidecars"=>$this->sidecars, "isPrimary"=>$this->isPrimary]);
		}
		else {
			return array();
		}

	}

	public function getAsText($serializeNestedObjects=false) {
		if(!$this->getFileHandler()) {
			return "";
		}
		return implode(" " , [$this->getFileHandler()->getObjectId(), $this->fileDescription, substr($this->getSearchData() ?? "" , 0, 100)]);
	}

	public function getFileHandler() {
		if($this->fileHandler) {
			return $this->fileHandler;
		}
		else {
			$this->fileHandler = $this->filehandler_router->getHandledObject($this->fileId);
			if($this->fileHandler) {
				// because we generate fileHandlers seperately from records, we could end up with fileHandlers taht don't point
				// back to the parent record.  If we happen upon one of these, go ahead and update it.  There's never a reason we want a
				// orphan filehandler
				if($this->fileHandler->parentObjectId == NULL || ($this->parentObjectId !== null && $this->fileHandler->parentObjectId != $this->parentObjectId)) {
					// $this->logging->logError("updating missing filehandler", $this->fileId);
					$this->fileHandler->parentObjectId = $this->parentObjectId;
					$this->fileHandler->save();
				}
				return $this->fileHandler;
			}
		}

		return FALSE;

	}

	public function getSearchData() {
		if($this->searchData !== NULL && strlen($this->searchData) > 0) {
			return $this->searchData;
		}
		$fileHandler = $this->getFileHandler();
		if(isset($fileHandler->globalMetadata["text"])) {
			$this->searchData = $fileHandler->globalMetadata["text"];
			return $this->searchData;
		}
		return NULL;
	}

	public function getLocationData() {

		if(!$this->extractLocation) {
			return null;
		}

		if($this->locationData !== NULL) {
			return $this->locationData;
		}
		$fileHandler = $this->getFileHandler();

		if(isset($fileHandler->sourceFile->metadata["coordinates"])) {
			$this->locationData = $fileHandler->sourceFile->metadata["coordinates"];
			return $this->locationData;
		}
		return NULL;
	}

	public function getDateData() {

		if(!$this->extractDate) {
			return null;
		}

		if($this->dateData !== NULL) {
			return $this->dateData;
		}
		$fileHandler = $this->getFileHandler();
		if(isset($fileHandler->sourceFile->metadata["creationDate"])) {
			$this->dateData = $fileHandler->sourceFile->metadata["creationDate"];
			return $this->dateData;
		}
		return NULL;
	}

	public function loadContentFromArray($value) {
		if(isset($value["fileId"]) && isset($value["fileType"]) && strlen($value["fileType"])>0) {
			// we're loading an object from the DB
			parent::loadContentFromArray($value);
			$this->fileId = $value['fileId'];

			// TOOD: DO WE NEED TO UPDATE PARENT???
			if(isset($value["regenerate"]) && $value["regenerate"] == "On") {
				$fileHandler = $this->getFileHandler();
				$fileHandler->regenerate = true;
				$fileHandler->save();
			}
			return TRUE;
		}
		else {
			parent::loadContentFromArray($value);
			if(isset($value["fileId"]) && strlen($value["fileId"])>0) {
				$this->fileHandler = $this->filehandler_router->getHandlerForObject($value["fileId"]);
				if(!$this->fileHandler) {
					return false;
				}
				$this->fileHandler->loadByObjectId($value["fileId"]);
				if($this->parentObjectId != NULL && $this->fileHandler->parentObjectId == NULL) {
					$this->fileHandler->parentObjectId = $this->parentObjectId;
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
		if($this->fileId) {
			return true;
		}
		else {
			return false;
		}
	}


	public function getContent() {
		return $this->fileId;
	}

	function isJson($string) {
 		json_decode($string);
 		return (json_last_error() == JSON_ERROR_NONE);
	}

}

/* End of upload widget_contents.php */
/* Location: ./application/models/widget_contents.php */