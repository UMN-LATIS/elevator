<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Location_contents extends Widget_contents_base {

	public $longitude = NULL;
	public $latitude = NULL;
	public $locationLabel = NULL;
	public $address = NULL;

	public function __construct()
		{
			parent::__construct();
			//Do your magic here
		}

	/**
	 * Out of the gate, we're only supporting Geo points
	 * @return [type] [description]
	 */
	public function getAsArray($serializeNestedObjects=false) {

		return ["locationLabel"=>$this->locationLabel, "address"=>$this->address, "loc"=>array("type"=>"Point","coordinates"=>[(float)$this->longitude,(float)$this->latitude]), "isPrimary"=>$this->isPrimary];
	}

	public function getAsText($serializeNestedObjects=false) {
		$returnString =  $this->locationLabel;
		if($returnString == NULL && $this->longitude != 0 && $this->latitude != 0) {
			$returnString = "(". $this->latitude . ", " . $this->longitude . ")";
		}
		return $returnString;
	}


	public function loadContentFromArray($value) {
		foreach($value as $key=>$entry) {
			if($key == "loc") {
				$this->longitude = $entry["coordinates"][0];
				$this->latitude = $entry["coordinates"][1];
			}
			else {

				$this->$key = $entry;
				if($key == "isPrimary" && ($entry == true || $entry == "on")) {
					$this->isPrimary = true;
				}
			}
		}
	}

	public function hasContents() {
		if($this->longitude != null || $this->latitude != null || $this->locationLabel != NULL) {
			return true;
		}
		else {
			return false;
		}
	}

	public function getContent() {
		return false;
	}

}

/* End of file widget_contents.php */
/* Location: ./application/models/widget_contents.php */