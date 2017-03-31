<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class ZipMedDicomHandler extends ZipHandler {
	protected $supportedTypes = array("dicom.zip");
	protected $noDerivatives = false;

	public $taskArray = [0=>["taskType"=>"identifyContents", "config"=>array()],
	1=>["taskType"=>"extractMetadataAndPublish", "config"=>["continue"=>true, "thumbnailSettings"=>[["width"=>250, "height"=>250, "type"=>"thumbnail", "path"=>"thumbnail"],
						  												["width"=>500, "height"=>500, "type"=>"thumbnail2x", "path"=>"thumbnail"],
						  												["width"=>75, "height"=>150, "type"=>"tiny", "path"=>"thumbnail"],
						  												["width"=>150, "height"=>75, "type"=>"tiny2x", "path"=>"thumbnail"]
						  												]
						  												]],
	2=>["taskType"=>"cleanupOriginal", "config"=>array()]
	];


	public function __construct()
	{
		parent::__construct();
		$this->load->helper("media");
	}

	function identifyTypeOfBundle($localFile) {
		foreach($localFile as $fileEntry) {
			$ext = pathinfo($fileEntry, PATHINFO_EXTENSION);
			if(strtolower($ext) == 'dcm') {
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
			$derivative[] = "dicom";
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
			}
		}
		if(count($returnArray)>0) {
			return $returnArray;
		}
		else {
			throw new Exception("Derivative not found");
		}
	}

	// we do bad things here - more than we should in this method.  We don't want to risk having to start over again
	// when he hit the next pass through
	public function extractMetadataAndPublish($args) {
		$fileStatus = $this->sourceFile->makeLocal();

		if($fileStatus == FILE_GLACIER_RESTORING) {
			$this->postponeTime = 900;
			return JOB_POSTPONE;
		}
		elseif($fileStatus == FILE_ERROR) {
			return JOB_FAILED;
		}

		$this->pheanstalk->touch($this->job);


		$zip = new ZipArchive;
		$res = $zip->open($this->sourceFile->getPathToLocalFile());

		$targetPath = $this->sourceFile->getPathToLocalFile() . "_extracted";
		if(!$res) {
			$this->logging->processingInfo("createDerivative","dicomHandler","Coudl not extract zip",$this->getObjectId(),$this->job->getId());
			return JOB_FAILED;
		}

		$zip->extractTo($targetPath);
		$zip->close();

		$directoryContents = array_diff(scandir($targetPath), [".", "..", "__MACOSX"]);
		if(count($directoryContents) == 1) {
			$targetPath = $targetPath . "/" . array_pop($directoryContents);
		}

		$contents = new DirectoryIterator($targetPath);

		$imageSets = array();
		foreach($contents as $folder){
			if($folder->isDir() && !$folder->isDot()) {
				$imageSets[] = $folder->getFilename();
			}
		}
		natsort($imageSets);

		$seriesNumber = 1;
		$seriesList = array();
		$firstEntry = true;
		$masterFile = null;
		foreach($imageSets as $imageSet) {
			$seriesEntry = array();

			$di = new RecursiveDirectoryIterator($targetPath . "/" . $imageSet,RecursiveDirectoryIterator::SKIP_DOTS);
			$it = new RecursiveIteratorIterator($di);
			$fileSet = array();
			foreach($it as $entry) {
				if($entry->getFilename()[0] === ".") {
					continue;
				}
				if($entry->isFile() && pathinfo($entry->getPathname(), PATHINFO_EXTENSION) == "dcm") {
					$fileSet[] = str_replace($targetPath, "", $entry->getPathname());

					if($firstEntry) {
						$masterFile = $entry->getPathname();
						$firstEntry = false;
					}
				}
			}

			if(count($fileSet) == 0) {
				continue;
			}

			natsort($fileSet);

			$fileSetSorted = array();
			foreach($fileSet as $fileEntry) {
				$fileSetSorted[] = ["imageId"=>$fileEntry];
			}

			$seriesEntry["seriesDescription"] = $imageSet;
			$seriesEntry["seriesNumber"] = $seriesNumber;
			$seriesEntry["instanceList"] = $fileSetSorted;
			$seriesList[] = $seriesEntry;

			$seriesNumber++;

		}

		if($masterFile) {
			$imageContainer = new FileContainer($masterFile);
			$imageData = getImageMetadata($imageContainer);
			$this->sourceFile->metadata = $imageData;
			$this->sourceFile->metadata["filesize"] = $this->sourceFile->getFileSize();

			// Build thumbs
			//


			ini_set('memory_limit', '512M');
			$success = true;

			foreach($args["thumbnailSettings"] as $derivativeSetting) {
				if(!is_array($derivativeSetting)) {
					continue;
				}

				$derivativeType = $derivativeSetting["type"];
				$width = $derivativeSetting["width"];
				$height = $derivativeSetting["height"];

				$localPath = $masterFile;
				$pathparts = pathinfo($localPath);

				$derivativeContainer = new fileContainerS3();
				$derivativeContainer->derivativeType = $derivativeType;
				$derivativeContainer->path = $derivativeSetting['path'];
				$derivativeContainer->setParent($this->sourceFile->getParent());
				$derivativeContainer->originalFilename = $pathparts['filename'] . "_" . $derivativeType . '.jpg';

				if(compressImageAndSave($imageContainer, $derivativeContainer, $width, $height)) {
					$derivativeContainer->ready = true;

					if(!$derivativeContainer->copyToRemoteStorage()) {
						//TODO: log
						//TODO: remove derivative
						$this->logging->processingInfo("createThumbnails", "dicomhandler", "Could not upload thumbnail", $this->getObjectId(), $this->job->getId());
						echo "Error copying to remote" . $derivativeContainer->getPathToLocalFile();
						$success=false;
					}
					else {
						if(!unlink($derivativeContainer->getPathToLocalFile())) {
							$this->logging->processingInfo("createThumbnails", "dicomhandler", "Could not delete source file", $this->getObjectId(), $this->job->getId());
							echo "Error deleting source" . $derivativeContainer->getPathToLocalFile();
							$success=false;
						}
					}
					$this->derivatives[$derivativeType] = $derivativeContainer;
				}
				else {
					$this->logging->processingInfo("createThumbnails", "dicomhandler", "Could not create derivative", $this->getObjectId(), $this->job->getId());
					echo "Error generating deriative" . $derivativeContainer->getPathToLocalFile();
					$success=false;
				}
			}

			if(!$success) {
				return JOB_FAILED;
			}



		}


		$pathparts = pathinfo($targetPath);
		$derivativeContainer = new fileContainerS3();
		$derivativeContainer->derivativeType = "dicom";
		$derivativeContainer->path = "derivative";
		$derivativeContainer->originalFilename = $pathparts['filename'];
		$derivativeContainer->setParent($this->sourceFile->getParent());
		$derivativeContainer->metadata = $seriesList;
		$this->derivatives['dicom'] = $derivativeContainer;


		$this->load->helper("file");
 		if($this->s3model->putDirectory($targetPath, "derivative/". $this->getReversedObjectId() . "-dicom")) {
 			$derivativeContainer->ready = true;
        	delete_files($targetPath, true);
        }
        else {
        	return JOB_FAILED;
        }

		if($args['continue'] == true) {
			$this->queueTask(2);
		}

		$this->triggerReindex();

		return JOB_SUCCESS;
	}

}

/* End of file  */
/* Location: ./application/controllers/ */
