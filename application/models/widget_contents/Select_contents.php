<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Select_contents extends Widget_contents_base {


	public function __construct()
		{
			parent::__construct();
			//Do your magic here
		}

	public function getAsText($serializeNestedObjects=false) {
		if(is_array($this->fieldContents)) {
			return implode(", ", $this->fieldContents);
		}
		else {
			return (string)$this->fieldContents;	
		}
		
	}

	public function getSearchEntry($serializeNestedObjects=false) {
		return $this->fieldContents;
	}

	public function hasContents() {
		if($this->fieldContents != NULL) {
			if(is_array($this->fieldContents) && count($this->fieldContents) == 1 && strlen($this->fieldContents[0]) == 0) {
				return false;
			}
			return true;
		}
		else {
			return false;
		}
	}


}

/* End of file widget_contents.php */
/* Location: ./application/models/widget_contents.php */