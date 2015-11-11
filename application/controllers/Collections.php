<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Collections extends Instance_Controller {

	public function __construct()
	{
		parent::__construct();
	}

	public function browseCollection($collectionId=null) {

		if(!$collectionId) {
			instance_redirect("/search");
		}


		$knownCollections = array();
		foreach($this->instance->getCollections() as $collection) {
			$knownCollections[] = $collection->getId();
		}

		if(!in_array($collectionId, $knownCollections)) {
			instance_redirect("/search");
		}

		$searchArray["searchText"] = "";
		$searchArray["collection"] = [$collectionId];
		$searchArray["fuzzySearch"] = false;

		if(count($searchArray) == 0) {
			return;
		}
		$collection = $this->doctrine->em->find('Entity\Collection', $collectionId);
		$this->user_model->addRecentCollection($collection);

		$this->searchId = (string)$this->qb->save('searchArchive', $searchArray);

		instance_redirect("search#".$this->searchId);

	}

}

/* End of file  */
/* Location: ./application/controllers/ */
