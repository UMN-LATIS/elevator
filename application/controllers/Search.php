<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class Search extends Instance_Controller {

	private $searchId = null;

	public function __construct()
	{
		parent::__construct();
		$this->load->model("asset_model");

		$jsLoadArray = ["handlebars-v1.1.2", "mapWidget","drawers", "jquery.fullscreen-0.4.1", "loadDrawer"];
		$this->template->loadJavascript($jsLoadArray);

		$this->template->content->view("drawers/drawerModal");
	}

	public function index($searchId = null)
	{
		if ($this->isUsingVueUI()) {
			return $this->template->publish('vueTemplate');
		}

		if(!$searchId) {
			instance_redirect("/");
		}

		$accessLevel = $this->user_model->getAccessLevel("instance",$this->instance);

		if($accessLevel < PERM_SEARCH) {
			if($this->user_model) {
				$allowedCollections = $this->user_model->getAllowedCollections(PERM_SEARCH);
				if(count($allowedCollections) == 0) {
					$this->errorhandler_helper->callError("noPermission");
				}
			}
			else {
				$this->errorhandler_helper->callError("noPermission");
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

		$sortArray = $this->buildSortStructure();

		$this->template->javascript->add("/assets/TimelineJS3/compiled/js/timeline.js");
		$this->template->javascript->add("/assets/js/sly.min.js");
		$this->template->stylesheet->add("/assets/TimelineJS3/compiled/css/timeline.css");
		$this->template->loadJavascript($jsLoadArray);
		$this->template->addToDrawer->view("drawers/add_to_drawer");
		$this->template->content->view("search/search", ["sortArray" => $sortArray]);
		$this->template->publish();
	}

	function s($args = null){
		if($args == null) {
			$this->errorhandler_helper->callError("badSearch");
		}
		$this->index($args);
	}



	public function map() {
		if ($this->isUsingVueUI()) {
			return $this->template->publish('vueTemplate');
		}
		$this->generateEmbed("map");
	}

	public function timeline() {
		if ($this->isUsingVueUI()) {
			return $this->template->publish('vueTemplate');
		}

		$this->template->javascript->add("/assets/TimelineJS3/compiled/js/timeline.js");
		$this->template->stylesheet->add("/assets/TimelineJS3/compiled/css/timeline.css");

		$this->generateEmbed("timeline");
	}

	public function gallery() {
		if ($this->isUsingVueUI()) {
			return $this->template->publish('vueTemplate');
		}

		$this->template->javascript->add("/assets/js/sly.min.js");
		$this->generateEmbed("gallery");
	}


	private function generateEmbed($type) {
		$accessLevel = $this->user_model->getAccessLevel("instance",$this->instance);

		if($accessLevel < PERM_SEARCH) {
			if($this->user_model) {
				$allowedCollections = $this->user_model->getAllowedCollections(PERM_SEARCH);
				if(count($allowedCollections) == 0) {
					$this->errorhandler_helper->callError("noPermission");
				}
			}
			else {
				$this->errorhandler_helper->callError("noPermission");
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
		$this->template->javascript->add('/assets/leaflet/leaflet.js');
		$this->template->javascript->add('/assets/leaflet/Leaflet.fullscreen.min.js');
		$this->template->javascript->add('/assets/leaflet/leaflet-measure.min.js');
		$this->template->javascript->add('/assets/leaflet/Control.MiniMap.min.js');
		$this->template->javascript->add('/assets/leaflet/esri-leaflet.js');
		$this->template->javascript->add('/assets/leaflet/leaflet.markercluster.js');
		$this->template->javascript->add('/assets/leaflet/L.Control.Locate.min.js');
		$this->template->set_template("noTemplate");
		$this->template->loadJavascript($jsLoadArray);

		$this->template->content->view("search/" . $type . "Only");
		$this->template->publish();

	}

	public function advancedSearchModal() {

		$this->template->javascript->set(null);

		$this->template->javascript->add("//maps.google.com/maps/api/js?key=". $this->config->item("googleApi") ."&sensor=false");
		$jsLoadArray = ["handlebars-v1.1.2", "mapWidget","drawers"];
		$this->template->loadJavascript($jsLoadArray);		
		// TODO: optimize this
		$directSearch = $this->doctrine->em->getRepository("Entity\Widget")->findBy(["directSearch"=>true]);

		$widgetArray = array();
		foreach($directSearch as $widget) {
			if($this->instance->getTemplates()->contains($widget->getTemplate())) {
				$widgetArray[$widget->getFieldTitle()] = ["label"=>$widget->getLabel(), "template"=>$widget->getTemplate()->getId()];
			}
		}

		uasort($widgetArray, function($a, $b) {
			return strcmp($a["label"], $b["label"]);
		});
		$allowedCollections = array();
		if($this->user_model) {
			$allowedCollections = $this->user_model->getAllowedCollections(PERM_SEARCH);
		}

		$this->load->view("modals/advanced_modal", ["collections"=>$this->instance->getCollections(),"allowedCollections"=>$allowedCollections, "searchableWidgets"=>$widgetArray]);


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
		else if(get_class($widget) == "Checkbox") {
			$returnInfo['type'] = "select";
			$returnInfo['values'] = ["boolean_false"=>"Unchecked", "boolean_true"=>"Checked"];
		}
		else if(get_class($widget) == "Tags") {
			// generate taglist here
			$this->load->model("search_model");
			$tags = $this->search_model->getAggregatedTags($field . ".raw");
			if(count($tags) > 0) {
				$returnInfo['type'] = "tag";
				$returnInfo['values'] = $tags;
			}
			else {
				$returnInfo['type'] = "text";
			}
			
		}
		else if(get_class($widget) == "Multiselect") {
			$this->load->helper("multiselect");
			$returnInfo['type'] = "multiselect";
			$returnInfo['values'] = array();
			$randomID = rand(1,100000);
			$returnInfo['renderContent'] = "<div id='cascade" . $randomID . "' class='multiselectGroup'>" . $this->load->view("widget_form_partials/multiselect_inner", ["widgetFieldData"=>$widget->getFieldData(), "formFieldName"=>"specificSearchText[]", "formFieldId"=>"cascade" . $randomID], true) . "</div>";
			$returnInfo['rawContent'] = $widget->getFieldData();
		}
		else {
			$returnInfo['type'] = "text";
		}

		return render_json($returnInfo);




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

		instance_redirect("search/s/".$this->searchId);
	}

	public function querySearch($searchString = null, $shouldReturnJson = false) {
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

		if ($this->isUsingVueUI() && $shouldReturnJson) {
			return render_json(["searchId" => $this->searchId]);
		}

		instance_redirect("search/s/".$this->searchId);
	}

	public function scopedQuerySearch($fieldName, $searchString = null, $shouldReturnJson = false) {
		if(!$searchString) {
			instance_redirect("/search");
		}


		if($fieldName == "template") {
			$searchArray["searchText"] = "";
			$searchArray["templateId"] = [$searchString];
		}
		else {
			$searchArray["searchText"] = "";
			$searchArray["specificSearchField"] = [$fieldName];
			$searchArray["specificSearchText"] = [rawurldecode($searchString)];
			$searchArray["specificFieldSearch"] = [["field"=>$fieldName, "text"=>rawurldecode($searchString), "fuzzy"=>false]];
			
			// elastic8 requires that we use range queries to search for content in "long" fields
			// right now only csvBatch falls into that category. If we need to add more, we should so something more clever with this
			if($fieldName == "csvBatch") {
				$searchArray["specificFieldSearch"][0]["numeric"] = true;
			}

			$searchArray["sort"] = "title.raw";
		}

		$searchArchive = new Entity\SearchEntry;
		$searchArchive->setUser($this->user_model->user);
		$searchArchive->setInstance($this->instance);
		$searchArchive->setSearchText("");
		$searchArchive->setSearchData($searchArray);
		$searchArchive->setCreatedAt(new DateTime());
		$searchArchive->setUserInitiated(false);

		$this->doctrine->em->persist($searchArchive);
		$this->doctrine->em->flush();
		$this->searchId = $searchArchive->getId();

		if ($this->isUsingVueUI() && $shouldReturnJson) {
			return render_json(["searchId" => $this->searchId]);
		}

		instance_redirect("search/s/".$this->searchId);
	}

	public function test() {
		$allowedCollectionsObject = $this->user_model->getAllowedCollections(PERM_ADDASSETS);
		$allowedCollections = array();
		foreach($allowedCollectionsObject as $collection) {
			$allowedCollections[$collection->getId()] = $collection->getTitle();
		}

		if(strlen($this->template->collectionId)>0) {
			$collectionId = intval($this->template->collectionId->__toString());
		}

		$collections = $this->buildCollectionArray($this->instance->getCollectionsWithoutParent());

		echo "<select>";
		echo $this->load->view("collection_select_partial", ["selectCollection"=>0, "collections"=>$this->instance->getCollectionsWithoutParent(), "allowedCollections"=>$allowedCollections],true);
		echo "</select>";
	}

	public function buildCollectionArray($collections) {

		$collectionReturn = array();
		foreach($collections as $collection) {
			if(!$collection->hasChildren()) {
				$collectionReturn[$collection->getId()] = $collection->getTitle();
			}
			else {
				$collectionReturn[$collection->getId()] = [$collection->getTitle() => $this->buildCollectionArray($collection->getChildren())];
			}

			
		}
		return $collectionReturn;

	}


	public function listCollections() {

		if ($this->isUsingVueUI()) {
			$this->template->set_template("vueTemplate");
			$this->template->publish();
			return;
		}

		$pages = $this->doctrine->em->getRepository("Entity\InstancePage")->findBy(["instance"=>$this->instance, "title"=>"Collection Page"]);
		$collectionText = null;
		if($pages) {
			$firstPage = current($pages);
			$collectionText = $firstPage->getBody();
		}

		$jsLoadArray[] = "templateSearch";
		$this->template->loadJavascript($jsLoadArray);

		$collections = $this->collection_model->getUserCollections();
		$this->buildPreviews($collections);

		$collections = array_values($collections);  // rekey array
		
		$this->template->loadJavascript(["templateSearch"]);
		$this->template->content->view("listCollections", ["collections"=>$collections, "collectionText"=>$collectionText]);
		$this->template->publish();

	}

	public function buildPreviews(&$collectionArray) {
		foreach($collectionArray as $key=>$collection) {
			if(!$collection->getShowInBrowse()) {
				unset($collectionArray[$key]);
			}
			else {
				if($collection->getPreviewImage() !== null && $collection->getPreviewImage() !== "") {
					$fileObject = $this->filehandler_router->getHandledObject($collection->getPreviewImage());
					if($fileObject) {
						$collection->previewImageHandler = $fileObject;
					}
					
				}
				else {
					$collection->previewImageHandler = null;
				}
				if($collection->hasChildren()) {
					$children = $collection->getChildren();
					$this->buildPreviews($children);
				}
			}
		}


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
		$suggestTerm = array();
		
		if(isset($resultArray["suggest"]) && isset($resultArray["suggest"]["suggestion-finder"]) && count($resultArray["suggest"]["suggestion-finder"])>0) {
			foreach($resultArray["suggest"]["suggestion-finder"] as $entry) {
				if(count($entry["options"])>0) {
					if($entry["options"][0]["score"]>= 0.8) {
						$suggestTerm[$entry["text"]]= $entry["options"][0]["text"];
					}
				}

			}
		}


		foreach($suggestTerm as $key=>$value) {
			$validTerm = false;
			$result = $this->search_model->find(["searchText"=>$value],true);
			if($result['totalResults'] > 0) {
				$validTerm = true;
			}


			if($validTerm) {
				$output[$key] = $value;
			}
		}
		
		return render_json($output);

	}

	public function getHighlight() {
		$searchId = $this->input->post("searchId");
		$objectId = $this->input->post("objectId");
		$this->load->model("search_model");
		if(!$searchId || strlen($searchId) < 10) {
			return json_encode([]);
		}

		if(!$objectId || strlen($objectId) < 10) {
			return json_encode([]);
		}


		// $searchId = "7f53eb65-316b-4faa-be0b-c288c7c31d74";
		// $objectId = "585d3408ba98a8f9404059c2";

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
		else if($this->input->post("searchText") !== NULL) { // direct search form
				$searchArray = array();
				$searchArray["searchText"] = $this->input->post("searchText");

				if($this->input->post("collectionId")) {
					$searchArray["collection"] = [$this->input->post("collectionId")];
				}
		}

		$allowedCollectionsIds = null;
		if($accessLevel < PERM_SEARCH) {
			if($this->user_model) {
				$allowedCollections = $this->user_model->getAllowedCollections(PERM_SEARCH);
				if(count($allowedCollections)>0) {
					$allowedCollectionsIds = array_map(function($n) { return $n->getId(); }, $allowedCollections);
				}
				else {
					$this->errorhandler_helper->callError("noPermission");
				}
			}
			else {
				$this->errorhandler_helper->callError("noPermission");
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

		if($this->input->post("fileTypesSearch")) {
			$searchArray["fileTypesSearch"] = $this->input->post("fileTypesSearch");
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

		/**
		 * if they've set "any" (0) for template specific search, disregard
		 */
		if(isset($searchArray["templateId"]) && is_array($searchArray["templateId"])) {
			foreach($searchArray["templateId"] as $templateId) {
				if($templateId == 0) {
					unset($searchArray["templateId"]);
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

		if($searchId && strlen($searchId) < 10) {
			// this is an invalid search id. 
			$searchId = null;
		}

		if($searchId && strlen($searchId) > 10) {
			$this->searchId = $searchId;
			if(!isset($searchArray)) {
				$searchArchiveEntry = $this->doctrine->em->find('Entity\SearchEntry', $searchId);
				$searchArray = $searchArchiveEntry->getSearchData();
			}
	
		}


		if($this->input->post("showHidden") || (isset($searchArray['showHidden']) && $searchArray['showHidden'] != false)) {
			// This will include items that are not yet flagged "Ready for display"
			$showHidden = true;
			$searchArray['showHidden'] = true;
		}


		if(!isset($searchArray) || !is_countable($searchArray) || count($searchArray) == 0) {
			return;
		}

		if($allowedCollectionsIds) {
			// this user has restricted permissions, lock their results down.
			if(!isset($searchArray['collection']) || (isset($searchArray['collection']) && array_diff($searchArray['collection'],$allowedCollectionsIds))) {
				$searchArray["collection"] = $allowedCollectionsIds;	
			}
		}

		$haveSort = false;
		if(isset($searchArray["sort"]) && $searchArray["sort"] != "0") {
			$haveSort = true;
		}

		$haveSearchText = false;
		if(!isset($searchText) && strlen(trim($searchArray["searchText"])) > 0) {
			$haveSearchText = true;
		}

		$haveSpecificSearch = false;
		if(isset($searchArray['specificFieldSearch']) && count($searchArray['specificFieldSearch']) > 0) {
			$haveSpecificSearch = true;
		}

		if(!$haveSort && !$haveSearchText && !$haveSpecificSearch) {
			$searchArray["sort"] = "title.raw";
		}

		$searchArray["searchDate"] = new \DateTime("now");


		if($searchId) {
			$searchArchive = $this->doctrine->em->find('Entity\SearchEntry', $searchId);
		}
		else {
			// change we're making on aug 11: don't update existing search entries.
			// this seems like it'll fix bugs with timeline/map/gallery embeds triggering
			// database updates that we don't want.
			$searchArchive = new Entity\SearchEntry;
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
		}

		

		$this->searchId = $searchArchive->getId();

		if($this->input->post("storeOnly") == true) {

			return render_json(["success"=>true, "searchId"=>$this->searchId]);
		}

		if($this->input->post("redirectSearch") == true) {
			instance_redirect("search/s/".$this->searchId);
			return;
		}


		$this->load->model("search_model");
		
		$matchArray = $this->search_model->find($searchArray, !$showHidden, $page, $loadAll);
		if(count($matchArray["searchResults"]) == 0) {
			// let's try again with fuzzyness
			$searchArray['matchType'] = "phrase_prefix"; // this is a leaky abstraction. But the whole thing is really.
			$matchArray = $this->search_model->find($searchArray, !$showHidden, $page, $loadAll);
		}

		// filter out the objectIds we passed in when doing a searchRelated
		if($this->input->post("searchRelated") && $this->input->post("searchRelated") == true) {
			foreach($matchArray["searchResults"] as $key=>$objectId) {
				if(in_array($objectId, $objectIdArray)) {
					unset($matchArray["searchResults"][$key]);
				}
			}
			$matchArray["searchResults"] = array_values($matchArray["searchResults"]); // reindex
		}

		$matchArray["searchId"] = $this->searchId;
		$matchArray["sortableWidgets"] = $this->buildSortStructure();
		return render_json($this->search_model->processSearchResults($searchArray, $matchArray));


	}

	public function getResult($direction, $searchId, $objectId) {

		$searchArchiveEntry = $this->doctrine->em->find('Entity\SearchEntry', $searchId);
		if(!$searchArchiveEntry) {
			echo "Invalid URL";
			return;
		}
		$searchArray = $searchArchiveEntry->getSearchData();
		$this->load->model("search_model");
		$page=0;
		$showHidden = false;

		$matchArray = $this->search_model->find($searchArray, !$showHidden, $page, true);

		if(count($matchArray["searchResults"]) == 0) {
			echo "Invalid Search";
			return;
		}
		$target = null;

		for($i =0; $i<count($matchArray["searchResults"]); $i++) {
			if($matchArray["searchResults"][$i] == $objectId) {
				if($direction == "next") {
					if($i !== (count($matchArray["searchResults"]) -1)) {
						$target = $matchArray["searchResults"][$i+1];
					}
					else {
						$target = $matchArray["searchResults"][0];
					}
				}
				else if($direction == "previous") {
					if($i !== 0) {
						$target = $matchArray["searchResults"][$i-1];
					}
					else {
						$target = $matchArray["searchResults"][count($matchArray["searchResults"])-1];
					}
				}
			}
		}
		if($target) {
			return render_json(["status" => "found", "targetId" => $target]);
		}
		else {
			return render_json(["status" => "notfound", "search" => $searchId]);
		}
		
		
	}


	public function searchList() {
		$accessLevel = $this->user_model->getAccessLevel("instance",$this->instance);

		if($accessLevel < PERM_ADDASSETS) {
			$this->errorhandler_helper->callError("noPermission");
		}
		$this->template->loadJavascript(["assets/js/templateSearch"]);


		$customSearches = $this->doctrine->em->getRepository("Entity\CustomSearch")->findBy(["instance"=>$this->instance, "user"=>$this->user_model->user]);;

		$this->template->content->view("customSearch/customSearchList", ["searches"=>$customSearches]);
		$this->template->publish();

	}


	public function getTemplates() {
		$accessLevel = $this->user_model->getAccessLevel("instance",$this->instance);

		if($accessLevel < PERM_ADDASSETS) {
			$this->errorhandler_helper->callError("noPermission");
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
			$this->errorhandler_helper->callError("noPermission");
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
			$this->errorhandler_helper->callError("noPermission");
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
			$this->errorhandler_helper->callError("noPermission");
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

	private function buildSortStructure() {
		if ($this->config->item('enableCaching')) {
			$this->doctrineCache->setNamespace('sortCache_');
			if ($storedObject = $this->doctrineCache->fetch($this->instance->getId())) {
				return $storedObject;
			}
		}
		$directSearch = $this->doctrine->em->getRepository("Entity\Widget")->findBy(["directSearch" => true]);
		$widgetArray = array();
		foreach ($directSearch as $widget) {
			if ($this->instance->getTemplates()->contains($widget->getTemplate())) {
				$widgetArray[$widget->getFieldTitle()] = ["label" => $widget->getLabel(), "template" => $widget->getTemplate()->getId(), "type" => $widget->getFieldType()->getName()];
			}
		}

		uasort($widgetArray, function ($a, $b) {
			return strcmp($a["label"], $b["label"]);
		});

		$formattedReturnArray = array();
		$formattedReturnArray["0"] = "Best Match";
		$formattedReturnArray["lastModified.desc"] = "Modified Date (newest to oldest)";
		$formattedReturnArray["lastModified.asc"] = "Modified Date (oldest to newest)";
		$formattedReturnArray["title.raw"] = "Default Title";
		$formattedReturnArray["collection"] = "Collection";
		if ($this->instance->getShowTemplateInSearchResults()) {
			$formattedReturnArray["template"] = "Template";
		}
		foreach ($widgetArray as $title => $values) {
			if ($values["type"] == "date") {
				$formattedReturnArray["dateCache.startDate.desc"] = $values["label"] . " (newest to oldest)";
				$formattedReturnArray["dateCache.startDate.asc"] = $values["label"] . " (oldest to newest)";
			} else {
				$formattedReturnArray[$title . ".raw"] = $values["label"];
			}
		}

		if ($this->config->item('enableCaching')) {
			$this->doctrineCache->setNamespace('sortCache_');
			$this->doctrineCache->save($this->instance->getId(), $formattedReturnArray, 14400);
		}

		return $formattedReturnArray;
	}

}

/* End of file search.php */
/* Location: ./application/controllers/search.php */
