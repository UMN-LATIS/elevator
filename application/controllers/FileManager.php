<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


/**
 * Handles serving any digital asset, via redirects to S3
 * This class could use some cleanup - it has some convenience methods that probably don't need to be around anymore.
 * It has a lot of anti-DRY code too
 */
class FileManager extends Instance_Controller {

	public function __construct()
	{
		parent::__construct();
		$this->load->model("asset_model");
	}

	public function index()
	{

	}

	function previewImage($objectId, $retina=false) {
		$this->asset_model->loadAssetById($objectId);
		try {
			$fileHandler = $this->asset_model->getPrimaryFilehandler();
		}
		catch (Exception $e) {
			$fileHandler = null;
		}
		if(!$fileHandler) {
			// no file handler for this asset
			$icon = getIconPath() . "_blank.png";
			redirect($icon, 307);
			return;
		}
		$this->redirectToPreviewImage($fileHandler, $retina, "thumbnail");
	}


	function tinyImage($objectId, $retina=false) {
		$this->asset_model->loadAssetById($objectId);

		try {
			$fileHandler = $this->asset_model->getPrimaryFilehandler();
		}
		catch (Exception $e) {
			$fileHandler = null;
		}


		if(!$fileHandler) {
			// no file handler for this asset
			$icon = getIconPath() . "_blank.png";
			redirect($icon, 307);
			return;
		}

		$this->redirectToPreviewImage($fileHandler, $retina, "tiny");
	}

	function previewImageByFileId($fileId, $retina=false) {

		if($retina === "false") {
			$retina = false;
		}

		//TODO : CHECK PERMS - should be able to pull the collection at this stage?

		$fileHandler = $this->filehandler_router->getHandlerForObject($fileId);
		if($fileHandler) {
			$fileHandler->loadByObjectId($fileId);
			$this->redirectToPreviewImage($fileHandler, $retina, "thumbnail");
		}
		else {
			show_404();
		}
		
	}

	function tinyImageByFileId($fileId, $retina=false) {
		if($retina === "false") {
			$retina = false;
		}

		//TODO : CHECK PERMS - should be able to pull the collection at this stage?

		$fileHandler = $this->filehandler_router->getHandlerForObject($fileId);
		$fileHandler->loadByObjectId($fileId);
		

		$this->redirectToPreviewImage($fileHandler, $retina, "tiny");
	}

	function getURLForPreviewImage($fileHandler, $retina, $size) {
		try {
			if($size == "thumbnail") {
				$targetURL = $fileHandler->getPreviewThumbnail($retina)->getURLForFile();
			}
			elseif($size == "tiny") {
				$targetURL = $fileHandler->getPreviewTiny($retina)->getURLForFile();
			}
		}
		catch (Exception $e) {
			return getIconPath($size) . $fileHandler->getIcon();
		}

		return $targetURL;
	}


	function redirectToPreviewImage($fileHandler, $retina, $size) {
		$resultURL = $this->getURLForPreviewImage($fileHandler, $retina, $size);
		redirect(matchScheme($resultURL), 307);
	}



	function previewImageAvailable($fileId=null, $retina=false) {
		$checkArray = [];

		if(!$fileId) {
			if($this->input->post("checkArray")) {
				$checkArray = json_decode($this->input->post("checkArray"));

			}
			else {
				return false;	
			}
			
		}
		else {
			$checkArray[] = $fileId;
		}


		$returnArray = [];
		foreach($checkArray as $fileId) {
			$fileHandler = $this->filehandler_router->getHandlerForObject($fileId);
			$status = "false";
			
			if($fileHandler) {
				$fileHandler->loadByObjectId($fileId);

				try {
					$fileContainer = $fileHandler->getPreviewThumbnail($retina);
					$targetURL = $fileContainer->getURLForFile();
					if(get_class($fileContainer) == "FileContainer") {

					// we got back a pointer to a local file
						$status = "icon";
					}
					else {
						$status = "true";
					}

				}
				catch (Exception $e) {
					if($fileHandler->sourceFile != null) {
						$status = "icon";
					}
					else {
						$status = "false";
					}

				}


			}
			

			$returnArray[] = ["status"=>$status, "fileId"=>$fileId];
		}

		echo json_encode($returnArray);
		
	}

