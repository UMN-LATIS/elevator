<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Widget_base extends CI_Model {

	private $fieldId;
	private $widgetObject;
	public $parentObjectId;

	/**
	 * Asset ivars
	 */
	public $fieldContentsArray;

	/**
	 * Drawing ivars (accessed by views)
	 */

	public $drawCount = 0;
	public $offsetCount = 0;

	public function __construct($widgetItem=null)
	{
		parent::__construct();
		//Do your magic here
		if($widgetItem) {
			$this->loadWidget($widgetItem);
		}
	}

	public function primarySort() {

		usort($this->fieldContentsArray, function($a, $b) {
			$a = ($a->isPrimary?1:-1);
			$b = ($b->isPrimary?1:-1);
			if ($a == $b) {
        		return 0;
    		}
    		$result = ($a > $b) ? -1 : 1;
    		return $result;
   		});



	}


	public function loadWidget($widgetItem) {
		if(is_object($widgetItem)) {
			/**
			 * In this case, we've been passed a DB row, we don't need to do anything.
			 */
			$this->widgetObject = $widgetItem;
		}
		else {
			$this->widgetObject = $this->doctrine->em->find('Entity\Widget', $widgetItem);
			if(!is_object($this->widgetObject)) {
				return FALSE;
			}
		}

		$this->fieldId = $this->widgetObject->getId();

		return true;

	}

	public function getContentContainer() {
		$containerClass = get_class($this) . "_contents";
		if(file_exists(APPPATH."models/widget_contents/".ucfirst(strtolower($containerClass)).".php")) {
			$this->load->model("widget_contents/" . $containerClass);
			return new $containerClass;
		}
		else {
			return new Widget_contents_base;
		}

	}


	public function getFieldId() {
		return $this->fieldId;
	}

	/**
	 * get ready to draw our widget - store a count of how many elements we already have.
	 * If this element doesn't allow multiple, we still need a draw count of one, so that we draw the control
	 * @return [type] [description]
	 */
	public function prepareForDrawing() {
		if($this->getAllowMultiple()) {
			if(isset($this->fieldContentsArray)) {
				$this->drawCount = count($this->fieldContentsArray);
			}
		}

		if($this->drawCount == 0) {
			$this->drawCount = 1;
		}
	}

	/**
	 * The getView and getForm methods could potentially be overloaded by the subclasses, but for
	 * most of the controls this should be enough.
	 */

	public function getView() {
		$this->primarySort();
		return $this->load->view("widget_view_partials/" . strtolower(get_class($this)), array("widgetModel"=>$this), true);
	}

	public function getForm() {
		$this->prepareForDrawing();
		return $this->load->view("widget_form_partials/" . strtolower(get_class($this)), array("widgetModel"=>$this), true);
	}


	public function addContent($contentObject) {
		$contentObject->parentWidget = $this;
		$this->fieldContentsArray[] = $contentObject;
	}


	public function hasContents() {
		if(is_array($this->fieldContentsArray)) {
			foreach($this->fieldContentsArray as $entry) {
				if($entry->hasContents()) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Catch invalid method calls and see if our Doctrine child instance
	 * will respond - if so, call through to that.  Otherwise, flag an error.
	 * @return calls through to Doctrine instance method
	 */
	public function __call($method, $args) {
		if (is_object($this->widgetObject)) {
			if(method_exists($this->widgetObject, $method)) {
				return call_user_func(array($this->widgetObject,$method), $args);
			}
		}

		trigger_error("Error Calling object method '$method' " . implode(', ', $args), E_USER_ERROR);
	}

	/**
	 * Get this widget as an array, for json-ification
	 *
	 */
	public function getAsArray($nestedDepth=false) {
		$outputArray = array();
		foreach($this->fieldContentsArray as $fieldEntry) {
			if($fieldEntry->hasContents()) {
				$outputArray[] = $fieldEntry->getAsArray($nestedDepth);
			}
		}

		return $outputArray;

	}

	public function getAsText($nestedDepth=false) {
		$outputArray = array();
		foreach($this->fieldContentsArray as $fieldEntry) {
			if($fieldEntry->hasContents()) {
				$outputArray[] = $fieldEntry->getAsText($nestedDepth);
			}
		}

		return $outputArray;

	}

	public function getSearchEntry($nestedDepth=false) {
		$outputArray = array();
		foreach($this->fieldContentsArray as $fieldEntry) {
			if($fieldEntry->hasContents()) {
				$outputArray[] = $fieldEntry->getSearchEntry($nestedDepth);
			}
		}

		return $outputArray;

	}

	public function getArrayOfText($serializeNestedObjects=false) {
		$outputArray = array();
		foreach($this->fieldContentsArray as $fieldEntry) {
			if($fieldEntry->hasContents()) {
				$outputArray[] = $fieldEntry->getAsText($serializeNestedObjects=false);
			}
		}

		return $outputArray;
	}

}

/* End of file modelName.php */
/* Location: ./application/models/widgets/widgetbase.php */
