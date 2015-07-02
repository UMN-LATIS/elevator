<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class ObjHandler extends FileHandlerBase {
	protected $supportedTypes = array("obj");
	protected $noDerivatives = false;

	protected $pathToBlenderStage;

	protected $sourceBlenderScript = "import bpy

bpy.ops.import_mesh.ply(filepath=r'{{PATHTOX3D}}', filter_glob=\"*.ply\")

bpy.ops.material.new()

bpy.data.materials[0].specular_intensity = 0.1

bpy.data.materials[0].use_vertex_color_paint = True

bpy.context.object.data.materials.append(bpy.data.materials[0])

world = bpy.context.scene.world

world.horizon_color = (1, 1, 1)

rnd = bpy.data.scenes[0].render

rnd.resolution_x = int(2000)
rnd.resolution_y = int(2000)";


	public $taskArray = [
	0=>["taskType"=>"extractMetadata", "config"=>["continue"=>true]],
	1=>["taskType"=>"createDerivative", "config"=>array()],
	2=>["taskType"=>"createThumbnails", "config"=>[["width"=>250, "height"=>250, "type"=>"thumbnail", "path"=>"thumbnail"],
						  												["width"=>500, "height"=>500, "type"=>"thumbnail2x", "path"=>"thumbnail"],
						  												["width"=>75, "height"=>150, "type"=>"tiny", "path"=>"thumbnail"],
						  												["width"=>150, "height"=>75, "type"=>"tiny2x", "path"=>"thumbnail"]
						  												]
						  												]
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
			$derivative[] = "ply";
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
		$meshlabScript = dirname(realpath(NULL)) . "/public/assets/blender/meshlab.mlx";
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

		$objFile = $localPath . ".obj";

		rename($localPath, $objFile);
		$baseFolder = pathinfo($localPath, PATHINFO_DIRNAME);

		$derivativeContainer = new fileContainerS3();
		$derivativeContainer->derivativeType = 'ply';
		$derivativeContainer->path = "derivative";
		$derivativeContainer->setParent($this->sourceFile->getParent());
		$derivativeContainer->originalFilename = $pathparts['filename'] . "_" . 'ply' . '.ply';

		putenv("DISPLAY=:99.0");



		$meshlabCommandLine = $this->config->item("meshlabPath") . "meshlabserver -i " . $objFile . ($foundMTL?(" -s " . $meshlabScript):"") . " -o " . $derivativeContainer->getPathToLocalFile() . ".ply -om vn vc";

		exec("cd " . $baseFolder . " && " . $meshlabCommandLine . " 2>/dev/null");
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
			$this->queueTask(2);
			return JOB_SUCCESS;
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
		return JOB_SUCCESS;

	}


	public function createThumbInternal($sourceFileContainer, $args) {


		$this->pathToBlenderStage =dirname(realpath(NULL)) . "/public/assets/blender/stage.blend";
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

		$this->pheanstalk->touch($this->job);

		rename($sourceFileContainer->getPathToLocalFile(), $sourceFileContainer->getPathToLocalFile() . ".ply");

		$outputBlenderScript = str_replace("{{PATHTOX3D}}", $sourceFileContainer->getPathToLocalFile() . ".ply",$this->sourceBlenderScript);

		$outputScript = $sourceFileContainer->getPathToLocalFile() . "_blender.py";
		file_put_contents($outputScript, $outputBlenderScript);

		$targetLargeFileShortName = $sourceFileContainer->getPathToLocalFile() . "_output";

		$blenderCommandLine = $this->config->item('blenderBinary') . " -b " . $this->pathToBlenderStage . " -P " . $outputScript . " -o " . $targetLargeFileShortName . " -F JPEG -x 1 -f 1";
		// blender will generate a new output name
		$targetLargeFile = $targetLargeFileShortName . "0001.jpg";

		exec($blenderCommandLine . " 2>/dev/null");
		unlink($sourceFileContainer->getPathToLocalFile() . ".ply");
		$derivativeArray = array();
		foreach($args as $derivativeSetting) {
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

			$image=new Imagick("JPEG" .":".$targetLargeFile);
			$image->setImageCompression(Imagick::COMPRESSION_JPEG);
			$image->setImageCompressionQuality(80);
			$image->rotateimage(new ImagickPixel('#00000000'), 90);
			$image = $image->flattenImages();

			$image->resizeImage($width,$height,imagick::FILTER_LANCZOS,1,true);

			if($image->writeImage($derivativeContainer->getPathToLocalFile())) {
				$derivativeContainer->ready = true;
				$this->extractMetadata(['fileObject'=>$derivativeContainer, "continue"=>false]);
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
				$derivativeArray[$derivativeType] = $derivativeContainer;
			}
			else {
				$this->logging->processingInfo("createThumbnails", "pdfhandler", "Could not create derivative", $this->getObjectId(), $this->job->getId());
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


}

/* End of file  */
/* Location: ./application/controllers/ */