<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use Packback\Lti1p3\LtiOidcLogin;
use Packback\Lti1p3\LtiMessageLaunch;
use Packback\Lti1p3\ImsStorage\ImsCache;
use Packback\Lti1p3\ImsStorage\ImsCookie;
use Packback\Lti1p3\LtiException;
use Packback\Lti1p3\LtiDeepLinkResource;
use Packback\Lti1p3\LtiDeepLinkResourceIframe;

class lti13 extends Instance_Controller {

	public function __construct()
	{
    Firebase\JWT\JWT::$leeway = 20;
		parent::__construct();
    $this->load->helper('url');

	}


  public function login() {
    $this->load->library("LTI13Database");
    echo "Heh";
    return LtiOidcLogin::new(new LTI13Database, new ImsCache, new ImsCookie)
        ->doOidcLoginRedirect(instance_url("api/v1/lti13/launch"))
        ->doRedirect();
}


  public function launch() {
    if(isset($_REQUEST['error']) && $_REQUEST['error'] == 'launch_no_longer_valid') {
      $exception = new \Exception($_REQUEST['error_description']);
      echo "fail";
      // if (app()->bound('sentry')) {
      //     app('sentry')->captureException($exception);
      // }
      // return view("errors.500", ["exception"=>$exception]);
  }

   try {
      $launch = LtiMessageLaunch::new(
          new LTI13Database, 
          new ImsCache, 
          new ImsCookie, 
          new \Packback\Lti1p3\LtiServiceConnector(
              new ImsCache, 
              new \GuzzleHttp\Client([
                  'timeout' => 30,
              ])
          )
      )
      ->validate();
  }
  catch (LtiException $e) {

      // canvas needs to update for new window to work https://github.com/instructure/canvas-lms/commit/811a1194cabccc1b3fb22aa3d13d64cde547116d#diff-79b6cd1bab1e82354966238b3d72cfa8fffb6357a61d2454bf4aba1c85b96a5e
            echo '<script>
            window.parent.postMessage(
  {
    messageType: "requestFullWindowLaunch",
    subject: "requestFullWindowLaunch",
    data: {
      url: "' . instance_url("api/v1/lti13/launch") . '",
      launchType: "new_window",
      "placement": "editor_button",
    }
  },
  "*"
)           
      </script>';
//       echo '<script>
//       window.parent.postMessage(
// {
// messageType: "requestFullWindowLaunch",
// data: "' . instance_url("api/v1/lti13/launch") . '"
// },
// "*"
// )           
//       </script>
//       <h1>Canvas Launch Error</h1>
//       <p>' . $e->getMessage() . "</p>";
      return;
  }
  $launchData = $launch->getLaunchData();
  if($launchData["https://purl.imsglobal.org/spec/lti/claim/message_type"] != "LtiDeepLinkingRequest")  {
    echo "fail";
    return;
  }
  $deepLinkSettings = $launchData["https://purl.imsglobal.org/spec/lti-dl/claim/deep_linking_settings"];
  $returnURL = $deepLinkSettings["deep_link_return_url"];;
  
      echo $this->load->view("lti/ltiViewer", ["instance"=>$this->instance, "returnURL"=>$returnURL, "ltiVersion"=>"1.3", "launchId"=>$launch->getLaunchId()], true);


  }

  public function config() {
    $this->load->helper('url');
    // key generated with https://mkjwk.org
    $configArray = [
        "title" => "Elevator",
        "description" => "Elevator Digital Asset Management System",
        "oidc_initiation_url" => instance_url("api/v1/lti13/login"),
        "target_link_uri" => instance_url("api/v1/lti13/launch"),
        "extensions" => [
            [
                "domain" => $_SERVER['HTTP_HOST'],
                "tool_id" => "elevator",
                "platform" => "canvas.instructure.com",
                "settings" => [
                    "privacy_level" => "public",
                    "text" => "Launch Elevator",
                    "icon_url" => site_url("/assets/images/elevatorIconTiny.png"),
                    "placements" => [
                            [
                                "text" => "Elevator",
                                "enabled" => true,
                                "placement" => "editor_button",
                                "message_type" => "LtiDeepLinkingRequest",
                                "target_link_uri" => instance_url("lti13/launch"),
                                "canvas_icon_class" => "icon-lti"
                            ]
                        ]
                    ]
                ]
        ],
        "public_jwk" => [
            "kty"=> "RSA",
            "e"=> "AQAB",
            "use"=> "sig",
            "kid"=> "sig-1698080546",
            "alg"=> "RS256",
            "n"=> "vwUtR5esRCNqoKf4np1m0kQpyp5-zwfnUImPsn8-wq-RgOMo4ffj-cX0z2tvnlZ_KXWKZ9ER-1V-Ez9Ukg2mw_RRjQXk1qc5DUBzLNhzOoiUUZn_AJ-_Bs0unpGAKfnWNNpZs0-so056blAIjMZgCU_zlTN6zbSp9QXoHnPgXw6pL2pfTF5tZgpK4MCZTBKQdc4PbpTxFZmltN8jUTPkMyL6uTluFydv13IFMYTTx58mHdtPkg8ZaFKMnsXnHFKpsn8afX2pvegur1iQ2lYUYqqWNWF2KpjBwPnVEqCbZutoJn4fQID962AtOtOM__ZKmxat47aSrME6x0J2-m8Zww"
        ],
        "custom_fields" => [  
            "canvas_integration_id" =>'$Canvas.user.sisSourceId',
            "user_username" => '$User.username',
            "canvas_user_id" => '$Canvas.user.id',
            "canvas_course_id" => '$Canvas.course.id'
        ]
    ];

    return render_json($configArray);
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


        $launchId = $this->input->post("launchId");
        if(!$launchId) {
          echo "HeH";
          return;
        }

        $launch = LtiMessageLaunch::fromCache($launchId, new LTI13Database, new ImsCache,  new \Packback\Lti1p3\LtiServiceConnector(
          new ImsCache, 
          new \GuzzleHttp\Client([
              'timeout' => 30,
          ])
      ));
        $deepLink = $launch->getDeepLink();
        $deepLinkResource = new LtiDeepLinkResource();
        $deepLinkResource->setType("link");
        $deepLinkResource->setUrl($embedLink);
        $deepLinkResource->setTitle("test");
        $deepLinkResource->setIframe(new LtiDeepLinkResourceIframe(640,480, $embedLink));
        // $string = ('{
        // "@context": "http://purl.imsglobal.org/ctx/lti/v1/ContentItem",
        // "@graph": [
        //   {
        //     "@type": "ContentItem",
        //     "mediaType": "text/html",
        //     "placementAdvice": {
        //       "presentationDocumentTarget": "iframe",
        //       "displayWidth": "640",
        //       "displayHeight": "480"
        //     },
        //     "text": "",
        //     "title": "Embedded Asset",
        //     "url": "' . $embedLink  .'"
        //   }
        // ]
        // }');
// var_dump($deepLinkResource->toArray());
        echo $deepLink->outputResponseForm([$deepLinkResource]);
        return;
    }
}

/* End of file  */
/* Location: ./application/controllers/ */