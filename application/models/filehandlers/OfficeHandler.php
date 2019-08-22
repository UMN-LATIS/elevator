<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * This filehandler has been deprecated in favor of a unified box.net handler.  Leaving this in the repo in case we ever
 * want to roll back, but this was a hacky implementation.
 */


//require_once("fileHandlerBase.php");
class OfficeHandler extends FileHandlerBase {

	protected $supportedTypes = array("doc","docx","ppt","pptx", "xls", "xlsx");
	protected $noDerivatives = true;
	// public $icon = "pdf.png";
	private $allowedSize = 30000000;

	public $taskArray = [0=>["taskType"=>"extractMetadata", "config"=>["continue"=>true]],
						  1=>["taskType"=>"createDerivatives", "config"=>["type"=>"shrunk_pdf", "type"=>"pdf"]],
						  2=>["taskType"=>"createThumbnails", "config"=>[["width"=>250, "height"=>250, "type"=>"thumbnail", "path"=>"thumbnail"],
						  												["width"=>500, "height"=>500, "type"=>"thumbnail2x", "path"=>"thumbnail"],
						  												["width"=>75, "height"=>150, "type"=>"tiny", "path"=>"thumbnail"],
						  												["width"=>150, "height"=>75, "type"=>"tiny2x", "path"=>"thumbnail"]
						  												]
						  												],	
							//2=>["taskType"=>"cleanupOriginal", "config"=>array()],
							3=>["taskType"=>"updateParent", "config"=>array()]];



	public function __construct()
	{
		parent::__construct();
		$this->load->helper("media");
		//Do your magic here
	}

