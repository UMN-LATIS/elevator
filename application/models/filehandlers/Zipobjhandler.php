<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class ZipObjHandler extends ZipHandler {
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

	# this is our static SVX data which we'll use when rendering 3d objects that don't have baked in data
	public $staticSVXData= '{"asset":{"type":"application/si-dpo-3d.document+json","version":"1.0","generator":"Voyager","copyright":"(c) Smithsonian Institution, all rights reserved"},"scene":0,"scenes":[{"units":"mm","nodes":[0,1,6],"meta":0,"setup":0}],"nodes":[{"translation":[-0.9240687,1.0505811,2.2558991],"rotation":[-0.2025829,-0.2200353,-0.04677,0.9530777],"scale":[1,1,1],"name":"Camera","camera":0},{"rotation":[0,-0.2249511,0,0.9743701],"name":"Lights","children":[2,3,4,5]},{"translation":[-0.6438616,0.7049399,1.1872544],"rotation":[0.4829741,-0.1070728,0.1880998,0.8484633],"scale":[0.2539177,0.2539177,0.2539177],"name":"Key","light":0},{"translation":[1.1602084,0.6859158,0.7102866],"rotation":[0.3546969,0.163893,-0.3861077,0.8356136],"scale":[0.2539177,0.2539177,0.2539177],"name":"Fill #1","light":1},{"translation":[-0.8890287,-1.1626011,0.4231521],"rotation":[0.9374013,-0.3018693,0.0532277,0.1652891],"scale":[0.2539177,0.2539177,0.2539177],"name":"Fill #2","light":2},{"translation":[1.3233654,0.0789017,-0.7506994],"rotation":[0.373256,0.6426073,-0.5786063,0.3360813],"scale":[0.2539177,0.2539177,0.2539177],"name":"Rim","light":3},{"name":"Model0","model":0}],"cameras":[{"type":"perspective","perspective":{"yfov":52,"znear":0.0106183,"zfar":10.6182517},"autoNearFar":true}],"lights":[{"color":[1,0.95,0.9],"intensity":1,"type":"directional","shadowEnabled":true,"shadowSize":2.5391769},{"color":[0.9,0.95,1],"intensity":0.7,"type":"directional","shadowEnabled":true,"shadowSize":2.5391769},{"color":[0.8,0.85,1],"intensity":0.5,"type":"directional"},{"color":[0.85,0.9078313,1],"intensity":0.6,"type":"directional"}],"models":[{"units":"cm","boundingBox":{"min":[-0.045969,-0.0705985,-0.08817],"max":[0.080032,0.058414,0.090586]},"derivatives":[]}],"metas":[{"collection":{"titles":{},"intros":{"EN":""}}}],"setups":[{"units":"cm","interface":{"visible":true,"logo":true,"menu":true,"tools":true},"viewer":{"shader":"Default","exposure":1,"gamma":2,"annotationsVisible":false},"reader":{"enabled":false,"position":"Overlay"},"navigation":{"type":"Orbit","enabled":true,"autoZoom":true,"lightsFollowCamera":true,"autoRotation":false,"orbit":{"orbit":[-24,-26,0],"offset":[0,0,150],"minOrbit":[-90,null,null],"maxOrbit":[90,null,null],"minOffset":[null,null,0.1],"maxOffset":[null,null,10000]}},"background":{"style":"RadialGradient","color0":[0.2,0.25,0.3],"color1":[0.01,0.03,0.05]},"floor":{"visible":false,"position":[0,-25,0],"size":50,"color":[0.6,0.75,0.8],"opacity":0.5,"receiveShadow":false},"grid":{"visible":false,"color":[0.5,0.7,0.8]},"tape":{"enabled":false,"startPosition":[0,0,0],"startDirection":[0,0,0],"endPosition":[0,0,0],"endDirection":[0,0,0]},"slicer":{"enabled":false,"axis":"X","inverted":false,"position":0.5}}]}';


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

		$zip = new ZipArchive;
		$res = $zip->open($this->sourceFile->getPathToLocalFile());

		$targetPath = $this->sourceFile->getPathToLocalFile() . "_extracted";
		if(!$res) {
			$this->logging->processingInfo("createDerivative","objHandler","Coudl not extract zip",$this->getObjectId(), 0);
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

		// we change dir inside docker so we have to pass in two args
		$meshlabCommandLine =  $this->config->item("meshlabPath") . " obj_to_ply " . $objFile . " " . $derivativeContainer->getPathToLocalFile() . ".ply";

		exec($meshlabCommandLine . " 2>/dev/null");
		if(!file_exists($derivativeContainer->getPathToLocalFile() . ".ply")) {
			// failed to process with the texture, let's try without.
			$this->logging->processingInfo("createDerivative","objHandler","Failed to generate PLY",$this->getObjectId(), 0);
			
		}

		rename($derivativeContainer->getPathToLocalFile() . ".ply", $derivativeContainer->getPathToLocalFile());

		$success = true;
		if(!$derivativeContainer->copyToRemoteStorage()) {
			$this->logging->processingInfo("createDerivative", "objHandler", "Could not upload ply", $this->getObjectId(), 0);
			echo "Error copying to remote" . $derivativeContainer->getPathToLocalFile();
			$success=false;
		}
		else {
			$derivativeContainer->ready = true;
			if(!unlink($derivativeContainer->getPathToLocalFile())) {
				$this->logging->processingInfo("createThumbnails", "objHandler", "Could not delete source file", $this->getObjectId(), 0);
				echo "Error deleting source" . $derivativeContainer->getPathToLocalFile();
				$success=false;
			}
		}
		$this->derivatives["ply"] = $derivativeContainer;



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

			$derivativeContainer = new fileContainerS3();
			$derivativeContainer->derivativeType = 'glb-' . $label;
			$derivativeContainer->path = "derivative";
			$derivativeContainer->setParent($this->sourceFile->getParent());
			$derivativeContainer->originalFilename = $pathparts['filename'] . "_" . 'glb' . '.glb';

			// we change dir inside docker so we have to pass in two args
			$blenderCommandLine =  $this->config->item("blenderBinary") . "  -P /root/glb.py -- " . $objFile . " " . $scale . " glb";
			exec($blenderCommandLine . " 2>/dev/null");
			if(!file_exists(str_replace(".obj","_output.glb", $objFile))) {
				// failed to process with the texture, let's try without.
				echo "Failed to generate GLB for\n";
				$this->logging->processingInfo("createDerivative","objHandler","Failed to generate GLB",$this->getObjectId(), 0);
				
			}

			rename(str_replace(".obj","_output.glb", $objFile), $derivativeContainer->getPathToLocalFile());

			$success = true;
			if(!$derivativeContainer->copyToRemoteStorage()) {
				$this->logging->processingInfo("createDerivative", "objHandler", "Could not upload glb", $this->getObjectId(), 0);
				echo "Error copying to remote" . $derivativeContainer->getPathToLocalFile();
				$success=false;
			}
			else {
				$derivativeContainer->ready = true;
				if(!unlink($derivativeContainer->getPathToLocalFile())) {
					$this->logging->processingInfo("createThumbnails", "objHandler", "Could not delete source file", $this->getObjectId(), 0);
					echo "Error deleting source" . $derivativeContainer->getPathToLocalFile();
					$success=false;
				}
			}
			$this->derivatives["glb-" . $label] = $derivativeContainer;
		}


		$derivativeContainer = new fileContainerS3();
		$derivativeContainer->derivativeType = 'usdz';
		$derivativeContainer->path = "derivative";
		$derivativeContainer->setParent($this->sourceFile->getParent());
		$derivativeContainer->originalFilename = $pathparts['filename'] . "_" . 'glb' . '.usdz';
		$derivativeContainer->forcedMimeType = "model/vnd.usdz+zip";
		// we change dir inside docker so we have to pass in two args
		$blenderCommandLine =  $this->config->item("blenderBinary") . "  -P /root/glb.py -- " . $objFile . " " . $scale . " usdz";
		exec($blenderCommandLine . " 2>/dev/null");
		if(!file_exists(str_replace(".obj","_output.usdz", $objFile))) {
			// failed to process with the texture, let's try without.
			echo "Failed to generate USDZ for\n";
			$this->logging->processingInfo("createDerivative","objHandler","Failed to generate GLB",$this->getObjectId(), 0);
			
		}

		rename(str_replace(".obj","_output.usdz", $objFile), $derivativeContainer->getPathToLocalFile());

		$success = true;
		if(!$derivativeContainer->copyToRemoteStorage()) {
			$this->logging->processingInfo("createDerivative", "objHandler", "Could not upload glb", $this->getObjectId(), 0);
			echo "Error copying to remote" . $derivativeContainer->getPathToLocalFile();
			$success=false;
		}
		else {
			$derivativeContainer->ready = true;
			if(!unlink($derivativeContainer->getPathToLocalFile())) {
				$this->logging->processingInfo("createThumbnails", "objHandler", "Could not delete source file", $this->getObjectId(), 0);
				echo "Error deleting source" . $derivativeContainer->getPathToLocalFile();
				$success=false;
			}
		}
		$this->derivatives["usdz"] = $derivativeContainer;

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
		$objHandler->sourceFile = $this->sourceFile;
		

		$targetDerivative = $this->derivatives['ply'];
		
		$result = $objHandler->createNxsFileInternal($targetDerivative, $args);


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

	public function getEmbedView($fileContainerArray, $includeOriginal=false, $embedded=false) {

		$uploadWidget = $this->getUploadWidget();

		$embedView = "objhandler";



		$glbItems = array_keys($this->derivatives);
		// filter the array ot only "glb-" items
		$glbItems = array_filter($glbItems, function($entry) {
			return strpos($entry, "glb-") !== false;
		});

		$haveGLB = false;
		foreach($glbItems as $glbEntry) {
			if($this->derivatives[$glbEntry]->ready) {
				$haveGLB = true;
			}
		}

		if($this->instance->getUseVoyagerViewer() == true && $haveGLB) {

			$embedView = "voyagerobjhandler";
		}


		return $this->load->view("fileHandlers/embeds/" . $embedView, ["widgetObject"=>$uploadWidget, "fileObject"=>$this, "embedded"=>$embedded, "allowOriginal"=>$includeOriginal, "fileContainers"=>$fileContainerArray], true);
	}

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

		return $this->load->view("fileHandlers/chrome/" . "objhandler_chrome", ["widgetObject"=>$uploadWidget, "fileObject"=>$this, "embedded"=>$embedded, "allowOriginal"=>$includeOriginal, "fileContainers"=>$fileContainerArray], true);
	}

	public function mungedSidecarData($sidecarData=null, $sidecarType=null) {
		if($sidecarType == "svx") {
			if(isset($sidecarData['svx']) && ((is_string($sidecarData['svx']) && strlen($sidecarData['svx'])>0) || is_array($sidecarData['svx']))) {
				$svxData = $sidecarData['svx'];
			}
			else{
				$svxData = json_decode($this->staticSVXData, true);
			}

			$derivatives = [];
			$derivativeTemplate = [
				"usage"=>"Web3D",
				"quality" => "High",
				"assets" => [
					[
						"uri"=>"",
						"type"=>"Model",
						"mimeType"=>"model/gltf-binary"
					]
				]
					];
					
			if(isset($this->derivatives["glb-thumb"])) {
				$lowDerivative = $derivativeTemplate;
				$lowDerivative["quality"] = "Thumb";
				$lowDerivative["assets"][0]["uri"] = $this->derivatives["glb-thumb"]->getProtectedURLForFile();
				$derivatives[] = $lowDerivative;
				
			}
			if(isset($this->derivatives["glb-medium"])) {
				$mediumDerivative = $derivativeTemplate;
				$mediumDerivative["quality"] = "Medium";
				$mediumDerivative["assets"][0]["uri"] = $this->derivatives["glb-medium"]->getProtectedURLForFile();
				$derivatives[] = $mediumDerivative;
				
			}
			if(isset($this->derivatives["glb-large"])) {
				$highDerivative = $derivativeTemplate;
				$highDerivative["quality"] = "High";
				$highDerivative["assets"][0]["uri"] = $this->derivatives["glb-large"]->getProtectedURLForFile();
				$derivatives[] = $highDerivative;
				
				$arDerivative = $derivativeTemplate;
				$arDerivative['usage'] = "App3D";
				$arDerivative["quality"] = "AR";
				$arDerivative["assets"][0]["uri"] = $this->derivatives["glb-large"]->getProtectedURLForFile();
				$derivatives[] = $arDerivative;
				
			}
			if(isset($this->derivatives["usdz"])) {
				$arDerivative = $derivativeTemplate;
				$arDerivative['usage'] = "iOSApp3D";
				$arDerivative["quality"] = "AR";
				$arDerivative["assets"][0]["uri"] = $this->derivatives["usdz"]->getProtectedURLForFile();
				$arDerivative["mimeType"] = "model/vnd.usdz+zip";
				$derivatives[] = $arDerivative;
			}
			

			if(isset($svxData["models"]) && isset($svxData["models"][0]) && isset($svxData["models"][0]["derivatives"])) {
				
				$svxData["models"][0]["derivatives"] = $derivatives;
			}
			
			return json_encode($svxData);
		}
		else {
			return $sidecarData[$sidecarType]?:null;
		}

	}

}

/* End of file  */
/* Location: ./application/controllers/ */