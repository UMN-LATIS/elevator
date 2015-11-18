<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MY_Controller extends CI_Controller {

	public $doctrineCache = null;

	function __construct() {

		parent::__construct();
		$this->template->stylesheet->add('assets/css/bootstrap.css');
		$this->template->javascript->add("assets/js/jquery-2.0.3.min.js");
		$this->template->javascript->add("assets/js/bootstrap.min.js");
		$this->template->javascript->add("assets/js/jquery-ui.min.js");
		$this->template->javascript->add("assets/js/jquery.cookie.min.js");
		$this->template->javascript->add("assets/js/retina-1.1.0.min.js");
		$this->template->javascript->add("assets/js/mousetrap.min.js");
		$this->template->javascript->add("assets/js/bootbox.min.js");
		if(defined('ENVIRONMENT') && ENVIRONMENT == "development") {
			$this->template->javascript->add("assets/js/serializeForm.js");
			$this->template->javascript->add("assets/js/dateWidget.js");
			$this->template->javascript->add("assets/js/template.js");

		}
		else {
			$this->template->javascript->add("assets/js/serializeDateTemplate.js");
		}

		if($this->router->fetch_class() != "search") {
			$this->template->javascript->add("assets/js/templateSearch.js");
		}

		$this->load->library("session");

		// set a forever cookie to help with use in iframes
		$this->input->set_cookie(["name"=>"ElevatorCookie", "value"=>true, "expire" => 60*60*24*365]);
		$this->load->driver('cache',  array('adapter' => 'apc', 'backup' => 'file'));

		$this->load->model("user_model");

		//$this->user_model->loadUser(1);

		if($this->config->item('enableCaching')) {
			$this->doctrineCache = new \Doctrine\Common\Cache\ApcCache();
		}

		$userId = $this->session->userdata('userId');
		if($userId) {
			if($this->config->item('enableCaching')) {
				$this->doctrineCache->setNamespace('userCache_');
				if($storedObject = $this->doctrineCache->fetch($userId)) {
				 	$user_model = unserialize($storedObject);
				 	if(!$user_model) {
				 		$this->user_model->loadUser($userId);
				 	}
				 	else {
				 		$this->user_model = $user_model;
				 	}
				}
				else {
					$this->user_model->loadUser($userId);
					$this->doctrineCache->save($userId, serialize($this->user_model), 900);
				}
			}
			else {
				$this->user_model->loadUser($userId);
			}
		}
		else {
			$this->user_model->resolvePermissions();
		}
	}

}

