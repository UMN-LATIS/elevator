<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class api_drawers extends API_Controller {

	public function __construct()
	{
		parent::__construct();

	}

	public function listDrawers()
	{
		$drawerArray = array();
		foreach($this->user_model->getDrawers() as $drawer) {
			$drawerArray[$drawer->getId()] = $drawer->getTitle();
		}
		echo json_encode($drawerArray);

	}

	// THIS WHOLE THING IS FULL OF HACKS
	public function getContentsOfDrawer($drawerId, $pageNumber=0, $mimeType=null)
	{
		// pagenumber is disregarded.
		require_once("application/controllers/Drawers.php");
		ob_start();
		Drawers::getDrawer($drawerId);
		$results = ob_get_contents();
		ob_end_clean();

		$decodedResults = json_decode($results, true);
		$outputArray = array();

		if($mimeType) {
			foreach($decodedResults["matches"] as $result) {
				if(!stristr($result["primaryHandlerType"], $mimeType) && (!isset($result["fileAssets"]) || $result["fileAssets"] == 1)) {

				}
				else {
					$outputArray[] = $result;
				}


			}

			$decodedResults["matches"] = $outputArray;
		}

		echo json_encode($decodedResults);


	}


}

/* End of file  */
/* Location: ./application/controllers/ */