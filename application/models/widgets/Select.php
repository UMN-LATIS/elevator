<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class Select extends Widget_base {

	public $parsedFieldData;

	public function __construct()
	{
		parent::__construct();

	}

	public function loadWidget($widgetItem) {
		parent::loadWidget($widgetItem);
		$this->parsedFieldData = $this->getFieldData();
	}


}

/* End of file text.php */
/* Location: ./application/models/text.php */