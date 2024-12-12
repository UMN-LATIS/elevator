<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

require_once("application/traits/ThreeDProcessing.php");

class ZipObjHandler extends ZipHandler {
	use ThreeDProcessing;
	protected $supportedTypes = array("obj.zip");
	protected $noDerivatives = false;
	
	public $taskArray = [0=>["taskType"=>"identifyContents", "config"=>array()],
	1=>["taskType"=>"extractMetadata", "config"=>["continue"=>true]],
	2=>["taskType"=>"createDerivative", "config"=>array()],
	3=>["taskType"=>"createThumbnails", "config"=>[["width"=>250, "height"=>250, "type"=>"thumbnail", "path"=>"thumbnail"],
						  												["width"=>500, "height"=>500, "type"=>"thumbnail2x", "path"=>"thumbnail"],
						  												["width"=>75, "height"=>150, "type"=>"tiny", "path"=>"thumbnail"],
						  												["width"=>150, "height"=>75, "type"=>"tiny2x", "path"=>"thumbnail"]
						  												]
						  												],
	4=>["taskType"=>"createNXS", "config"=>["ttr"=>900]],
	5=>["taskType"=>"createSTL", "config"=>[]]
	];

	


	public function __construct()
	{
		parent::__construct();
	}

	function identifyTypeOfBundle($localFile) {
		foreach($localFile as $fileEntry) {
			$ext = pathinfo($fileEntry, PATHINFO_EXTENSION);
			if(strtolower($ext) == 'obj') {
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
			$this->queueTask(2);
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

		list($objFile, $baseFolder) = $this->getObjFromZip();

		$localPath = $this->sourceFile->getPathToLocalFile();
		$pathparts = pathinfo($localPath);
		$outputFilename = $pathparts['filename'];
		$baseFolder = pathinfo($localPath, PATHINFO_DIRNAME);

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


		if($success) {
			$this->queueTask(3);
			return JOB_SUCCESS;
		}
		else {
			return JOB_FAILED;
		}

	}

	public function getObjFromZip() {
		$zip = new ZipArchive;
		if(!file_exists($this->sourceFile->getPathToLocalFile() . "_extracted")) {
			$res = $zip->open($this->sourceFile->getPathToLocalFile());

			$targetPath = $this->sourceFile->getPathToLocalFile() . "_extracted";
			if(!$res) {
				$this->logging->processingInfo("createDerivative","objHandler","Coudl not extract zip",$this->getObjectId(), 0);
				return JOB_FAILED;
			}
	
			$zip->extractTo($targetPath);
			$zip->close();
		}
		

		$di = new RecursiveDirectoryIterator($targetPath,RecursiveDirectoryIterator::SKIP_DOTS);
		$it = new RecursiveIteratorIterator($di);
		$baseFolder = "";
		$objFile = "";
		$foundMTL = false;
		foreach($it as $file) {
			$onlyFilename = pathinfo($file, PATHINFO_FILENAME);
			if(substr($onlyFilename, 0,1) == ".") {
				continue;
			}

    		if(strtolower(pathinfo($file,PATHINFO_EXTENSION)) == "obj") {
    			$objFile = $file;
    			$baseFolder = pathinfo($file, PATHINFO_DIRNAME);
    		}
			if(strtolower(pathinfo($file,PATHINFO_EXTENSION)) == "mtl") {
				// if we found an mtl, we need ot apply a script for texutre conversion
				$foundMTL = TRUE;
    		}


		}
		return [$objFile,$baseFolder];
	}


}

/* End of file  */
/* Location: ./application/controllers/ */