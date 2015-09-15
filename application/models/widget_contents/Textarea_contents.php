<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Textarea_contents extends Widget_contents_base {

	public function __construct()
		{
			parent::__construct();
			//Do your magic here
		}



	public function getAsText($serializeNestedObjects=false) {
		return (string)strip_tags($this->fieldContents);
	}

}

/* End of file widget_contents.php */
/* Location: ./application/models/widget_contents.php */