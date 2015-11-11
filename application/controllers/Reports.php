<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Reports extends Admin_Controller {

	public function __construct()
	{
		parent::__construct();

		// $accessLevel = $this->user_model->getAccessLevel("instance", $this->instance);
		// if(!$this->input->is_cli_request() && $accessLevel<PERM_ADMIN) {
		// 	instance_redirect("/errorHandler/error/noPermission");
		// 	return;
		// }
	}

	public function index()
	{

		$this->template->content->view('reports/list');
		$this->template->publish();

	}

	public function collectionStats() {

		$templateId = null;
		if($this->input->post("templateId")) {
			$templateId = $this->input->post("templateId");
		}

		$map = new MongoCode("function() { emit(this.collectionId,1); }");
		$reduce = new MongoCode("function(k, vals) { ".
    		"var sum = 0;".
    		"for (var i in vals) {".
        		"sum += vals[i];".
    		"}".
    		"return sum; }");

		$knownCollections = array();
		foreach($this->instance->getCollections() as $collection) {
			$knownCollections[] = $collection->getId();
		}

		$command = array(
    		"mapreduce" => $this->config->item('mongoCollection'),
    		"map" => $map,
    		"reduce" => $reduce,
			"query" => array("collectionId" => array('$in'=>$knownCollections)),
    		"out" => array("inline" => 1));

		if($templateId) {
			$command["query"] = array("templateId"=>(int)$templateId);
		}


		$results = $this->qb->command($command);

		$collectionInfo = array();
		foreach($results["results"] as $result) {
			if(!$this->collection_model->getCollection($result["_id"])) {
				continue;
			}

			$collectionInfo[$result["_id"]] = ["collection"=>$this->collection_model->getCollection($result["_id"]), "count"=>$result["value"] ];
		}

		$this->template->content->view("reports/collectionList", ["collections"=>$collectionInfo]);
		$this->template->publish();

	}

	public function resaveAssets() {

		set_time_limit(0);
		ini_set('max_execution_time', 0);
		$this->doctrine->extendTimeout();
		$result = $this->qb->get($this->config->item('fileCollection'), true);
		$count = 0;
		while ($result->hasNext()) {
			$fileHandlerBase = new fileHandlerBase();
			$fileHandlerBase->loadFromObject($result->getNext());
			$fileHandlerBase->save();
			echo $fileHandlerBase->objectId . "\n";
			if($count % 100 == 0) {
				gc_collect_cycles();
			}
		}


	}


	// public function fixCollection() {
	// 	set_time_limit(0);
	// 	ini_set('max_execution_time', 0);
	// 	$this->doctrine->extendTimeout();
	// 	$result = $this->qb->get($this->config->item('fileCollection'), true);

	// 	$count = 0;
	// 	while ($result->hasNext()) {
	// 		$entry = $result->getNext();
	// 		$fileHandler = $this->filehandler_router->getHandlerForObject($entry["_id"]);
	// 		$fileHandler->loadFromObject($entry);
	// 		$fileHandler->save();
	// 		echo $fileHandler->objectId . "\n";

	// 		if($count % 100 == 0) {
	// 			gc_collect_cycles();
	// 		}

	// 	}


	// }

	public function fileStats() {


		$map = new MongoCode("function() { emit(this.type.toLowerCase(), {size:this.sourceFile.metadata.filesize, count: 1}); }");
		$reduce = new MongoCode("function(k, vals) { ".
    		"var sum = { size:0, count:0};".
    		"for (var i in vals) {".
    			"if(parseFloat(vals[i].size)) {".
        			"sum.size += parseFloat(vals[i].size);".
        			"sum.count += vals[i].count".
        		"}".
    		"}".
    		"return sum; }");

		$knownCollections = array();
		foreach($this->instance->getCollections() as $collection) {
			$knownCollections[] = $collection->getId();
		}

		$command = array(
    		"mapreduce" => $this->config->item('fileCollection'),
    		"map" => $map,
    		"reduce" => $reduce,
    		"query" => array("collectionId" => array('$in'=>$knownCollections)),
    		"out" => array("inline" => 1));

		$results = $this->qb->command($command);

		$this->template->content->view("reports/filetypeList", ["results"=>$results["results"]]);
		$this->template->publish();

	}

	public function drawerList() {

		$drawers = $this->doctrine->em->getRepository("Entity\Drawer")->findBy(["instance"=>$this->instance]);

		$this->template->content->view("reports/drawerList", ["drawers"=>$drawers]);
		$this->template->publish();


	}


}

/* End of file  */
/* Location: ./application/controllers/ */