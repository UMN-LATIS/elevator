<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class search extends API_Controller {

	public function __construct()
	{
		parent::__construct();

	}

	public function performSearch()
	{
		$searchText = $this->input->post("searchTerm");
		$pageNumber = $this->input->post("pageNumber");
		
		$searchArray["searchText"] = $searchText;
		$searchArray["fuzzySearch"] = false;
		if(count($searchArray) == 0) {
			echo json_encode([]);
			return;
		}
		$sort = $this->input->post("sort");
		if($sort) {
			$searchArray["sort"] = $sort;
		}
		$this->load->model("search_model");
		$this->load->model("asset_model");
		$matchArray = $this->search_model->find($searchArray, true, $pageNumber, false);
		$cleanedOutput = $this->search_model->processSearchResults($searchArray, $matchArray);
		$cleanedOutput["assetsPerPage"] = $this->search_model->pageLength;
		echo json_encode($cleanedOutput);

	}

}

/* End of file  */
/* Location: ./application/controllers/ */