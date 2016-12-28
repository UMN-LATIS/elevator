<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * This filehandler has been deprecated in favor of a unified box.net handler.  Leaving this in the repo in case we ever
 * want to roll back, but this was a hacky implementation.
 */


//require_once("fileHandlerBase.php");
class PDFHandler extends FileHandlerBase {

	protected $supportedTypes = array("pdf");
	protected $noDerivatives = true;
	public $icon = "pdf.png";
	private $allowedSize = 30000000;
	private $maxProcessingSize = 262144000;

	public $taskArray = [0=>["taskType"=>"extractMetadata", "config"=>["continue"=>true]],
						  1=>["taskType"=>"createThumbnails", "config"=>[["width"=>250, "height"=>250, "type"=>"thumbnail", "path"=>"thumbnail"],
						  												["width"=>500, "height"=>500, "type"=>"thumbnail2x", "path"=>"thumbnail"],
						  												["width"=>75, "height"=>150, "type"=>"tiny", "path"=>"thumbnail"],
						  												["width"=>150, "height"=>75, "type"=>"tiny2x", "path"=>"thumbnail"]
						  												]
						  												],
						  	2=>["taskType"=>"ocrText", "config"=>["type"=>"ocr_pdf"]],
						  	3=>["taskType"=>"createDerivatives", "config"=>["type"=>"shrunk_pdf"]],
							//2=>["taskType"=>"cleanupOriginal", "config"=>array()],
							4=>["taskType"=>"updateParent", "config"=>array()]];



	public function __construct()
	{
		parent::__construct();
		$this->load->helper("media");
		//Do your magic here
	}

