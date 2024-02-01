<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class lti extends Instance_Controller {

	public function __construct()
	{
		parent::__construct();

	}

	public function launchLTI()
	{
        $returnURL = $this->input->post('content_item_return_url');
        echo $this->load->view("lti/ltiViewer", ["instance"=>$this->instance, "returnURL"=>$returnURL, "ltiVersion"=>"1.1"], true);
	}


	public function ltiConfig() 
	{

        if($this->input->get("Instance_Name")) {
            $this->config->set_item("instance_name", $this->input->get("Instance_Name"));
            Instance_Controller::setInstance();
            $this->config->set_item("instance_absolute", Instance_Controller::getAbsolutePath());
        }

        echo $this->load->view("lti/ltiConfig", ["instance"=>$this->instance], true);
	}

    public function ltiPayload() {
        if(!$this->user_model->userLoaded) {
          return;
        }
        $objectId = $this->input->post("object");
        $fileHandler = $this->filehandler_router->getHandlerForObject($objectId);
        if(!$fileHandler) {
        }

        $fileHandler->loadByObjectId($objectId);
        if(!$fileHandler->parentObjectId) {
        }
        
        $apiKey = $this->user_model->getApiKeys()?$this->user_model->getApiKeys()->first():null;
        if(!$apiKey) {
            $apiKey = $this->user_model->generateKeys();
        }


        $timestamp = time();
        $signedString = sha1($timestamp . $fileHandler->parentObjectId . $apiKey->getApiSecret());

        $targetQuery = ["apiHandoff"=>$signedString, "authKey"=>$apiKey->getApiKey(), "timestamp"=>$timestamp, "targetObject"=>$fileHandler->parentObjectId];

        $excerptId = $this->input->post("excerptId");
        if($excerptId) {
          $embedLink = instance_url("/asset/viewExcerpt/" . $excerptId.  "/true?" . http_build_query($targetQuery));
        }
        else {
          $embedLink = instance_url("/asset/getEmbed/" . $objectId.  "/null/true?" . http_build_query($targetQuery));
        }
        
        $string = ('{
        "@context": "http://purl.imsglobal.org/ctx/lti/v1/ContentItem",
        "@graph": [
          {
            "@type": "ContentItem",
            "mediaType": "text/html",
            "placementAdvice": {
              "presentationDocumentTarget": "iframe",
              "displayWidth": "640",
              "displayHeight": "480"
            },
            "text": "",
            "title": "Embedded Asset",
            "url": "' . $embedLink  .'"
          }
        ]
        }');
        echo $string;
        return;
    }
}

/* End of file  */
/* Location: ./application/controllers/ */