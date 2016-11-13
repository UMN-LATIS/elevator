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
		$parser = new \Smalot\PdfParser\Parser();
		try {
			$pdf    = $parser->parseFile($pdfFile);
			$metadata = $pdf->getDetails();
		}
		catch (Exception $e) {
			$this->CI->logging->logError("pdf libray","Could not get pdf metadata");
			return false;
		}
		return $metadata;

	}

	public function scrapeText($pdfFile) {
		$parser = new \Smalot\PdfParser\Parser();
		$pages = array();

		try {
			$pdf    = $parser->parseFile($pdfFile);
			$pages  = $pdf->getPages();
		}
		catch (Exception $e) {
			$this->CI->logging->logError("pdf library", "Could not extract text");
			return "";
		}

		$pageText = "";
		foreach ($pages as $page) {
    		$pageText .= $page->getText();
    		$this->CI->pheanstalk->touch($this->CI->job);
		}
		$pageText = preg_replace("/\x{00A0}/", " ", $pageText);
		$pageText = preg_replace("/\n/", " ", $pageText);
		$pageText = preg_replace("/[^A-Za-z0-9 ]/", '', $pageText);
		return $pageText;

	}

	public function ocrText($pdfFile) {
		$pathparts = pathinfo($pdfFile);
		$outFile = $pathparts['dirname'] . "/" . $pathparts['filename'] . "_ocr.pdf";
		$commandLine = $this->CI->config->item('pypdfocr') . " "  . $pdfFile;
		$process = new Cocur\BackgroundProcess\BackgroundProcess($commandLine);
		$process->run();
		while($process->isRunning()) {
			sleep(5);
			$this->CI->pheanstalk->touch($this->CI->job);
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