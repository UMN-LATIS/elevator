<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class ObjHandler extends FileHandlerBase {
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
    4=>["taskType"=>"createSTL", "config"=>[]],
	5=>["taskType"=>"generateAltText", "config"=>array("ttr"=>600)],
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
			$derivative[] = "glb";
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
				$returnArray[$entry]->downloadable = true;
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
		$meshlabScript = realpath(NULL) . "/assets/blender/meshlab.mlx";
		$fileStatus = $this->sourceFile->makeLocal();

		if($fileStatus == FILE_GLACIER_RESTORING) {
			$this->postponeTime = 900;
			return JOB_POSTPONE;
		}
		elseif($fileStatus == FILE_ERROR) {
			return JOB_FAILED;
		}

		if(!file_exists($this->sourceFile->getPathToLocalFile())) {
			$this->logging->processingInfo("createDerivative","objHandler","Local File Not Found",$this->getObjectId(),0);
			return JOB_FAILED;
		}

		$sourceFile = $this->swapLocal3MFForSTL($this->sourceFile);

		$foundMTL = false;

		$localPath = $sourceFile->getPathToLocalFile();
		$pathparts = pathinfo($localPath);
		$originalExtension = pathinfo($sourceFile->originalFilename, PATHINFO_EXTENSION);

		$objFile = $localPath . "." .$originalExtension;

		rename($localPath, $objFile);
		$baseFolder = pathinfo($localPath, PATHINFO_DIRNAME);

		$derivativeContainer = new fileContainerS3();
		$derivativeContainer->derivativeType = 'ply';
		$derivativeContainer->path = "derivative";
		$derivativeContainer->setParent($this->sourceFile->getParent());
		$derivativeContainer->originalFilename = $pathparts['filename'] . "_" . 'ply' . '.ply';


		$meshlabCommandLine =  $this->config->item("meshlabPath") . " obj_to_ply " . $objFile . " " . $derivativeContainer->getPathToLocalFile() . ".ply";

		exec("cd " . $baseFolder . " && " . $meshlabCommandLine . " 2>/dev/null");
		rename($derivativeContainer->getPathToLocalFile() . ".ply", $derivativeContainer->getPathToLocalFile());

		$success = true;
		if(!$derivativeContainer->copyToRemoteStorage()) {
			$this->logging->processingInfo("createDerivative", "objHandler", "Could not upload ply", $this->getObjectId(), 0);
			echo "Error copying to remote" . $derivativeContainer->getPathToLocalFile();
			$success=false;
		}
		else {
			if(!unlink($derivativeContainer->getPathToLocalFile())) {
				$this->logging->processingInfo("createThumbnails", "objHandler", "Could not delete source file", $this->getObjectId(), 0);
				echo "Error deleting source" . $derivativeContainer->getPathToLocalFile();
				$success=false;
			}
		}
		$derivativeContainer->ready = true;
		$this->derivatives["ply"] = $derivativeContainer;


		// create glb
		$derivativeContainer = new fileContainerS3();
		$derivativeContainer->derivativeType = 'glb';
		$derivativeContainer->path = "derivative";
		$derivativeContainer->setParent($this->sourceFile->getParent());
		$derivativeContainer->originalFilename = $pathparts['filename'] . "_" . 'glb' . '.glb';


		$meshlabCommandLine =  $this->config->item("meshlabPath") . " obj_to_glb " . $objFile . " " . $derivativeContainer->getPathToLocalFile() . ".glb";

		exec("cd " . $baseFolder . " && " . $meshlabCommandLine . " 2>/dev/null");
		rename($derivativeContainer->getPathToLocalFile() . ".ply", $derivativeContainer->getPathToLocalFile());

		$success = true;
		if(!$derivativeContainer->copyToRemoteStorage()) {
			$this->logging->processingInfo("createDerivative", "objHandler", "Could not upload glb", $this->getObjectId(), 0);
			echo "Error copying to remote" . $derivativeContainer->getPathToLocalFile();
			$success=false;
		}
		else {
			if(!unlink($derivativeContainer->getPathToLocalFile())) {
				$this->logging->processingInfo("createThumbnails", "objHandler", "Could not delete source file", $this->getObjectId(), 0);
				echo "Error deleting source" . $derivativeContainer->getPathToLocalFile();
				$success=false;
			}
		}
		$derivativeContainer->ready = true;
		$this->derivatives["glb"] = $derivativeContainer;

		if($success) {
			$this->queueTask(2);
			return JOB_SUCCESS;
		}
		else {
			return JOB_FAILED;
		}

	}

	public function createSTL($args) {


		$result = $this->createSTLInternal($this->derivatives['ply'], $args);
		if($result == JOB_POSTPONE) {
			return JOB_POSTPONE;
		}
		if($result == JOB_FAILED) {
			return JOB_FAILED;
		}
		else {
			$this->derivatives = array_merge($this->derivatives, $result);
		}
		return JOB_SUCCESS;


	}

	public function createSTLInternal($sourceFileContainer, $args) {
		$success = true;


		$fileStatus = $sourceFileContainer->makeLocal();

		if($fileStatus == FILE_GLACIER_RESTORING) {
			$this->postponeTime = 900;
			return JOB_POSTPONE;
		}
		elseif($fileStatus == FILE_ERROR) {
			return JOB_FAILED;
		}


		$sourceFileLocalName = $sourceFileContainer->getPathToLocalFile() . ".ply";
		rename($sourceFileContainer->getPathToLocalFile(), $sourceFileLocalName);



		$localPath = $sourceFileContainer->getPathToLocalFile();
		$pathparts = pathinfo($localPath);
		$baseFolder = pathinfo($localPath, PATHINFO_DIRNAME);

		$derivativeContainer = new fileContainerS3();
		$derivativeContainer->derivativeType = "stl";
		$derivativeContainer->path = "derivative";
		$derivativeContainer->setParent($this->sourceFile->getParent());
		$derivativeContainer->originalFilename = $pathparts['filename'] . "_" . "stl" . '.stl';
		//TODO: catch errors here

		$meshlabCommandLine =  $this->config->item("meshlabPath") . " ply_to_stl " . $sourceFileLocalName . " " . $derivativeContainer->getPathToLocalFile() . ".stl";

		exec("cd " . $baseFolder . " && " . $meshlabCommandLine . " 2>/dev/null");
		rename($derivativeContainer->getPathToLocalFile() . ".stl", $derivativeContainer->getPathToLocalFile());

		$success = true;
		if(!$derivativeContainer->copyToRemoteStorage()) {
			$this->logging->processingInfo("createDerivative", "objHandler", "Could not upload stl", $this->getObjectId(), 0);
			echo "Error copying to remote" . $derivativeContainer->getPathToLocalFile();
			$success=false;
		}
		else {
			if(!unlink($derivativeContainer->getPathToLocalFile())) {
				$this->logging->processingInfo("createThumbnails", "objHandler", "Could not delete source file", $this->getObjectId(), 0);
				echo "Error deleting source" . $derivativeContainer->getPathToLocalFile();
				$success=false;
			}
		}

		unlink($sourceFileLocalName);
		$derivativeContainer->ready = true;
		$derivativeArray['stl'] = $derivativeContainer;
		
		if($success) {
			return $derivativeArray;
		}
		else {
			return JOB_FAILED;
		}



	}

	public function createThumbnails($args) {

		$result = $this->createThumbInternal($this->derivatives['ply'], $args);
		if($result == JOB_POSTPONE) {
			return JOB_POSTPONE;
		}
		if($result == JOB_FAILED) {
			return JOB_FAILED;
		}
		else {
			$this->derivatives = array_merge($this->derivatives, $result);
		}
		$this->queueTask(3);
		$this->triggerReindex();
		return JOB_SUCCESS;

	}

	public function createNXS($args) {

		$result = $this->createNxsFileInternal($this->derivatives['ply'], $args);
		if($result == JOB_POSTPONE) {
			return JOB_POSTPONE;
		}
		if($result == JOB_FAILED) {
			return JOB_FAILED;
		}
		else {
			$this->derivatives = array_merge($this->derivatives, $result);
		}
		$this->queueTask(4);
		return JOB_SUCCESS;

	}



	public function createThumbInternal($sourceFileContainer, $args) {


		ini_set('memory_limit', '512M');
		$success = true;

		$fileStatus = $sourceFileContainer->makeLocal();

		if($fileStatus == FILE_GLACIER_RESTORING) {
			$this->postponeTime = 900;
			return JOB_POSTPONE;
		}
		elseif($fileStatus == FILE_ERROR) {
			return JOB_FAILED;
		}


		rename($sourceFileContainer->getPathToLocalFile(), $sourceFileContainer->getPathToLocalFile() . ".ply");

		$targetLargeFileShortName = $sourceFileContainer->getPathToLocalFile() . "_output";

		$blenderCommandLine = $this->config->item('blenderBinary') . " -b /opt/stage.blend -P /root/convert.py". " -o " . $targetLargeFileShortName . " -F JPEG -x 1 -f 1 -- " . $sourceFileContainer->getPathToLocalFile() . ".ply";

		// blender will generate a new output name
		$targetLargeFile = $targetLargeFileShortName . "0001.jpg";

		$process = new Cocur\BackgroundProcess\BackgroundProcess($blenderCommandLine);
		$process->run();
		while($process->isRunning()) {
			sleep(5);
			echo ".";
		}
		unlink($sourceFileContainer->getPathToLocalFile() . ".ply");
		$derivativeArray = array();
		foreach($args as $derivativeSetting) {
			if(!isset($derivativeSetting['type'])) {
				continue;
			}
			$derivativeType = $derivativeSetting['type'];
			$width = $derivativeSetting['width'];
			$height = $derivativeSetting['height'];

			$localPath = $sourceFileContainer->getPathToLocalFile();
			$pathparts = pathinfo($localPath);

			$derivativeContainer = new fileContainerS3();
			$derivativeContainer->derivativeType = $derivativeType;
			$derivativeContainer->path = $derivativeSetting['path'];
			$derivativeContainer->setParent($this->sourceFile->getParent());
			$derivativeContainer->originalFilename = $pathparts['filename'] . "_" . $derivativeType . '.jpg';
			//TODO: catch errors here





			if(compressImageAndSave(new FileContainer($targetLargeFile), $derivativeContainer,$width, $height, 80, 0)) {
				$derivativeContainer->ready = true;
				$this->extractMetadata(['fileObject'=>$derivativeContainer, "continue"=>false]);
				if(!$derivativeContainer->copyToRemoteStorage()) {
					//TODO: log
					//TODO: remove derivative
					$this->logging->processingInfo("createThumbnails", "pdfhandler", "Could not upload thumbnail", $this->getObjectId(), 0);
					echo "Error copying to remote" . $derivativeContainer->getPathToLocalFile();
					$success=false;
				}
				else {
					if(!unlink($derivativeContainer->getPathToLocalFile())) {
						$this->logging->processingInfo("createThumbnails", "pdfhandler", "Could not delete source file", $this->getObjectId(), 0);
						echo "Error deleting source" . $derivativeContainer->getPathToLocalFile();
						$success=false;
					}
				}
				$derivativeArray[$derivativeType] = $derivativeContainer;
			}
			else {
				$this->logging->processingInfo("createThumbnails", "pdfhandler", "Could not create derivative", $this->getObjectId(), 0);
				echo "Error generating derivatives" . $derivativeContainer->getPathToLocalFile();
				$success=false;
			}
		}

		if($success) {
			return $derivativeArray;
		}
		else {
			return JOB_FAILED;
		}


	}


	public function createNxsFileInternal($sourceFileContainer, $args) {

		$success = true;


		$fileStatus = $sourceFileContainer->makeLocal();

		if($fileStatus == FILE_GLACIER_RESTORING) {
			$this->postponeTime = 900;
			return JOB_POSTPONE;
		}
		elseif($fileStatus == FILE_ERROR) {
			return JOB_FAILED;
		}


		$sourceFileLocalName = $sourceFileContainer->getPathToLocalFile() . ".ply";
		rename($sourceFileContainer->getPathToLocalFile(), $sourceFileLocalName);



		$localPath = $sourceFileContainer->getPathToLocalFile();
		$pathparts = pathinfo($localPath);

		$derivativeContainer = new fileContainerS3();
		$derivativeContainer->derivativeType = "nxs";
		$derivativeContainer->path = "derivative";
		$derivativeContainer->setParent($this->sourceFile->getParent());
		$derivativeContainer->originalFilename = $pathparts['filename'] . "_" . "nxs" . '.nxs';
		//TODO: catch errors here
		$nxsBuild = $this->config->item("nxsBuild");
		$nxsBuilderString = $nxsBuild . " -o " . $derivativeContainer->getPathToLocalFile() . " " . $sourceFileLocalName;
		exec($nxsBuilderString . " 2>/dev/null");
		unlink($sourceFileLocalName);

		if(file_exists($derivativeContainer->getPathToLocalFile() . ".nxs")) {
			rename($derivativeContainer->getPathToLocalFile() . ".nxs", $derivativeContainer->getPathToLocalFile());
			$derivativeContainer->ready = true;
			if(!$derivativeContainer->copyToRemoteStorage()) {
				//TODO: log
				//TODO: remove derivative
				$this->logging->processingInfo("createThumbnails", "pdfhandler", "Could not upload thumbnail", $this->getObjectId(), 0);
				echo "Error copying to remote" . $derivativeContainer->getPathToLocalFile();
				$success=false;
			}
			else {
				if(!unlink($derivativeContainer->getPathToLocalFile())) {
					$this->logging->processingInfo("createThumbnails", "pdfhandler", "Could not delete source file", $this->getObjectId(), 0);
					echo "Error deleting source" . $derivativeContainer->getPathToLocalFile();
					$success=false;
				}
			}
			$derivativeArray['nxs'] = $derivativeContainer;
		}
		else {
			$this->logging->processingInfo("createNXS", "objHandler", "Could not create derivative", $this->getObjectId(), 0);
			echo "Error generating derivatives" . $derivativeContainer->getPathToLocalFile();
			$success=false;
		}


		if($success) {
			return $derivativeArray;
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
			return;
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
			echo ".";
		}
		return new FileContainer($dest);

	}

	public function generateAltText() {

		$derivative = $this->derivatives["thumbnail2x"];
		$derivative->makeLocal();
		
		$uploadWidget = $this->getUploadWidget();


		$metadata = [];
		foreach($this->parentObject->assetObjects as $widget) {
			if($widget->getDisplay() && $widget->hasContents()) {
				$metadata[$widget->getLabel()] = $widget->getAsText();
			}
		}

		$metadata["type"] = "3d model";

		$altText = $this->getAltTextForMedia("", $metadata, $derivative);
		$uploadWidget = $this->getUploadWidget(true);
		$uploadWidget->fileDescription = $altText;
		$this->parentObject->save(true,false);

		

	}

}

/* End of file  */
/* Location: ./application/controllers/ */