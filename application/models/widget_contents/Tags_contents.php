<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Tags_contents extends Widget_contents_base {

	public $tags = array();

	public function __construct()
		{
			parent::__construct();
			//Do your magic here
			//
		}


	public function getAsArray($serializeNestedObjects=false) {
		return ["tags"=>$this->tags, "isPrimary"=>$this->isPrimary];
	}

	public function getAsText($serializeNestedObjects=false) {
		return join(", ", $this->tags);
	}

	public function getSearchEntry($serializeNestedObjects=false) {
		return $this->tags;
	}


	public function loadContentFromArray($value) {
		foreach($value as $key=>$entry) {
			/**
			 * coming in from a form submission, explode on commas
			 * @var [type]
			 */

			if($key == "tags" && !is_array($entry)) {
				$tempArray = str_getcsv(stripslashes($entry), escape: "\\");
				$this->tags = array();
				foreach($tempArray as $value) {
					if(trim($value??"") !== "") {
						$this->tags[] = trim($value);
					}
				}
			}
			/**
			 * an empty array in mongo will reinstantiate as having one element, not zero
			 */
			elseif($key == "tags"&& is_array($entry)) {
				if(count($entry) == 1 && $entry[0] == "") {
					$this->tags = array();
				}
				else {
					$this->tags = $entry;
				}
			}
			elseif($key == "isPrimary" && ($entry == true || $entry == "on")) {
				$this->isPrimary = true;
			}
			else {
				$this->$key = $entry;
			}


		}

	}

	public function hasContents() {
		if(count($this->tags) > 0) {
			return true;
		}
		else {
			return false;
		}
	}

	public function getContent() {
		return $this->tags;
	}

}

/* End of file widget_contents.php */
/* Location: ./application/models/widget_contents.php */