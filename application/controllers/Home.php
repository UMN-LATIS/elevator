<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Home extends Instance_Controller {

	public $noAuth = true;

	public function __construct()
	{
		parent::__construct();
	}

	public function test() {
		$client = new Google_Client();
		$client->setAuthConfig("application/controllers/test");
		$client->setApplicationName("Elevator");
		$client->setScopes([Google_Service_Directory::ADMIN_DIRECTORY_USER_READONLY]);
		$service = new Google_Service_Directory($client);
		var_dump($service->groups->listGroups());

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

}

/* End of file home.php */
/* Location: ./application/controllers/home.php */