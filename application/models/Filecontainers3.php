<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * FileContainer
 * Abstraction for files
 *
 * @package default
 * @author
 **
*/
require_once("Filecontainer.php");
class FileContainerS3 extends FileContainer {

	private $client;
	private $parent;
	public $storageClass = AWS_REDUCED;

	public $originalFilename;
	public $path = null;
	public $derivativeType = null;
	public $metadata = array();

	public $basePath = "";
	public $baseWebPath = "s3.amazonaws.com";

	public $ready = false;

	public $forcedMimeType = null;
	public $forcedContentEncoding = null;
	public $storageType;
	public $downloadable = true;

	public function __construct($fileEntry=null)
	{
		parent::__construct();
		$this->load->helper("url");

		$this->basePath = $this->config->item("scratchSpace");
		if($fileEntry != null) {
			$this->loadFile($fileEntry);
		}

		// use the instance storage type as default, can be overridden (Setting storageClass) if necessary
		if(isset($this->instance) && $this->instance != null) {
			$this->storageClass= $this->instance->getS3StorageType();
		}

	}

	public function setParent($parent) {
		$this->parent = $parent;
		if($this->path == null || $this->derivativeType == null) {
			throw new Exception('Attempting to set a storage key without necessary parameters');
		}

		$this->storageKey = $this->path . "/" .  $this->getCompositeName();
	}

	public function getParent() {
		return $this->parent;
	}

	public function loadFile($fileEntry) {
		$this->originalFilename = $fileEntry["originalFilename"];
		$this->path = $fileEntry["path"];
		$this->metadata = $fileEntry["metadata"];
		$this->ready = $fileEntry["ready"];

		return true;

	}

	public function isArchived($extendRestore=false) {
		$storageClass = $this->parent->s3model->getStorageClass($this->storageKey);
		if($storageClass == "GLACIER") {
			//TODO: check how we know something is aaiable?
			return true;
		}
		else {
			if($storageClass == "RESTORED" && $extendRestore) {
				$this->parent->s3model->restoreObject($this->storageKey);
			}
			return false;
		}
	}

	public function restoreFromArchive() {
		$objectInfo = $this->parent->s3model->objectInfo($this->storageKey);
		if($objectInfo['Restore'] == 'ongoing-request="true"') {
			return true;
		}
		$this->parent->s3model->restoreObject($this->storageKey);
		return true;
	}

	public function removeLocalFile() {
		if(unlink($this->getPathToLocalFile())) {
			return TRUE;
		}
		else {
			return FALSE;
		}
	}

	public function isLocal() {
		if(file_exists($this->basePath . "/" . $this->getCompositeName())) {
			clearstatcache();
			if(filesize($this->basePath . "/" . $this->getCompositeName()) == $this->getFileSize()) {
				return FILE_LOCAL;
			}
			$this->logging->logError("Filesize Mismatch", "Local: " . filesize($this->basePath . "/" . $this->getCompositeName()) . " Remote:" . $this->getFileSize() . " " . $this->storageKey);
		}
		return FILE_ERROR;
	}

	public function makeLocal() {
		if($this->isLocal()) {
			return FILE_LOCAL;
		}

		if($this->isArchived()) {
			$this->restoreFromArchive();
			return FILE_GLACIER_RESTORING;
		}

		return $this->copyToLocalStorage();
	}

	public function setStorageClass($targetClass) {
		if(!$targetClass) {
			return;
		}

		$this->parent->s3model->setStorageClass($this->storageKey, $targetClass);

	}

	public function setContentType($contentType) {
		if(!$contentType) {
			return;
		}
		$this->parent->s3model->setContentType($this->storageKey, $contentType);

	}

	public function copyToLocalStorage() {
		$targetFile = $this->basePath . "/" . $this->getCompositeName();
		$result = $this->parent->s3model->getObject($this->storageKey, $targetFile);
		if(!$this->isLocal()) {
			return FILE_ERROR;
		}
		if($result) {
			return FILE_LOCAL;
		}
		else {
			return FILE_ERROR;
		}
	}

	public function copyToRemoteStorage($withExtension=null) {
		return $this->parent->s3model->putObject($this->getPathToLocalFile(), $this->storageKey . $withExtension, $this->storageClass, $this->forcedMimeType, $this->forcedContentEncoding);

	}

	public function getFileSize() {
		$objectInfo = $this->parent->s3model->objectInfo($this->storageKey);
		if(!$objectInfo) {
			return false;
		}
		return($objectInfo["ContentLength"]);
	}

	public function getCompositeName() {
		if(!isset($this->derivativeType)) {
			throw new Exception('Derivative Type Not Set');
		}
		return $this->parent->getReversedObjectId() . "-" . $this->derivativeType;
	}

	public function getPathToLocalFile() {
		return $this->basePath . "/" . $this->getCompositeName();
	}

	/**
	 * Get the path to the binary asset
	 * @return fully qualified URL for asset
	 */
	public function getURLForFile($stripHTTP = false) {
		if(!$this->ready) {
			throw new Exception("File not available");
			return false;
		}
		$returnPath = $this->parent->s3model->getObjectURL($this->path ."/" . $this->getCompositeName());
		// $returnPath =  "http://" . $this->parent->collection->getBucket() .".". $this->baseWebPath . "/" . $this->path ."/" . $this->getCompositeName();
		if($stripHTTP) {
			return stripHTTP($returnPath);
		}
		else {
			return $returnPath;
		}


	}

	/**
	 * get a signed s3 url to override bucket permissions
	 * optionally, append an extra string to have signed in.
	 * @param  [type] $appendedString [description]
	 * @return [type]                 [description]
	 */
	public function getProtectedURLForFile($appendedString=null, $timeString="+240 minutes", $forceMimeType = null) {
		return $this->parent->s3model->getProtectedURL($this->storageKey . $appendedString, $this->originalFilename, $timeString, $forceMimeType);
	}

	public function getAsArray() {
		return ["originalFilename"=>$this->originalFilename, "path"=>$this->path, "metadata"=>$this->metadata, "ready"=>$this->ready];
	}

	public function getType() {
		return pathinfo($this->originalFilename, PATHINFO_EXTENSION);
	}

	/**
	 * DELETE FILE
	 */

	function deleteFile($serial=null, $mfa=null) {
		if(strlen($this->storageKey) < 15) {
			$this->logging->logError("Deletion Error", "Was told to delete ". $this->storageKey . " but refusing due to bad length");
			return false;
		}
		return $this->parent->s3model->deleteObject($this->storageKey,$serial,$mfa);
	}
}

/* End of file  */
/* Location: ./application/models/ */