	public function allDerivativesForAccessLevel($accessLevel) {
		$derivative = array();

		if($accessLevel>=$this->getPermission()) {
			$derivative[] = "shrunk_pdf";
			$derivative[] = "pdf";
		}
		if($accessLevel>PERM_NOPERM) {
			$derivative[] = "thumbnail";
			$derivative[] = "thumbnail2x";
			$derivative[] = "tiny";
			$derivative[] = "tiny2x";
		}

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
		}
		if(count($returnArray)>0) {
			return $returnArray;
		}
		else {
			throw new Exception("Derivative not found");
		}
	}

	public function extractMetadata($args) {
		ini_set('memory_limit', '512M');
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

		$fileObject->metadata["filesize"] = $this->sourceFile->getFileSize();

		if($args['continue'] == true) {
			$this->queueTask(1);
		}

		return JOB_SUCCESS;
	}

	public function createThumbnails($args) {
		ini_set('memory_limit', '512M');
		$success = true;

		if(isset($this->derivatives["pdf"])) {
			$targetFile = $this->derivatives["pdf"];
		}
		else {
			$this->logging->processingInfo("createThumbnails", "officeHandler", "No PDF found", $this->getObjectId(), $this->job->getId());
			return JOB_FAILED;
		}

		foreach($args as $derivativeSetting) {
			$derivativeType = $derivativeSetting['type'];
			$width = $derivativeSetting['width'];
			$height = $derivativeSetting['height'];

			if(!$targetFile->isLocal()) {
				if($targetFile->makeLocal()) {
					$this->pheanstalk->touch($this->job);
				}
				else {
					return JOB_FAILED;
				}

			}

			if(!file_exists($targetFile->getPathToLocalFile())) {
				return JOB_FAILED;
			}

			$localPath = $targetFile->getPathToLocalFile();
			$pathparts = pathinfo($localPath);

			$derivativeContainer = new fileContainerS3();
			$derivativeContainer->derivativeType = $derivativeType;
			$derivativeContainer->path = $derivativeSetting['path'];
			$derivativeContainer->setParent($this->sourceFile->getParent());
			$derivativeContainer->originalFilename = $pathparts['filename'] . "_" . $derivativeType . '.jpg';
			//TODO: catch errors here

			if(compressImageAndSave($targetFile, $derivativeContainer, $width, $height)) {
				$derivativeContainer->ready = true;
				// $this->extractMetadata(['fileObject'=>$derivativeContainer, "continue"=>false]);
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
				$this->derivatives[$derivativeType] = $derivativeContainer;
			}
			else {
				$this->logging->processingInfo("createThumbnails", "pdfhandler", "Could not create derivative", $this->getObjectId(), $this->job->getId());
				echo "Error generating deriative" . $derivativeContainer->getPathToLocalFile();
				$success=false;
			}
		}
		$this->triggerReindex();

		unlink($targetFile->getPathToLocalFile());

		if($success) {
			$this->queueTask(3);
			return JOB_SUCCESS;
		}
		else {
			return JOB_FAILED;
		}

	}

	public function createDerivatives($args) {
		if(!$this->sourceFile->isLocal()) {
			if($this->sourceFile->makeLocal()) {
				$this->pheanstalk->touch($this->job);
			}
			else {
				return JOB_FAILED;
			}

		}

		if(!file_exists($this->sourceFile->getPathToLocalFile())) {
			return JOB_FAILED;
		}

		$success = false;


		$pathparts = pathinfo($this->sourceFile->getPathToLocalFile());
		$derivativeContainer = new fileContainerS3();
		$derivativeContainer->derivativeType = "pdf";
		$derivativeContainer->path = "derivative";
		$derivativeContainer->setParent($this->sourceFile->getParent());
		$derivativeContainer->originalFilename = $pathparts['filename'] . "_pdf" . '.pdf';

		$unoconv = Unoconv\Unoconv::create(['timeout'=> 240, 'unoconv.binaries' => '/usr/local/bin/openoffice']);
		$unoconv->transcode($this->sourceFile->getPathToLocalFile(), 'pdf', $derivativeContainer->getPathToLocalFile());

		if(!file_exists($derivativeContainer->getPathToLocalFile())) {
			$this->logging->processingInfo("createDerivatives", "officeHandler", "Could not convert to PDF", $this->getObjectId(), $this->job->getId());
			return JOB_FAILED;
		}
		$derivativeContainer->ready = true;
		if(!$derivativeContainer->copyToRemoteStorage()) {
			$this->logging->processingInfo("createDerivatives", "pdfhandler", "Could not upload derivative", $this->getObjectId(), $this->job->getId());
			return JOB_FAILED;
		}

		$this->load->library("PDFHelper");
		$pdfHelper = new PDFHelper;
		if($pages = $pdfHelper->scrapeText($derivativeContainer->getPathToLocalFile())) {
			$this->globalMetadata["text"] = $pages;
		}


		$this->derivatives["pdf"] = $derivativeContainer;


		if(filesize($derivativeContainer->getPathToLocalFile()) > $this->allowedSize) {
			$this->load->library("PDFHelper");
			$pdfHelper = new PDFHelper;

			$minified = $pdfHelper->minifyPDF($derivativeContainer->getPathToLocalFile());
			if(file_exists($minified)) {
				$pathparts = pathinfo($this->sourceFile->getPathToLocalFile());
				$derivativeContainer = new fileContainerS3();
				$derivativeContainer->derivativeType = "shrunk_pdf";
				$derivativeContainer->path = "derivative";
				$derivativeContainer->setParent($this->sourceFile->getParent());
				$derivativeContainer->originalFilename = $pathparts['filename'] . "_" . "shrunk_pdf" . '.pdf';
				rename($minified, $derivativeContainer->getPathToLocalFile());
				$derivativeContainer->ready = true;
				if(!$derivativeContainer->copyToRemoteStorage()) {
					$success = false;
					$this->logging->processingInfo("createDerivatives", "officeHandler", "Could not upload derivative", $this->getObjectId(), $this->job->getId());
					echo "Error copying to remote" . $derivativeContainer->getPathToLocalFile();
				}
				else {
					unlink($derivativeContainer->getPathToLocalFile());
					$this->derivatives["shrunk_pdf"] = $derivativeContainer;
					$success = true;
				}	
			}
			else {
				$success = false;
			}
		}
		else {
			$success = true;
		}
		unlink($this->sourceFile->getPathToLocalFile());
		if($success) {
			$this->queueTask(2);
			return JOB_SUCCESS;
		}
		else {
			return JOB_FAILED;
		}
	}

	/**
	 * Override the parent handler and return the pdf_handler
	 */

	public function getEmbedView($fileContainerArray, $includeOriginal=false, $embedded=false) {

		$uploadWidget = $this->getUploadWidget();
		return $this->load->view("fileHandlers/embeds/pdfhandler", ["widgetObject"=>$uploadWidget, "fileObject"=>$this, "embedded"=>$embedded, "allowOriginal"=>$includeOriginal, "fileContainers"=>$fileContainerArray], true);
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

		return $this->load->view("fileHandlers/chrome/" . "pdfhandler_chrome", ["widgetObject"=>$uploadWidget, "fileObject"=>$this, "embedded"=>$embedded, "allowOriginal"=>$includeOriginal, "fileContainers"=>$fileContainerArray], true);
	}

}

/* End of file imageHandler.php */
/* Location: ./application/models/imageHandler.php */