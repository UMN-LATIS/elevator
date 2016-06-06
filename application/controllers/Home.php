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

		$this->template->title = $this->instance->getName();
		$this->template->content->view("home", $data);
		$this->template->publish();
	}

}

/* End of file home.php */
/* Location: ./application/controllers/home.php */