<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MY_Controller extends CI_Controller {

	public $doctrineCache = null;

	function __construct() {

		parent::__construct();
		$cssLoadArray = ["bootstrap", "screen"];
		$jsLoadArray = ["bootstrap", "jquery-ui","jquery.cookie", "jquery.lazy", "sugar","retina-1.1.0", "mousetrap", "bootbox"];

		if(defined('ENVIRONMENT') && ENVIRONMENT == "development") {
			$jsLoadArray= array_merge($jsLoadArray, ["serializeForm", "dateWidget", "template"]);

		}
		else {
			$jsLoadArray[] = "serializeDateTemplate";
		}

		if($this->router->fetch_class() != "search") {
			$jsLoadArray[] = "templateSearch";
		}

		$this->template->loadCSS($cssLoadArray);
		$this->template->loadJavascript($jsLoadArray);


		$this->load->library("session");

		// set a forever cookie to help with use in iframes
		$this->input->set_cookie(["name"=>"ElevatorCookie", "value"=>true, "expire" => 60*60*24*365]);
		$this->load->driver('cache',  array('adapter' => 'apc'));

		$this->load->model("user_model");

		//$this->user_model->loadUser(1);

		if($this->config->item('enableCaching')) {
			$redisCache = new \Doctrine\Common\Cache\RedisCache();
        	$redisCache->setRedis($this->doctrine->redisHost);
			$this->doctrineCache = $redisCache;
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
					$this->doctrineCache->save($userId, serialize($this->user_model), 3600);
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

	function throwBacktrace() {
		$e = new Exception;
		var_dump($e->getTraceAsString());
	}


}

