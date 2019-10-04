<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Home extends Instance_Controller {

	public $noAuth = true;

	public function __construct()
	{
		parent::__construct();
	}

	public function index()
	{


		if($this->user_model) {
			$accessLevel = $this->user_model->getAccessLevel("instance",$this->instance);
			if($accessLevel == PERM_NOPERM) {
				if(count($this->user_model->getAllowedCollections(PERM_SEARCH)) == 0) {
					$this->useUnauthenticatedTemplate = true;
					$this->template->content->view("login/needAuth");
					$this->template->publish();
					return;
				}
			}
		}

		$data = array();
		if($this->instance->getFeaturedAsset()) {
			$this->load->model("asset_model");
			if($this->asset_model->loadAssetById($this->instance->getFeaturedAsset())) {
				$data['assetData'] = $this->asset_model->getSearchResultEntry();	
			}
		}

		$pages = $this->doctrine->em->getRepository("Entity\InstancePage")->findBy(["instance"=>$this->instance, "title"=>"Home Page"]);
		if($pages) {
			$firstPage = current($pages);
			$data['homeText'] = $firstPage->getBody();
		}

		$this->template->title = $this->instance->getName();
		$this->template->content->view("home", $data);
		$this->template->publish();
	}

	public function robots() {

		if($this->instanceType == "subdomain") {
			$allowRoot = $this->instance->getAllowIndexing();
		}
		else {
			$allowRoot = false;
		}

		$instances = $this->doctrine->em->getRepository("Entity\Instance")->findAll();

		$paths = Array();
		foreach($instances as $instance) {
			$status = ($this->instanceType == "subdomain")?false:$instance->getAllowIndexing();
			$paths[$instance->getDomain()] = $status;
		}

		$this->load->view("robots", ["paths"=>$paths, "allowRoot"=>$allowRoot]);

	}

	public function debug($dump) {
		echo "<pre>";
		if($dump=="shib") {
			$shib = ["eduPersonAffiliation","eppn","isGuest","uid","umnDID","umnJobSummary","umnRegSummary"];
			foreach($_SERVER as $key=>$value) {
				if(in_array($key, $shib)) {
					echo $key . ": " . $value . "\n";
				}
			}
		}
	}

	/*
	 * Vend the interstitial text if it's enabled, or return nothing
	 */
	public function interstitial() {
		$returnArray = [];
		if($this->instance->getEnableInterstitial()) {
			$returnArray["haveInterstitial"] = true;
			$returnArray["interstitialText"] = $this->instance->getInterstitialText();
		}
		else {
			$returnArray["haveInterstitial"] = false;
		}
		echo json_encode($returnArray);
	}

}

/* End of file home.php */
/* Location: ./application/controllers/home.php */