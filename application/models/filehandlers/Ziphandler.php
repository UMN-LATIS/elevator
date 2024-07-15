<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class ZipHandler extends FileHandlerBase {

	protected $supportedTypes = array("zip");
	protected $noDerivatives = true;

	public $taskArray = [0=>["taskType"=>"identifyContents", "config"=>array()],
						  1=>["taskType"=>"extractMetadata", "config"=>["continue"=>true]]
							];


	public function __construct()
	{
		parent::__construct();
	}

	public function allDerivativesForAccessLevel($accessLevel) {
		$derivative = array();

		if($accessLevel>PERM_NOPERM) {
			$derivative[] = "thumbnail";
			$derivative[] = "thumbnail2x";
			$derivative[] = "tiny";
			$derivative[] = "tiny2x";
		}

		$returnArray = array();
		foreach($derivative as $entry) {
			if($entry == "original") {
				$returnArray[$entry] = $this->sourceFile;
			}
			else if(isset($this->derivatives[$entry])) {
				$returnArray[$entry] = $this->derivatives[$entry];
			}
		}
		if(count($returnArray)>0) {
			return $returnArray;
		}
		else {
			throw new Exception("Derivative not found");
		}
	}

	public function identifyContents($args) {
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

		$this->pheanstalk->touch($this->job);

		$allZipHandlers = $this->getSubclassesOfParentClass(get_class($this));

		$fileList = $this->listContentsOfZip($this->sourceFile->getPathToLocalFile());
		$handler = null;
		foreach($allZipHandlers as $handler) {
			$handler = new $handler;
			if($handler->identifyTypeOfBundle($fileList)) {
				$this->overrideHandlerClass = get_class($handler);
				break;
			}
		}
		// use the handlers task array to enque our next item
		if($handler) {
			$this->taskArray = $handler->taskArray;
			$this->taskListHasChanged = true;
		}

		$this->queueTask(1, $this->taskArray[1]["config"]);

		return JOB_SUCCESS;


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


	function listContentsOfZip($targetFile) {
		$zip = new ZipArchive;
		$res = $zip->open($targetFile);
		$filenames = array();
		if ($res === TRUE) {
			for( $i = 0; $i < $zip->numFiles; $i++ ){
    			$stat = $zip->statIndex( $i );
    			$filenames[] = basename( $stat['name'] );
    		}
    	}
		return $filenames;
	}

	function getSubclassesOfParentClass($parent) {
	    $result = array();
	    foreach (get_declared_classes() as $class) {
	        if (is_subclass_of($class, $parent))
	            $result[] = $class;
	    }
	    return $result;
	}

}

/* End of file  */
/* Location: ./application/controllers/ */