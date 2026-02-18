<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
use Doctrine\DBAL\Logging\DebugStack;
class Home extends Instance_Controller {

	public $noAuth = true;

	public function __construct()
	{
		parent::__construct();
	}

	public function index()
	{

		if ($this->isUsingVueUI()) {
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
		$data['homeText'] = "";
		if($pages) {
			$firstPage = current($pages);
			if($firstPage) {
				$data['homeText'] = $firstPage->getBody();
			}
			
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
			$map = $this->session->userdata("userAttributesCache");
			$shib = ["eduPersonAffiliation","eppn","isGuest","uid","uniqueIdentifier","umnJobSummary","umnRegSummary", "eduCourseMember","umnEmplId","email", "name","first_name","last_name"];
			
			foreach($map as $key=>$value) {
				if(in_array($key, $shib)) {
					echo $key . ": " . (is_array($value)?join(",",$value):$value) . "\n";
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
		$headerData["instanceShowCollectionInSearchResults"] = $this->instance->getShowCollectionInSearchResults();
		$headerData["instanceShowTemplateInSearchResults"] = $this->instance->getShowTemplateInSearchResults();
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

		// Search results data

		$directSearch = $this->doctrine->em->getRepository("Entity\Widget")->findBy(["directSearch"=>true]);

		$widgetArray = array();
		foreach($directSearch as $widget) {
			if($this->instance->getTemplates()->contains($widget->getTemplate())) {
				$widgetArray[$widget->getFieldTitle()] = ["label"=>$widget->getLabel(), "template"=>$widget->getTemplate()->getId(), "type"=>$widget->getFieldType()->getName()];	
			}
		}

		uasort($widgetArray, function($a, $b) {
			return strcmp($a["label"], $b["label"]);
		});

		$headerData["sortableFields"] = $widgetArray;


		$headerData["contact"] = null;
		if ($this->instance->getOwnerHomepage()) {
			$headerData["contact"] = $this->instance->getOwnerHomepage();
		}

		$headerData["useCentralAuth"] = $this->instance->getUseCentralAuth();
		$headerData["centralAuthLabel"] = $this->config->item("remoteLoginLabel");
		$headerData["showPreviousNext"] = $this->instance->getShowPreviousNextSearchResults();

		$rootCollections = $this->instance->getCollectionsWithoutParent();
		$viewableCollectionIds = array_map(fn($c) => $c->getId(), $this->user_model->getAllowedCollections(PERM_SEARCH));
		$editableCollectionIds = array_map(fn($c) => $c->getId(), $this->user_model->getAllowedCollections(PERM_ADDASSETS));

		// nest and add `canView` and `canEdit` props to collections
		$headerData['collections'] = $this->getNestedCollectionsWithPrivileges($rootCollections, $viewableCollectionIds, $editableCollectionIds);
		if($headerData["userCanManageAssets"]) {
			$templates[] = array();
			foreach($this->instance->getTemplates() as $template) {
				if(!$template->getIsHidden()) {
					$templates[$template->getId()] = $template->getName();
				}
			}
			$headerData["templates"] = $templates;
		}
		else {
			$headerData["templates"] = array();
		}

		// useCustomHeader also controls whether or not to show the custom footer
		$headerData['customHeaderMode'] = $this->instance->getUseCustomHeader();
		if ($this->instance->getUseCustomHeader()) {
			$headerData['customHeaderText'] = $this->instance->getCustomHeaderText();
			$headerData['customFooterText'] = $this->instance->getCustomFooterText();
		}

		$headerData['useCustomCSS'] = $this->instance->getUseCustomCSS() ?? false;
		$headerData['customHeaderCSS'] = $this->instance->getCustomHeaderCSS() ?? "";

		$headerData['useVoyagerViewer'] = $this->instance->getUseVoyagerViewer() ?? false;

		$headerData['theming'] = [
			'availableThemes' => $this->instance->getAvailableThemes(),
			'enabled' => $this->instance->getEnableThemes(),
			'defaultTheme' => $this->instance->getDefaultTheme(),
		];

		return render_json($headerData);
	}

	private function getNestedCollectionsWithPrivileges($rootCollections, $viewableCollectionIds, $editableCollectionIds = [])
	{
		$result = [];

		// if a user can edit ANY collection, show all collections
		// with their view/edit status
		$canEditSomeCollection = count($editableCollectionIds) > 0;
		$workingIterator = new ArrayIterator($rootCollections);
		foreach ($workingIterator as $collection) {
			$canView = in_array($collection->getId(), $viewableCollectionIds);
			$canEdit = in_array($collection->getId(), $editableCollectionIds);
			
			// if the user can't view this collection, or edit any collection,
			// we don't want to include it in the json output. But we might have rights to its children,
			// so we add them to the iterator to be checked later.
			if (!$canView && !$canEditSomeCollection) {
				if($collection->hasChildren()) {
					foreach($collection->getChildren() as $child) {
						$workingIterator->append($child);
					}	
				}
				continue;
			}

			$collectionEntry = [
				'id' => $collection->getId(),
				'title' => $collection->getTitle(),
				'showInBrowse' => $collection->getShowInBrowse(),
				'canView' => $canView,
				'canEdit' => $canEdit,
				'previewImageId' => $collection->getPreviewImage()
			];

			if ($collection->hasChildren()) {

				$collectionEntry["children"] = $this->getNestedCollectionsWithPrivileges($collection->getChildren()->toArray(), $viewableCollectionIds, $editableCollectionIds);
				
			}
			$result[] = $collectionEntry;
		}

		return $result;
	}
}

/* End of file home.php */
/* Location: ./application/controllers/home.php */
