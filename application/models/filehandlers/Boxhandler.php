<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


//require_once("fileHandlerBase.php");
class BoxHandler extends FileHandlerBase {

	protected $supportedTypes = array();
	protected $noDerivatives = true;

	// public $icon = "doc.png";

	public $taskArray = [0=>["taskType"=>"extractMetadataAndRequestConversion", "config"=>array()],
						 1=>["taskType"=>"createThumbnails", "config"=>[["width"=>250, "height"=>250, "type"=>"thumbnail", "path"=>"thumbnail"],
						  												["width"=>500, "height"=>500, "type"=>"thumbnail2x", "path"=>"thumbnail"],
						  												["width"=>75, "height"=>150, "type"=>"tiny", "path"=>"thumbnail"],
						  												["width"=>150, "height"=>75, "type"=>"tiny2x", "path"=>"thumbnail"]
						  												]
						  												],
						 2=>["taskType"=>"extractText", "config"=>array("docId"=>null)],
						 3=>["taskType"=>"updateParent", "config"=>array()]];



	public function __construct()
	{
		parent::__construct();
		$this->load->helper("media");
		//Do your magic here
	}


	public function allDerivativesForAccessLevel($accessLevel) {
			$derivative = array();

		if($accessLevel>=$this->getPermission()) {
			$derivative[] = "boxView";
			$derivative[] = "pdf";
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


}

/* End of file boxHandler.php */
/* Location: ./application/models/boxHandler.php */