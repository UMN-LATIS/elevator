<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class Related_asset extends Widget_base {

	var $nestData = false;
	var $collapseNestedChildren = false;
	var $thumbnailView = false;
	var $showLabel = false;
	var $defaultTemplate = 0;
	var $matchAgainst = array();
	var $displayInline = false;
	var $ignoreForDigitalAsset = false;
	var $ignoreForLocationSearch = false;
	var $ignoreForDateSearch = false;

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
			$fieldEntry->nestData = $this->nestData;
			$fieldEntry->collapseNestedChildren = $this->collapseNestedChildren;
			$fieldEntry->thumbnailView = $this->thumbnailView;
			$fieldEntry->defaultTemplate = $this->defaultTemplate;
			$fieldEntry->displayInline = $this->displayInline;
			$fieldEntry->matchAgainst = $this->matchAgainst;
			$fieldEntry->ignoreForDigitalAsset = $this->ignoreForDigitalAsset;
			$fieldEntry->ignoreForLocationSearch = $this->ignoreForLocationSearch;
			$fieldEntry->ignoreForDateSearch = $this->ignoreForDateSearch;
		}

		return parent::getAsArray($nestedDepth);

	}

	public function getForm() {
		$this->prepareForDrawing();
		if($this->displayInline) {
			$viewClass = "nested_asset";
		}
		else {
			$viewClass = strtolower(get_class($this));
		}
		return $this->load->view("widget_form_partials/" . $viewClass, array("widgetModel"=>$this), true);
	}



	public function getArrayOfText($serializeNestedObjects=false) {
		foreach($this->fieldContentsArray as $fieldEntry) {
			$fieldEntry->nestData = $this->nestData;
		}

		return parent::getArrayOfText($serializeNestedObjects);
	}

}

/* End of file text.php */
/* Location: ./application/models/text.php */