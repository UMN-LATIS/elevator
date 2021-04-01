<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Checkbox_contents extends Widget_contents_base {


	public function __construct()
		{
			parent::__construct();
			//Do your magic here
		}



	public function loadContentFromArray($value) {
		parent::loadContentFromArray($value);
		if($this->fieldContents == "on") {
			$this->fieldContents = true;
		}
		else {
			$this->fieldContents = false;
		}
	}

	public function hasContents() {
		return true;
	}

	public function getSearchEntry($serializeNestedObjects=false) {
		return $this->fieldContents == true ? "1":"0";
	}

}

/* End of file widget_contents.php */
/* Location: ./application/models/widget_contents.php */