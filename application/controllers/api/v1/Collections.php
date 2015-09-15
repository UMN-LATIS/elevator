<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class collections extends API_Controller {

	public function __construct()
	{
		parent::__construct();

	}

	public function listCollections()
	{

		$collectionArray = array();
		foreach($this->instance->getCollections() as $collection) {
			$collectionArray[$collection->getId()] = $collection->getTitle();
		}
		echo json_encode($collectionArray);

	}

	public function getContentsOfCollection($collectionId, $page=0, $fileTypes=null)
	{

		if($fileTypes) {
			$searchArray["specificFieldSearch"][] = ["field"=>"fileTypesCache", "text"=> $fileTypes, "fuzzy"=>false];
		}

		$searchArray["searchText"] = "";
		$searchArray["collection"] = [$collectionId];
		$searchArray["fuzzySearch"] = false;

		if(count($searchArray) == 0) {
			echo json_encode([]);
			return;
		}

		$this->load->model("search_model");
		$this->load->model("asset_model");

		$matchArray = $this->search_model->find($searchArray, true, $page, false);

		$cleanedOutput = $this->search_model->processSearchResults($searchArray, $matchArray);
		$cleanedOutput['assetsPerPage'] = $this->search_model->pageLength;
		echo json_encode($cleanedOutput);


	}


}

/* End of file  */
/* Location: ./application/controllers/ */