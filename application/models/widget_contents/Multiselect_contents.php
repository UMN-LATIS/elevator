<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Multiselect_contents extends Widget_contents_base {

	public $topLevels = null;

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
		return join(", ", array_filter($this->getSortedValues()));
	}

	public function getSortedValues() {
		if($this->topLevels == null) {
			$this->load->helper("multiselect_helper");      
			$this->topLevels = getTopLevels($this->parentWidget->getFieldData());
			$this->topLevels = array_map("makeSafeForTitle", $this->topLevels);
		}

		$sortedContent = array_replace(array_flip($this->topLevels), $this->fieldContents);
		return $sortedContent;
	}

}

/* End of file widget_contents.php */
/* Location: ./application/models/widget_contents.php */