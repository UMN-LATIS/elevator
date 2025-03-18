<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class ZipScormHandler extends ZipHandler {
	protected $supportedTypes = array("scorm.zip");
	protected $noDerivatives = false;

	public $taskArray = [0=>["taskType"=>"identifyContents", "config"=>array()],
	1=>["taskType"=>"extractMetadata", "config"=>["continue"=>true]],
	2=>["taskType"=>"cleanupOriginal", "config"=>array()]

	];


	public function __construct()
	{
		parent::__construct();
	}

	function identifyTypeOfBundle($localFile) {
		foreach($localFile as $fileEntry) {
			$filename = pathinfo($fileEntry, PATHINFO_FILENAME);
			// TODO: this is preliminary, should be more in depth.
			if(strtolower($filename) == "imsmanifest") {
				return true;
			}
		}
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

		$fileObject = $this->sourceFile;
		$fileObject->metadata["filesize"] = $this->sourceFile->getFileSize();

		if($args['continue'] == true) {
			$this->queueTask(2);
		}

		return JOB_SUCCESS;
	}



	/**
	 * Override the parent handler and return the obj_handler
	 */

	public function getEmbedViewWithFiles($fileContainerArray, $includeOriginal=false, $embedded=false) {

		if(!$this->parentObject && $this->parentObjectId) {
			$this->parentObject = new Asset_model($this->parentObjectId);
		}

		$uploadWidget = null;
		if($this->parentObject) {
			$uploadObjects = $this->parentObject->getAllWithinAsset("Upload");
			foreach($uploadObjects as $upload) {
				foreach($upload->fieldContentsArray as $widgetContents) {
					if($widgetContents->fileId == $this->objectId) {
						$uploadWidget = $widgetContents;
					}
				}
			}

		}

		return $this->load->view("fileHandlers/" . "filehandlerbase", ["widgetObject"=>$uploadWidget, "fileObject"=>$this, "embedded"=>$embedded, "allowOriginal"=>$includeOriginal, "fileContainers"=>$fileContainerArray], true);
	}

}

/* End of file  */
/* Location: ./application/controllers/ */