	function extractedData($fileId) {
		$fileHandler = $this->filehandler_router->getHandlerForObject($fileId);
		if(!$fileHandler) {
			echo "false";
			return;
		}
		if($this->user_model->getAccessLevel("instance",$this->instance)<PERM_ADDASSETS) {
			$this->errorhandler_helper->callError("noPermission");
		}

		$fileHandler->loadByObjectId($fileId);
		echo json_encode($fileHandler->globalMetadata);

	}

	function removeData($fileId) {
		$fileHandler = $this->filehandler_router->getHandlerForObject($fileId);
		if(!$fileHandler) {
			echo "false";
			return;
		}
		if($this->user_model->getAccessLevel("instance",$this->instance)<PERM_ADDASSETS) {
			$this->errorhandler_helper->callError("noPermission");
		}
		$fileHandler->loadByObjectId($fileId);
		$fileHandler->globalMetadata = array();
		$fileHandler->save();

	}


	function bestDerivativeByObjectId($objectId, $stillOnly=false) {


		$this->asset_model->loadAssetById($objectId);
		$accessLevel = $this->user_model->getAccessLevel("asset", $this->asset_model);


		try {
			$fileHandler = $this->asset_model->getPrimaryFilehandler();
		}
		catch (Exception $e) {
			$fileHandler = null;
		}

		if(!$fileHandler) {
			$this->logging->logError("bestDerivativeByObjectId", "could not find a file handler");
			instance_redirect("errorHandler/error/fileNotFound");
			return;
		}

		try {
			$targetContainer = $fileHandler->highestQualityDerivativeForAccessLevel($accessLevel, $stillOnly);
			if($stillOnly && $targetContainer->derivativeType == "imageSequence") {
				$targetURL = $targetContainer->getProtectedURLForFile("/2");
			}
			else {
				$targetURL = $targetContainer->getProtectedURLForFile();
			}
		}
		catch (Exception $e) {
			//TODO: go to error page
			$this->logging->logError("bestDerivativeByObjectId", "Tried to find best derivative for an object but couldn't", $objectId);
			redirect(getIconPath() . $fileHandler->getIcon(), 307);
		}

		redirect(matchScheme($targetURL), 307);


	}

	function bestDerivativeByFileId($fileId=null)	 {

		$fileHandler = $this->filehandler_router->getHandlerForObject($fileId);
		$fileHandler->loadByObjectId($fileId);

		if(!($fileHandler)) {
			instance_redirect("errorHandler/error/unknownFile", 307);
			return;
		}

		if(!$this->asset_model->loadAssetById($fileHandler->parentObjectId)) {
			$this->logging->logError("bestDerivativeByFileId", "could not load asset for fileHandler" . $fileHandler->getObjectId());
			instance_redirect("errorHandler/error/unknownFile", 307);
			return;
		}

		$accessLevel = $this->user_model->getAccessLevel("asset", $this->asset_model);

		try {
			$targetURL = $fileHandler->highestQualityDerivativeForAccessLevel($accessLevel)->getProtectedURLForFile();
		}
		catch (Exception $e) {
			//TODO: go to error page
			instance_redirect("errorHandler/error/noMediaFound", 307);
		}
		redirect(matchScheme($targetURL), 307);

	}

	function deleteFileObject() {
		$fileId = $this->input->post("fileObjectId");

		if(!$fileHandler = $this->filehandler_router->getHandlerForObject($fileId)) {
			$this->logging->logError("deleteFileObject", "error loading file handler for delete" . $fileId);
			instance_redirect("errorHandler/error/unknownFile");
			return;
		}
		$fileHandler->loadByObjectId($fileId);
		if(!$this->asset_model->loadAssetById($fileHandler->parentObjectId)) {
			$this->logging->logError("deleteFileObject", "could not load asset from fileHandler" . $fileId);
			instance_redirect("errorHandler/error/unknownFile");
			return;
		}

		$accessLevel = $this->user_model->getAccessLevel("asset", $this->asset_model);

		if($accessLevel <PERM_ADDASSETS) {
			instance_redirect("errorHandler/error/invalidPermissions");
			return;
		}

		if($fileHandler->deleteFile()) {
			echo "success";
		}
		else {
			echo "fail";
		}

	}

