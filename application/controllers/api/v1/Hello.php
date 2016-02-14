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

	public function clearCache($cacheName) {
		if($this->config->item('enableCaching')) {
			$this->doctrineCache->setNamespace('searchCache_');
			$this->doctrineCache->delete($cacheName);
		}
	}

}

/* End of file  */
/* Location: ./application/controllers/ */