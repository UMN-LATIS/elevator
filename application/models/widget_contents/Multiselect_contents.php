<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Multiselect_contents extends Widget_contents_base {

	public function hasContents() {
		if(is_array($this->fieldContents )) {
			foreach($this->fieldContents as $entry) {
				if(strlen($entry)>0) {
					return true;
				}
			}	
		}
		
		return false;
	}


	public function getAsText($serializeNestedObjects=false) {
		return join(", ", array_filter($this->fieldContents));
	}

}

/* End of file widget_contents.php */
/* Location: ./application/models/widget_contents.php */