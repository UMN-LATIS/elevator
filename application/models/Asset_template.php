<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * TODO: this won't fail in a particularly helpful way if you try to load a template that doesn't exist.
 */
class Asset_template extends CI_Model {
	private $templateId;
	private $templateCache = array();
	private $templateObject = null;
	public $widgetArray = array();
	public $name = NULL;
	public $displayInline = false;
	public $useCache = true;

	public function __construct($templateId = null)
	{
		parent::__construct();

		$this->load->helper("directory");
		$this->load->model("widgets/widget_base");

		$widgets = directory_map(APPPATH."models/widgets", TRUE);
		foreach($widgets as $widget) {
    		if( ! is_array($widget)) {
        		$class = str_replace(".php", "", $widget);
        		$className = "widgets/" . $class;
        		$this->load->model($className);
			}
		}

		if(is_numeric($templateId)) {
			$this->loadTemplate($templateId);
		}

    }

    private function loadTemplate($templateId) {
    	$this->templateId = $templateId;

    	$this->templateObject = $this->doctrine->em->find('Entity\Template', $templateId);
		if(!is_object($this->templateObject)) {
			$this->templateObject = NULL;
			return FALSE;
		}

		$this->name = $this->getName();


		$widgetQuery = $this->doctrine->em->getRepository("Entity\Widget")->findBy(["template"=>$templateId]);

		foreach($widgetQuery as $widget) {
			$widgetArray[$widget->getFieldTitle()] = $this->widget_router->getWidget($widget);
		}


		$this->widgetArray = $widgetArray;

		$this->sortBy("templateOrder");

	}

	public function getAsArray() {
		$returnArray = [];
		$returnArray["templateId"] = $this->templateId;
		$returnArray["templateName"] = $this->name;
		$returnArray["widgetArray"] = [];
		foreach($this->widgetArray as $widget) {
			$widgetArray = $widget->getWidgetDataAsArray();
			$returnArray["widgetArray"][] = $widgetArray;


		}
		return $returnArray;


	}


	public function getTemplate($templateId) {

		if(!isset($this->templateCache[$templateId]) || $this->useCache == false) {
			$assetTemplate = new Asset_template($templateId);
			if($assetTemplate->name) {
				$this->templateCache[$templateId] = $assetTemplate;
			}
			
		}
		if(isset($this->templateCache[$templateId])) {
			return $this->templateCache[$templateId];
		}
		return NULL;


	}



	public function sortBy($sortType) {
		if($sortType == "templateOrder") {
   			uasort($this->widgetArray, function($a, $b) {
    				return $a->getTemplateOrder() > $b->getTemplateOrder() ? 1 : -1;
    		});
   		}
   		elseif($sortType == "viewOrder") {
   			uasort($this->widgetArray, function($a, $b) {
    				return $a->getViewOrder() > $b->getViewOrder() ? 1 : -1;
    		});
   		}


	}


	public function templateForm($asset=null) {
		$widgetHTML = "";

		$data['template'] = $this->templateObject;

		if(isset($asset)) {
			$data['asset'] = $asset;
			foreach($this->widgetArray as $key=>$value) {
				if(isset($asset->assetObjects[$key])) {
					$this->widgetArray[$key] = $asset->assetObjects[$key];
				}
			}
		}


		$allowedCollections = $this->user_model->getAllowedCollections(PERM_ADDASSETS);


		$data['allowedCollections'] = $allowedCollections;


		$this->sortBy('templateOrder');

		$widgetList = array();

		foreach($this->widgetArray as $widget) {
			$widgetList[$widget->getFieldTitle()] = $widget->getLabel();
			$widgetHTML .= $widget->getForm();
		}

		$data['widgetList'] = $widgetList;
		$data['displayInline'] = $this->displayInline;

		if($asset) {
			$data['assetModel'] = $asset;
		}

		$pageHTML = "";

		if($asset) {
			$data['lastModifiedBy'] = $asset->getLastModifiedName();	
			$data['lastModifiedAt'] = $asset->getGlobalValue("modified")->format('Y-m-d H:i:s');	

		}
		else {
			$data['lastModifiedBy'] = "";
			$data['lastModifiedAt'] = "";
		}
		

		if($this->displayInline) {
			$pageHTML .= $this->load->view("assetManager/templateFormHeaderInline", $data, TRUE);
		}
		else {
			$pageHTML .= $this->load->view("assetManager/templateFormHeader", $data, TRUE);
		}
		$pageHTML .= $widgetHTML;
		$pageHTML .= $this->load->view("assetManager/templateFormFooter", "", TRUE);
		return $pageHTML;
	}


	/**
	 * Things which would be class methods go here
	 */

	public function listTemplatesForInstance($instanceId=null) {

		if(!$instanceId && $this->instance) {
			$templates = $this->instance->getTemplates();
		}
		elseif($instanceId != null) {
			$templates = $this->doctrine->em->find('Entity\Instance', $instanceId)->getTemplates();
		}
		else {
			return array();
		}

		return $templates;

	}


	/**
	 * Catch invalid method calls and see if our Doctrine child instance
	 * will respond - if so, call through to that.  Otherwise, flag an error.
	 * @return calls through to Doctrine instance method
	 */
	public function __call($method, $args) {
		if (is_object($this->templateObject)) {
			if(method_exists($this->templateObject, $method)) {
				return call_user_func(array($this->templateObject,$method), $args);
			}
		}
		
		trigger_error("Error Calling object method '$method' " . implode(', ', $args), E_USER_ERROR);
	}


}

/* End of file template.php */
/* Location: ./application/models/template.php */

?>
