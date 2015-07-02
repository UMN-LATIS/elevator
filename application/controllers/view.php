<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class view extends CI_Controller {

	public function __construct()
	{
		parent::__construct();
	}

	public function load($viewTitle)
	{

		$view = $this->doctrine->em->getRepository("Entity\StaticPage")->findOneBy(["instance"=>$this->instance, "viewTitle"=>$viewTitle]);


	}

}

/* End of file  */
/* Location: ./application/controllers/ */