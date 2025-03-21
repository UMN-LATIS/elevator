<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Templates extends Instance_Controller {

	public function __construct()
	{

		parent::__construct();
		$this->template->loadCSS(['template']);
		$accessLevel = $this->user_model->getAccessLevel("instance", $this->instance);
		if($accessLevel<PERM_ADMIN) {
			instance_redirect("/errorHandler/error/noPermission");
			return;
		}
	}

	public function index()
	{

		//TODO Permissions checking
		$data['templates'] = $this->instance->getTemplates();
		$this->template->title = 'Template Index';
		$this->template->javascript->add("assets/datatables/datatables.min.js");
		$this->template->stylesheet->add("assets/datatables/datatables.min.css");
    	$this->template->content->view('templates/index', $data);
    	$this->template->publish();
	}

	public function copy($id) {
		$template = $this->doctrine->em->find('Entity\Template', $id);
		$newTemplate = clone $template;
		$newTemplate->setName($template->getName() . " (copy)");
		$newTemplate->setSourceTemplate($template);



		$this->instance->addTemplate($newTemplate);
		foreach($template->getWidgets() as $widget) {
			$newWidget = clone $widget;
			$newWidget->setTemplate($newTemplate);
			$this->doctrine->em->persist($newWidget);
		}
		$this->doctrine->em->persist($newTemplate);
		$this->doctrine->em->flush();


		instance_redirect("templates/");
	}

	public function edit($id=null)
	{
		//TODO Permissions checki
		if($id == null) {
			$data['template'] = new Entity\Template;
		}
		else {
			$data['template'] = $this->doctrine->em->find('Entity\Template', $id);
		}
		$data['field_types'] = $this->doctrine->em->getRepository("Entity\Field_type")->findBy([], ['name' => 'ASC']);;

		if (empty($data['template']))
		{
			show_404();
		}

		$this->template->title = 'Edit Template';
	    $this->template->loadJavascript(["handlebars-v1.1.2"]);
  		$this->template->content->view('templates/edit', $data);
    	$this->template->publish();
	}

	public function update()
	{
		if(is_numeric($this->input->post('templateId'))) {
			$template = $this->doctrine->em->find('Entity\Template', $this->input->post('templateId'));
		}
		else {
			$template = new Entity\Template();
			$template->setCreatedAt(new \DateTime('now'));
			$template->addInstance($this->instance);

		}

		if ($template === null) {
		    show_404();
		}

		// Question: I think the most efficient way in code to do this is to delete all the widgets and re-create them
		// It seems like the easist way to handle the order of things, at least, rather than trying to place something in the middle.
		// It could probably be done better but this is fine for development, at least.

		if($template->getId()) {
			$deleteQuery = $this->doctrine->em->createQuery("delete from Entity\Widget w where w.template = " . $template->getId());
			$deleteQuery->execute();
		}

		$template->setName($this->input->post('name'));
		$template->setModifiedAt(new \DateTime('now'));
		$template->setIncludeInSearch(($this->input->post("includeInSearch")=="On")?1:0);
		$template->setIndexForSearching(($this->input->post("indexforSearching")=="On")?1:0);
		$template->setIsHidden(($this->input->post("isHidden")=="On")?1:0);
		$template->setShowCollection(($this->input->post("showCollection")=="On")?1:0);
		$template->setShowTemplate(($this->input->post("showTemplate")=="On")?1:0);
		$template->setCollectionPosition($this->input->post("collectionPosition"));
		$template->setTemplatePosition($this->input->post("templatePosition"));
		
		$template->setTemplateColor($this->input->post("templateColor"));
		$template->setRecursiveIndexDepth($this->input->post("recursiveIndexDepth"));
		$this->doctrine->em->persist($template);
		$this->doctrine->em->flush();

		$orderIndex = 0;
		if(is_array($this->input->post('widget'))) {
			foreach ($this->input->post('widget') as $key => $widget) {
				$display = $orderIndex + 1;

				if($widget["viewOrder"] == "") {
					$widget["viewOrder"] = $display;
				}
				if($widget["templateOrder"] == "") {
					$widget["templateOrder"] = $display;
				}

				if(strlen(trim($widget['fieldTitle'])) == 0 || strlen(trim($widget['label'])) == 0) {
					continue;
				}

				// Create new widget
				$newWidget = new Entity\Widget();

				// Set parameters
				$newWidget->setDisplay(isset($widget['display'])?1:0);
				$newWidget->setRequired(isset($widget['required'])?1:0);
				$newWidget->setAllowMultiple(isset($widget['allowMultiple'])?1:0);
				$newWidget->setFieldTitle($widget['fieldTitle']);
				$newWidget->setLabel($widget['label']);
				$newWidget->setTooltip($widget['tooltip']);

				$fieldData = json_decode($widget['fieldData']);

				if($fieldData) {
					$newWidget->setFieldData($fieldData);
				}

				$newWidget->setTemplate($template);
				$newWidget->setTemplateOrder($widget["templateOrder"]);
				$newWidget->setViewOrder($widget["viewOrder"]);
				$newWidget->setDisplayInPreview(isset($widget['displayInPreview'])?1:0);
				$newWidget->setSearchable(isset($widget['searchable'])?1:0);
				$newWidget->setAttemptAutocomplete(isset($widget['attemptAutocomplete'])?1:0);
				$newWidget->setFieldType($this->doctrine->em->find('Entity\Field_type', $widget['fieldType']));
				$newWidget->setDirectSearch(isset($widget['directSearch'])?1:0);
				$newWidget->setClickToSearch(isset($widget['clickToSearch'])?1:0);
				$newWidget->setClickToSearchType($widget['clickToSearchType']??1);


				// Persist
				$this->doctrine->em->persist($newWidget);

				$orderIndex++;
			}
		}
		$this->doctrine->em->flush();



		/**
		 * HACK HACK HACK
		 * trash the search cahce.  Cause right now we don't have cache namespaces.
		 * Todo: namespaces.
		 */

		if($this->config->item('enableCaching')) {
			$this->doctrineCache->setNamespace('searchCache_');
			$this->doctrineCache->deleteAll();
			$this->doctrineCache->setNamespace('sortCache_');
			$this->doctrineCache->deleteAll();
	   	}


	   	if($this->input->post("needsRebuild") == 1) {
	   		$this->reindexTemplate($template->getId());
	   	}


		instance_redirect('templates/');

	}

	public function delete($id)
	{
		//TODO Permissions checking
		$template = $this->doctrine->em->find('Entity\Template', $id);
		if ($template === null) {
			show_404();
		}


		$instances = $template->getInstances();
		foreach($instances as $instance) {
			$instance->removeTemplate($template);
		}
		$this->doctrine->em->flush();

		// Remove widgets first, or else a constraint fails
		$deleteQuery = $this->doctrine->em->createQuery("delete from Entity\Widget w where w.template = " . $template->getId());
		$deleteQuery->execute();


		$this->doctrine->em->remove($template);
		$this->doctrine->em->flush();

		instance_redirect('templates');

	}

	public function sort($id) {

		//TODO Permissions checking
		$data['template'] = $this->doctrine->em->find('Entity\Template', $id);

		// This seems like the easiest way to get the widgets in their display order
		$data['widgetsViewOrder'] = $this->doctrine->em->getRepository('Entity\Widget')
          ->findBy(
             array('template'=> $data['template']),
             array('view_order' => 'ASC')
           );

    $data['widgetsTemplateOrder'] = $this->doctrine->em->getRepository('Entity\Widget')
          ->findBy(
             array('template'=> $data['template']),
             array('template_order' => 'ASC')
           );

		if ($data['template'] === null) {
			show_404();
		}

		$this->template->title = 'Sort Template';

    $this->template->loadCSS(['template']);
  	$this->template->content->view('templates/sort', $data);
    $this->template->publish();

	}

	public function sort_update() {

		//TODO Permissions checking
		$template = $this->doctrine->em->find('Entity\Template', $this->input->post('templateId'));

		if ($template === null) {
		    show_404();
		}

		$widgets = $template->getWidgets();

		$template->setModifiedAt(new \DateTime('now'));

		foreach ($widgets as $key => $widget) {
			 $widget->setViewOrder($this->input->post('widget')[$widget->getId()]['view_order']);
			 $widget->setTemplateOrder($this->input->post('widget')[$widget->getId()]['template_order']);
		}

		$this->doctrine->em->flush();

	   	if($this->input->post("needsRebuild") == 1) {
	   		$this->reindexTemplate($template->getId());
	   	}

		instance_redirect('templates/');

	}

	public function forceRecache($templateId=null) {
		

		if($templateId) {
			$this->reindexTemplate($templateId);
		}
		
		$this->template->title = 'Reindex';

    	// $this->template->loadCSS(['template']);
  		$this->template->content->set("Reindexing Initiated");
    	$this->template->publish();


	}


	public function reindexTemplate($templateId=null) {
		$pheanstalk =  Pheanstalk\Pheanstalk::create($this->config->item("beanstalkd"));
		$tube = new Pheanstalk\Values\TubeName('cacheRebuild');
		// run a 15 minute TTR because zipping all these could take a while
		$pheanstalk->useTube($tube);

		$newTask = json_encode(["templateId"=>$templateId,"instance"=>$this->instance->getId()]);
		$jobId= $pheanstalk->put($newTask, Pheanstalk\Pheanstalk::DEFAULT_PRIORITY, 1);
		
	}


}

