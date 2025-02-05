<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Filehandler_router {

	private $fileHandlerArray = array();

	public function __construct()
	{

		$CI =& get_instance();
		
		$CI->load->helper("directory");
		$CI->load->model("filehandlers/filehandlerbase");
		
		$fileHandlers = directory_map(APPPATH."models/filehandlers", TRUE);
		sort($fileHandlers);
		foreach($fileHandlers as $fileHandler) {
    		if( ! is_array($fileHandler)) {
        		$class = str_replace(".php", "", $fileHandler);
        		$className = "filehandlers/" . $class;
        		$CI->load->model($className);
        		$this->fileHandlerArray[] = new $class;
			}
		}
	}

	public function getHandlerForType($fileType) {
		foreach($this->fileHandlerArray as $fileHandler) {
			if($fileHandler->supportsType($fileType)) {
				$targetClass = get_class($fileHandler);
				return new $targetClass;
			}
		}
		return new FileHandlerBase;
	}


	/**
	 * make sure filehandlerarray is read only.
	 */
	public function getAllHandlers() {
		return $this->fileHandlerArray;
	}

	public function getHandlerForObject($objectId) {
		$CI =& get_instance();
		try {
			$asset = $CI->doctrine->em->getRepository('Entity\FileHandler')->findOneBy(["fileObjectId"=>$objectId, "deleted"=>false]);
		}
		catch (Exception $e) {
			return false;
		}
		if(!$asset){
			return false;
		}
		if($asset->getHandler() !== null && class_exists($asset->getHandler())) {
			$handlerType = $asset->getHandler();
			return new $handlerType;
		}
		else {
			return $this->getHandlerForType($asset->getFileType());
		}
	}

	public function getHandledObject($objectId) {
		$CI =& get_instance();
		try {
			$asset = $CI->doctrine->em->getRepository('Entity\FileHandler')->findOneBy(["fileObjectId"=>$objectId, "deleted"=>false]);
		}
		catch (Exception $e) {
			return false;
		}
		if(!$asset){
			return false;
		}
		if($asset->getHandler() !== null && class_exists($asset->getHandler())) {
			$handlerType = $asset->getHandler();
		}
		else {
			$handlerType = $this->getHandlerForType($asset->getFileType());
		}

		$handler = new $handlerType;
		$handler->loadFromObject($asset);
		// if($asset) {
		// 	$CI->doctrine->em->detach($asset);
		// }
		return $handler;


	}


}

/* End of file modelName.php */
/* Location: ./application/models/modelName.php */
