<?php 

class PDFHelper {

	private $CI = null;
	private $pathToScripts = null;

	function __construct()
	{
		$this->CI =& get_instance();
		$this->pathToScripts =realpath(NULL) . "/assets/pdf_scripts/";
		ini_set('memory_limit','2048M');
	}

	public function minifyPDF($pdfFile) {
		$pathToShrinkScript = $this->pathToScripts . "shrinkpdf.sh";
		$outFile = $pdfFile . "_shrunk";
		$commandLine = $pathToShrinkScript . " "  . $pdfFile . " " . $outFile . " 150";
		$process = new Cocur\BackgroundProcess\BackgroundProcess($commandLine);
		$process->run();
		while($process->isRunning()) {
			sleep(5);
			$this->CI->pheanstalk->touch($this->CI->job);
			echo ".";
		}
		if(!file_exists($outFile)) {
			$this->CI->logging->logError("pdf library","Shrinking pdf failed");
			return false;
		}
		return $outFile;

	}

	public function getPDFMetadata($pdfFile) {

		exec("/usr/local/bin/pdfinfo " . $pdfFile, $output, $returnVar);
        
		if($returnVar > 0) {
			$this->CI->logging->logError("pdf library","Could not get pdf metadata");
			return false;
		}
		$metadata = array();
		foreach($output as $entry) {
			$line = explode(":" , $entry);
			$metadata[trim($line[0])] = trim($line[1]);
		}

		// test for 3d content
		$grepLine = "grep -a Subtype/U3D " . $pdfFile;
		exec($grepLine, $response);
		if(count($response) > 0) {
			$metadata["3dcontent"] = true;
		}

		return $metadata;

	}

	public function scrapeText($pdfFile) {
		$output = $pdfFile . ".txt";
		$commandLine = "/usr/bin/pdftotext" . " "  . $pdfFile . " " . $output;
		$process = new Cocur\BackgroundProcess\BackgroundProcess($commandLine);
		$process->run();
		while($process->isRunning()) {
			sleep(5);
			$this->CI->pheanstalk->touch($this->CI->job);
			echo ".";
		}

		if(!file_exists($output)) {
			$this->CI->logging->logError("pdf library","Scraping of pdf failed");
			return "";
		}

		$pageText = file_get_contents($output);
		$pageText = preg_replace("/\x{00A0}/", " ", $pageText);
		$pageText = preg_replace("/\n/", " ", $pageText);
		$pageText = preg_replace("/[^A-Za-z0-9 ]/", '', $pageText);
		return $pageText;

	}

	public function ocrText($pdfFile) {
		$pathparts = pathinfo($pdfFile);
		$outFile = $pathparts['dirname'] . "/" . $pathparts['filename'] . "_ocr.pdf";
		$commandLine = $this->CI->config->item('pypdfocr') . " "  . $pdfFile . " "  . $outFile;
		$process = new Cocur\BackgroundProcess\BackgroundProcess($commandLine);
		$process->run();
		$iterationCount = 0;
		while($process->isRunning()) {
			sleep(5);
			$this->CI->pheanstalk->touch($this->CI->job);
			$iterationCount++;
			if($iterationCount > 180) {
				// we give it a max of 15 minutes to try.
				// reevaluate this with tesseract 3.04 on ubuntu 16.04
				$process->stop();
				break;
			}
			echo ".";
		}
		if(!file_exists($outFile)) {
			$this->CI->logging->logError("pdf library","OCRing of pdf failed");
			return false;
		}
		return $outFile;


	}


}

 
?>