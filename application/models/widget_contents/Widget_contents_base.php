<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Widget_contents_base extends CI_Model {

	public $fieldContents = NULL;
	public $isPrimary = FALSE;
	public $parentObjectId = NULL;
	public $parentWidget = NULL;

	public function __construct()
		{
			parent::__construct();
			//Do your magic here
		}

	public function getAsArray($serializeNestedObjects=false) {
		return ["fieldContents"=>$this->fieldContents, "isPrimary"=>$this->isPrimary];
	}

	public function getAsText($serializeNestedObjects=false) {
		return (string)$this->fieldContents;
	}

	/**
	 * allow widgets to override what gets indexed for searching
	 */
	public function getSearchEntry($serializeNestedObjects=false) {
		return $this->getAsText($serializeNestedObjects);
	}

	public function loadContentFromArray($value) {
		foreach($value as $key=>$entry) {
			$this->$key = $entry;
			if($key == "isPrimary" && ($entry == true || $entry == "on")) {
				$this->isPrimary = true;
			}
		}
	}

	public function getContent() {
		return $this->fieldContents;
	}

	public function hasContents() {
		if($this->fieldContents != NULL) {
			return true;
		}
		else {
			return false;
		}
	}


}

/* End of file widget_contents.php */
/* Location: ./application/models/widget_contents.php */