<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


/**
 * Dummy base class, allows for overloading by S3 and referencing local files
 */

class FileContainer extends CI_Model {

	public $localAsset;
	public $metadata = array();

	public function __construct($targetFile=null)
	{
		parent::__construct();
		if($targetFile) {
			$this->localAsset = $targetFile;
			$this->originalFilename = $targetFile;
		}


	}

	public function getURLForFile($stripHTTP=false) {
		return site_url($this->localAsset);
	}

	public function getType() {
		return pathinfo($this->originalFilename, PATHINFO_EXTENSION);
	}

	public function getPathToLocalFile() {
		return $this->localAsset;
	}

}

/* End of file filecontainer.php */
/* Location: ./application/models/filecontainer.php */