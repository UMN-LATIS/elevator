<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


//require_once("fileHandlerBase.php");
class DocHandler extends FileHandlerBase {

	protected $supportedTypes = array();
	protected $noDerivatives = true;

	public $icon = "doc.png";

	public $taskArray = [0=>["taskType"=>"extractMetadata", "config"=>["continue"=>true]],
						 //1=>["taskType"=>"cleanupOriginal", "config"=>array()],
						 1=>["taskType"=>"updateParent", "config"=>array()]];



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

	private function read_doc($filename) {
		$fileHandle = fopen($filename, "r");
		$line = @fread($fileHandle, filesize($filename));
		$lines = explode(chr(0x0D),$line);
		$outtext = "";
		foreach($lines as $thisline)
		{
			$pos = strpos($thisline, chr(0x00));
			if (($pos !== FALSE)||(strlen($thisline)==0))
			{
			} else {
				$outtext .= $thisline." ";
			}
		}
		$outtext = preg_replace("/[^a-zA-Z0-9\s\,\.\-\n\r\t@\/\_\(\)]/","",$outtext);
		return $outtext;
	}

	private function read_docx($filename){

		$striped_content = '';
		$content = '';

		$zip = zip_open($filename);

		if (!$zip || is_numeric($zip)) return false;

		while ($zip_entry = zip_read($zip)) {

			if (zip_entry_open($zip, $zip_entry) == FALSE) continue;

			if (zip_entry_name($zip_entry) != "word/document.xml") continue;

			$content .= zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));

			zip_entry_close($zip_entry);
        }// end while

        zip_close($zip);

        $content = str_replace('</w:r></w:p></w:tc><w:tc>', " ", $content);
        $content = str_replace('</w:r></w:p>', " \r\n", $content);
        $striped_content = strip_tags($content);

        return $striped_content;
    }

	public function extractMetadata($args) {

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

		if(!file_exists($this->sourceFile->getPathToLocalFile())) {
			return JOB_FAILED;
		}

		if($this->sourceFile->getType() == "doc") {
			$pageText = $this->read_doc($this->sourceFile->getPathToLocalFile());
		}
		elseif($this->sourceFile->getType() == "docx") {
			$pageText = $this->read_docx($this->sourceFile->getPathToLocalFile());
		}
		$pageText = preg_replace("/[^A-Za-z0-9 ]/", ' ', $pageText);

		$fileObject->metadata["filesize"] = $this->sourceFile->getFileSize();

		$this->globalMetadata = $pageText;



		if($args['continue'] == true) {
			$this->queueTask(1);
		}

		return JOB_SUCCESS;
	}


}

/* End of file imageHandler.php */
/* Location: ./application/models/imageHandler.php */