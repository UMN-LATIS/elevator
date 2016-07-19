<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Transcoder_Model extends CI_Model {

	public $videoToolkitConfig;
	public $threadCount = 4;
	private $fileHandler;

	public function __construct()
	{
		parent::__construct();

		$this->videoToolkitConfig = new \PHPVideoToolkit\Config(array(
            'temp_directory'              => $this->basePath = $this->config->item("scratchSpace"),
            'ffmpeg'                      => $this->config->item("ffmpegBinary"),
            'ffprobe'                     => $this->config->item("ffprobeBinary"),
            'yamdi'                       => $this->config->item("yamdiBinary"),
            'qtfaststart'                 => $this->config->item("qtfaststartBinary"),
            'gif_transcoder'              => 'php',
            'convert'                     => $this->config->item("convertBinary"),
            'gifsicle'                    => $this->config->item("gifsicleBinary"),
            'php_exec_infinite_timelimit' => true,
        ));
		$this->load->helper("file");
	}

	/**
	 * parent task makes sure we're not in glacier by the time transcoder is invoked
	 */
	public function checkLocalAndCopy() {
		if(!$this->fileHandler->sourceFile->isLocal()) {
			$fileStatus = $this->fileHandler->sourceFile->makeLocal();
			if($fileStatus === FILE_ERROR || $fileStatus == FILE_GLACIER_RESTORING) {
				$this->logging->processingInfo("copy Local", "video","Could not copy local file", $this->fileHandler->getObjectId(), $this->job->getId());
				return false;
			}
			$this->pheanstalk->touch($this->job);
		}
		return true;
	}

	public function extractMetadata() {

		if(!$this->checkLocalAndCopy()) {
			return JOB_POSTPONE;
		}


		$phpvideotoolkit_media = new \PHPVideoToolkit\FfmpegProcess("ffprobe", $this->videoToolkitConfig);

		$raw_data = $phpvideotoolkit_media->setInputPath($this->fileHandler->sourceFile->getPathToLocalFile())
	         ->addCommand('-show_streams')
	         ->addCommand('-show_format')
	         ->addCommand('-print_format', "json")
	         ->addCommand('-v', "quiet")
	         ->execute()
	         ->getBuffer();

		$sourceMetadata = json_decode($raw_data,true);
		$metadata = array();
		foreach($sourceMetadata["streams"] as $stream) {
			$metadata[$stream["codec_type"]] = $stream;
		}
		$metadata["format"] = $sourceMetadata["format"];
		$targetMetadata = array();
		if(isset($metadata["video"])) {
			$targetMetadata["width"] = $metadata["video"]["width"];
			$targetMetadata["height"] = $metadata["video"]["height"];
			$targetMetadata["videoCodec"] = $metadata["video"]["codec_name"];
			$targetMetadata["displayAspect"] = $metadata["video"]["display_aspect_ratio"];
			$targetMetadata["pixelAspect"] = $metadata["video"]["sample_aspect_ratio"];
			if(isset($metadata["video"]["tags"]["rotate"])) {
				$targetMetadata["rotation"] = $metadata["video"]["tags"]["rotate"];
			}
			else {
				$targetMetadata["rotation"] = 0;
			}


			if(isset($metadata["format"]["tags"]["location"])) {
				$location = $metadata["format"]["tags"]["location"];
				$splitGPS = preg_split( "/(\+|-|\/)/", $location, null,PREG_SPLIT_DELIM_CAPTURE );

				$lat = floatval($splitGPS[1] . $splitGPS[2]);
				$lon = floatval($splitGPS[3] . $splitGPS[4]);
				$targetMetadata["coordinates"] = [$lon, $lat]; //store it lon/lat becuase that's what elastic wants
			}
			if(isset($metadata["format"]["tags"]["creation_time"])) {
				$dateString = $metadata["format"]["tags"]["creation_time"];
				$targetMetadata["creationDate"] = $dateString;
			}

		}

		if(isset($metadata["format"]["duration"])) {
			$targetMetadata["duration"] = $metadata["format"]["duration"];
		}

		if(isset($metadata["audio"])) {
			$targetMetadata["audioCodec"] = $metadata["audio"]["codec_name"];
			$targetMetadata["channels"] = $metadata["audio"]["channels"];
			$targetMetadata["sampleRate"] = $metadata["audio"]["sample_rate"];
		}

		$targetMetadata["bulkMetadata"] = json_encode($metadata);
		$targetMetadata["filesize"] = $this->fileHandler->sourceFile->getFileSize();

/**
		 * As these standards evolve this should be refactored
		 */
		$this->load->model("asset_model");
		$uploadWidget = $this->fileHandler->getUploadWidget();
		if(stristr($uploadWidget->fileDescription, "spherical")) {
			$targetMetadata["spherical"] = true;
		
			if(stristr($uploadWidget->fileDescription, "stereo")) {
				$targetMetadata["stereo"] = true;
			}
		}
		else {

			$fileType = strtolower($this->fileHandler->asset->getFileType());
			if($fileType == "mov" || $fileType == "mp4") {
				rename($this->fileHandler->sourceFile->getPathToLocalFile(), $this->fileHandler->sourceFile->getPathToLocalFile() . "." . $fileType);
				$commandString = "python " . $this->config->item("spatialMedia"). " " . $this->fileHandler->sourceFile->getPathToLocalFile() . "." . $fileType;
				exec($commandString, $output);
				rename($this->fileHandler->sourceFile->getPathToLocalFile() . "." . $fileType, $this->fileHandler->sourceFile->getPathToLocalFile());
				foreach($output as $line) {
					if(stristr($line, "spherical") && stristr($line, "true")) {
						$targetMetadata["spherical"] = true;
					}
					if(stristr($line, "stereo") && stristr($line, "true")) {
						$targetMetadata["stereo"] = true;
					}
				}



			}

		}

		$this->fileHandler->sourceFile->metadata = array_merge($this->fileHandler->sourceFile->metadata, $targetMetadata);

		//TODO: update asset for reindexing
		//
		return JOB_SUCCESS;

	}

	public function createSequence($args) {
		if(!$this->checkLocalAndCopy()) {
			return JOB_POSTPONE;
		}

		$localPath = $this->fileHandler->sourceFile->getPathToLocalFile();
		$pathparts = pathinfo($localPath);
		$derivativeContainer = new fileContainerS3();
		$derivativeContainer->derivativeType = "imageSequence";
		$derivativeContainer->path = "derivative";
		$derivativeContainer->originalFilename = $pathparts['filename'] .  "_" . "sequence" . '.jpg';
		$derivativeContainer->setParent($this->fileHandler);
		$this->fileHandler->derivatives['imageSequence'] = $derivativeContainer;


		$outputFormat = new \PHPVideoToolkit\ImageFormat_Jpeg('output', $this->videoToolkitConfig);
		$outputFormat->setThreads($this->threadCount);
		$video = new \PHPVideoToolkit\Video($this->fileHandler->sourceFile->getPathToLocalFile(), $this->videoToolkitConfig);

		$duration = $this->fileHandler->sourceFile->metadata["duration"];

		$rate = $duration / 20;
		$time = new \PHPVideoToolkit\Timecode(0);
		$end = new \PHPVideoToolkit\Timecode(floor($duration));


		$isRotated=false;
		$rotationString = "";
		if($this->fileHandler->sourceFile->metadata["rotation"] != 0) {
			$isRotated = true;
			switch ($this->fileHandler->sourceFile->metadata["rotation"]) {
				case 90:
					$rotationString = "transpose=1,";
					break;
				case 180:
					$rotationString = "transpose=2,transpose=2,";
					break;
				case 270:
					$rotationString = "transpose=2,";
					break;
			}

		}


		$process = $video->getProcess();
		$process->addCommand("-vf", $rotationString . "fps=1/" . $rate . ",scale=iw*sar:ih", true);

		$video->extractFrames($time, $end, null);
		if(!file_exists($derivativeContainer->getPathToLocalFile())) {
			mkdir($derivativeContainer->getPathToLocalFile());
		}

		$output = $this->runTask($video, $derivativeContainer->getPathToLocalFile() . "/%index.jpg", $outputFormat);
		if(!$output) {
			delete_files($derivativeContainer->getPathToLocalFile(), true);
			return JOB_FAILED;
		}

		$frames = $output->getOutput();
        $frame_paths = array();
        if(empty($frames) === false)
        {
            foreach ($frames as $frame)
            {
                array_push($frame_paths, $frame->getMediaPath());
            }
        }
        $i=1;
        foreach($frame_paths as $path) {
        	rename($path, str_replace(".jpg", "", $path));
        }

        if($this->putAllFilesInFolderToKey($derivativeContainer->getPathToLocalFile(), "derivative/". $this->fileHandler->getReversedObjectId() . "-imageSequence", "image/jpeg")) {
        	delete_files($derivativeContainer->getPathToLocalFile(), true);
        }
        else {
        	return JOB_FAILED;
        }
        return JOB_SUCCESS;
	}

	public function createThumbnail($args) {
		if(!$this->checkLocalAndCopy()) {
			return JOB_POSTPONE;
		}

		$localPath = $this->fileHandler->sourceFile->getPathToLocalFile();
		$pathparts = pathinfo($localPath);

		$derivativeContainer = new fileContainerS3();
		$derivativeContainer->derivativeType = "thumbnail";
		$derivativeContainer->path = "thumbnail";
		$derivativeContainer->originalFilename = $pathparts['filename'] .  "_" . "thumbnail" . '.jpg';
		$derivativeContainer->setParent($this->fileHandler);
		$this->fileHandler->derivatives['thumbnail'] = $derivativeContainer;
		$outputformat = new \PHPVideoToolkit\ImageFormat_Jpeg('output', $this->videoToolkitConfig);

		try {
			$video = new \PHPVideoToolkit\Video($this->fileHandler->sourceFile->getPathToLocalFile(), $this->videoToolkitConfig);
		}
		catch(Exception $e)
		{
			$this->logging->processingInfo("ffmpeg", "video",$e->getMessage(), "", $this->job->getId());
			return JOB_FAILED;
		}


		$duration = $this->fileHandler->sourceFile->metadata["duration"];

		$time = new \PHPVideoToolkit\Timecode($duration*.2);

		$isRotated=false;
		$rotationString = "";
		if($this->fileHandler->sourceFile->metadata["rotation"] != 0) {
			$isRotated = true;
			switch ($this->fileHandler->sourceFile->metadata["rotation"]) {
				case 90:
					$rotationString = "transpose=1,";
					break;
				case 180:
					$rotationString = "transpose=2,transpose=2,";
					break;
				case 270:
					$rotationString = "transpose=2,";
					break;
			}

		}


		$process = $video->getProcess();
		$process->setProcessTimelimit(60);
		$process->addCommand("-vf", $rotationString . "scale=250:trunc(ow/dar/2)*2", true);
		try {
			$output = $video->extractFrame($time)->save($derivativeContainer->getPathToLocalFile(), $outputformat, \PHPVideoToolkit\Video::OVERWRITE_EXISTING);
		}
		catch (PHPVideoToolkit\FfmpegProcessOutputException $e) {
			$this->logging->processingInfo("ffmpeg", "video",$e->getMessage(), "", $this->job->getId());
			return JOB_FAILED;
		}

		$derivativeContainer->copyToRemoteStorage();
		$derivativeContainer->ready = true;
		$derivativeContainer->removeLocalFile();

		$derivativeContainer = new fileContainerS3();
		$derivativeContainer->derivativeType = "thumbnail2x";
		$derivativeContainer->path = "thumbnail";
		$derivativeContainer->originalFilename = $pathparts['filename'] .  "_" . "thumbnail2x" . '.jpg';
		$derivativeContainer->setParent($this->fileHandler);
		$this->fileHandler->derivatives['thumbnail2x'] = $derivativeContainer;

		try {
		$video = new \PHPVideoToolkit\Video($this->fileHandler->sourceFile->getPathToLocalFile(), $this->videoToolkitConfig);
		}
		catch(Exception $e)
		{
			$this->logging->processingInfo("ffmpeg", "video",$e->getMessage(), "", $this->job->getId());
			return JOB_FAILED;
		}


		$duration = $this->fileHandler->sourceFile->metadata["duration"];
		$time = new \PHPVideoToolkit\Timecode($duration*.2);
		$process = $video->getProcess();

		$process->addCommand("-vf", $rotationString . "scale=500:trunc(ow/dar/2)*2", true);


 		$output = $video->extractFrame($time)->save($derivativeContainer->getPathToLocalFile(), $outputformat, \PHPVideoToolkit\Video::OVERWRITE_EXISTING);

        if(!$output) {
        	return JOB_FAILED;
        }
        $derivativeContainer->copyToRemoteStorage();
        $derivativeContainer->removeLocalFile();
        $derivativeContainer->ready = true;


        $this->fileHandler->triggerReindex();
 		return JOB_SUCCESS;
	}


		public function createTiny($args) {
		if(!$this->checkLocalAndCopy()) {
			return JOB_POSTPONE;
		}

		$localPath = $this->fileHandler->sourceFile->getPathToLocalFile();
		$pathparts = pathinfo($localPath);

		$derivativeContainer = new fileContainerS3();
		$derivativeContainer->derivativeType = "tiny";
		$derivativeContainer->path = "thumbnail";
		$derivativeContainer->originalFilename = $pathparts['filename'] .  "_" . "tiny" . '.jpg';
		$derivativeContainer->setParent($this->fileHandler);
		$this->fileHandler->derivatives['tiny'] = $derivativeContainer;
		$outputformat = new \PHPVideoToolkit\ImageFormat_Jpeg('output', $this->videoToolkitConfig);

		try {
			$video = new \PHPVideoToolkit\Video($this->fileHandler->sourceFile->getPathToLocalFile(), $this->videoToolkitConfig);
		}
		catch(Exception $e)
		{
			$this->logging->processingInfo("ffmpeg", "video",$e->getMessage(), "", $this->job->getId());
			return JOB_FAILED;
		}


		$duration = $this->fileHandler->sourceFile->metadata["duration"];

		$time = new \PHPVideoToolkit\Timecode($duration*.2);

		$isRotated=false;
		$rotationString = "";
		if($this->fileHandler->sourceFile->metadata["rotation"] != 0) {
			$isRotated = true;
			switch ($this->fileHandler->sourceFile->metadata["rotation"]) {
				case 90:
					$rotationString = "transpose=1,";
					break;
				case 180:
					$rotationString = "transpose=2,transpose=2,";
					break;
				case 270:
					$rotationString = "transpose=2,";
					break;
			}

		}


		$process = $video->getProcess();
		$process->setProcessTimelimit(60);
		$process->addCommand("-vf", $rotationString . "scale=75:trunc(ow/dar/2)*2", true);

 		$output = $video->extractFrame($time)->save($derivativeContainer->getPathToLocalFile(), $outputformat, \PHPVideoToolkit\Video::OVERWRITE_EXISTING);
		$derivativeContainer->copyToRemoteStorage();
		$derivativeContainer->removeLocalFile();
		$derivativeContainer->ready = true;

		$derivativeContainer = new fileContainerS3();
		$derivativeContainer->derivativeType = "tiny2x";
		$derivativeContainer->path = "thumbnail";
		$derivativeContainer->originalFilename = $pathparts['filename'] .  "_" . "tiny2x" . '.jpg';
		$derivativeContainer->setParent($this->fileHandler);
		$this->fileHandler->derivatives['tiny2x'] = $derivativeContainer;
		$video = new \PHPVideoToolkit\Video($this->fileHandler->sourceFile->getPathToLocalFile(), $this->videoToolkitConfig);

		$duration = $this->fileHandler->sourceFile->metadata["duration"];
		$time = new \PHPVideoToolkit\Timecode($duration*.2);
		$process = $video->getProcess();

		$process->addCommand("-vf", $rotationString . "scale=150:trunc(ow/dar/2)*2", true);


 		$output = $video->extractFrame($time)->save($derivativeContainer->getPathToLocalFile(), $outputformat, \PHPVideoToolkit\Video::OVERWRITE_EXISTING);
        if(!$output) {
        	return JOB_FAILED;
        }
        $derivativeContainer->copyToRemoteStorage();
        $derivativeContainer->removeLocalFile();
        $derivativeContainer->ready = true;
        $this->fileHandler->triggerReindex();
 		return JOB_SUCCESS;
	}



	public function createVTT($args) {
		if(!$this->checkLocalAndCopy()) {
			return JOB_POSTPONE;
		}

		$localPath = $this->fileHandler->sourceFile->getPathToLocalFile();
		$pathparts = pathinfo($localPath);

		$derivativeContainer = new fileContainerS3();
		$derivativeContainer->derivativeType = "vtt";
		$derivativeContainer->path = "vtt";
		$derivativeContainer->originalFilename = $pathparts['filename'] .  "_" . "vtt" . '.jpg';
		$derivativeContainer->setParent($this->fileHandler);
		$this->fileHandler->derivatives['vtt'] = $derivativeContainer;

		$isRotated=false;
		$rotationString = "";
		if($this->fileHandler->sourceFile->metadata["rotation"] != 0) {
			$isRotated = true;
			switch ($this->fileHandler->sourceFile->metadata["rotation"]) {
				case 90:
					$rotationString = "transpose=1,";
					break;
				case 180:
					$rotationString = "transpose=2,transpose=2,";
					break;
				case 270:
					$rotationString = "transpose=2,";
					break;
			}

		}


		try {
			$video = new \PHPVideoToolkit\Video($this->fileHandler->sourceFile->getPathToLocalFile(), $this->videoToolkitConfig);
		}
		catch(Exception $e)
		{
			$this->logging->processingInfo("ffmpeg", "video",$e->getMessage(), "", $this->job->getId());
			return JOB_FAILED;
		}


		$process = $video->getProcess();

		$outputFormat = new \PHPVideoToolkit\VideoFormat('output', $this->videoToolkitConfig);
		$outputFormat->setVideoBitrate("20M")->setFormat("image2")->setThreads($this->threadCount);

		$process->addCommand("-vf", $rotationString . " fps=1/30");


		if(!file_exists($derivativeContainer->getPathToLocalFile(). "-contents")) {
			mkdir($derivativeContainer->getPathToLocalFile(). "-contents");
		}


		$output = $this->runTask($video, $derivativeContainer->getPathToLocalFile() . "-contents/output%3index.jpg", $outputFormat);
		if(!$output) {
			delete_files($derivativeContainer->getPathToLocalFile(). "-contents/", true);
			return JOB_FAILED;
		}
		$fileList = array_diff(scandir($derivativeContainer->getPathToLocalFile() . "-contents/"),array('..', '.', ".DS_Store"));

		foreach($fileList as $file) {
			exec("mogrify -geometry 100 " . $derivativeContainer->getPathToLocalFile() . "-contents/" . $file);
		}

		$dimensions = getimagesize($derivativeContainer->getPathToLocalFile() . "-contents/" . $file);

		$width = $dimensions[0];
		$height = $dimensions[1];

		$gridRowCount = ceil(count($fileList) / 4);

		exec("montage " . $derivativeContainer->getPathToLocalFile() . "-contents/output*jpg -tile 4x" . $gridRowCount . " -geometry " . $width."x".$height."+0+0 " . $derivativeContainer->getPathToLocalFile());

		$derivativeContainer->copyToRemoteStorage(".jpg");


		// this is just for uplaoding the vtt, we dont' actually keep it around
		$vttContainer = new fileContainerS3();
		$vttContainer->derivativeType = "vtt";
		$vttContainer->path = "vtt";
		$vttContainer->originalFilename = $pathparts['filename'] .  "_" . "vtt" . '.vtt';
		$vttContainer->setParent($this->fileHandler);


		$fp = fopen($vttContainer->getPathToLocalFile(), "w");
		fwrite($fp, "WEBVTT\n\n");
		reset($fileList);
		$clipStart = 0;
		$clipCount = 1;
		foreach($fileList as $file) {
			$timingString = gmdate("H:i:s", $clipStart) . " --> " . gmdate("H:i:s", $clipStart+30) . "\n";
			$locationString = $this->fileHandler->getReversedObjectId() . "-vtt.jpg" . "#xywh=" . $this->get_grid_coordinates($clipCount, 4, $width, $height) . "\n\n";
			$clipStart += 30;
			$clipCount++;
			fwrite($fp, $timingString);
			fwrite($fp, $locationString);

		}
		fclose($fp);
		$vttContainer->copyToRemoteStorage(".vtt");

		delete_files($derivativeContainer->getPathToLocalFile() . "-contents", true);
		return JOB_SUCCESS;

	}


	private function get_grid_coordinates($imageNumber,$gridWidth,$width,$height) {
    	$y = floor(($imageNumber - 1)/$gridWidth);
    	$x = ($imageNumber -1) - ($y * $gridWidth);
    	$imgx = $x * $width;
    	$imgy = $y * $height;
    	return $imgx . "," . $imgy . "," . $width . "," . $height;
	}


	public function cleanup($args) {
		// We shouldn't be here unless we've hit the wrong machine.
		// TODO: if we're scaling to multiple machines, each derivative should just clean itself up?
		// or perhaps just a process to do it?
		if(!$this->fileHandler->sourceFile->isLocal()) {
			return JOB_SUCCESS;
		}

		if($this->fileHandler->sourceFile->removeLocalFile()) {
			return JOB_SUCCESS;
		}

		return JOB_FAILED;

	}


	public function createDerivative($args) {
		$type = $args['type'];
		if(!$this->checkLocalAndCopy()) {
			return JOB_POSTPONE;
		}

		$localPath = $this->fileHandler->sourceFile->getPathToLocalFile();
		$pathparts = pathinfo($localPath);


		$derivativeContainer = new fileContainerS3();

		$isRotated=false;
		$rotationString = "";
		if(isset($this->fileHandler->sourceFile->metadata["rotation"]) && $this->fileHandler->sourceFile->metadata["rotation"] != 0) {
			$isRotated = true;
			switch ($this->fileHandler->sourceFile->metadata["rotation"]) {
				case 90:
					$rotationString = "transpose=1,";
					break;
				case 180:
					$rotationString = "transpose=2,transpose=2,";
					break;
				case 270:
					$rotationString = "transpose=2,";
					break;
			}

		}


        switch ($type) {
        	case "SD":
				$derivativeContainer->derivativeType = "mp4sd";
				$derivativeContainer->path = "derivative";
				$derivativeContainer->originalFilename = $pathparts['filename'] . "_" . "mp4sd.mp4";
				$derivativeContainer->setParent($this->fileHandler);
				$this->fileHandler->derivatives['mp4sd'] = $derivativeContainer;

				$video = new \PHPVideoToolkit\Video($localPath, $this->videoToolkitConfig);
				$process = $video->getProcess();

        		$outputFormat = new \PHPVideoToolkit\VideoFormat_H264('output', $this->videoToolkitConfig);
 				$outputFormat->setAudioCodec('aac')->setAudioChannels(2)->setVideoCodec('h264');

 				if(isset($this->fileHandler->sourceFile->metadata["sampleRate"])) {
 					$outputFormat->setAudioSampleFrequency((int)$this->fileHandler->sourceFile->metadata["sampleRate"]);
 				}
 				else {
 					$outputFormat->setAudioSampleFrequency(44100);
 				}

 				$outputFormat->setH264Preset("veryfast");
        		$outputFormat->setFormat("mp4")->setAudioBitrate("96k")->setQualityVsStreamabilityBalanceRatio(null)->setThreads($this->threadCount);
				$process->addCommand("-movflags", "faststart");
				$process->addCommand("-video_track_timescale", "90000"); // is this a good idea? make sure we don't end up with unreasonable timescales.
				$process->addCommand("-crf", 23);
				$process->addCommand("-pix_fmt", "yuv420p");
				$process->addCommand("-vf", $rotationString . "scale=trunc(oh*dar/2)*2:480,setdar=0", true);
				if($isRotated) {
	        		$process->addCommand('-metadata:s:v', 'rotate=""');
	        	}
				$this->mungeAspect($process);
				$output = $this->runTask($video, $derivativeContainer->getPathToLocalFile(), $outputFormat);
				if(!$output) {
					$this->logging->processingInfo("createDerivative", "SD not created","", "", $this->job->getId());
					return JOB_FAILED;
				}
        		echo "Uploading\n";
        		if($derivativeContainer->copyToRemoteStorage()) {
        			$derivativeContainer->removeLocalFile();
        			$derivativeContainer->ready = true;
        		}
        		else {
        			$this->logging->processingInfo("createDerivative", "Could not upload SD","", "", $this->job->getId());
        			return JOB_FAILED;
        		}
        		break;
			case "HD":
				$derivativeContainer->derivativeType = "mp4hd";
				$derivativeContainer->path = "derivative";
				$derivativeContainer->originalFilename = $pathparts['filename'] . "_" . "mp4hd.mp4";
				$derivativeContainer->setParent($this->fileHandler);
				$this->fileHandler->derivatives['mp4hd'] = $derivativeContainer;

				$video = new \PHPVideoToolkit\Video($localPath, $this->videoToolkitConfig);
				$process = $video->getProcess();

        		$outputFormat = new \PHPVideoToolkit\VideoFormat_H264('output', $this->videoToolkitConfig);
 				$outputFormat->setAudioCodec('aac')->setAudioChannels(2)->setVideoCodec('h264');

 				if(isset($this->fileHandler->sourceFile->metadata["sampleRate"])) {
 					$outputFormat->setAudioSampleFrequency((int)$this->fileHandler->sourceFile->metadata["sampleRate"]);
 				}
 				else {
 					$outputFormat->setAudioSampleFrequency(44100);
 				}

 				$outputFormat->setH264Preset("veryfast");
	       		$outputFormat->setFormat("mp4")->setAudioBitrate("128k")->setQualityVsStreamabilityBalanceRatio(null)->setThreads($this->threadCount);
        		$process->addCommand("-movflags", "faststart");
        		$process->addCommand("-video_track_timescale", "90000"); // is this a good idea? make sure we don't end up with unreasonable timescales.
				$process->addCommand("-crf", 23);
				$process->addCommand("-maxrate", "3500k");
				$process->addCommand("-bufsize", "1835k");
				$process->addCommand("-pix_fmt", "yuv420p");
				if($isRotated) {
	        		$process->addCommand('-metadata:s:v', 'rotate=""');
	        	}
				$process->addCommand("-vf", $rotationString . "scale=trunc(oh*dar/2)*2:720,setdar=0", true);
				$output = $this->runTask($video, $derivativeContainer->getPathToLocalFile(), $outputFormat);
				if(!$output) {
					$this->logging->processingInfo("createDerivative", "HD not created","", "", $this->job->getId());
					return JOB_FAILED;
				}
        		echo "Uploading";
				if($derivativeContainer->copyToRemoteStorage()) {
        			$derivativeContainer->removeLocalFile();
        			$derivativeContainer->ready = true;
        		}
        		else {
        			$this->logging->processingInfo("createDerivative", "could not upload HD","", "", $this->job->getId());
        			return JOB_FAILED;
        		}
        		break;
        	case "HD1080":
				$derivativeContainer->derivativeType = "mp4hd1080";
				$derivativeContainer->path = "derivative";
				$derivativeContainer->originalFilename = $pathparts['filename'] . "_" . "mp4hd1080.mp4";
				$derivativeContainer->setParent($this->fileHandler);
				$this->fileHandler->derivatives['mp4hd1080'] = $derivativeContainer;

				$video = new \PHPVideoToolkit\Video($localPath, $this->videoToolkitConfig);
				$process = $video->getProcess();

        		$outputFormat = new \PHPVideoToolkit\VideoFormat_H264('output', $this->videoToolkitConfig);
 				$outputFormat->setAudioCodec('aac')->setAudioChannels(2)->setVideoCodec('h264');

 				if(isset($this->fileHandler->sourceFile->metadata["sampleRate"])) {
 					$outputFormat->setAudioSampleFrequency((int)$this->fileHandler->sourceFile->metadata["sampleRate"]);
 				}
 				else {
 					$outputFormat->setAudioSampleFrequency(44100);
 				}

 				$outputFormat->setH264Preset("veryfast");
	       		$outputFormat->setFormat("mp4")->setAudioBitrate("128k")->setQualityVsStreamabilityBalanceRatio(null)->setThreads($this->threadCount);
        		$process->addCommand("-movflags", "faststart");
        		$process->addCommand("-video_track_timescale", "90000"); // is this a good idea? make sure we don't end up with unreasonable timescales.
				$process->addCommand("-crf", 23);
				$process->addCommand("-maxrate", "4500k");
				$process->addCommand("-bufsize", "1835k");
				$process->addCommand("-pix_fmt", "yuv420p");
				if($isRotated) {
	        		$process->addCommand('-metadata:s:v', 'rotate=""');
	        	}
				$process->addCommand("-vf", $rotationString . "scale=1920:trunc(ow/dar/2)*2,setdar=0", true);
				$output = $this->runTask($video, $derivativeContainer->getPathToLocalFile(), $outputFormat);
				if(!$output) {
					$this->logging->processingInfo("createDerivative", "HD1080 not created","", "", $this->job->getId());
					return JOB_FAILED;
				}
        		echo "Uploading";
				if($derivativeContainer->copyToRemoteStorage()) {
        			$derivativeContainer->removeLocalFile();
        			$derivativeContainer->ready = true;
        		}
        		else {
        			$this->logging->processingInfo("createDerivative", "could not upload HD","", "", $this->job->getId());
        			return JOB_FAILED;
        		}
        		break;
        	case "HLS":

        		// THIS SUCKS

				$derivativeContainer->derivativeType = "streaming";
				$derivativeContainer->path = "derivative";
				$derivativeContainer->originalFilename = $pathparts['filename'] . "_" . "_streaming";
				$derivativeContainer->setParent($this->fileHandler);
				$this->fileHandler->derivatives['streaming'] = $derivativeContainer;
				if(!file_exists($derivativeContainer->getPathToLocalFile() . "/stream/")) {
					mkdir($derivativeContainer->getPathToLocalFile() . "/stream/", 0777,true);
				}


				/**
				 * 2000K HD
				 */
				$haveHD = false;
				if($this->fileHandler->sourceFile->metadata["height"]>=720) {
					$haveHD = true;
					$video = new \PHPVideoToolkit\Video($localPath, $this->videoToolkitConfig);
					$process = $video->getProcess();

	        		$outputFormat = new \PHPVideoToolkit\VideoFormat_H264('output', $this->videoToolkitConfig);
	 				$outputFormat->setAudioCodec('aac')->setAudioChannels(2)->setVideoCodec('h264');

	 				if(isset($this->fileHandler->sourceFile->metadata["sampleRate"])) {
	 					$outputFormat->setAudioSampleFrequency((int)$this->fileHandler->sourceFile->metadata["sampleRate"]);
	 				}
	 				else {
	 					$outputFormat->setAudioSampleFrequency(44100);
	 				}

	 				$outputFormat->setH264Preset("veryfast");

	        		$outputFormat->setVideoBitrate("2000k")->setFormat("hls")->setAudioBitrate("128k")->setQualityVsStreamabilityBalanceRatio(null)->setThreads($this->threadCount);
	        		$process->addCommand("-vf", $rotationString . "scale=trunc(oh*dar/2)*2:720,setdar=0", true);
					$process->addCommand("-hls_time", 10);
					$process->addCommand("-hls_list_size", 999);
					$process->addCommand("-pix_fmt", "yuv420p");
	        		if($isRotated) {
	        			$process->addCommand('-metadata:s:v', 'rotate=""');
	        		}


	        		$output = $this->runTask($video, $derivativeContainer->getPathToLocalFile() . "/stream/stream-2000k.m3u8", $outputFormat);
	        		if(!$output) {
	        			$this->logging->processingInfo("createDerivative", "hls not created","", "", $this->job->getId());
						return JOB_FAILED;
					}
				}

				/**
				 * 1200K SD
				 */
				$video = new \PHPVideoToolkit\Video($localPath, $this->videoToolkitConfig);
				$process = $video->getProcess();

        		$outputFormat = new \PHPVideoToolkit\VideoFormat_H264('output', $this->videoToolkitConfig);
 				$outputFormat->setAudioCodec('aac')->setAudioChannels(2)->setVideoCodec('h264');
 				$outputFormat->setH264Preset("veryfast")->setThreads($this->threadCount);

 				if(isset($this->fileHandler->sourceFile->metadata["sampleRate"])) {
 					$outputFormat->setAudioSampleFrequency((int)$this->fileHandler->sourceFile->metadata["sampleRate"]);
 				}
 				else {
 					$outputFormat->setAudioSampleFrequency(44100);
 				}

        		$outputFormat->setVideoBitrate("1200k")->setFormat("hls")->setAudioBitrate("128k")->setQualityVsStreamabilityBalanceRatio(null);
        		$process->addCommand("-vf", $rotationString . "scale=trunc(oh*dar/2)*2:480,setdar=0", true);
				$process->addCommand("-hls_time", 10);
				$process->addCommand("-hls_list_size", 999);
				$process->addCommand("-pix_fmt", "yuv420p");
				$this->mungeAspect($process);
        		if($isRotated) {
        			$process->addCommand('-metadata:s:v', 'rotate=""');
        		}


        		$output = $this->runTask($video, $derivativeContainer->getPathToLocalFile() . "/stream/stream-1200k.m3u8", $outputFormat);
				if(!$output) {
					$this->logging->processingInfo("createDerivative", "hls not created","", "", $this->job->getId());
					return JOB_FAILED;
				}
				/**
				 * 600K 360p
				 */
				$video = new \PHPVideoToolkit\Video($localPath, $this->videoToolkitConfig);
				$process = $video->getProcess();

        		$outputFormat = new \PHPVideoToolkit\VideoFormat_H264('output', $this->videoToolkitConfig);
 				$outputFormat->setAudioCodec('aac')->setAudioChannels(2)->setVideoCodec('h264');
 				$outputFormat->setH264Preset("veryfast")->setThreads($this->threadCount);

 				if(isset($this->fileHandler->sourceFile->metadata["sampleRate"])) {
 					$outputFormat->setAudioSampleFrequency((int)$this->fileHandler->sourceFile->metadata["sampleRate"]);
 				}
 				else {
 					$outputFormat->setAudioSampleFrequency(44100);
 				}
        		$outputFormat->setVideoBitrate("600k")->setFormat("hls")->setAudioBitrate("64k")->setQualityVsStreamabilityBalanceRatio(null);
        		$process->addCommand("-vf", $rotationString . "scale=trunc(oh*dar/2)*2:360,setdar=0", true);
				$process->addCommand("-hls_time", 10);
				$process->addCommand("-hls_list_size", 999);
				$process->addCommand("-pix_fmt", "yuv420p");
				$this->mungeAspect($process);
        		if($isRotated) {
        			$process->addCommand('-metadata:s:v', 'rotate=""');
        		}


        		$ouptut = $this->runTask($video, $derivativeContainer->getPathToLocalFile() . "/stream/stream-600k.m3u8", $outputFormat);
				if(!$output) {
					$this->logging->processingInfo("createDerivative", "hls not created","", "", $this->job->getId());
					return JOB_FAILED;
				}

        		/**
        		 * write out stream file
        		 * @var [type]
        		 */
        		$fp = fopen($derivativeContainer->getPathToLocalFile() . "/stream/stream.m3u8", "w");

        		fwrite($fp, "#EXTM3U\n");
        		fwrite($fp, "#EXT-X-STREAM-INF:PROGRAM-ID=1,BANDWIDTH=1388000,RESOLUTION=".$this->getResolutionForHeight(480) . "\n");
        		fwrite($fp, "stream-1200k.m3u8\n");
        		if($haveHD) {
        			fwrite($fp, "#EXT-X-STREAM-INF:PROGRAM-ID=1,BANDWIDTH=2191000,RESOLUTION=".$this->getResolutionForHeight(720) . "\n");
        			fwrite($fp, "stream-2000k.m3u8\n");
        		}
        		fwrite($fp, "#EXT-X-STREAM-INF:PROGRAM-ID=1,BANDWIDTH=790000,RESOLUTION=".$this->getResolutionForHeight(360) . "\n");
        		fwrite($fp, "stream-600k.m3u8\n");
        		fclose($fp);


        		$this->putAllFilesInFolderToKey($derivativeContainer->getPathToLocalFile() . "/stream/",  "derivative/". $this->fileHandler->getReversedObjectId() . "-streaming");
        		delete_files($derivativeContainer->getPathToLocalFile() . "/stream/", true);
        		$derivativeContainer->ready = true;
        		break;
        	case "mp3":
        		$derivativeContainer->derivativeType = "mp3";
				$derivativeContainer->path = "derivative";
				$derivativeContainer->originalFilename = $pathparts['filename'] . "_" . "mp3.mp3";
				$derivativeContainer->setParent($this->fileHandler);
				$this->fileHandler->derivatives['mp3'] = $derivativeContainer;

				$video = new \PHPVideoToolkit\Audio($localPath, $this->videoToolkitConfig);
				$process = $video->getProcess();
				$process->addCommand("-vn");
        		$outputFormat = new \PHPVideoToolkit\AudioFormat_Mp3('output', $this->videoToolkitConfig);
	       		$outputFormat->setFormat("mp3")->setAudioBitrate("128k")->setThreads($this->threadCount);

				$output = $this->runTask($video, $derivativeContainer->getPathToLocalFile(), $outputFormat);
				if(!$output) {
					$this->logging->processingInfo("createDerivative", "mp3 not created","", "", $this->job->getId());
					return JOB_FAILED;
				}
        		echo "Uploading";
				if($derivativeContainer->copyToRemoteStorage()) {
        			$derivativeContainer->removeLocalFile();
        			$derivativeContainer->ready = true;
        		}
        		else {
        			$this->logging->processingInfo("createDerivative", "uploading MP3 failed","", "", $this->job->getId());
        			return JOB_FAILED;
        		}
        		break;
        	case "m4a":
        		$derivativeContainer->derivativeType = "m4a";
				$derivativeContainer->path = "derivative";
				$derivativeContainer->originalFilename = $pathparts['filename'] . "_" . "m4a.m4a";
				$derivativeContainer->setParent($this->fileHandler);
				$this->fileHandler->derivatives['m4a'] = $derivativeContainer;

				$video = new \PHPVideoToolkit\Audio($localPath, $this->videoToolkitConfig);
				$process = $video->getProcess();
				$process->addCommand("-vn");
        		$outputFormat = new \PHPVideoToolkit\AudioFormat_Aac('output', $this->videoToolkitConfig);
	       		$outputFormat->setFormat("mp4")->setAudioBitrate("256k")->setThreads($this->threadCount);

	       		$derivativeContainer->forcedMimeType = "audio/m4a";
				$output = $this->runTask($video, $derivativeContainer->getPathToLocalFile(), $outputFormat);
				
				if(!$output) {
					$this->logging->processingInfo("createDerivative", "m4a not created","", "", $this->job->getId());
					return JOB_FAILED;
				}
        		echo "Uploading";
				if($derivativeContainer->copyToRemoteStorage()) {
        			$derivativeContainer->removeLocalFile();
        			$derivativeContainer->ready = true;
        		}
        		else {
        			$this->logging->processingInfo("createDerivative", "uploading m4a failed","", "", $this->job->getId());
        			return JOB_FAILED;
        		}
        		break;
        }


        return JOB_SUCCESS;
	}

	public function extractWaveform($args) {
		if(!$this->checkLocalAndCopy()) {
			return JOB_POSTPONE;
		}

		$localPath = $this->fileHandler->sourceFile->getPathToLocalFile();
		$pathparts = pathinfo($localPath);

		$derivativeContainer = new fileContainerS3();
		$derivativeContainer->derivativeType = "thumbnail2x";
		$derivativeContainer->path = "thumbnail";
		$derivativeContainer->originalFilename = $pathparts['filename'] .  "_" . "thumbnail2x" . '.png';
		$derivativeContainer->setParent($this->fileHandler);
		$this->fileHandler->derivatives['thumbnail2x'] = $derivativeContainer;


		$ffmpeg = $this->config->item("ffmpegBinary");

		$rawData = $localPath . "_raw";

		$commandString = $ffmpeg . " -i " . $localPath . " -y -ac 1 -filter:a aresample=8000 -map 0:a -c:a pcm_s16le -f data " . $rawData . " 2>&1";

		exec($commandString);
		$pathToOutput = $derivativeContainer->getPathToLocalFile();


		$gnuPath = $this->config->item("gnuPlot");

		$scriptAppend = null;
		if(filesize($rawData) > 50*1024*1024) {
			$scriptAppend = "every 4 using 1:4";  // for long recordings, subsample
		}

		$gnuScript = "set terminal png size {width},{height};
set output '{output}';

unset key;
unset tics;
unset border;
set lmargin 1;
set rmargin 1;
set tmargin 1;
set bmargin 1;

plot '<cat' binary filetype=bin format='%int16' endian=little array=1:0 " . $scriptAppend . " with lines lt rgb 'black';";

		$targetScript = str_replace("{output}", $pathToOutput, $gnuScript);
		$targetScript = str_replace("{width}", 500, $targetScript);
		$targetScript = str_replace("{height}", 400, $targetScript);
		$outputScript = "cat " . $rawData . " | " . $gnuPath . " -e \"" . $targetScript . "\"";
		exec($outputScript);
		$derivativeContainer->copyToRemoteStorage();
        $derivativeContainer->removeLocalFile();
        $derivativeContainer->ready = true;



		$derivativeContainer = new fileContainerS3();
		$derivativeContainer->derivativeType = "thumbnail";
		$derivativeContainer->path = "thumbnail";
		$derivativeContainer->originalFilename = $pathparts['filename'] .  "_" . "thumbnail" . '.png';
		$derivativeContainer->setParent($this->fileHandler);
		$this->fileHandler->derivatives['thumbnail'] = $derivativeContainer;

		$pathToOutput = $derivativeContainer->getPathToLocalFile();

		$targetScript = str_replace("{output}", $pathToOutput, $gnuScript);
		$targetScript = str_replace("{width}", 250, $targetScript);
		$targetScript = str_replace("{height}", 200, $targetScript);
		$outputScript = "cat " . $rawData . " | " . $gnuPath . " -e \"" . $targetScript . "\"";
		exec($outputScript);
		$derivativeContainer->copyToRemoteStorage();
        $derivativeContainer->removeLocalFile();
        $derivativeContainer->ready = true;
        $this->fileHandler->triggerReindex();
		unlink($rawData);
 		return JOB_SUCCESS;
	}

	private function putAllFilesInFolderToKey($folder, $destKey, $mimeType=null) {
		$files =  array_diff(scandir( $folder),array('..', '.'));
		foreach($files as $file) {
			if(substr($file, 0,1) == ".") {
				continue;
			}
			$this->pheanstalk->touch($this->job);
        	$pathToFile = $folder . "/" . $file;
        	if(!$this->fileHandler->s3model->putObject($pathToFile, $destKey . "/" . $file)) {
        		$this->logging->processingInfo("putAllFilesInFolderToKey", "uploading file failed","", $pathToFile, $this->job->getId());
        	}
        	if($mimeType) {
        		$this->fileHandler->s3model->setContentType($destKey . "/" . $file, $mimeType);
        	}
        }
        return TRUE;

	}

	private function getResolutionForHeight($targetHeight) {

		if($this->fileHandler->sourceFile->metadata["rotation"] == 90 || $this->fileHandler->sourceFile->metadata["rotation"] == 270) {
			$height = $this->fileHandler->sourceFile->metadata["width"];
			$width = $this->fileHandler->sourceFile->metadata["height"];
		}
		else {
			$height = $this->fileHandler->sourceFile->metadata["height"];
			$width = $this->fileHandler->sourceFile->metadata["width"];
		}

		$scaling = $targetHeight / $height;
		$newWidth = round($width * $scaling);
		return $newWidth . "x" . $targetHeight;

	}

	private function runTask($videoHandler, $targetPath, $outputFormat) {
		$progressHandler = new \PHPVideoToolkit\ProgressHandlerNative(null, $this->videoToolkitConfig);

		try {
			$output = $videoHandler->saveNonBlocking($targetPath, $outputFormat, \PHPVideoToolkit\Video::OVERWRITE_EXISTING, $progressHandler);
		}
		catch(FfmpegProcessOutputException $e)
		{
			$this->logging->processingInfo("ffmpeg", "video",$e->getMessage(), $targetPath, $this->job->getId());
		}

		// $process = $videoHandler->getProcess();

		while($progressHandler->completed !== true)
        {
        	$result = $progressHandler->probe(true);
        	if($result["error"]) {
        		$this->logging->processingInfo("ffmpeg", "video",$result, $targetPath, $this->job->getId());
        		return FALSE;
        	}
        	echo $result['percentage'] . " ";
        	// echo $process->getExecutedCommand()."\n";
            sleep(5);
			$this->pheanstalk->touch($this->job);
        }

        return $output;

	}

	public function mungeAspect(&$process) {
		if($this->fileHandler->sourceFile->metadata["width"] == 720 && ($this->fileHandler->sourceFile->metadata["height"] == 480 || $this->fileHandler->sourceFile->metadata["height"] == 486)) {
			if(!isset($this->fileHandler->sourceFile->metadata["aspect"]) || $this->fileHandler->sourceFile->metadata["aspect"] == "3:2") {
				$process->addCommand("-aspect","4:3");
			}
		}
	}

	public function performTask($fileHandler,$job) {
		$this->job = $job; //cache the job so we can touch it if necessary
		$this->fileHandler = $fileHandler;
		$task = json_decode($job->getData(), true);
		if(method_exists($this, $task["task"])) {
			return call_user_func(array($this,$task["task"]), $task['config']);
		}
		else {
			return false;
		}

	}


}

/* End of file  */
/* Location: ./application/models/ */
