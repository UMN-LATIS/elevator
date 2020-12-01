<?

/**
* Box Wrapper
*/
class BoxHelper
{

	private $apiKey;
	private $boxView;
	private $document = null;
	private $pathToExtractedFiles = null;
	public $error;

	function __construct($apiKey=null)
	{
		$this->apiKey = $apiKey;
		if($apiKey) {
			$this->boxView = new Box\View\Client($this->apiKey);
		}


	}

	function createDocumentFromURL($url) {
		try {
    		$this->document = $this->boxView->uploadUrl($url, ['name' => 'Temporary File']);
    		$this->docId = $this->document->id();
    		return true;
		} catch (Box\View\BoxViewException $e) {
			$this->error = $e->getMessage();
			var_dump($e->getMessage());
			die;
			return false;
		}
	}

	public function getDocumentId() {
		return $this->docId;
	}

	public function setDocumentId($docId) {
		try {
			if($this->document = $this->boxView->getDocument($docId)) {
				return true;
			}
			else {
				return false;
			}
		} catch (Box\View\BoxViewException $e) {
			$this->error = $e->getMessage();
			return false;
		}

	}

	function deleteCurrentDocument() {
		if(!$this->document) {
			return;
		}
		try {
		    $deleted = $this->document->delete();

		    if ($deleted) {
		    	return true;
		    } else {
		    	return false;
		    }
		} catch (Box\View\BoxViewException $e) {
			$this->error = $e->getMessage();
			return false;
		}
	}

	function checkIfReady() {
		try {
			$documentDuplicate = $this->boxView->getDocument($this->document->id());
			$status = $documentDuplicate->status();
			if($status == "done") {
				return true;
			}
			else {
				return false;
			}
		} catch (Box\View\BoxViewException $e) {
			$this->error = $e->getMessage();
			return false;
		}

	}

	function getAsPDF($outputPath) {
		try {
    		$contents = $this->document->download('pdf');
    		$handle   = fopen($outputPath, 'w');

    		fwrite($handle, $contents);
    		fclose($handle);
    		return true;
		} catch (Box\View\BoxViewException $e) {
			$this->error = $e->getMessage();
			return false;
		}

	}

	function getZippedContents($outputPath) {
		try {
    		$contents = $this->document->download('zip');
    		$handle   = fopen($outputPath . ".zip", 'w');

    		fwrite($handle, $contents);
    		fclose($handle);


			$zip = new ZipArchive;
    		if ($zip->open($outputPath . ".zip") === TRUE) {
    			if(!file_exists($outputPath)) {
    				mkdir($outputPath);
    			}

    			if($zip->extractTo($outputPath)) {
    				$this->pathToExtractedFiles = $outputPath;
    				$zip->close();
    				return $outputPath;
    			}
			}
			return false;

		} catch (Box\View\BoxViewException $e) {
			$this->error = $e->getMessage();
			return false;
		}
		return false;
	}

	function extractText() {
		if(!$this->pathToExtractedFiles) {
			return false;
		}

		$di = new RecursiveDirectoryIterator($this->pathToExtractedFiles,RecursiveDirectoryIterator::SKIP_DOTS);
		$it = new RecursiveIteratorIterator($di);
		$returnedContents = null;
		foreach($it as $file) {
			if(strtolower(pathinfo($file,PATHINFO_EXTENSION)) == "html") {
				$htmlFiles[] = $file;
			}
		}

		natsort($htmlFiles);
		foreach($htmlFiles as $file) {
			$fileContents = file_get_contents($file);
			$cleanedContents = strip_tags(html_entity_decode($fileContents, ENT_QUOTES));
			$returnedContents .= $cleanedContents . " ";
		}
		return $returnedContents;
	}

	function getThumbnail($outputPath) {
		try {
    		$thumbnailContents = $this->document->thumbnail(500, 500);
    		$handle            = fopen($outputPath, 'w');

    		fwrite($handle, $thumbnailContents);
    		fclose($handle);

    		return true;
		} catch (Box\View\BoxViewException $e) {
			$this->error = $e->getMessage();
			return false;
		}
	}

	function deleteDocument() {
		try {
    		$deleted = $this->document->delete();

    		if ($deleted) {
    			return true;
    		} else {
    			return false;
    		}
		} catch (Box\View\BoxViewException $e) {
			$this->error = $e->getMessage();
			return false;
		}

	}

}