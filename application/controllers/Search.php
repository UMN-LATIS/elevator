<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class Search extends Instance_Controller {

	private $searchId = null;

	public function __construct()
	{
		parent::__construct();
		$this->load->model("asset_model");
		$this->template->javascript->add("//maps.google.com/maps/api/js?libraries=geometry");

		$jsLoadArray = ["handlebars-v1.1.2", "jquery.gomap-1.3.2", "mapWidget", "markerclusterer", "sugar","drawers", "galleria-1.3.3"];
		$this->template->loadJavascript($jsLoadArray);

		$this->template->content->view("drawers/drawerModal");
	}

	public function index()
	{
		$accessLevel = $this->user_model->getAccessLevel("instance",$this->instance);

		if($accessLevel < PERM_SEARCH) {
			if($this->user_model) {
				$allowedCollections = $this->user_model->getAllowedCollections(PERM_SEARCH);
				if(count($allowedCollections) == 0) {
					instance_redirect("errorHandler/error/noPermission");
				}
			}
			else {
				instance_redirect("errorHandler/error/noPermission");
			}
		}

		$jsloadArray = array();
		if(defined('ENVIRONMENT') && ENVIRONMENT == "development") {
			$jsLoadArray = ["search", "searchForm"];

		}
		else {
			$jsLoadArray = ["searchMaster"];
		}
		$jsLoadArray[] = "spin";

		$this->template->loadJavascript($jsLoadArray);
		$this->template->addToDrawer->view("drawers/add_to_drawer");
		$this->template->content->view("search");
		$this->template->publish();
	}

	public function advancedSearchModal() {


		// TODO: optimize this
		$directSearch = $this->doctrine->em->getRepository("Entity\Widget")->findBy(["directSearch"=>true]);

		$widgetArray = array();
		foreach($directSearch as $widget) {
			if($this->instance->getTemplates()->contains($widget->getTemplate())) {
				$widgetArray[$widget->getFieldTitle()] = ["label"=>$widget->getLabel(), "template"=>$widget->getTemplate()->getId()];
			}
		}

		$allowedCollections = array();
		if($this->user_model) {
			$allowedCollections = $this->user_model->getAllowedCollections(PERM_SEARCH);
		}

		$this->load->view("advanced_modal", ["collections"=>$this->instance->getCollections(),"allowedCollections"=>$allowedCollections, "searchableWidgets"=>$widgetArray]);


	}

	public function getFieldInfo() {
		$field = $this->input->post("fieldTitle");
		$templateId = $this->input->post("template");
		$template = $this->asset_template->getTemplate($templateId);

		$returnInfo = array();

		if(!isset($template->widgetArray[$field])) {
			echo json_encode($returnInfo);
			return;
		}

		$widget = $template->widgetArray[$field];

		// right now we only special case select fields, everything else is plaintext. Longer term, if adavnced search is widely used,
		// we could essentially reuse the curation views here
		//
		if(get_class($widget) == "Select") {
			$returnInfo['type'] = "select";
			$returnInfo['values'] = $widget->parsedFieldData["selectGroup"];
		}
		else {
			$returnInfo['type'] = "text";
		}

		echo json_encode($returnInfo);




	}

	public function nearbyAssets($latitude, $longitude) {
		if(!$latitude || !$longitude) {
			instance_redirect("/search");
		}

		$searchArray["searchText"] = "";
		$searchArray["latitude"] = $latitude;
		$searchArray["longitude"] = $longitude;
		$searchArray["distance"] = "100"; // miles
		if(count($searchArray) == 0) {
			return;
		}

		$searchArchive = new Entity\SearchEntry;
		$searchArchive->setUser($this->user_model->user);
		$searchArchive->setInstance($this->instance);
		$searchArchive->setSearchText($searchArray['searchText']);
		$searchArchive->setSearchData($searchArray);
		$searchArchive->setCreatedAt(new DateTime());
		$searchArchive->setUserInitiated(false);

		$this->doctrine->em->persist($searchArchive);
		$this->doctrine->em->flush();
		$this->searchId = $searchArchive->getId();

		instance_redirect("search#".$this->searchId);
	}

	public function querySearch($searchString = null) {
		if(!$searchString) {
			instance_redirect("/search");
		}

		$searchArray["searchText"] = rawurldecode($searchString);
		$searchArchive = new Entity\SearchEntry;
		$searchArchive->setUser($this->user_model->user);
		$searchArchive->setInstance($this->instance);
		$searchArchive->setSearchText($searchArray['searchText']);
		$searchArchive->setSearchData($searchArray);
		$searchArchive->setCreatedAt(new DateTime());
		$searchArchive->setUserInitiated(false);

		$this->doctrine->em->persist($searchArchive);
		$this->doctrine->em->flush();
		$this->searchId = $searchArchive->getId();
		instance_redirect("search#".$this->searchId);
	}

	public function listCollections() {

		$accessLevel = $this->user_model->getAccessLevel("instance",$this->instance);

		if($accessLevel < PERM_SEARCH) {
			$allowedCollections = $this->user_model->getAllowedCollections(PERM_SEARCH);
			if(count($allowedCollections) == 0) {
				instance_redirect("errorHandler/error/noPermission");
			}
			$collections = $this->instance->getCollectionsWithoutParent();

			$collections = array_intersect($collections, $allowedCollections);

		}
		else {
			$collections = $this->instance->getCollectionsWithoutParent();
		}


		$this->template->loadJavascript(["assets/js/templateSearch"]);
		$this->template->content->view("listCollections", ["collections"=>$collections]);
		$this->template->publish();

	}

	public function autocomplete() {
		$searchTerm = $this->input->post("searchTerm");
		$fieldTitle = $this->input->post("fieldTitle");
		$templateId = $this->input->post("templateId");

		$this->load->model("search_model");
		$resultArray = $this->search_model->autocompleteResults($searchTerm, $fieldTitle, $templateId);

		echo json_encode(array_values($resultArray), true);
	}

	public function getSuggestion() {
		$searchTerm = $this->input->post("searchTerm");

		$this->load->model("search_model");
		$resultArray = $this->search_model->getSuggestions($searchTerm);

		$output = array();

		if(isset($resultArray["suggestion-finder"]) && count($resultArray["suggestion-finder"])>0) {
			foreach($resultArray["suggestion-finder"] as $entry) {
				if(count($entry["options"])>0) {
					if($entry["options"][0]["score"]>= 0.8) {
						$output[$entry["text"]] = $entry["options"][0]["text"];
					}
				}

			}

		}
		echo json_encode($output);

	}

	public function getHighlight() {
		$searchId = $this->input->post("searchId");
		$objectId = $this->input->post("objectId");
		$this->load->model("search_model");


		$searchArchiveEntry = $this->doctrine->em->find('Entity\SearchEntry', $searchId);
		$searchArray = $searchArchiveEntry->getSearchData();

		$searchArray['highlightForObject'] = $objectId;

		$matchArray = $this->search_model->find($searchArray, true);

		$highlightArray = array();

		if(isset($matchArray['highlight'])) {

			foreach($matchArray['highlight'] as $entry) {
				if(is_array($entry)) {
					foreach ($entry as $individualEntry) {
						// we know the last thing in a relevant highlight will be the object id we need
						// if we end up with other cruft here, it doesn't matter - the client will discard it.

						$potentialObject = substr($individualEntry, strrpos($individualEntry, ' ') + 1);
						if(strlen($potentialObject) == 24)  {
							$highlightArray[] = $potentialObject;
						}

					}
				}
			}
		}

		$returnArray = array();

		if(count($highlightArray)>1) {
			// for now, return the first - we should look at scores and stuff and return the best.  But we don't really understand elastic and this seems like a mess.
			$returnArray = [$highlightArray[0]];
		}
		elseif(count($highlightArray)>0) {
			$returnArray = [$highlightArray[0]];
		}



		echo json_encode($returnArray);

	}


	public function searchResults($searchId=null, $page=0, $loadAll = false) {

		$accessLevel = $this->user_model->getAccessLevel("instance",$this->instance);

		$loadAll = ($loadAll == "true")?true:false;

		if($this->input->post("searchQuery")) {
			$searchArray = json_decode($this->input->post("searchQuery"), true);
		}

		$allowedCollectionsIds = null;
		if($accessLevel < PERM_SEARCH) {
			if($this->user_model) {
				$allowedCollections = $this->user_model->getAllowedCollections(PERM_SEARCH);
				if(count($allowedCollections>0)) {
					$allowedCollectionsIds = array_map(function($n) { return $n->getId(); }, $allowedCollections);
				}
				else {
					instance_redirect("errorHandler/error/noPermission");
				}
			}
			else {
				instance_redirect("errorHandler/error/noPermission");
			}
		}

		if($this->input->post("page")) {
			$page = $this->input->post("page");
		}
		if($this->input->post("loadAll")) {
			$loadAll = ($this->input->post("loadAll")=="true")?true:false;
		}

		if($this->input->post("templateId")) {
			$searchArray['templateId'] = $this->input->post("templateId");
		}




		/**
		 * if they've set "any" (0) for collection specific search, disregard
		 */
		if(isset($searchArray["collection"])) {
			foreach($searchArray["collection"] as $collection) {
				if($collection == 0) {
					unset($searchArray["collection"]);
				}
			}
		}


		if(isset($searchArray["specificSearchText"])) {

			$searchFieldArray = $searchArray["specificSearchField"];
			$searchFieldTextArray = $searchArray["specificSearchText"];
			$searchFieldFuzzyArray = $searchArray["specificSearchFuzzy"];

			$specificSearchArray = array();
			for($i=0; $i<count($searchFieldArray); $i++) {
				if(strlen($searchFieldTextArray[$i]) == 0) {
					continue;
				}
				$specificSearchArray[] = ["field"=>$searchFieldArray[$i], "text"=>$searchFieldTextArray[$i], "fuzzy"=>($searchFieldFuzzyArray[$i]==1)?true:false];
			}
			$searchArray["specificFieldSearch"] = $specificSearchArray;
		}



		// this finds assets that point to the objectId passed in teh search term
		if($this->input->post("searchRelated") && $this->input->post("searchRelated") == true) {

			$searchArray["fuzzySearch"] = false;
			$searchArray["crossFieldOr"] = true;
			$assetModel = new Asset_model;
			$assetModel->loadAssetById($searchArray['searchText']);
			$relatedAssets = $assetModel->getAllWithinAsset("Related_asset", null, 1);
			$objectIdArray = array();
			foreach($relatedAssets as $relatedAsset) {
			 	foreach($relatedAsset->fieldContentsArray as $fieldContents) {
			 		$objectIdArray[] = $fieldContents->targetAssetId;
			 	}
			}

			$objectIdArray[] = $searchArray['searchText'];
			$searchArray['searchText'] = join(" " ,$objectIdArray);

		}

		$showHidden = false;

		if($searchId) {
			$this->searchId = $searchId;
			if(!isset($searchArray)) {
				$searchArchiveEntry = $this->doctrine->em->find('Entity\SearchEntry', $searchId);
				$searchArray = $searchArchiveEntry->getSearchData();
			}
		}


		if($this->input->post("showHidden") || isset($searchArray['showHidden'])) {
			// This will include items that are not yet flagged "Ready for display"
			$showHidden = true;
			$searchArray['showHidden'] = true;
		}


		if(count($searchArray) == 0) {
			return;
		}

		if($allowedCollectionsIds) {
			// this user has restricted permissions, lock their results down.
			$searchArray["collection"] = $allowedCollectionsIds;
		}

		$searchArray["searchDate"] = new \DateTime("now");


		if($searchId) {
			$searchArchive = $this->doctrine->em->find('Entity\SearchEntry', $searchId);
		}
		else {
			$searchArchive = new Entity\SearchEntry;
		}

		$searchArchive->setUser($this->user_model->user);
		$searchArchive->setInstance($this->instance);
		$searchArchive->setSearchText($searchArray['searchText']);
		$searchArchive->setSearchData($searchArray);
		$searchArchive->setCreatedAt(new DateTime());
		$searchArchive->setUserInitiated(false);
		if(!$this->input->post("suppressRecent")) {
			$searchArchive->setUserInitiated(true);
		}

		$this->doctrine->em->persist($searchArchive);
		$this->doctrine->em->flush();

		$this->searchId = $searchArchive->getId();

		if($this->input->post("storeOnly") == true) {

			echo json_encode(["success"=>true, "searchId"=>$this->searchId]);
			return;
		}

		$this->load->model("search_model");

		$matchArray = $this->search_model->find($searchArray, !$showHidden, $page, $loadAll);
		if(count($matchArray["searchResults"]) == 0) {
			// let's try again with fuzzyness
			$searchArray['matchType'] = "phrase_prefix"; // this is a leaky abstraction. But the whole thing is really.
			$matchArray = $this->search_model->find($searchArray, !$showHidden, $page, $loadAll);
		}
		$matchArray["searchId"] = $this->searchId;

		echo json_encode($this->search_model->processSearchResults($searchArray, $matchArray));


	}


	public function searchList() {
		$accessLevel = $this->user_model->getAccessLevel("instance",$this->instance);

		if($accessLevel < PERM_ADDASSETS) {
			instance_redirect("errorHandler/error/noPermission");
		}
		$this->template->loadJavascript(["assets/js/templateSearch"]);


		$customSearches = $this->doctrine->em->getRepository("Entity\CustomSearch")->findBy(["instance"=>$this->instance, "user"=>$this->user_model->user]);;

		$this->template->content->view("customSearch/customSearchList", ["searches"=>$customSearches]);
		$this->template->publish();

	}


	public function getTemplates() {
		$accessLevel = $this->user_model->getAccessLevel("instance",$this->instance);

		if($accessLevel < PERM_ADDASSETS) {
			instance_redirect("errorHandler/error/noPermission");
		}

		$templateArray = array();
		$templateArray[] = "";
		foreach($this->instance->getTemplates() as $template) {

			$templateArray[$template->getId()] = $template->getName();

		}
		echo json_encode($templateArray);

	}

	public function getFields($templateId=null) {
		$accessLevel = $this->user_model->getAccessLevel("instance",$this->instance);

		if($accessLevel < PERM_ADDASSETS) {
			instance_redirect("errorHandler/error/noPermission");
		}

		if(!$templateId) {
			return;
		}

		$template = $this->asset_template->getTemplate($templateId);


		$widgetArray = array();
		$widgetArray[] = "";
		foreach($template->widgetArray as $widget) {

			$widgetArray[$widget->getFieldTitle()] = $widget->getLabel();

		}
		echo json_encode($widgetArray);

	}



	public function searchBuilder($customSearchId=null) {
		$this->template->loadJavascript(["customSearch"]);
		$accessLevel = $this->user_model->getAccessLevel("instance",$this->instance);

		if($accessLevel < PERM_ADDASSETS) {
			instance_redirect("errorHandler/error/noPermission");
		}
		$this->template->loadJavascript(["templateSearch"]);

		if(!$customSearchId) {
			$customSearch = new Entity\CustomSearch;
		}
		else {
			$customSearch = $this->doctrine->em->getRepository("Entity\CustomSearch")->find($customSearchId);
		}


		$searchParameters = $customSearch->getSearchConfig();
		if(!$searchParameters) {
			$searchParameters = "{}";
		}
		$searchTitle = $customSearch->getSearchTitle();

		$decodedParams = json_decode($searchParameters);

		$showHidden = null;
		if(isset($decodedParams->showHidden)) {
			$showHidden = $decodedParams->showHidden?"CHECKED":null;
		}
		$template = null;
		if(isset($decodedParams->templateId)) {
			$template = $decodedParams->templateId;
		}

		$this->template->content->view("customSearch/customSearchViewer", ["showHidden"=>$showHidden, "targetTemplate"=>$template, "searchData"=>$searchParameters, "searchTitle"=>$searchTitle, "searchId"=>$customSearchId]);
		$this->template->publish();

	}

	public function saveSearch() {
		$accessLevel = $this->user_model->getAccessLevel("instance",$this->instance);

		if($accessLevel < PERM_ADDASSETS) {
			instance_redirect("errorHandler/error/noPermission");
		}

		$customSearchId = $this->input->post("customSearchId");

		if(!is_numeric($customSearchId)) {
			$customSearch = new Entity\CustomSearch;
			$customSearch->setUser($this->user_model->user);
			$customSearch->setInstance($this->instance);
		}
		else {
			$customSearch = $this->doctrine->em->getRepository("Entity\CustomSearch")->find($customSearchId);
		}

		$customSearch->setSearchConfig(json_encode($this->input->post("searchData")));
		$customSearch->setSearchTitle($this->input->post("searchTitle"));
		$this->doctrine->em->persist($customSearch);
		$this->doctrine->em->flush();
		echo $customSearch->getId();

	}

	public function deleteSearch($customSearchId) {

		$customSearch = $this->doctrine->em->getRepository("Entity\CustomSearch")->findOneBy(["id"=>$customSearchId, "user"=>$this->user_model->user]);

		if($customSearch) {
			$this->doctrine->em->remove($customSearch);
			$this->doctrine->em->flush();
		}
		instance_redirect("search/searchList");

	}



}

/* End of file search.php */
/* Location: ./application/controllers/search.php */
