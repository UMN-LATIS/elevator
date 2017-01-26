<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class ZipObjHandler extends ZipHandler {
	protected $supportedTypes = array("obj.zip");
	protected $noDerivatives = false;

	protected $pathToBlenderStage;

	protected $sourceBlenderScript = "import bpy

bpy.ops.import_mesh.ply(filepath=r'{{PATHTOX3D}}', filter_glob=\"*.ply\")

maxDimension = 5.0

scaleFactor = maxDimension / max(bpy.context.active_object.dimensions)

bpy.context.active_object.scale = (scaleFactor, scaleFactor, scaleFactor)

bpy.ops.object.origin_set()

bpy.ops.material.new()

bpy.data.materials[0].specular_intensity = 0.1

bpy.data.materials[0].use_vertex_color_paint = True

bpy.context.object.data.materials.append(bpy.data.materials[0])

world = bpy.context.scene.world

world.horizon_color = (1, 1, 1)

rnd = bpy.data.scenes[0].render

rnd.resolution_x = int(2000)
rnd.resolution_y = int(2000)
";


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
		$meshlabScript = realpath(NULL) . "/assets/blender/meshlab.mlx";

		$fileStatus = $this->sourceFile->makeLocal();

		if($fileStatus == FILE_GLACIER_RESTORING) {
			$this->postponeTime = 900;
			return JOB_POSTPONE;
		}
		elseif($fileStatus == FILE_ERROR) {
			return JOB_FAILED;
		}

		$this->pheanstalk->touch($this->job);

		$zip = new ZipArchive;
		$res = $zip->open($this->sourceFile->getPathToLocalFile());

		$targetPath = $this->sourceFile->getPathToLocalFile() . "_extracted";
		if(!$res) {
			$this->logging->processingInfo("createDerivative","objHandler","Coudl not extract zip",$this->getObjectId(),$this->job->getId());
			return JOB_FAILED;
		}

		$zip->extractTo($targetPath);
		$zip->close();

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


		$localPath = $this->sourceFile->getPathToLocalFile();
		$pathparts = pathinfo($localPath);

		$derivativeContainer = new fileContainerS3();
		$derivativeContainer->derivativeType = 'ply';
		$derivativeContainer->path = "derivative";
		$derivativeContainer->setParent($this->sourceFile->getParent());
		$derivativeContainer->originalFilename = $pathparts['filename'] . "_" . 'ply' . '.ply';

		putenv("DISPLAY=:1.0");

		$meshlabCommandLine =  $this->config->item("meshlabPath") . " -i " . $objFile . ($foundMTL?(" -s " . $meshlabScript):"") . " -o " . $derivativeContainer->getPathToLocalFile() . ".ply -om vc vn";

		exec("cd " . $baseFolder . " && " . $meshlabCommandLine . " 2>/dev/null");
		if(!file_exists($derivativeContainer->getPathToLocalFile() . ".ply")) {
			// failed to process with the texture, let's try without.
			$this->logging->processingInfo("createDerivative","objHandler","Failed to load texture, trying without",$this->getObjectId(),$this->job->getId());
			$meshlabCommandLine =  $this->config->item("meshlabPath") . " -i " . $objFile . " -o " . $derivativeContainer->getPathToLocalFile() . ".ply -om vc vn";
			exec("cd " . $baseFolder . " && " . $meshlabCommandLine . " 2>/dev/null");

		}

		rename($derivativeContainer->getPathToLocalFile() . ".ply", $derivativeContainer->getPathToLocalFile());

		$success = true;
		if(!$derivativeContainer->copyToRemoteStorage()) {
			$this->logging->processingInfo("createDerivative", "objHandler", "Could not upload ply", $this->getObjectId(), $this->job->getId());
			echo "Error copying to remote" . $derivativeContainer->getPathToLocalFile();
			$success=false;
		}
		else {
			if(!unlink($derivativeContainer->getPathToLocalFile())) {
				$this->logging->processingInfo("createThumbnails", "objHandler", "Could not delete source file", $this->getObjectId(), $this->job->getId());
				echo "Error deleting source" . $derivativeContainer->getPathToLocalFile();
				$success=false;
			}
		}
		$this->derivatives["ply"] = $derivativeContainer;
		if($success) {
			$this->queueTask(3);
			return JOB_SUCCESS;
		}
		else {
			return JOB_FAILED;
		}

	}

	public function createThumbnails($args) {

		$objHandler = new ObjHandler;
		$objHandler->job = $this->job;
		$objHandler->pheanstalk = $this->pheanstalk;
		$objHandler->sourceFile = $this->sourceFile;

		$result = $objHandler->createThumbInternal($this->derivatives['ply'], $args);
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
		$this->triggerReindex();
		return JOB_SUCCESS;

	}


	public function createNXS($args) {
		$objHandler = new ObjHandler;
		$objHandler->job = $this->job;
		$objHandler->pheanstalk = $this->pheanstalk;
		$objHandler->sourceFile = $this->sourceFile;
		

		$targetDerivative = $this->derivatives['ply'];
		$targetPath = $this->sourceFile->getPathToLocalFile() . "_extracted";
		if(!file_exists($targetPath)) {
			$zip = new ZipArchive;
			$res = $zip->open($this->sourceFile->getPathToLocalFile());
			if(!$res) {
				$this->logging->processingInfo("createDerivative","objHandler","Coudl not extract zip",$this->getObjectId(),$this->job->getId());
				return JOB_FAILED;
			}

			$zip->extractTo($targetPath);
			$zip->close();

		}
		
		
		// flatten any zipped dir structure
		$d = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($targetPath,RecursiveDirectoryIterator::SKIP_DOTS));
		foreach($d as $file){
        	if($file->isFile()) { 
        		rename($file->getPathname(), $targetPath . "/" . $file->getFilename());
        	}
		}

		$di = new RecursiveDirectoryIterator($targetPath,RecursiveDirectoryIterator::SKIP_DOTS);
		$it = new RecursiveIteratorIterator($di);
		$baseFolder = "";
		$objFile = "";
		$foundMTL = false;
		$foundTexture = false;
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
				$foundMTL = TRUE;
			}
			if(strtolower(pathinfo($file,PATHINFO_EXTENSION)) == "jpg" || strtolower(pathinfo($file,PATHINFO_EXTENSION)) == "png") {
				$foundTexture = TRUE;
			}

		}


		if($foundMTL && $foundTexture) {
			$localPath = $this->sourceFile->getPathToLocalFile();
			$pathparts = pathinfo($localPath);

			$derivativeContainer = new fileContainerS3();
			$derivativeContainer->derivativeType = 'ply_texture';
			$derivativeContainer->path = "derivative";
			$derivativeContainer->setParent($this->sourceFile->getParent());
			$derivativeContainer->originalFilename = $pathparts['filename'] . "_" . 'ply_texture' . '.ply';

			putenv("DISPLAY=:1.0");

			$meshlabCommandLine =  $this->config->item("meshlabPath") . " -i " . $objFile . " -o " . $derivativeContainer->getPathToLocalFile() . ".ply -om wt vn";

			exec("cd " . $baseFolder . " && " . $meshlabCommandLine . " 2>/dev/null");
			if(file_exists($derivativeContainer->getPathToLocalFile() . ".ply")) {
				$sourceFileLocalName = $targetPath . "/targetFile.ply";
				rename($derivativeContainer->getPathToLocalFile() . ".ply", $sourceFileLocalName);
				$targetDerivative = $derivativeContainer;
			}
		}

		if($targetDerivative->derivativeType == "ply_texture") {
			$localPath = $targetDerivative->getPathToLocalFile();
			$pathparts = pathinfo($localPath);

			$derivativeContainer = new fileContainerS3();
			$derivativeContainer->derivativeType = "nxs";
			$derivativeContainer->path = "derivative";
			$derivativeContainer->setParent($this->sourceFile->getParent());
			$derivativeContainer->originalFilename = $pathparts['filename'] . "_" . "nxs" . '.nxs';
			//TODO: catch errors here
			$nxsBuild = $this->config->item("nxsBuild");
			$nxsBuilderString = $nxsBuild . " -o " . $derivativeContainer->getPathToLocalFile() . " " . $sourceFileLocalName;
			exec("cd " . $targetPath . " && " . $nxsBuilderString . " 2>/dev/null");
			unlink($sourceFileLocalName);
			if(!file_exists($derivativeContainer->getPathToLocalFile() . ".nxs")) { 
				// try agian without the texture
				$nxsBuilderString = $nxsBuild . " -u -o " . $derivativeContainer->getPathToLocalFile() . " " . $sourceFileLocalName;
				exec("cd " . $targetPath . " && " . $nxsBuilderString . " 2>/dev/null");
				unlink($sourceFileLocalName);
			}

			$success = true;
			if(file_exists($derivativeContainer->getPathToLocalFile() . ".nxs")) {
				rename($derivativeContainer->getPathToLocalFile() . ".nxs", $derivativeContainer->getPathToLocalFile());
				$derivativeContainer->ready = true;
				if(!$derivativeContainer->copyToRemoteStorage()) {
					//TODO: log
					//TODO: remove derivative
					$this->logging->processingInfo("createThumbnails", "pdfhandler", "Could not upload thumbnail", $this->getObjectId(), $this->job->getId());
					echo "Error copying to remote" . $derivativeContainer->getPathToLocalFile();
					$success=false;
				}
				else {
					if(!unlink($derivativeContainer->getPathToLocalFile())) {
						$this->logging->processingInfo("createThumbnails", "pdfhandler", "Could not delete source file", $this->getObjectId(), $this->job->getId());
						echo "Error deleting source" . $derivativeContainer->getPathToLocalFile();
						$success=false;
					}
				}
				$derivativeArray['nxs'] = $derivativeContainer;
			}
			else {
				$this->logging->processingInfo("createNXS", "objHandler", "Could not create derivative", $this->getObjectId(), $this->job->getId());
				echo "Error generating derivatives" . $derivativeContainer->getPathToLocalFile();
				$success=false;
			}

			if($success) {
				$result = $derivativeArray;
			}
			else {
				$result = JOB_FAILED;
			}



		}
		else {
			$result = $objHandler->createNxsFileInternal($targetDerivative, $args);
		}

		if($result == JOB_POSTPONE) {
			return JOB_POSTPONE;
		}
		if($result == JOB_FAILED) {
			return JOB_FAILED;
		}
		else {
			$this->derivatives = array_merge($this->derivatives, $result);
		}
		$this->queueTask(5);
		return JOB_SUCCESS;

	}

	public function createSTL($args) {
		$objHandler = new ObjHandler;
		$objHandler->job = $this->job;
		$objHandler->pheanstalk = $this->pheanstalk;
		$objHandler->sourceFile = $this->sourceFile;

		$result = $objHandler->createSTLInternal($this->derivatives['ply'], $args);
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
					if($widgetContents->fileId == $this->getObjectId()) {
						$uploadWidget = $widgetContents;
					}
				}
			}

		}

		return $this->load->view("fileHandlers/" . "objhandler", ["widgetObject"=>$uploadWidget, "fileObject"=>$this, "embedded"=>$embedded, "allowOriginal"=>$includeOriginal, "fileContainers"=>$fileContainerArray], true);
	}

}

/* End of file  */
/* Location: ./application/controllers/ */