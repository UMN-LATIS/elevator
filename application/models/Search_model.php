<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class search_model extends CI_Model {

	private $es = NULL;
	public $showHidden = false;
	public $pageLength = 30;
	public $loadAllLength = 1000;
	public $bulkUpdates = ['body'=>[]];

	public function __construct()
	{
		parent::__construct();
		$params= array (
    		$this->config->item('elastic'));
		$this->es = Elastic\Elasticsearch\ClientBuilder::create()->setHosts($params)->build();

	}

	public function remove($asset) {
		$params['index'] = $this->config->item('elasticIndex');
    	$params['id']    = $asset->getObjectId();
    	if(!$params['id'] || strlen($params['id']<5)) {
    		// if you don't pass an id, elasticsearch will eat all your data
    		return;
    	}
    	try {
    		$ret = $this->es->delete($params);
    		return $ret;
    	}
    	catch (Exception $e) {

    	}


	}

	public function wipeIndex() {
		$deleteParams['index'] = $this->config->item('elasticIndex');
		try {
			$this->es->indices()->delete($deleteParams);
		}
		catch (Exception $e) {
			$this->logging->logError("admin","wipeindex", "error deletig index" . json_encode($e));
		}

		$indexParams['index'] = $this->config->item('elasticIndex');
		try {
			$this->es->indices()->create($indexParams);
		}
		catch (Exception $e) {
			$this->logging->logError("admin","wipeindex", "error creating index" . json_encode($e));
		}

	}

	public function addOrUpdate($asset, $bulk = false) {

		if(!$asset->assetTemplate || !$asset->assetTemplate->getIndexForSearching()) {
			return;
		}
		$this->asset_model->enableObjectCache();

		/* HACK
		* for now, make sure we have a mapping each time we add a record
		*/
		// $params['index'] = $this->config->item('elasticIndex');
		// $params['type']  = 'asset';

		// $myTypeMapping2 = array(
		// 	'_source' => array(
		// 		'enabled' => true
		// 		),
		// 	'date_detection' => false,
		// 	'properties' => array(
		// 		'locationCache' => array(
		// 			'type' => 'geo_point'
		// 			)
		// 		)
		// 	);
		// $params['body']['asset'] = $myTypeMapping2;

		// Update the index mapping
		// $this->es->indices()->putMapping($params);

 		$params = array();

 		if(!is_numeric($asset->getGlobalValue("collectionId"))) {
 			// we only index things in a collection;
 			return;
 		}


    	/**
    	 * special case location data, one level deep
    	 */

    	$locations = $asset->getAllWithinAsset("Location",null, 1);
		$locationArray = array();
		if(count($locations)>0) {

			foreach($locations as $location) {
				foreach($location->fieldContentsArray as $locationContent) {
					if($locationContent->hasContents() && $location->getSearchable() && abs(floatval($locationContent->longitude) /180) <= 1 && abs(floatval($locationContent->latitude) / 90) <= 1) {
						$locationArray[] = [floatval($locationContent->longitude), floatval($locationContent->latitude)];
					}
				}
			}



		}

		/**
		 * special case date date, one level deep
		 */
		$dates = $asset->getAllWithinAsset("Date",null, 1);
		$dateArray = array();
		if(count($dates)>0) {

			foreach($dates as $date) {
				foreach($date->fieldContentsArray as $dateContent) {
					if($dateContent->hasContents() && $date->getSearchable()) {
						$tempArray=array();
						$tempArray["startDate"]=intval($dateContent->start["numeric"]);
						if($dateContent->range) {
							$tempArray["endDate"]=intval($dateContent->end["numeric"]);
						}
						else {
							$tempArray["endDate"]=-631139000000000000; // negative 20 billion years ago
						}
						$dateArray[] = $tempArray;

					}
				}
			}
			if(count($dateArray)>0) {

			}
		}

    	/**
    	 * special case upload data, no levels deep
    	 */

    	$uploads = $asset->getAllWithinAsset("Upload",null, 0);
    	$uploadContentArray = array();
    	$locationDataArray = array();
    	$dateDataArray = array();
		if(count($uploads)>0) {
			foreach($uploads as $upload) {
				if($upload->getSearchable()) {
					foreach($upload->fieldContentsArray as $uploadContent) {
						if($uploadContent->hasContents() && $uploadContent->getSearchData() != "") {
							$uploadContentArray[] = $uploadContent->getSearchData();
							$uploadContent->searchData = "";
						}
						if($uploadContent->hasContents() && $uploadContent->getLocationData() != "") {

							$locationDataArray[] = $uploadContent->getLocationData();
							$uploadContent->locationData = "";
						}
						if($uploadContent->hasContents() && $uploadContent->getDateData() != "") {
							$tempArray = array();
							$tempArray["startDate"]=intval(strtotime($uploadContent->getDateData()));
							$tempArray["endDate"]=-631139000000000000; // negative 20 billion years ago
							$dateDataArray[] = $tempArray;
							$uploadContent->dateData = "";
						}
					}
				}
			}
			if(count($uploadContentArray)>0) {


			}
			if(count($locationDataArray) > 0) {
				$locationArray = array_merge($locationArray, $locationDataArray);
			}

			if(count($dateDataArray)>0) {
				$dateArray = array_merge($dateArray, $dateDataArray);
			}

		}

    	$fileTypes = $asset->getAllWithinAsset("Upload",null, 1);
		$fileTypeArray = array();
		if(count($fileTypes)>0) {
			foreach($fileTypes as $upload) {
				foreach($upload->fieldContentsArray as $uploadContent) {
					if($uploadContent->hasContents() && $uploadContent->getFileHandler()) {
						$fileTypeArray[] = strtolower(str_ireplace("handler", "", get_class($uploadContent->getFileHandler())));
					}
				}
			}
		}

 		// only go only level deep in recursion?
		$recursiveDepth = 1;
		if( $asset->assetTemplate) {
			$recursiveDepth = $asset->assetTemplate->getRecursiveIndexDepth();
		}
		
    	$body  = $asset->getSearchEntry($recursiveDepth);

    	// strip any illegal UTF8 characters, elastic is more picky about this
    	$body = $this->cleanCharacters($body);

    	$params['body'] = $body;

		if(isset($locationArray) && count($locationArray)>0) {
			$params['body']['locationCache'] = $locationArray;
		}
		if(isset($dateArray) && count($dateArray)>0) {
			$params['body']['dateCache'] = $dateArray;
		}
		if(isset($uploadContentArray) && count($uploadContentArray)>0) {
			$params['body']['fileSearchData'] = $uploadContentArray;
		}

		if(isset($fileTypeArray) && count($fileTypeArray)> 0) {
			$params['body']['fileTypesCache'] = $fileTypeArray;
		}

		$params['body']['lastModified'] = $asset->getGlobalValue("modified")->format('Y-m-d\TH:i:s\Z');


		$params['body']['includeInSearch'] = $asset->assetTemplate->getIncludeInSearch();

		$params['body']['title'] = $asset->getAssetTitle(true);

		if(!function_exists("stripHTML")) {
			function stripHTML($n) {
				if(is_string($n)) {
					return strip_tags($n);
				}
				else {
					return $n;
				}
			}
		}
		if(!function_exists("array_map_recursive")) {
			function array_map_recursive($callback, $array)
			{
				$func = function ($item) use (&$func, &$callback) {
					return is_array($item) ? array_map($func, $item) : call_user_func($callback, $item);
				};

				return array_map($func, $array);
			}
		}

		$params['body'] = array_map_recursive("stripHTML", $params['body']);


		
    	/**
    	 * inject the assetId for searching - we could search against _id too, but that can't be
    	 * grouped with other results.  This saves us having logic to detect mongoids in the query
    	 */
    	$params['body']['assetId'] = $asset->getObjectId();
    	$params['index'] = $this->config->item('elasticIndex');
    	$params['id']    = $asset->getObjectId();

    	if($bulk) {
    		$bulkArrayEntry = [];
    		$bulkArrayEntry["index"] =  ["_index"=> $params["index"], "_id"=> $params["id"]];
    		$bulkArrayEntry["value"] = $params["body"];
    		$this->bulkUpdates['body'][] = ["index" => $bulkArrayEntry["index"]];
    		$this->bulkUpdates['body'][] = $bulkArrayEntry["value"];
    	}
    	else {
    		$ret = $this->es->index($params);

			if(!isset($ret["_id"]) || $ret["_id"] !== $asset->getObjectId()) {
    			$this->logging->logError("search error", $ret);
    		}	
    	}
    	
    	$this->asset_model->disableObjectCache();

	}

	public function flushBulkUpdates() {
		if(count($this->bulkUpdates) == 0 || count($this->bulkUpdates['body']) == 0) {
			return;
		}
		$result = $this->es->bulk($this->bulkUpdates);
		$this->bulkUpdates = ['body'=>[]];
		return true;
	}

	public function cleanCharacters($nestedArray) {
		$outputArray = array();
		foreach($nestedArray as $key=>$item) {
			if(is_array($item)) {
				$outputArray[$key] = $this->cleanCharacters($item);
			}
			elseif(is_string($item)) {
				$outputArray[$key] = mb_convert_encoding($item, 'UTF-8', 'UTF-8');
			}
			else {
				$outputArray[$key] = $item;
			}
		}
		return $outputArray;
	}

	public function find($searchArray, $readyforDisplayOnly=true, $pageStart=0, $loadAll=false) {

 		$searchParams['index'] = $this->config->item('elasticIndex');
    	//$searchParams['type']  = 'asset';

    	$filter = array();

		$collections = array();
		if(isset($searchArray["collection"]) && is_array($searchArray["collection"])) {
			foreach($searchArray['collection'] as $collection) {
				// when we have nested collections selected, we need to flatten them all.
				$loadedCollection = $this->collection_model->getCollection($collection);
				$collections[] = $loadedCollection->getId();
				if($loadedCollection->hasChildren()) {
					foreach($loadedCollection->getFlattenedChildren() as $child) {
						$collections[] = $child->getId();
					}
				}
			}
		}
		else {
			foreach($this->instance->getCollections() as $collection) {
				$collections[] = $collection->getId();
			}



		}

		if(count($collections) == 0) {
			// if we don't have any collections, we can't search.
			return ["searchResults"=>array(), "totalResults"=>0];
		}


		$sort = array();
		if(isset($searchArray["latitude"]) && isset($searchArray["longitude"]) && strlen($searchArray["latitude"])>0 &&  strlen($searchArray["longitude"])>0 ) {
			$geoFilter['geo_distance']['locationCache']['lat'] = $searchArray['latitude'];
			$geoFilter['geo_distance']['locationCache']['lon'] = $searchArray['longitude'];
			$geoFilter['geo_distance']['distance'] = $searchArray['distance'] . "mi";
			$filter[] = $geoFilter;
    		$sortFilter["_geo_distance"]["locationCache"]["lat"] = $searchArray["latitude"];
    		$sortFilter["_geo_distance"]["locationCache"]["lon"] = $searchArray["longitude"];
    		$sortFilter["_geo_distance"]["order"] = "asc";
    		$sortFilter["_geo_distance"]["unit"] = "mi";

    		$sort[] = $sortFilter;
		}



		if(isset($searchArray["sort"]) && $searchArray["sort"] != "0") {
			$sortFilter = [];
			
			if($searchArray["sort"] == "collection" && $this->instance) {
				$sortCollections = $this->instance->getCollectionsWithoutParent();
				if(!function_exists("recursiveFunctionWalker")) {
					function recursiveFunctionWalker($collection) {
						$childrenArray = [];
						if($collection->hasChildren()) {
							foreach($collection->getChildren() as $child) {
								$childrenArray = $childrenArray + recursiveFunctionWalker($child);
							}
						}
						return [$collection->getId()=>$collection->getTitle()] + $childrenArray;
					};
				}
				
				$collectionArray = [];
				foreach($sortCollections as $collection) {
					$collectionArray = $collectionArray + recursiveFunctionWalker($collection);
				}

				$collectionSortIds = array_keys($collectionArray);

				$sortFilter["_script"] = [
					"type" => "number",
					"script" => [
						"lang" => "painless",
						"params" => [
							"ids" => $collectionSortIds
						],
						"inline" => "int idsCount = params.ids.size();def id = (int)doc['collectionId'].value;int foundIdx = params.ids.indexOf(id);return foundIdx > -1 ? foundIdx: idsCount + 1;"
					]
				];

			}
			else if($searchArray["sort"] == "template" && $this->instance) {
				$templates = $this->instance->getTemplates();
				
				$templateArray = [];
				foreach($templates as $template) {
					$templateArray[$template->getId()] = $template->getName();
				}
				asort($templateArray);
				$templateIds = array_keys($templateArray);
				$sortFilter["_script"] = [
					"type" => "number",
					"script" => [
						"lang" => "painless",
						"params" => [
							"ids" => $templateIds
						],
						"inline" => "int idsCount = params.ids.size();def id = (int)doc['templateId'].value;int foundIdx = params.ids.indexOf(id);return foundIdx > -1 ? foundIdx: idsCount + 1;"
					]
				];

			}
			else {
				$sortTerm = $searchArray["sort"];
				if(substr($searchArray["sort"], -5, 5) == ".desc") {
					$sortTerm = str_replace(".desc", "", $sortTerm);
					$sortFilter[$sortTerm]["order"] = "desc";
				}
				else {
					$sortTerm = str_replace(".asc", "", $sortTerm);
					$sortFilter[$sortTerm]["order"] = "asc";
				}
			}

			
    		$sort[] = $sortFilter;

		}

		if(isset($searchArray["startDate"]) && strlen($searchArray["startDate"])>0) {
			$startDate = $searchArray["startDate"];

			$endDate = $searchArray["endDate"];

			if(strlen($endDate)==0) {
				$endDate=NULL;
			}

			$filter[]['range']['dateCache.startDate']['gte'] = intval($startDate);


			if($endDate) {
				$filter[]['range']['dateCache.startDate']['lte'] = intval($endDate);
				$filter[]['range']['dateCache.endDate']['lte'] = intval($endDate);
			}
		}

    	if(count($collections) > 0) {
			$filter[]['terms']['collectionId'] = $collections;
		}

		if($readyforDisplayOnly) {
			$filter[]['terms']['readyForDisplay'] = [$readyforDisplayOnly];
			$filter[]['terms']['includeInSearch'] = [$readyforDisplayOnly];
		}


		if(isset($searchArray['templateId'])) {
			if(is_array($searchArray['templateId']) && count($searchArray['templateId']) > 0) {
				if(count($searchArray['templateId']) == 1 && $searchArray['templateId'][0] == 0) {
				
				}
				else {
					$filter[]['terms']['templateId'] = $searchArray['templateId'];
				}
				
			}
			else {
				$filter[]['terms']['templateId'] = [$searchArray['templateId']];
			}

		}

		if(isset($searchArray["fileTypesSearch"]) && strlen($searchArray["fileTypesSearch"]) > 0) {
			$filter[]['terms']['fileTypesCache'] = [$searchArray['fileTypesSearch']];
		}
		

		$fuzzySearch = false;
		if (isset($searchArray["fuzzySearch"]) && $searchArray['fuzzySearch']==true) {
			$fuzzySearch = true;
		}

		// make sure at least one of our "should" matches
		$searchParams['body']['query']['bool']['minimum_should_match'] = 1;


		$query = array();
		$i=0;
		if(isset($searchArray['useBoolean']) && $searchArray['useBoolean'] == true) {
			$searchParams['body']['query']['bool']['should'][$i]['query_string']['query'] = $searchArray["searchText"];
			$searchParams['body']['query']['bool']['should'][$i]['query_string']['fields'] = ["my_all", "fileSearchData^0.8"];
		}
		else if(preg_match("/[*]+/u", $searchArray["searchText"])) {
			$searchParams['body']['query']['bool']['should'][$i]['wildcard'] = ["my_all"=>strtolower($searchArray["searchText"])];
		}
		else if(preg_match("/.*\\.\\.\\..*/u", $searchArray["searchText"])) {
			list($start, $end) = explode("...", strtolower($searchArray["searchText"]));
			$searchParams['body']['query']['bool']['should'][$i]['range'] = ["my_all" => ["gte"=>trim($start), "lte"=>trim($end)]];
		}
		else if(substr($searchArray["searchText"],0,1) == '"' && substr($searchArray["searchText"], -1,1) == '"') {
			$searchParams['body']['query']['bool']['should'][$i]['match_phrase'] = ["my_all"=>strtolower($searchArray["searchText"])];
		}
		else if($searchArray["searchText"] != "") {

			// $searchParams['body']['query']['filtered']['query']['bool']['should'][0]["match_phrase_prefix"]["_all"] = $searchArray["searchText"];
			// $searchParams['body']['query']['filtered']['query']['bool']['should'][1]["query_string"]["query"] = $searchArray["searchText"];
			// $searchParams['body']['query']['filtered']['query']['bool']['should'][2]["fuzzy_like_this"]["like_text"] = $searchArray["searchText"];
			// $searchParams['body']['query']['filtered']['query']['bool']['should'][2]["fuzzy_like_this"]["fuzziness"] = "AUTO";
			// $searchParams['body']['query']['filtered']['query']['bool']['should'][2]["fuzzy_like_this"]["boost"] = 0.5;





// "analysis" :{
//                 "analyzer": {
//                     "default": {
//                         "type" : "snowball",
//                         "language" : "English"
//                     }
//                 }
//             }
			$tokenizedSearch = explode(" ", $searchArray["searchText"]);
			if(count($tokenizedSearch) > 500) {
				// we don't want to search for more than 500 terms at once
				$tokenizedSearch = array_slice($tokenizedSearch, 0, 500);
				$searchArray["searchText"] = implode(" ", $tokenizedSearch);
			}
			$searchParams['body']['query']['bool']['should'][$i]['multi_match']['query'] = $searchArray["searchText"];

			//TOOD: the intenion here is to reduce the weight of fileSearchData fields, but that isn't waht this is doing.
			$searchParams['body']['query']['bool']['should'][$i]['multi_match']['fields'] = ["my_all", "fileSearchData^0.8"];
			if(!$fuzzySearch) {
				$matchType = "cross_fields";
				if(isset($searchArray['matchType'])) {
					$matchType = $searchArray['matchType'];
				}
				$searchParams['body']['query']['bool']['should'][$i]['multi_match']['type'] = $matchType;
				// by default, we want cross field to be an "and" so our matching document matches all of the relevant search terms
				// however, sometimes we want to match any document with any of the terms, setting crossFieldOr to true will enable that.
				// this is primarily used for "related items" searches where we pass in a ton of MongoIds
				//

				if(!isset($searchArray['crossFieldOr']) || $searchArray['crossFieldOr'] == FALSE) {
					$searchParams['body']['query']['bool']['should'][$i]['multi_match']['operator'] = "and";
				}

			}
			else {
				//$searchParams['body']['query']['filtered']['query']['bool']['should'][$i]['multi_match']['analyzer'] = "snowball";
				$searchParams['body']['query']['bool']['should'][$i]['multi_match']['fuzziness'] = "AUTO";
				$searchParams['body']['query']['bool']['should'][$i]['multi_match']['prefix_length'] = 2;
				 //$searchParams['body']['query']['filtered']['query']['bool']['should'][$i]['multi_match']['type'] = "cross_fields";
				$searchParams['body']['query']['bool']['should'][$i]['multi_match']['type'] = "best_fields";
			}
			$i++;
		}
		else {
			$searchParams['body']['query']['bool']['minimum_should_match'] = 0;
		}


		if(isset($searchArray["specificFieldSearch"]) && count($searchArray["specificFieldSearch"]) > 0) {
			foreach($searchArray["specificFieldSearch"] as $entry) {

				if(preg_match("/[?*]+/u", $entry["text"])) {
					$searchParams['body']['query']['bool']['should'][$i]['wildcard'] = [$entry["field"]=>strtolower($entry["text"])];
				}
				else if(preg_match("/.*\\.\\.\\..*/u", $entry["text"])) {
					list($start, $end) = explode("...", strtolower($entry["text"]));
					$searchParams['body']['query']['bool']['should'][$i]['range'] = [$entry["field"] => ["gte"=>trim($start), "lte"=>trim($end)]];
				}
				else if(substr($entry["text"],0,1) == '"' && substr($entry["text"], -1,1) == '"') {
					$searchParams['body']['query']['bool']['should'][$i]['match_phrase'] = [$entry["field"]=>strtolower($entry["text"])];
				}
				else if($entry["text"] == "boolean_true" || $entry["text"] =="boolean_false") {
					$searchParams['body']['query']['bool']['should'][$i]['multi_match']['query'] = ($entry["text"] == "boolean_true")?1:0;
					$searchParams['body']['query']['bool']['should'][$i]['multi_match']['fields'] = [$entry["field"]];
				}
				else {
					$searchParams['body']['query']['bool']['should'][$i]['multi_match']['query'] = $entry["text"];
					$searchParams['body']['query']['bool']['should'][$i]['multi_match']['fields'] = [$entry["field"]];
					if($entry["fuzzy"]) {
						$searchParams['body']['query']['bool']['should'][$i]['multi_match']['fuzziness'] = "AUTO";
						$searchParams['body']['query']['bool']['should'][$i]['multi_match']['prefix_length'] = 2;
						$searchParams['body']['query']['bool']['should'][$i]['multi_match']['type'] = "best_fields";
					}
					else {
						$searchParams['body']['query']['bool']['should'][$i]['multi_match']['type'] = "phrase_prefix";
					}
				}


				$i++;

			}
			
			if(isset($searchArray["combineSpecificSearches"]) && $searchArray["combineSpecificSearches"] == "AND")  {

				$searchParams['body']['query']['bool']['minimum_should_match'] = count($searchArray["specificFieldSearch"]) + (($searchArray["searchText"] != "")?1:0) ;
			}
			else {
				$searchParams['body']['query']['bool']['minimum_should_match'] = 1;
			}
			

		}

		$highlightSingleObject = false;
		if(isset($searchArray['highlightForObject'])) {
			$highlightSingleObject = true;
			$targetObjectId = $searchArray['highlightForObject'];
			$filter[]['ids']['values'] = [$targetObjectId];

			$searchParams['body']['highlight']['pre_tags'] = [""];
			$searchParams['body']['highlight']['post_tags'] = [""];
			$searchParams['body']['highlight']['number_of_fragments'] = 0;
			$searchParams['body']['highlight']['fields']["*"] = new stdClass();
			$searchParams['body']['highlight']['require_field_match'] = false;

		}


		//$searchParams['body']['query']['match_phrase_prefix']['_all']['query'] = $searchString;
		//$searchParams['body']['query']['match_phrase_prefix']['_all']['max_expansions'] = 10;
		//$searchParams['body']['query']['fuzzy']['_all'] = $searchString;
		//$searchParams['body']['filter']['terms']['collection'] = $collections;


		$searchParams['body']['sort'] = $sort;

		// 100% arbitrary right now
		//$searchParams['body']['min_score'] = 0.3;

		$searchParams['from'] = $pageStart*$this->pageLength;
		if($loadAll) {
			$searchParams['size'] = $this->loadAllLength; // this is arbitrary, we don't actually load all the results
		}
		else {
			$searchParams['size'] = $this->pageLength;
		}

		//$searchParams['min_score'] = 0.2;


		if(count($filter) > 0) {
			$searchParams['body']['query']['bool']['filter'] = $filter;
		}



    	$searchParams['body']['stored_fields'] = "_id";
		$searchParams['body']['track_total_hits'] = true;
    	// $this->logging->logError("params", $searchParams);
		$queryResponse = $this->es->search($searchParams);
    	// $this->logging->logError("queryParams", $queryResponse);

    	$matchArray = array();
    	$matchArray["searchResults"] = array();



    	$totalResults = $searchParams['from'];

    	$truncatedResults = false;
    	if(isset($queryResponse['hits'])) {
    		$maxScore = $queryResponse['hits']['max_score'];

    		if(!$fuzzySearch || $maxScore == NULL) {
    			$scoreDelta	= 0;
    			$maxScore = 1;
    		}
    		else {
    			if($maxScore < 1) {
    				$scoreDelta = "0.1";
    			}
    			else {
    				$scoreDelta = "0.3";
    			}
    			if($maxScore <= 0) {
    				$maxScore = 0.1;
    			}
    		}


    		foreach($queryResponse['hits']['hits'] as $match) {
    			if($match['_score'] / $maxScore >= $scoreDelta) {
    				if($highlightSingleObject && isset($match['highlight'])) {
    					// we're hightlighting one object here, let's save its highlight index
    					$matchArray['highlight'] = $match['highlight'];
    				}
    				$matchArray["searchResults"][] = $match["_id"];
    				$totalResults++;
    			}
    			else {
    				$truncatedResults = true;
    				break;
    			}

    		}
    	}

    	if($truncatedResults) {
    		$matchArray['totalResults'] = $totalResults;
    	}
    	else {
    		$matchArray['totalResults'] = $queryResponse['hits']['total']['value'];
    	}

    	return $matchArray;
	}

	public function getSuggestions($searchTerm) {

		$searchParams = array();
		$searchParams['index'] = $this->config->item('elasticIndex');
		$searchParams['body'] = [
			'suggest' => [
				'suggestion-finder' => [
					'text' => $searchTerm, // your search term
					'term' => [
						'field' => 'my_all', // or specify a more specific field if needed
						'size' => 5 // number of suggestions to return (optional)
					]
				]
			]
		];

		$queryResponse = $this->es->search($searchParams);

		return $queryResponse;

	}

	/*
	 * Get all of the used tags within a specific field of the index. These will be instance-unique but not template-unique.
	 * Elastic returns them ranked by use, but we reorder alphabetically. Long term, moving to a more dynamic UI component could
	 * allow us to lift the cap on results.
	 */
	public function getAggregatedTags($tagField) {
		$searchParams = array();
		$searchParams['index'] = $this->config->item('elasticIndex');
		$searchParams['body']["size"]=0;
		$searchParams['body']["aggs"]["tags"]["terms"]["field"] = $tagField;
		$searchParams['body']["aggs"]["tags"]["terms"]["size"] = 1000;

		$queryResponse = $this->es->search($searchParams);
		if(!isset($queryResponse["aggregations"]["tags"]["buckets"]) || count($queryResponse["aggregations"]["tags"]["buckets"]) >= 1000) {
			return [];
		} else {
			$tags = array_map(function($value) {
				return $value["key"];
			}, $queryResponse["aggregations"]["tags"]["buckets"]);
			sort($tags);
			return $tags;
		}
		
	}


	public function autocompleteResults($searchTerm, $fieldTitle, $templateId) {
		// todo : do this the right way


		$searchParams['index'] = $this->config->item('elasticIndex');
    	//$searchParams['type']  = 'asset';

    	$filter = array();

		$collections = array();
		if($templateId) {
			if(is_array($templateId)) {
				$filter[]['terms']['templateId'] = $templateId;
			}
			else {
				$filter[]['terms']['templateId'] = [$templateId];
			}

		}



		$query = array();

		$searchParams['body']['query']['bool']['must']['multi_match']['query'] = $searchTerm;

		$searchParams['body']['query']['bool']['must']['multi_match']['fields'] = [$fieldTitle];
		$searchParams['body']['query']['bool']['must']['multi_match']['type'] = "phrase_prefix";

		if(count($filter)>0) {
			$searchParams['body']['query']['bool']['filter'] = $filter;
		}

    	$searchParams['_source'] = $fieldTitle;
    	$searchParams['size'] = 10;

    	// $this->logging->logError("params", $searchParams);
		$queryResponse = $this->es->search($searchParams);

    	$termArray = array();
    	// $this->logging->logError("queryParams", $queryResponse);
    	if(isset($queryResponse['hits'])) {
    		foreach($queryResponse['hits']['hits'] as $match) {
    			if(!is_array($match["_source"][$fieldTitle])) {
    				if(stristr($match["_source"][$fieldTitle], $searchTerm)) {
    					$termArray[] = $match["_source"][$fieldTitle];
    				}

    			}
    			else {
    				foreach($match["_source"][$fieldTitle] as $entry) {
    					if(is_array($entry)) {
    						foreach($entry as $nestedEntry) {
    							if(stristr($nestedEntry, $searchTerm)) {
									$termArray[] = $nestedEntry;
								}
    						}
    					} else {
							if(stristr($entry, $searchTerm)) {
								$termArray[] = $entry;
							}
    					}
    					
					}
    			}


    		}
    	}

    	return  array_unique($termArray);
	}

	public function processSearchResults($searchArray, $matchArray) {

		$resultsArray = array();

		$this->asset_model->enableObjectCache();
		$showCollection = false;
		if($this->instance->getShowCollectionInSearchResults()) {
			$showCollection = true;
			$collectionLinkCache = [];
		}
		
		$showTemplate = false;
		if($this->instance->getShowTemplateInSearchResults()) {
			$showTemplate = true;
			$templateCache = [];
		}

		foreach($matchArray["searchResults"] as $match) {
			$asset = new Asset_model;

			if($asset->loadAssetById($match, $noHydrate=true) === false) {
				continue;
			}

			/**
			 * if they searched for an object id and we found that, make sure it's the first result
			 */
			if($asset->getObjectId() == $searchArray["searchText"]) {
				array_unshift($resultsArray, $asset->getSearchResultEntry());
			}
			else {
				if($this->config->item('enableCaching')) {
					$this->doctrineCache->setNamespace('searchCache_');
					if($storedObject = $this->doctrineCache->fetch($match)) {

					}
					else {
						$storedObject = $asset->getSearchResultEntry();
						if($this->config->item('enableCaching')) {
							$this->doctrineCache->save($match, $storedObject, 900);
						}
					}
				}
				else {
					$storedObject = $asset->getSearchResultEntry();
				}

				if($showCollection) {
					$assetCollection = $asset->assetObject->getCollectionId();
					if(!isset($collectionLinkCache[$assetCollection])) {

						$hierarchy = array_map(function($value) {
							return ["id"=>$value->getId(), "title"=> $value->getTitle()];
						}, $this->collection_model->getFullHierarchy($assetCollection));
						$collectionLinkCache[$assetCollection] = array_reverse($hierarchy);
					}
					$storedObject["collectionHierarchy"] = $collectionLinkCache[$assetCollection];
				}
				
				if($showTemplate) {
					$assetTemplateId = $asset->assetObject->getTemplateId();
					if(!isset($templateCache[$assetTemplateId])) {
						$template = new Asset_template($assetTemplateId);
						$templateCache[$assetTemplateId] = $template->name;
					}

					$storedObject["template"]["name"] = $templateCache[$assetTemplateId];
					$storedObject["template"]["id"] = $assetTemplateId;
				}
				

				$resultsArray[] = $storedObject;


			}
			unset($asset);
		}


		$this->asset_model->disableObjectCache();
		$matchArray['matches'] = $resultsArray;
		$matchArray["success"] = true;
		$matchArray["searchEntry"] = $searchArray;
		return $matchArray;
	}

}

/* End of file  */
/* Location: ./application/models/ */