	public function allDerivativesForAccessLevel($accessLevel) {
		$derivative = array();

		if($accessLevel>=$this->getPermission()) {
			$derivative[] = "ocr_pdf";
			$derivative[] = "shrunk_pdf";
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
		ini_set('memory_limit', '4096M');
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

		$this->load->library("PDFHelper");


		$fileObject->metadata["filesize"] = $this->sourceFile->getFileSize();

		if(!is_array($this->globalMetadata)) {
			$this->globalMetadata = array();
		}


		if($fileObject->metadata["filesize"] < $this->maxProcessingSize) {
			$pdfHelper = new PDFHelper;
			if($metadata = $pdfHelper->getPDFMetadata($this->sourceFile->getPathToLocalFile())) {
				$fileObject->metadata = $metadata;
			}

			if($pages = $pdfHelper->scrapeText($this->sourceFile->getPathToLocalFile())) {
				$this->globalMetadata["text"] = $pages;
			}

		}
		
		if($args['continue'] == true) {
			$this->queueTask(1);
		}

		return JOB_SUCCESS;
	}

	public function createThumbnails($args) {
		ini_set('memory_limit', '4096M');
		$success = true;
		foreach($args as $derivativeSetting) {
			$derivativeType = $derivativeSetting['type'];
			$width = $derivativeSetting['width'];
			$height = $derivativeSetting['height'];

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

			$localPath = $this->sourceFile->getPathToLocalFile();
			$pathparts = pathinfo($localPath);

			$derivativeContainer = new fileContainerS3();
			$derivativeContainer->derivativeType = $derivativeType;
			$derivativeContainer->path = $derivativeSetting['path'];
			$derivativeContainer->setParent($this->sourceFile->getParent());
			$derivativeContainer->originalFilename = $pathparts['filename'] . "_" . $derivativeType . '.jpg';
			//TODO: catch errors here

			if(compressImageAndSave($this->sourceFile, $derivativeContainer, $width, $height)) {
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
		if($success) {

			$this->queueTask(2);
			return JOB_SUCCESS;
		}
		else {
			return JOB_FAILED;
		}

	}

	public function ocrText($args) {

		if(!$this->sourceFile->isLocal()) {
			if($this->sourceFile->makeLocal()) {
				$this->pheanstalk->touch($this->job);
			}
			else {
				return JOB_FAILED;
			}

		}

		$fileSize = filesize($this->sourceFile->getPathToLocalFile());
		if((isset($this->globalMetadata["text"]) && strlen(trim($this->globalMetadata["text"])) > 10) || $fileSize > $this->maxProcessingSize) {
			// we have some text here, don't bother looking for more.
			if($fileSize > $this->allowedSize) {
				unlink($this->sourceFile->getPathToLocalFile());
				$this->queueTask(3);	
			}
			else {
				$this->queueTask(4);
			}
			return JOB_SUCCESS;
		}
		$this->load->library("PDFHelper");
		$pdfHelper = new PDFHelper;

		$ocrFile = $pdfHelper->ocrText($this->sourceFile->getPathToLocalFile());
		$textFound = false;
		$pages = $pdfHelper->scrapeText($ocrFile);
		if(strlen(trim($pages)) > 10) {
			$textFound = true;
			$this->globalMetadata["text"] = $pages;	
		}

		if(!$textFound) {

			unlink($ocrFile);
			if(filesize($this->sourceFile->getPathToLocalFile()) > $this->allowedSize) {
				unlink($this->sourceFile->getPathToLocalFile());
				$this->queueTask(3);	
			}
			else {
				$this->queueTask(4);
			}
			return JOB_SUCCESS;
		}



		$pathparts = pathinfo($this->sourceFile->getPathToLocalFile());
		$derivativeContainer = new fileContainerS3();
		$derivativeContainer->derivativeType = $args["type"];
		$derivativeContainer->path = "derivative";
		$derivativeContainer->setParent($this->sourceFile->getParent());
		$derivativeContainer->originalFilename = $pathparts['filename'] . "_" . $args["type"] . '.pdf';
		rename($ocrFile, $derivativeContainer->getPathToLocalFile());
		$derivativeContainer->ready = true;
		if(!$derivativeContainer->copyToRemoteStorage()) {
			$success = false;
			$this->logging->processingInfo("createDerivatives", "pdfhandler", "Could not upload derivative", $this->getObjectId(), $this->job->getId());
			echo "Error copying to remote" . $derivativeContainer->getPathToLocalFile();
		}
		else {
			$this->derivatives[$args["type"]] = $derivativeContainer;
			$success = true;
		}

		// even if OCR fails, we shouldn't give up
		if(!$success) {
			$this->queueTask(4);
		}
		else {
			if(filesize($derivativeContainer->getPathToLocalFile()) > $this->allowedSize) {
			unlink($derivativeContainer->getPathToLocalFile());
			$this->queueTask(3);	
			}
			else {
				$this->queueTask(4);
			}	
		}
		
		return JOB_SUCCESS;



	}

	public function createDerivatives($args) {
		
		if(isset($this->derivatives["ocr_pdf"])) {
			$targetFile = $this->derivatives["ocr_pdf"];
		}
		else {
			$targetFile = $this->sourceFile;
		}

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

		$success = false;

		$this->load->library("PDFHelper");
		$pdfHelper = new PDFHelper;

		$minified = $pdfHelper->minifyPDF($targetFile->getPathToLocalFile());
		if(file_exists($minified)) {
			$pathparts = pathinfo($this->sourceFile->getPathToLocalFile());
			$derivativeContainer = new fileContainerS3();
			$derivativeContainer->derivativeType = $args["type"];
			$derivativeContainer->path = "derivative";
			$derivativeContainer->setParent($this->sourceFile->getParent());
			$derivativeContainer->originalFilename = $pathparts['filename'] . "_" . $args["type"] . '.pdf';
			rename($minified, $derivativeContainer->getPathToLocalFile());
			$derivativeContainer->ready = true;
			if(!$derivativeContainer->copyToRemoteStorage()) {
				$success = false;
				$this->logging->processingInfo("createDerivatives", "pdfhandler", "Could not upload derivative", $this->getObjectId(), $this->job->getId());
				echo "Error copying to remote" . $derivativeContainer->getPathToLocalFile();
			}
			else {
				unlink($derivativeContainer->getPathToLocalFile());
				$this->derivatives[$args["type"]] = $derivativeContainer;
				$success = true;
			}	
		}
		else {
			$success = false;
		}


		if($success) {
			unlink($targetFile->getPathToLocalFile());
			$this->queueTask(4);
			return JOB_SUCCESS;
		}
		else {
			return JOB_FAILED;
		}
	}



}

/* End of file imageHandler.php */
/* Location: ./application/models/imageHandler.php */