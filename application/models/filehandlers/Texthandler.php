<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


//require_once("fileHandlerBase.php");
class TextHandler extends FileHandlerBase {

	protected $supportedTypes = array("txt");
	protected $noDerivatives = true;

	public $icon = "txt.png";

	public $taskArray = [0=>["taskType"=>"extractMetadata", "config"=>["continue"=>true]],
						 //1=>["taskType"=>"cleanupOriginal", "config"=>array()],
						 1=>["taskType"=>"updateParent", "config"=>array()]];



	public function __construct()
	{
		parent::__construct();
		$this->load->helper("media");
		//Do your magic here
	}

	public function allDerivativesForAccessLevel($accessLevel) {
		$derivative = array();

		/**
		 * normally, this array should be best to worst, but we pack original in here later so that it
		 * doesn't get displayed in the view
		 */
		// if($accessLevel>=PERM_ORIGINALSWITHOUTDERIVATIVES) {
		// 	$derivative[] = "original";
		// }

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

		$fileStatus = $this->sourceFile->makeLocal();

		if($fileStatus == FILE_GLACIER_RESTORING) {
			$this->postponeTime = 900;
			return JOB_POSTPONE;
		}
		elseif($fileStatus == FILE_ERROR) {
			return JOB_FAILED;
		}


		if($this->sourceFile->getType() == "txt") {
			$pageText =file_get_contents($this->sourceFile->getPathToLocalFile());
		}

		$fileObject->metadata["filesize"] = $this->sourceFile->getFileSize();

		$this->globalMetadata["text"] = $pageText;



		if($args['continue'] == true) {
			$this->queueTask(1);
		}

		return JOB_SUCCESS;
	}


}

/* End of file imageHandler.php */
/* Location: ./application/models/imageHandler.php */