<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use OpenTelemetry\API\Logs\Map\Psr3;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Symfony\Component\Cache\Psr16Cache;

class MY_Controller extends CI_Controller {

	public ?Psr16Cache $userCache= null;
	public ?Psr16Cache $userGuestCache= null;
	public ?Psr16Cache $assetCache= null;
	public ?Psr16Cache $searchCache= null;
	

	function __construct() {
		parent::__construct();
		
			\Sentry\init([
  				'dsn' => $this->config->item('sentry_dsn'),
				'environment' => (defined(ENVIRONMENT) ? ENVIRONMENT:"development"),
				'server_name' => $this->config->item('authHelper')
			]);
		
		if($this->config->item('css_override') && $this->config->item('css_override') !== "FALSE") {
			$cssLoadArray = ["bootstrap_" . $this->config->item('css_override'), $this->config->item('css_override')];
		}
		else {
			$cssLoadArray = ["bootstrap", "screen"];
		}

		
		$jsLoadArray = ["bootstrap", "jquery-ui","jquery.cookie", "jquery.lazy", "sugar", "mousetrap", "bootbox"];

		if(defined('ENVIRONMENT') && ENVIRONMENT == "development") {
			$jsLoadArray= array_merge($jsLoadArray, ["serializeForm", "dateWidget", "template", "advancedSearchForm", "multiselectWidget"]);

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

		// $this->load->driver('cache',  array('adapter' => 'apc'));

		$this->load->model("user_model");
		//$this->user_model->loadUser(1);

		if ($this->config->item('enableCaching')) {
			
			$this->userCache = $this->getCache('userCache');
			$this->userGuestCache = $this->getCache('userGuestCache');
			$this->searchCache = $this->getCache('searchCache');
		}

		$userId = $this->session->userdata('userId');
		// HACK HACK HACK
		// Close the session if we're not going to be doing a login, prevent session locks in case of hung urls
		if($this->uri->segment(2) && strtolower($this->uri->segment(2)) !== "loginmanager" && strtolower($this->uri->segment(1)) !== "loginmanager" && strtolower($this->uri->segment(1)) !== "shibboleth"  && strtolower($this->uri->segment(1)) !== "shibboleth.sso") {
			session_write_close();
		}
		if($userId) {
			
			if ($this->config->item('enableCaching')) {
				if($storedObject = $this->userCache->get($userId)) {
					$user_model = $storedObject;
					if(!is_object($user_model)) {
						$this->userCache->delete($userId);
						$user_model = null;
					}
				 	if(!$user_model) {
				 		$this->user_model->loadUser($userId);
				 	}
				 	else {
						 $this->user_model = $user_model;
				 	}
				}
				else {
					$this->user_model->loadUser($userId);
					if($this->user_model->userLoaded) {

						$this->userCache->set($userId, $this->user_model, 14400);
					}
					
				}
			}
			else {
				$this->user_model->loadUser($userId);
			}
		}

		// if the user isn't loaded, make a guest login
		if(!$this->user_model->userLoaded) {
			$this->user_model = new User_model();
			if ($this->config->item('enableCaching')) {
				$userId = session_id();
				if($storedObject = $this->userGuestCache->get($userId)) {
				 	$user_model = $storedObject;
				 	if(!$user_model) {
				 		$this->user_model->resolvePermissions();
				 	}
				 	else {
						 $this->user_model = $user_model;
				 	}
				}
				else {
					$this->user_model->resolvePermissions();
					$userId = session_id();
					// reset our namespace in case the user model changed it
					$this->userGuestCache->set($userId, ($this->user_model), 14400);
				}
			}
			else {
				$this->user_model->resolvePermissions();
			}
			
		}
		$authKey = null;
		

		if($this->input->get('apiHandoff', TRUE)) {
			$signedString = $this->input->get('apiHandoff');
			$authKey = $this->input->get('authKey');
			$timestamp = $this->input->get('timestamp');
			$targetObject = $this->input->get('targetObject');
			$this->input->set_cookie(["name"=>"ApiHandoff", "value"=>$signedString, "expire"=>0]);
			$this->input->set_cookie(["name"=>"AuthKey", "value"=>$authKey, "expire"=>0]);
			$this->input->set_cookie(["name"=>"Timestamp", "value"=>$timestamp, "expire"=>0]);
			$this->input->set_cookie(["name"=>"TargetObject", "value"=>$targetObject, "expire"=>0]);
		}
		elseif($this->input->cookie('ApiHandoff')) {
			// $signedString = $this->input->cookie('ApiHandoff');
			// $authKey = $this->input->cookie('AuthKey');
			// $timestamp = $this->input->cookie('Timestamp');
			// $targetObject = $this->input->cookie('TargetObject');
		}
		if($authKey) {
			$apiKey = $this->doctrine->em->getRepository("Entity\ApiKey")->findOneBy(["apiKey"=>$authKey]);
			if($apiKey) {
				$secret = $apiKey->getApiSecret();
				
				if(sha1($timestamp . $targetObject . $secret) == $signedString) {	
					if(!$this->user_model->userLoaded) {
						$this->user_model->assetOverride = true; // set a flag that this isn't a fully loaded user
					}
					$this->user_model->userLoaded=true;
					$this->user_model->assetPermissions = [$targetObject => PERM_DERIVATIVES_GROUP_2];
				}
			}
		}
	
		
	}

	function throwBacktrace() {
		$e = new Exception;
		var_dump($e->getTraceAsString());
	}


	public function getCache($cacheNamespace) {
		$redis = new Redis();
            
        $redis->connect($this->config->item("redis"),$this->config->item("redisPort"));
		$cache = new RedisAdapter($redis, $cacheNamespace);
		return new Psr16Cache($cache);
	}

}