	function getDerivativeById($fileId=null, $derivativeType=null) {
		if(!$fileId) {
			instance_redirect("errorHandler/error/unknownFile");
			return;
		}
		$fileHandler = $this->filehandler_router->getHandlerForObject($fileId);
		if(!$fileHandler || !$fileHandler->loadByObjectId($fileId)) {
			instance_redirect("errorHandler/error/unknownFile");
			return;
		}

		if(!$this->asset_model->loadAssetById($fileHandler->parentObjectId)) {
			$this->logging->logError("deleteFileObject", "could not load asset from fileHandler" . $fileId);
			instance_redirect("errorHandler/error/unknownFile");
			return;
		}

		$accessLevel = $this->user_model->getAccessLevel("asset", $this->asset_model, true);

		try {
			$allDerivatives = $fileHandler->allDerivativesForAccessLevel($accessLevel);
		}
		catch (Exception $e) {
			if($this->user_model->userLoaded || !$this->instance->getUseCentralAuth()) {
				$this->errorhandler_helper->callError("noPermission");
			}
			else {
				redirect(instance_url("loginManager/remoteLogin/?redirect=".current_url()));
			}

		}
		if(!$derivativeType) {
			$derivativeType = "thumbnail";
		}

		if(!array_key_exists($derivativeType, $allDerivatives)) {
			instance_redirect("errorHandler/error/derivativeNotAvailable");
			return;
		}

		if($fileHandler->derivatives[$derivativeType]->isArchived(true)) {

			//TODO: better alert

			$fileHandler->derivatives[$derivativeType]->restoreFromArchive();
			return;
		}


		try {
			// override the filename of the derivative so they get back original name _ derivative type
			$targetName = pathinfo($fileHandler->sourceFile->originalFilename, PATHINFO_FILENAME);
			$targetName = $targetName . "_" . $derivativeType . "." . pathinfo( $fileHandler->derivatives[$derivativeType]->originalFilename, PATHINFO_EXTENSION);
			$fileHandler->derivatives[$derivativeType]->originalFilename = $targetName;
			$targetURL = $fileHandler->derivatives[$derivativeType]->getProtectedURLForFile();
		}
		catch (Exception $e) {
			if($this->user_model->userLoaded || !$this->instance->getUseCentralAuth()) {
				$this->errorhandler_helper->callError("noPermission");
			}
			else {
				redirect(instance_url("loginManager/remoteLogin/?redirect=".current_url()));
			}
		}

		redirect(matchScheme($targetURL), 307);

	}


	function getOriginal($fileId) {

		$fileHandler = $this->filehandler_router->getHandlerForObject($fileId);
		$fileHandler->loadByObjectId($fileId);
		if(!($fileHandler)) {
			instance_redirect("errorHandler/error/unknownFile");
			return;
		}

		if(!$this->asset_model->loadAssetById($fileHandler->parentObjectId)) {
			$this->logging->logError("getOriginal", "could not load asset from fileHandler" . $fileId);
			instance_redirect("errorHandler/error/unknownFile");
			return;
		}

		$accessLevel = $this->user_model->getAccessLevel("asset", $this->asset_model);

		/**
		 * if this file type specifies that it doesn't have derivatives, users need lower
		 * permission levels to get to the original.
		 */
		if($fileHandler->noDerivatives()) {
			$requiredAccessLevel = $fileHandler->getPermission();
		}
		else {
			$requiredAccessLevel = PERM_ORIGINALS;
		}

		if($accessLevel < $requiredAccessLevel) {
			instance_redirect("/errorHandler/error/noPermission");
		}

		if($fileHandler->sourceFile->isArchived(true)) {


			$pheanstalk = new Pheanstalk\Pheanstalk($this->config->item("beanstalkd"));
			$pathToFile = instance_url("/fileManager/getOriginal/".$fileId);
			$newTask = json_encode(["objectId"=>$fileHandler->getObjectId(), "userContact"=>$this->user_model->getEmail(), "instance"=>$this->instance->getId(), "pathToFile"=>$pathToFile]);
			$jobId= $pheanstalk->useTube('restoreTube')->put($newTask, NULL, 1);

			$this->template->content->view('restoringFile');
			$this->template->publish();
			$fileHandler->sourceFile->restoreFromArchive();
			return;
		}

		try {
			$targetURL = $fileHandler->sourceFile->getProtectedURLForFile();
		}
		catch (Exception $e) {
			instance_redirect("/errorHandler/error/originalNotAvailable");
		}

		redirect(matchScheme($targetURL), 307);

	}

