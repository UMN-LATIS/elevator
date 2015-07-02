<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Widget_router {

	
	public function __construct()
	{
		$CI =& get_instance();
		$CI->load->model("widget_contents/Widget_contents_base");
	}
	
	public function getWidget($widgetRow) {
		$targetClass = $widgetRow->getFieldType()->getModelName();
		if(class_exists($targetClass)) {
			$newWidget = new $targetClass;	
		}
		else {
			$newWidget = new Widget_base;
		}
		$newWidget->loadWidget($widgetRow);
		return $newWidget;
	}

}