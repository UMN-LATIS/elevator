<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class errorHandler extends Instance_Controller {

	public function __construct()
	{
		$this->noRedirect = true;
		parent::__construct();
	}

	public function index()
	{

	}

	public function fatalError() {

		$this->load->library('session');
		$errorMessage = $this->session->flashdata('error');

		$log = new Entity\Log();
		$log->setMessage($errorMessage);
		$log->setCreatedAt(new \DateTime("now"));
		$this->doctrine->em->persist($log);
		$this->doctrine->em->flush();
		$this->useUnauthenticatedTemplate = true;
		$this->template->content->view("errors/fatalError", ["errorMessage"=>$errorMessage]);
		$this->template->publish();
	}

	public function error($errorName)
	{
		$this->errorhandler_helper->callError($errorName);
	}

}

/* End of file  */
/* Location: ./application/controllers/ */