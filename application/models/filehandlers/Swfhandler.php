<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class SwfHandler extends FileHandlerBase {

	protected $supportedTypes = array("swf");
	protected $noDerivatives = true;

	public $taskArray = [0=>["taskType"=>"extractMetadata", "config"=>array()]
							];


	public function __construct()
	{
		parent::__construct();
	}

	public function allDerivativesForAccessLevel($accessLevel) {
		$derivative = array();

		$returnArray = array();
		foreach($derivative as $entry) {
			if($entry == "original") {
				$returnArray[$entry] = $this->sourceFile;
			}
			else if(isset($this->derivatives[$entry])) {
				$returnArray[$entry] = $this->derivatives[$entry];
			}
			$returnArray[$entry]->downloadable = true;
		}
		if(count($returnArray)>0) {
			return $returnArray;
		}
		else {
			throw new Exception("Derivative not found");
		}
	}

	public function extractMetadata($args) {

		if(!isset($args['fileObject'])) {
			$fileObject = $this->sourceFile;
		}
		else {
			$fileObject = $args['fileObject'];
		}


		$fileObject->metadata["filesize"] = $this->sourceFile->getFileSize();

		return JOB_SUCCESS;
	}

}

/* End of file  */
/* Location: ./application/controllers/ */