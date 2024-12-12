<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once("application/traits/ThreeDProcessing.php");
class ObjHandler extends FileHandlerBase {

	use ThreeDProcessing;
	
	protected $supportedTypes = array("obj", "stl", "3mf");
	protected $noDerivatives = false;




	public $taskArray = [
	0=>["taskType"=>"extractMetadata", "config"=>["continue"=>true]],
	1=>["taskType"=>"createDerivative", "config"=>array()],
	2=>["taskType"=>"createThumbnails", "config"=>[["width"=>250, "height"=>250, "type"=>"thumbnail", "path"=>"thumbnail"],
						  												["width"=>500, "height"=>500, "type"=>"thumbnail2x", "path"=>"thumbnail"],
						  												["width"=>75, "height"=>150, "type"=>"tiny", "path"=>"thumbnail"],
						  												["width"=>150, "height"=>75, "type"=>"tiny2x", "path"=>"thumbnail"]
						  												]],
    3=>["taskType"=>"createNXS", "config"=>["ttr"=>900]],
    4=>["taskType"=>"createSTL", "config"=>[]]
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
			$derivative[] = "nxs";
			$derivative[] = "ply";
			$derivative[] = "stl";
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

		$sourceFile = $this->swapLocal3MFForSTL($this->sourceFile);

		$foundMTL = false;

		$localPath = $sourceFile->getPathToLocalFile();
		$pathparts = pathinfo($localPath);
		$originalExtension = pathinfo($sourceFile->originalFilename, PATHINFO_EXTENSION);

		if(!str_contains($localPath, ".".$originalExtension)) {
			$objFile = $localPath . "." .$originalExtension;
			rename($localPath, $objFile);
		}
		else {
			$objFile = $localPath;
		}
		
		
		$baseFolder = pathinfo($localPath, PATHINFO_DIRNAME);
			
		$outputFilename = $pathparts['filename'];
		if($derivativeContainer = $this->generatePLY($objFile, $outputFilename, $baseFolder)) {
			$this->derivatives["ply"] = $derivativeContainer;
		}
		

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

	
	function swapLocal3MFForSTL($sourceFile= null) {
		if(!$sourceFile) {
			$sourceFile = $this->sourceFile;
		}
		// this is ugly, but we might get passed in an intermediate. We need to look at the original to see if 
		// it was a whole slide
		if($sourceFile->getType() != "3mf") {
			return $sourceFile;
		}

		$source = $sourceFile->getPathToLocalFile();
		// use the original filename as well to keep extensions sane
		$dest = $this->sourceFile->getPathToLocalFile() . ".stl";
		if(file_exists($dest)) {
			return new FileContainer($dest);
		}
		$convertString = $this->config->item('3mf2stl') . " -i " . $source . " -o " . $dest;

		$process = new Cocur\BackgroundProcess\BackgroundProcess($convertString);
		$process->run();
		while($process->isRunning()) {
			sleep(5);
			$this->pheanstalk->touch($this->job);
			echo ".";
		}

		return new FileContainer($dest);

	}

	
}

/* End of file  */
/* Location: ./application/controllers/ */