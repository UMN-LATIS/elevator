<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class Hello extends API_Controller {

	public function __construct()
	{
		parent::__construct();
	}

	public function index()
	{
		if($this->isAuthenticated) {
			echo "HELLO";
		}
	}

}

/* End of file  */
/* Location: ./application/controllers/ */