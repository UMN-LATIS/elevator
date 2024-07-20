<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class TranscoderCommandsAWS {

	private $pheanstalk;
    private $transcoderModel;
	private $fileHandler;

	public function __construct($pheanstalk = null, $videoTTR = null, $fileHandler = null) {

		if($pheanstalk) {
			$this->pheanstalk = $pheanstalk;
			$this->videoTTR = $videoTTR;
		}
		$CI =& get_instance();
        
		if($fileHandler) {
			$CI->load->model("transcoder_model");
        	$this->transcoderModel = new Transcoder_Model();
        	$this->transcoderModel->setFileHandler($fileHandler);
		}
	}


	public function checkCompletion($jobId) {

	
	}


	public function extractMetadata($objectId)
	{
		return $this->transcoderModel->extractMetadata([]);
	}


	public function extractWaveform($objectId) {
        return $this->transcoderModel->extractWaveform([]);
	}

	public function createVTT($objectId) {
		return $this->transcoderModel->createVTT([]);
	}


	public function createDerivative($objectId, $type) {
        return $this->transcoderModel->createDerivative(["type"=>$type]);
	}


	public function createThumbnail($objectId) {
		return $this->transcoderModel->createThumbnail([]);
	}

	public function createTiny($objectId) {
		return $this->transcoderModel->createTiny([]);
	}


	public function createSequence($objectId) {
		return $this->transcoderModel->createSequence([]);
	}

	public function cleanup($objectId) {
		return $this->transcoderModel->cleanup([]);
	}

}

/* End of file  */
