<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once("application/traits/ThreeDProcessing.php");
class GlbHandler extends FileHandlerBase {

	use ThreeDProcessing;
	
	protected $supportedTypes = array("glb");
	protected $noDerivatives = false;




	public $taskArray = [
	0=>["taskType"=>"extractMetadata", "config"=>["continue"=>true]],
	1=>["taskType"=>"createDerivative", "config"=>array()],
	2=>["taskType"=>"createThumbnails", "config"=>[["width"=>250, "height"=>250, "type"=>"thumbnail", "path"=>"thumbnail"],
						  												["width"=>500, "height"=>500, "type"=>"thumbnail2x", "path"=>"thumbnail"],
						  												["width"=>75, "height"=>150, "type"=>"tiny", "path"=>"thumbnail"],
						  												["width"=>150, "height"=>75, "type"=>"tiny2x", "path"=>"thumbnail"]
						  												]],
																		3=>["taskType"=>"noTask", "config"=>[]]
	];


	public function __construct()
	{
		parent::__construct();
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

		if($accessLevel>=$this->getPermission()) {
			$derivative[] = "glb-thumb";
			$derivative[] = "glb-medium";
			$derivative[] = "glb-large";
			$derivative[] = "usdz";
		}
		if($accessLevel>PERM_NOPERM) {
			$derivative[] = "thumbnail";
			$derivative[] = "thumbnail2x";
			$derivative[] = "tiny";
			$derivative[] = "tiny2x";
		}

		$returnArray = array();
		foreach($derivative as $entry) {
			if(isset($this->derivatives[$entry])) {
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

	public function extractMetadata($args) {

		$fileObject = $this->sourceFile;
		$fileObject->metadata["filesize"] = $this->sourceFile->getFileSize();

		if($args['continue'] == true) {
			$this->queueTask(1);
		}

		return JOB_SUCCESS;
	}


	public function createDerivative($args) {
		$fileStatus = $this->sourceFile->makeLocal();

		if($fileStatus == FILE_GLACIER_RESTORING) {
			$this->postponeTime = 900;
			return JOB_POSTPONE;
		}
		elseif($fileStatus == FILE_ERROR) {
			return JOB_FAILED;
		}

		$this->pheanstalk->touch($this->job);
		if(!file_exists($this->sourceFile->getPathToLocalFile())) {
			$this->logging->processingInfo("createDerivative","objHandler","Local File Not Found",$this->getObjectId(),$this->job->getId());
			return JOB_FAILED;
		}


		$foundMTL = false;

		$localPath = $this->sourceFile->getPathToLocalFile();
		$pathparts = pathinfo($localPath);
		$originalExtension = pathinfo($this->sourceFile->originalFilename, PATHINFO_EXTENSION);

		$objFile = $localPath . "." .$originalExtension;

		rename($localPath, $objFile);
		$baseFolder = pathinfo($localPath, PATHINFO_DIRNAME);
			
		$outputFilename = $pathparts['filename'];
		


		// create a set of GLB files as well
		$glbDerivativeSets = [
			"thumb" => "thumb",
			"medium" => "medium",
			"large" => "large"
		];

		if(filesize($objFile) < 10*1024*1024) {
			// small files, just do the large derivative
			$glbDerivativeSets = [
				"large" => "large"
			];
		}
		foreach($glbDerivativeSets as $label=>$scale) {

			if($derivativeContainer = $this->generateGLB($objFile, $outputFilename, $label, $scale)) {
				$this->derivatives["glb-" . $label] = $derivativeContainer;
			}
		}

		if($derivativeContainer = $this->generateUSDZ($objFile, $outputFilename, "large")) {
			$this->derivatives["usdz"] = $derivativeContainer;
		}


		if(count($this->derivatives) > 0) {
			$this->queueTask(2);
			return JOB_SUCCESS;
		}
		else {
			return JOB_FAILED;
		}

	}

	
	public function noTask($args) {
		return JOB_SUCCESS;
	}
	
}

/* End of file  */
/* Location: ./application/controllers/ */