	function getMetadataForObject($fileId) {

		$fileHandler = $this->filehandler_router->getHandlerForObject($fileId);
		$fileHandler->loadByObjectId($fileId);
		if(!($fileHandler)) {
			instance_redirect("errorHandler/error/unknownFile");
			return;
		}

		if(!$this->asset_model->loadAssetById($fileHandler->parentObjectId)) {
			$this->logging->logError("getOriginal", "could not load asset from fileHandler" . $fileId);
			instance_redirect("errorHandler/error/unknownFile");
			return;
		}

		$accessLevel = $this->user_model->getAccessLevel("asset", $this->asset_model);

		$requiredAccessLevel = PERM_SEARCH;

		if($accessLevel < $requiredAccessLevel) {
			instance_redirect("/errorHandler/error/noPermission");
		}

		$metadata = $fileHandler->sourceFile->metadata;
		$metadata['sourcefile'] = $fileHandler->sourceFile->originalFilename;
		$metadata["handlerType"] = get_class($fileHandler);
		return render_json($metadata);

	}

	function getSidecarViewForObject() {
		$fileId = $this->input->post("fileId");
		$rootFormField = $this->input->post("rootFormField");

		if(!$fileId || !$rootFormField) {
			return "";
		}

		$fileHandler = $this->filehandler_router->getHandlerForObject($fileId);
		if(!($fileHandler)) {
			instance_redirect("errorHandler/error/unknownFile");
			return;
		}
		$fileHandler->loadByObjectId($fileId);

		$sidecar = $fileHandler->getSidecarView(array(), $rootFormField);
		echo $sidecar;

	}

	function getSignedChildrenForObject() {

		$fileId = $this->input->post("fileId");
		if($this->input->post("path")) {
			$subPath = $this->input->post("path");
		}

		if($this->input->post("paths")) {
			$subPaths = $this->input->post("paths");
		}
		
		$derivative = $this->input->post("derivative");

	
		$fileHandler = $this->filehandler_router->getHandlerForObject($fileId);
		$fileHandler->loadByObjectId($fileId);


		if(!($fileHandler)) {
			instance_redirect("errorHandler/error/unknownFile");
			return;
		}


		if(!$this->asset_model->loadAssetById($fileHandler->parentObjectId)) {
			$this->logging->logError("deleteFileObject", "could not load asset from fileHandler" . $fileId);
			instance_redirect("errorHandler/error/unknownFile");
			return;
		}

		$accessLevel = $this->user_model->getAccessLevel("asset", $this->asset_model);

		try {
			$allDerivatives = $fileHandler->allDerivativesForAccessLevel($accessLevel);
		}
		catch (Exception $e) {

			if($this->user_model->userLoaded || !$this->instance->getUseCentralAuth()) {
				$this->errorhandler_helper->callError("noPermission");
			}
			else {
				redirect(instance_url("loginManager/remoteLogin/?redirect=".current_url()));
			}

		}

		if(!array_key_exists($derivative, $allDerivatives)) {
			instance_redirect("errorHandler/error/derivativeNotAvailable");
			return;
		}

		if(isset($subPath)) {
			$urls = $fileHandler->getSignedURLs($derivative, true, $subPath);	
		}
		elseif(isset($subPaths)) {
			$urls = array();
			foreach($subPaths as $subPath) {
				$urls = array_merge($fileHandler->getSignedURLs($derivative, true, $subPath), $urls);	
			}
		}


		echo json_encode($urls);

	}


