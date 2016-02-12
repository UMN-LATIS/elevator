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

		$qb = $this->doctrine->em->getRepository("Entity\Asset")->createQueryBuilder("a");
			$qb->select("count(a.collectionId) as colCount", "a.collectionId")
			->where("a.assetId IS NOT NULL")
			->groupBy("a.collectionId");

		if($templateId) {
			$qb->where("a.templateId = :templateId")->setParameter(":templateId", $templateId);
		}

		$results= $qb->getQuery()->execute();

		$collectionInfo = array();
		foreach($results as $result) {
			if(!$this->collection_model->getCollection($result["collectionId"]) || !in_array($this->instance, $this->collection_model->getCollection($result["collectionId"])->getInstances())) {
				continue;
			}

			$collectionInfo[$result["collectionId"]] = ["collection"=>$this->collection_model->getCollection($result["collectionId"]), "count"=>$result["colCount"] ];
		}

		$this->template->content->view("reports/collectionList", ["collections"=>$collectionInfo]);
		$this->template->publish();

	}

	public function resaveAssets() {

		set_time_limit(0);
		ini_set('max_execution_time', 0);
		$this->doctrine->extendTimeout();

		$results = $this->doctrine->em->getRepository("Entity\FileHandler")->findAll();

		$count = 0;
		foreach($results as $result) {
			$fileHandlerBase = new fileHandlerBase();
			$fileHandlerBase->loadFromObject($result);
			$fileHandlerBase->save();
			echo $fileHandlerBase->getObjectId(). "\n";
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


		$rsm = new Doctrine\ORM\Query\ResultSetMapping;
		$rsm->addEntityResult('Entity\FileHandler', 'f');

		$rsm->addScalarResult('size', 'size');
		$rsm->addScalarResult('c', 'count');
		$rsm->addScalarResult('type', 'type');
		$query = $this->doctrine->em->createNativeQuery('select count(f.id) as c, f.filetype as type, SUM(CAST(f.sourceFile->\'metadata\'->>\'filesize\' AS bigint)) as size from filehandlers f group by filetype',$rsm);
		$results = $query->getResult();
		$this->template->content->view("reports/filetypeList", ["results"=>$results]);
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
