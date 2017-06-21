<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class CollectionManager extends Instance_Controller {

	public function __construct()
	{
		parent::__construct();
		$accessLevel = $this->user_model->getAccessLevel("instance",$this->instance);

		if($accessLevel < PERM_ADMIN) {
			instance_redirect("errorHandler/error/noPermission");
		}

	}

	public function index()
	{
		//TODO Permissions checking
		$data['collections'] = $this->instance->getCollections();
		$this->template->title = 'Collection Index';

		$this->template->content->view('collectionManager/index', $data);
		$this->template->publish();
	}

	public function new_collection()
	{
		//TODO Permissions checking
		$this->template->title = 'New Collection';
    	$this->template->content->view('collectionManager/edit');
    	$this->template->publish();
	}

	public function share($collectionId)
	{

		$collection = $this->doctrine->em->find('Entity\Collection', $collectionId);
		if($this->input->post("targetInstance") && is_numeric($this->input->post("targetInstance"))) {

			$collection->addInstance($this->doctrine->em->getReference("Entity\Instance", $this->input->post("targetInstance")));
			$this->doctrine->em->flush();
			instance_redirect("collectionManager");

		}

		$instanceList = $this->doctrine->em->getRepository("Entity\Instance")->findAll();
		$this->template->title = 'New Collection';
    	$this->template->content->view('collectionManager/share', ["sourceCollection"=>$collection, "instanceList"=>$instanceList]);
    	$this->template->publish();
	}



	public function edit($id=null)
	{
		$this->template->loadJavascript(["bootstrap-show-password"]);
		if($id) {
			$data['collection'] = $this->doctrine->em->find('Entity\Collection', $id);
		}
		else {
			$collection = new Entity\Collection();
			$collection->setS3Key($this->instance->getAmazonS3Key());
			$collection->setS3Secret($this->instance->getAmazonS3Secret());
			$collection->setBucket($this->instance->getDefaultBucket());
			$collection->setBucketRegion($this->instance->getBucketRegion());
			$collection->setShowInBrowse(true);
			$data['collection'] = $collection;

		}

		$this->template->title = 'Edit Collection';
		$this->template->javascript->add("assets/tinymce/tinymce.min.js");
  		$this->template->content->view('collectionManager/edit', $data);
    	$this->template->publish();
	}

	public function save()
	{
		//TODO Permissions checking
		//
		if(is_numeric($this->input->post("collectionId"))) {
			$collection = $this->doctrine->em->find('Entity\Collection', $this->input->post("collectionId"));
		}
		else {
			$collection = new Entity\Collection();
			$collection->addInstance($this->instance);
		}

		// TODO: check for recursion on parents!!!


		$collection->setTitle($this->input->post('title'));
		$collection->setBucket($this->input->post('bucket'));
		$collection->setBucketRegion($this->input->post('bucketRegion'));
		$collection->setS3Key($this->input->post('S3Key'));
		$collection->setS3Secret($this->input->post('S3Secret'));
		$collection->setShowInBrowse($this->input->post('showInBrowse')=="on"?1:0);
		$collection->setCollectionDescription($this->input->post('collectionDescription'));

		if($this->input->post("parent") !== "0") {

			$collection->setParent($this->doctrine->em->getReference("Entity\Collection", $this->input->post("parent")));
		}
		else {
			$collection->setParent(null);
		}


		$this->doctrine->em->persist($collection);
		$this->doctrine->em->flush();

		instance_redirect("collectionManager/");
	}

	public function delete($id)
	{
		// TODO: don't let them delete a collection until they've moved all their assets.
		//TODO Permissions checking
		$this->load->helper('url');

		$collection = $this->doctrine->em->find('Entity\Collection', $id);

		$children = $collection->getChildren();
		foreach($children as $child) {
			$child->setParent(null);
		}

		$recentCollections = $this->doctrine->em->getRepository("Entity\RecentCollection")->findBy(["collection"=>$collection]);
		foreach($recentCollections as $recentCollection) {
			$this->doctrine->em->remove($recentCollection);
		}

		$this->doctrine->em->flush();

		if ($collection === null) {
	 	  show_404();
		}


		$this->doctrine->em->remove($collection);

		$this->doctrine->em->flush();

		instance_redirect('collectionManager/index');
	}
}

