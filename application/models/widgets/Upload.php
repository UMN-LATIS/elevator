<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class Upload extends Widget_base {

	public $extractLocation;
	public $extractDate;
	public $enableTiling = false;
	public $enableIframe = false;
	public $interactiveTranscript = false;
	public $ignoreForDigitalAsset = false;
	public $forceTiling = false;
	public $enableDendro = false;
	public $enableAnnotation = false;
	
	public function __construct()
	{
		parent::__construct();

	}

	public function loadWidget($widgetItem) {
		parent::loadWidget($widgetItem);
		$parsedFieldData = $this->getFieldData();
		if(isset($parsedFieldData)) {
			foreach($parsedFieldData as $key=>$entry) {
				$this->$key = $entry;
			}
		}
	}

	public function getAsArray($nestedDepth=false) {
		foreach($this->fieldContentsArray as $fieldEntry) {
			$fieldEntry->extractDate = $this->extractDate;
			$fieldEntry->extractLocation = $this->extractLocation;
		}

		return parent::getAsArray($nestedDepth);

	}

	public function getArrayOfText($serializeNestedObjects=false) {
		foreach($this->fieldContentsArray as $fieldEntry) {
			$fieldEntry->extractDate = $this->extractDate;
			$fieldEntry->extractLocation = $this->extractLocation;
		}

		return parent::getArrayOfText($serializeNestedObjects);
	}
}

/* End of file text.php */
/* Location: ./application/models/text.php */