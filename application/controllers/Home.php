<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Home extends Instance_Controller {

	public $noAuth = true;

	public function __construct()
	{
		parent::__construct();
	}
	
	public function index()
	{

		if ($this->instance->getInterfaceVersion() == 1) {
			$this->template->set_template("vueTemplate");
			$this->template->publish();
			return;
		}
		
		$this->template->loadJavascript(["bootstrap-show-password"]);

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
			$shib = ["eduPersonAffiliation","eppn","isGuest","uid","umnDID","umnJobSummary","umnRegSummary", "eduCourseMember","umnEmplId"];
			
			foreach($_SERVER as $key=>$value) {
				if(in_array($key, $shib)) {
					echo $key . ": " . $value . "\n";
				}
			}
		}
		if($dump=="user") {
			var_dump($this->user_model->userData);
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

	public function getInstanceNav()
	{

		$headerData = [];

		$instancePages = $this->instance->getPages()->filter(
			function ($entry) {
			    return $entry->getParent() == null;
		    }
		);

		// load pages for the instance, including any children
		$outputPages = [];
		foreach ($instancePages as $page) {
			$pageEntry = [];
			$pageEntry["title"] = $page->getTitle();
			$pageEntry["id"] = $page->getId();
			$pageEntry["includeInNav"] = $page->getIncludeInHeader();
			$pageEntry["children"] = [];
			if ($page->getChildren()->count() > 0) {
				foreach ($page->getChildren() as $child) {
					$childPage = [];
					$childPage["id"] = $child->getId();
					$childPage["title"] = $child->getTitle();
					$childPage["includeInNav"] = $child->getIncludeInHeader();
					$pageEntry["children"][] = $childPage;
				}
			}
			$outputPages[] = $pageEntry;
		}
		$headerData["pages"] = $outputPages;

		$headerData["userIsloggedIn"] = false;

		$headerData["userCanSearchAndBrowse"] = false;
		if ($this->user_model) {
			$accessLevel = $this->user_model->getAccessLevel("instance", $this->instance);
			if ($accessLevel == PERM_NOPERM) {
				if (count($this->user_model->getAllowedCollections(PERM_SEARCH)) > 0) {
					$headerData["userCanSearchAndBrowse"] = true;
				}
			} else {
				$headerData["userCanSearchAndBrowse"] = true;
			}
		}
		
		$headerData["userCanCreateDrawers"] = false;
		$headerData["userCanManageAssets"] = false;
		$headerData["userId"] = null;
		$headerData["userDisplayName"] = null;
		$headerData["userIsAdmin"] = false;
		$headerData["userIsSuperAdmin"] = false;
		$headerData["instanceName"] = $this->instance->getName();
		$headerData["instanceId"] = $this->instance->getId();
		$headerData["instanceHasLogo"] = $this->instance->getUseHeaderLogo();
		$headerData["instanceLogo"] = $this->instance->getId();
		$headerData["featuredAssetId"] = $this->instance->getFeaturedAsset();
		$headerData["featuredAssetText"] = $this->instance->getFeaturedAssetText();

		// load prefs for a logged in user
		if ($this->user_model->userLoaded && !$this->user_model->assetOverride) {
			$headerData["userIsloggedIn"] = true;
			$headerData["userId"] = $this->user_model->getId();
			$headerData["userDisplayName"] = $this->user_model->getDisplayName();

			if ($this->user_model->getIsSuperAdmin() || $this->user_model->getAccessLevel("instance", $this->instance) >= PERM_CREATEDRAWERS || $this->user_model->getMaxCollectionPermission() >= PERM_CREATEDRAWERS) {
				$headerData["userCanCreateDrawers"] = true;
			}

			if ($this->user_model->getIsSuperAdmin() || $this->user_model->getAccessLevel("instance", $this->instance) >= PERM_ADDASSETS || $this->user_model->getMaxCollectionPermission() >= PERM_ADDASSETS) {
				$headerData["userCanManageAssets"] = true;
			}

			$outputDrawers = [];
			foreach ($this->user_model->getRecentDrawers() as $drawer) {
				$drawerEntry = [];
				$drawerEntry["id"] = $drawer->getDrawer()->getId();
				$drawerEntry["title"] = $drawer->getDrawer()->getTitle();
				$outputDrawers[] = $drawerEntry;
			}
			$outputCollections = [];
			foreach ($this->user_model->getRecentCollections() as $collection) {
				$collectionEntry = [];
				$collectionEntry["id"] = $collection->getCollection()->getId();
				$collectionEntry["title"] = $collection->getCollection()->getTitle();
				$outputCollections[] = $collectionEntry;
			}

			$headerData["recentDrawers"] = $outputDrawers;
			$headerData["recentCollections"] = $outputCollections;

			if ($this->user_model->isInstanceAdmin() || $this->user_model->getIsSuperAdmin()) {
				$headerData["userIsAdmin"] = true;
			}
			if ($this->user_model->getIsSuperAdmin()) {
				$headerData["userIsSuperAdmin"] = true;
			}

		}


		$headerData["contact"] = null;
		if ($this->instance->getOwnerHomepage()) {
			$headerData["contact"] = $this->instance->getOwnerHomepage();
		}

		$headerData["useCentralAuth"] = $this->instance->getUseCentralAuth();
		$headerData["centralAuthLabel"] = $this->config->item("remoteLoginLabel");
		$headerData["showPreviousNext"] = $this->instance->getShowPreviousNextSearchResults();

		// collection information
		$outputCollections = $this->getNestedCollections($this->collection_model->getUserCollections());
		$headerData["collections"] = $outputCollections;


		return render_json($headerData);


	}

	private function getNestedCollections($collectionList)
	{
		$result = [];
		foreach ($collectionList as $collection) {
			if ($collection->getShowInBrowse()) {

				$collectionEntry = [];
				$collectionEntry["id"] = $collection->getId();
				$collectionEntry["title"] = $collection->getTitle();
				$collectionEntry["previewImageId"] = $collection->getPreviewImage();
				if ($collection->hasChildren()) {
					$collectionEntry["children"] = $this->getNestedCollections($collection->getChildren());
				}
				$result[] = $collectionEntry;
			}
		}
		return $result;

	}

}

/* End of file home.php */
/* Location: ./application/controllers/home.php */