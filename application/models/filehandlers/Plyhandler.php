<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


//require_once("fileHandlerBase.php");
class PlyHandler extends FileHandlerBase {

	protected $supportedTypes = array("ply");
	protected $noDerivatives = false;




	//public $icon = "doc.png";

	public $taskArray = [0=>["taskType"=>"extractMetadata", "config"=>["continue"=>true]],
						  1=>["taskType"=>"createThumbnails", "config"=>[["width"=>250, "height"=>250, "type"=>"thumbnail", "path"=>"thumbnail"],
						  												["width"=>500, "height"=>500, "type"=>"thumbnail2x", "path"=>"thumbnail"],
						  												["width"=>75, "height"=>150, "type"=>"tiny", "path"=>"thumbnail"],
						  												["width"=>150, "height"=>75, "type"=>"tiny2x", "path"=>"thumbnail"]
						  												]
						  												],
						  2=>["taskType"=>"createNxsFile", "config"=>["ttr"=>900]],
						  3=>["taskType"=>"createSTL", "config"=>[]]
						  												];



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

		if($accessLevel>=$this->getPermission()) {
			$derivative[] = "nxs";
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


	public function createThumbnails($args) {


		$objHandler = new ObjHandler;
		$objHandler->job = $this->job;
		$objHandler->sourceFile = $this->sourceFile;

		$result = $objHandler->createThumbInternal($this->sourceFile, $args);
		if($result == JOB_POSTPONE) {
			return JOB_POSTPONE;
		}
		if($result == JOB_FAILED) {
			return JOB_FAILED;
		}
		else {
			$this->derivatives = array_merge($this->derivatives, $result);
		}
		$this->queueTask(2);
		$this->triggerReindex();
		return JOB_SUCCESS;

	}

	public function createNxsFile($args) {
		$objHandler = new ObjHandler;
		$objHandler->job = $this->job;
		$objHandler->sourceFile = $this->sourceFile;

		$result = $objHandler->createNxsFileInternal($this->sourceFile, $args);
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
		return JOB_SUCCESS;


	}

	public function createSTL($args) {
		$objHandler = new ObjHandler;
		$objHandler->job = $this->job;
		$objHandler->sourceFile = $this->sourceFile;

		$result = $objHandler->createSTLInternal($this->sourceFile, $args);
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
	 * Overriding so we can use the OBJ handler
	 */
	public function getEmbedViewWithFiles($fileContainerArray, $includeOriginal=false, $embedded=false) {

		$uploadWidget = $this->getUploadWidget();

		return $this->load->view("fileHandlers/chrome/objhandler_chrome", ["widgetObject"=>$uploadWidget, "fileObject"=>$this, "embedded"=>$embedded, "allowOriginal"=>$includeOriginal, "fileContainers"=>$fileContainerArray], true);
	}

	public function getEmbedView($fileContainerArray, $includeOriginal=false, $embedded=false) {

		$uploadWidget = $this->getUploadWidget();
		return $this->load->view("fileHandlers/embeds/objhandler", ["widgetObject"=>$uploadWidget, "fileObject"=>$this, "embedded"=>$embedded, "allowOriginal"=>$includeOriginal, "fileContainers"=>$fileContainerArray], true);
	}


}

/* End of file imageHandler.php */
/* Location: ./application/models/imageHandler.php */