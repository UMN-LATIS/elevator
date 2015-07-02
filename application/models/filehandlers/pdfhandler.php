<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


//require_once("fileHandlerBase.php");
class PDFHandler extends FileHandlerBase {

	protected $supportedTypes = array();
	protected $noDerivatives = true;
	public $icon = "pdf.png";


	public $taskArray = [0=>["taskType"=>"extractMetadata", "config"=>["continue"=>true]],
						  1=>["taskType"=>"createThumbnails", "config"=>[["width"=>250, "height"=>250, "type"=>"thumbnail", "path"=>"thumbnail"],
						  												["width"=>500, "height"=>500, "type"=>"thumbnail2x", "path"=>"thumbnail"],
						  												["width"=>75, "height"=>150, "type"=>"tiny", "path"=>"thumbnail"],
						  												["width"=>150, "height"=>75, "type"=>"tiny2x", "path"=>"thumbnail"]
						  												]
						  												],
							//2=>["taskType"=>"cleanupOriginal", "config"=>array()],
							2=>["taskType"=>"updateParent", "config"=>array()]];



	public function __construct()
	{
		parent::__construct();
		$this->load->helper("media");
		//Do your magic here
	}

	public function allDerivativesForAccessLevel($accessLevel) {
		$derivative = array();

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


		$parser = new \Smalot\PdfParser\Parser();
		try {
			$pdf    = $parser->parseFile($this->sourceFile->getPathToLocalFile());
			$pages  = $pdf->getPages();
			$fileObject->metadata = $pdf->getDetails();
		}
		catch (Exception $e) {
			$pages = array();
			$this->logging->processingInfo("pdf extract", "pdfhandler", "Could not extract text", $this->getObjectId(), $this->job->getId());

		}

		$pageText = "";
		foreach ($pages as $page) {
    		$pageText .= $page->getText();
    		$this->pheanstalk->touch($this->job);
		}

		$pageText = preg_replace("/\x{00A0}/", " ", $pageText);
		$pageText = preg_replace("/\n/", " ", $pageText);
		$pageText = preg_replace("/[^A-Za-z0-9 ]/", '', $pageText);

		$fileObject->metadata["filesize"] = $this->sourceFile->getFileSize();

		$this->globalMetadata = $pageText;

		if($args['continue'] == true) {
			$this->queueTask(1);
		}

		return JOB_SUCCESS;
	}

	public function createThumbnails($args) {
		ini_set('memory_limit', '512M');
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

		if($success) {
			$this->queueTask(2);
			return JOB_SUCCESS;
		}
		else {
			return JOB_FAILED;
		}

	}



}

/* End of file imageHandler.php */
/* Location: ./application/models/imageHandler.php */