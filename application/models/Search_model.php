<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class search_model extends CI_Model {

	private $es = NULL;
	public $showHidden = false;
	public $pageLength = 30;

	public function __construct()
	{
		parent::__construct();
		$params['hosts'] = array (
    		$this->config->item('elastic'));

		$this->es = new Elasticsearch\Client($params);

	}

	public function remove($asset) {
		$params['index'] = $this->config->item('elasticIndex');
    	$params['type']  = 'asset';
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

	public function addOrUpdate($asset) {

		if(!$asset->assetTemplate || !$asset->assetTemplate->getIndexForSearching()) {
			return;
		}
		$this->asset_model->enableObjectCache();

		/** HACK
		* for now, make sure we have a mapping each time we add a record
		*/
		$params['index'] = $this->config->item('elasticIndex');
		$params['type']  = 'asset';

		$myTypeMapping2 = array(
			'_source' => array(
				'enabled' => true
				),
			'date_detection' => 0,
			'properties' => array(
				'locationCache' => array(
					'type' => 'geo_point'
					)
				)
			);
		$params['body']['asset'] = $myTypeMapping2;

		// Update the index mapping
		$this->es->indices()->putMapping($params);

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
					if($locationContent->hasContents()) {
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
					if($dateContent->hasContents()) {
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
					if($uploadContent->hasContents()) {
						$fileTypeArray[] = strtolower(str_ireplace("handler", "", get_class($uploadContent->getFileHandler())));
					}
				}
			}
		}

 		// only go only level deep in recursion?
    	$params['body']  = $asset->getSearchEntry(2);

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

		$params['body']['includeInSearch'] = $asset->assetTemplate->getIncludeInSearch();


    	/**
    	 * inject the assetId for searching - we could search against _id too, but that can't be
    	 * grouped with other results.  This saves us having logic to detect mongoids in the query
    	 */
    	$params['body']['assetId'] = $asset->getObjectId();
    	$params['index'] = $this->config->item('elasticIndex');
    	$params['type']  = 'asset';
    	$params['id']    = $asset->getObjectId();

    	$ret = $this->es->index($params);
		if(!isset($ret["_id"]) || $ret["_id"] !== $asset->getObjectId()) {
    		$this->logging->logError("search error", $ret);
    	}
    	$this->asset_model->disableObjectCache();

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

    		$sortFilter[$searchArray["sort"]]["order"] = "asc";
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


		if(isset($searchArray['templateId']) && count($searchArray['templateId']) > 0) {
			if(count($searchArray['templateId']) == 1 && $searchArray['templateId'][0] == 0) {

			}
			else {
				if(is_array($searchArray['templateId'])) {
					$filter[]['terms']['templateId'] = $searchArray['templateId'];
				}
				else {
					$filter[]['terms']['templateId'] = [$searchArray['templateId']];
				}
			}

		}

		$fuzzySearch = false;
		if (isset($searchArray["fuzzySearch"]) && $searchArray['fuzzySearch']==true) {
			$fuzzySearch = true;
		}


		$query = array();
		$i=0;
		if(preg_match("/[*]+/u", $searchArray["searchText"])) {
			$searchParams['body']['query']['filtered']['query']['bool']['should'][$i]['wildcard'] = ["_all"=>strtolower($searchArray["searchText"])];
		}
		else if(preg_match("/.*\\.\\.\\..*/u", $searchArray["searchText"])) {
			list($start, $end) = explode("...", strtolower($searchArray["searchText"]));
			$searchParams['body']['query']['filtered']['query']['bool']['should'][$i]['range'] = ["_all" => ["gte"=>trim($start), "lte"=>trim($end)]];
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
			$searchParams['body']['query']['filtered']['query']['bool']['should'][$i]['multi_match']['query'] = $searchArray["searchText"];

			//TOOD: the intenion here is to reduce the weight of fileSearchData fields, but that isn't waht this is doing.
			$searchParams['body']['query']['filtered']['query']['bool']['should'][$i]['multi_match']['fields'] = ["_all"];
			if(!$fuzzySearch) {
				$matchType = "cross_fields";
				if(isset($searchArray['matchType'])) {
					$matchType = $searchArray['matchType'];
				}
				$searchParams['body']['query']['filtered']['query']['bool']['should'][$i]['multi_match']['type'] = $matchType;
				// by default, we want cross field to be an "and" so our matching document matches all of the relevant search terms
				// however, sometimes we want to match any document with any of the terms, setting crossFieldOr to true will enable that.
				// this is primarily used for "related items" searches where we pass in a ton of MongoIds
				//

				if(!isset($searchArray['crossFieldOr']) || $searchArray['crossFieldOr'] == FALSE) {
					$searchParams['body']['query']['filtered']['query']['bool']['should'][$i]['multi_match']['operator'] = "and";
				}

			}
			else {
				//$searchParams['body']['query']['filtered']['query']['bool']['should'][$i]['multi_match']['analyzer'] = "snowball";
				$searchParams['body']['query']['filtered']['query']['bool']['should'][$i]['multi_match']['fuzziness'] = "AUTO";
				$searchParams['body']['query']['filtered']['query']['bool']['should'][$i]['multi_match']['prefix_length'] = 2;
				 //$searchParams['body']['query']['filtered']['query']['bool']['should'][$i]['multi_match']['type'] = "cross_fields";
				$searchParams['body']['query']['filtered']['query']['bool']['should'][$i]['multi_match']['type'] = "best_fields";
			}
			$i++;
		}


		if(isset($searchArray["specificFieldSearch"])) {

			foreach($searchArray["specificFieldSearch"] as $entry) {

				if(preg_match("/[?*]+/u", $entry["text"])) {
					$searchParams['body']['query']['filtered']['query']['bool']['should'][$i]['wildcard'] = [$entry["field"]=>strtolower($entry["text"])];
				}
				else if(preg_match("/.*\\.\\.\\..*/u", $entry["text"])) {
					list($start, $end) = explode("...", strtolower($entry["text"]));
					$searchParams['body']['query']['filtered']['query']['bool']['should'][$i]['range'] = [$entry["field"] => ["gte"=>trim($start), "lte"=>trim($end)]];
				}
				else {
					$searchParams['body']['query']['filtered']['query']['bool']['should'][$i]['multi_match']['query'] = $entry["text"];
					$searchParams['body']['query']['filtered']['query']['bool']['should'][$i]['multi_match']['fields'] = [$entry["field"]];
					if($entry["fuzzy"]) {
						$searchParams['body']['query']['filtered']['query']['bool']['should'][$i]['multi_match']['fuzziness'] = "AUTO";
						$searchParams['body']['query']['filtered']['query']['bool']['should'][$i]['multi_match']['prefix_length'] = 2;
						$searchParams['body']['query']['filtered']['query']['bool']['should'][$i]['multi_match']['type'] = "best_fields";
					}
					else {
						$searchParams['body']['query']['filtered']['query']['bool']['should'][$i]['multi_match']['type'] = "phrase_prefix";
					}
				}


				$i++;

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
			$searchParams['size'] = 1000; // this is arbitrary, we don't actually load all the results
		}
		else {
			$searchParams['size'] = $this->pageLength;
		}

		//$searchParams['min_score'] = 0.2;


		if(count($filter) > 0) {
			$searchParams['body']['query']['filtered']['filter']['and'] = $filter;
		}



    	$searchParams['fields'] = "_id";
// $this->logging->logError("Query Params", $searchParams);

    	$queryResponse = $this->es->search($searchParams);


 // $this->logging->logError("Query Response", $queryResponse);


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
    		$matchArray['totalResults'] = $queryResponse['hits']['total'];
    	}



    	return $matchArray;
	}

	public function getSuggestions($searchTerm) {

		$searchParams = array();
		$searchParams['index'] = $this->config->item('elasticIndex');
		$searchParams['body']["suggestion-finder"]["text"] = $searchTerm;
		$searchParams['body']["suggestion-finder"]["term"]["field"] = "_all";

		$queryResponse = $this->es->suggest($searchParams);

		return $queryResponse;

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

		$searchParams['body']['query']['filtered']['query']['multi_match']['query'] = $searchTerm;

		$searchParams['body']['query']['filtered']['query']['multi_match']['fields'] = [$fieldTitle];
		$searchParams['body']['query']['filtered']['query']['multi_match']['type'] = "phrase_prefix";

		if(count($filter)>0) {
			$searchParams['body']['query']['filtered']['filter']['and'] = $filter;
		}

    	$searchParams['fields'] = $fieldTitle;
    	$searchParams['size'] = 10;

    	// $this->logging->logError("params", $searchParams);
		$queryResponse = $this->es->search($searchParams);

    	$termArray = array();

    	if(isset($queryResponse['hits'])) {
    		foreach($queryResponse['hits']['hits'] as $match) {
    			if(!is_array($match["fields"][$fieldTitle])) {
    				if(stristr($match["fields"][$fieldTitle], $searchTerm)) {
    					$termArray[] = $match["fields"][$fieldTitle];
    				}

    			}
    			else {
    				foreach($match["fields"][$fieldTitle] as $entry) {
    					if(stristr($entry, $searchTerm)) {
							$termArray[] = $entry;
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
		foreach($matchArray["searchResults"] as $match) {
			$asset = new Asset_model;

			if($asset->loadAssetById($match) === false) {
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
