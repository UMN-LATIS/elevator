<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Instances extends Instance_Controller {

	public function index()
	{
		if(!$this->user_model->getIsSuperAdmin()) {
			instance_redirect("errorHandler/error/noPermission");
			return;
		}

		$data['instances'] = $this->doctrine->em->getRepository("Entity\Instance")->findAll();

		$this->template->title = 'Instance Index';

		$this->template->content->view('instances/index', $data);
		$this->template->publish();

	}

	public function save()
	{


		//TODO Permissions checking

		if(is_numeric($this->input->post("instanceId"))) {
			$instance = $this->doctrine->em->find('Entity\Instance', $this->input->post("instanceId"));
			$accessLevel = $this->user_model->getAccessLevel("instance", $instance);
			if($accessLevel<PERM_ADMIN) {
				instance_redirect("/errorHandler/error/noPermission");
				return;
			}
		}
		else {
			if(!$this->user_model->getIsSuperAdmin()) {
				instance_redirect("errorHandler/error/noPermission");
				return;
			}
			$instance = new Entity\Instance();
		}

		$instance->setName($this->input->post('name'));
		$instance->setDomain($this->input->post('domain'));
		$instance->setOwnerHomepage($this->input->post('ownerHomepage'));
		$instance->setAmazonS3Key($this->input->post('amazonS3Key'));
		$instance->setAmazonS3Secret($this->input->post('amazonS3Secret'));
		$instance->setS3StorageType($this->input->post('s3StorageType'));
		$instance->setBucketRegion($this->input->post('bucketRegion'));
		$instance->setDefaultBucket($this->input->post('defaultBucket'));
		$instance->setGoogleAnalyticsKey($this->input->post('googleAnalyticsKey'));
		$instance->setClarifaiId($this->input->post('clarifaiId'));
		$instance->setClarifaiSecret($this->input->post('clarifaiSecret'));
		$instance->setBoxKey($this->input->post('boxKey'));
		$instance->setIntroText($this->input->post('introText'));
		$instance->setUseCustomHeader($this->input->post('useCustomHeader')?1:0);
		$instance->setUseHeaderLogo($this->input->post('useHeaderLogo')?1:0);
		$instance->setUseCustomCSS($this->input->post('useCustomCSS')?1:0);
		$instance->setUseCentralAuth($this->input->post('useCentralAuth')?1:0);
		$instance->setFeaturedAsset($this->input->post('featuredAsset'));
		$instance->setFeaturedAssetText($this->input->post('featuredAssetText'));

		$this->doctrine->em->persist($instance);
		$this->doctrine->em->flush();

		instance_redirect('instances/edit/' . $instance->getId());

	}

	public function edit($id=null)
	{
		if($id) {
			$data['instance'] = $this->doctrine->em->find('Entity\Instance', $id);
			$accessLevel = $this->user_model->getAccessLevel("instance", $data['instance']);
			if($accessLevel<PERM_ADMIN) {
				instance_redirect("/errorHandler/error/noPermission");
				return;
			}
		}
		else {
			if(!$this->user_model->getIsSuperAdmin()) {
				instance_redirect("errorHandler/error/noPermission");
				return;
			}
			$data['instance'] = new Entity\Instance();
		}



		if (empty($data['instance']))
		{
			show_404();
		}
		$this->template->title = 'Edit Instance';
		$this->template->javascript->add('assets/js/parsley.min.js');
		$this->template->content->view('instances/edit', $data);
		$this->template->publish();
	}


	public function delete($id)
	{
		if(!$this->user_model->getIsSuperAdmin()) {
			instance_redirect("errorHandler/error/noPermission");
			return;
		}

		$instance = $this->doctrine->em->find('Entity\Instance', $id);
		if ($instance === null) {
			show_404();
		}

		$this->doctrine->em->remove($instance);
		$this->doctrine->em->flush();

		instance_redirect('instances/index');
	}


	public function customPages() {
		$pages = $this->instance->getPages();
		$this->template->title = 'Custom Pages';
		$this->template->content->view('instances/pageList', ["pages"=>$pages]);
		$this->template->publish();
	}

	public function editPage($pageId=null) {


		$this->template->javascript->add("assets/tinymce/tinymce.min.js");
		// $this->template->javascript->add("assets/tinymce/jquery.tinymce.min.js");
		if($pageId) {
			$page = $this->doctrine->em->find("Entity\InstancePage", $pageId);

		}
		else {
			$page = new Entity\InstancePage();
		}

		$this->template->title = 'Edit Page';
		$this->template->content->view('instances/editPage', ["page"=>$page]);
		$this->template->publish();
	}

	public function deletePage($pageId) {
		$page = $this->doctrine->em->find("Entity\InstancePage", $pageId);
		$this->doctrine->em->remove($page);
		$this->doctrine->em->flush();
		instance_redirect("instances/customPages");

	}

	public function savePage() {
		if($this->input->post("pageId")) {
			$page = $this->doctrine->em->find("Entity\InstancePage", $this->input->post("pageId"));

		}
		else {
			$page = new Entity\InstancePage();
		}


		$page->setTitle($this->input->post("title"));
		$page->setBody($this->input->post("body"));
		$page->setIncludeInHeader($this->input->post("includeInHeader")?1:0);
		$page->setInstance($this->instance);
		$this->doctrine->em->persist($page);
		$this->doctrine->em->flush();
		instance_redirect("instances/customPages");

	}



}