	function getSidecar($fileId, $sidecarLabel="captions") {

		$fileHandler = $this->filehandler_router->getHandlerForObject($fileId);
		$fileHandler->loadByObjectId($fileId);
		if(!($fileHandler)) {
			instance_redirect("errorHandler/error/unknownFile");
			return;
		}

		if(!$this->asset_model->loadAssetById($fileHandler->parentObjectId)) {
			$this->logging->logError("getOriginal", "could not load asset from fileHandler" . $fileId);
			instance_redirect("errorHandler/error/unknownFile");
			return;
		}

		$accessLevel = $this->user_model->getAccessLevel("asset", $this->asset_model);

		$requiredAccessLevel = PERM_SEARCH;

		if($accessLevel < $requiredAccessLevel) {
			instance_redirect("/errorHandler/error/noPermission");
		}

		// so, now we need to find the actual widget associated with this file object.  This is one of those annoying
		// brute force searches that would be less complicated in a better designed db

		$uploadHandlers = $this->asset_model->getAllWithinAsset("Upload", null, 0);

		foreach($uploadHandlers as $uploadHandler) {

			foreach($uploadHandler->fieldContentsArray as $uploadHandlerContent) {

				if($uploadHandlerContent->fileId == $fileId) {

					if(isset($uploadHandlerContent->sidecars) && array_key_exists($sidecarLabel, $uploadHandlerContent->sidecars)) {
						echo $uploadHandlerContent->sidecars[$sidecarLabel];
						return;
					}
				}
			}
		}
	}


	function getStream($fileId, $streamType) {
		// do some explicitly cache control because Chrome seems to be aggressive about caching m3u8?
		header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
		header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
		$fileHandler = $this->filehandler_router->getHandlerForObject($fileId);
		$fileHandler->loadByObjectId($fileId);
		if(!($fileHandler)) {
			instance_redirect("errorHandler/error/unknownFile");
			return;
		}

		if(!$this->asset_model->loadAssetById($fileHandler->parentObjectId)) {
			$this->logging->logError("getOriginal", "could not load asset from fileHandler" . $fileId);
			instance_redirect("errorHandler/error/unknownFile");
			return;
		}

		$accessLevel = $this->user_model->getAccessLevel("asset", $this->asset_model, true);

		$requiredAccessLevel = PERM_VIEWDERIVATIVES;

		if($accessLevel < $requiredAccessLevel) {
			instance_redirect("/errorHandler/error/noPermission");
		}

		if(!isset($fileHandler->derivatives['stream'])) {
			$this->errorhandler_helper->callError("noPermission");
			return;
		}


		$hls = $fileHandler->derivatives['stream'];
		if(isset($hls->metadata[$streamType])) {
			$streamData = $hls->metadata[$streamType];
			$streamDataByLines = explode("\n", $streamData);
			if($streamType == 'base') {
				for($i=0; $i<count($streamDataByLines); $i++) {
					if(substr($streamDataByLines[$i], 0, 6) == "stream") { // if this is a line that starts with a 'stream'
						$streamDataByLines[$i] = instance_url("/fileManager/getStream/" . $fileId . "/" . str_replace(".m3u8", "", $streamDataByLines[$i]));
					}
				}
			}
			if($streamType == "stream-1200k" || $streamType == "stream-2000k") {
				$streamURL = $hls->getProtectedURLForFile("/" . $streamType . ".ts");
				for($i=0; $i<count($streamDataByLines); $i++) {
					if(substr($streamDataByLines[$i], 0, 6) == "stream") { 
						$streamDataByLines[$i] = $streamURL;
					}
				}
			}
			$rejoined = join("\n", $streamDataByLines);
			header('Content-type: application/x-mpegURL');
			echo $rejoined;
		}
	}

}

/* End of file  */
/* Location: ./application/controllers/ */
