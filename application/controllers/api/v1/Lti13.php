<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use Packback\Lti1p3\LtiOidcLogin;
use Packback\Lti1p3\LtiException;
use Packback\Lti1p3\Factories\MessageFactory;
use Packback\Lti1p3\DeepLinkResources\Resource as LtiDeepLinkResource;
use Packback\Lti1p3\DeepLinkResources\Iframe as LtiDeepLinkResourceIframe;
use Packback\Lti1p3\Messages\DeepLinkingRequest;

class lti13 extends Instance_Controller {

	public function __construct()
	{
    Firebase\JWT\JWT::$leeway = 20;
		parent::__construct();
    $this->load->helper('url');

	}


  public function login() {
    $this->load->library("LTI13Database");
    $this->load->library("LTI13Cache");
    $this->load->library("LTI13Cookie");

    $redirectUrl = LtiOidcLogin::new(new LTI13Database, new LTI13Cache, new LTI13Cookie)
      ->getRedirectUrl(instance_url("api/v1/lti13/launch"), $_REQUEST);

    header('Location: '.$redirectUrl, true, 302);
    exit;
}


  public function launch() {
    $this->load->library("LTI13Database");
    if(isset($_REQUEST['error']) && $_REQUEST['error'] == 'launch_no_longer_valid') {
      $exception = new \Exception($_REQUEST['error_description']);
      echo "fail";
      return;
  }

   try {
      $this->load->library("LTI13Cache");
      $this->load->library("LTI13Cookie");
      $database = new LTI13Database;
      $cache = new LTI13Cache;
      $cookie = new LTI13Cookie;
      $serviceConnector = new \Packback\Lti1p3\LtiServiceConnector(
        $cache,
        new \GuzzleHttp\Client([
          'timeout' => 30,
        ])
      );

      $messageFactory = new MessageFactory($database, $serviceConnector, $cache, $cookie);
      $launch = $messageFactory->create($_REQUEST);
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
      return;
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
  if (!($launch instanceof DeepLinkingRequest)) {
    echo "fail";
    return;
  }

  $launchData = $launch->getBody();

  $customData = $launchData['https://purl.imsglobal.org/spec/lti/claim/custom'];
  $userEmail = $launchData["email"];
  $courseId = $launch->contextClaim()->id();
  $user = $this->doctrine->em->getRepository("Entity\User")->findOneBy(['email' => $userEmail]);


  $returnURL = $launch->deepLinkSettingsClaim()->deepLinkReturnUrl();
  

  if($user) {
    $ltiCourses = $user->getLtiCourses();
    $connectedInstance = null;
    if($ltiCourses) {
      foreach($ltiCourses as $ltiCourse) {
        if($ltiCourse->getLmsCourse() == $courseId) {
          $connectedInstance = $ltiCourse;
          break;
        }
      }
    }
    if(!$connectedInstance) {
      
      $this->template->title = 'Set LTI Association';
      $this->template->set_template("chromelessTemplate");
      $this->template->content->view('lti/setLTIInstance', ["userId"=>$user->getId(), "courseId"=>$courseId, "returnURL"=>$returnURL, "ltiVersion"=>"1.3", "launchId"=>$launch->getLaunchId()]);
      $this->template->publish();

    }
    else {
      echo $this->load->view("lti/ltiViewer", ["instance"=>$connectedInstance->getInstance(), "returnURL"=>$returnURL, "ltiVersion"=>"1.3", "launchId"=>$launch->getLaunchId(), "userId"=>$user->getId()], true);
    }
  }

  }

  public function updateLTIinstance() {

    $user = $this->input->post("user");
    $courseId = $this->input->post("courseID");
    $returnURL = $this->input->post("returnURL");
    $instanceId = $this->input->post("apiInstance");
    $launchId = $this->input->post("launchId");

    $user = $this->doctrine->em->getRepository("Entity\User")->find($user);
    $instance = $this->doctrine->em->getRepository("Entity\Instance")->find($instanceId);
    
    $ltiCourse = new Entity\LTI13InstanceAssociation();
    $ltiCourse->setUser($user);
    $ltiCourse->setLmsCourse($courseId);
    $ltiCourse->setInstance($instance);
    $this->doctrine->em->persist($ltiCourse);
    $this->doctrine->em->flush();

    echo $this->load->view("lti/ltiViewer", ["instance"=>$instance, "returnURL"=>$returnURL, "ltiVersion"=>"1.3", "launchId"=>$launchId, "userId"=>$user->getId()], true);
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
                "privacy_level" => "public",
                "settings" => [
                    "text" => "Launch Elevator",
                    "icon_url" => site_url("/assets/images/elevatorIconTiny.png"),
                    "placements" => [
                            [
                                "text" => "Elevator",
                                "enabled" => true,
                                "placement" => "editor_button",
                                "message_type" => "LtiDeepLinkingRequest",
                                "target_link_uri" => instance_url("lti13/launch"),
                                "canvas_icon_class" => "icon-lti",
                                "selection_width" => 1200,
                                "selection_height"=>640
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
          $user = $this->input->post("userId");
          if($user) {
            $this->user_model->loadUser($user);
          }
          else {
            return;
          }
          
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
          return;
        }

        $this->load->library("LTI13Database");
        $this->load->library("LTI13Cache");
        $database = new LTI13Database;
        $cache = new LTI13Cache;

        $launchData = $cache->getLaunchData($launchId);
        if (!$launchData) {
          return;
        }

        $clientId = $launchData['aud'] ?? null;
        if (is_array($clientId)) {
          $clientId = $clientId[0] ?? null;
        }

        $issuer = $launchData['iss'] ?? null;
        if (!$issuer || !$clientId) {
          return;
        }

        $registration = $database->findRegistrationByIssuer($issuer, $clientId);
        if (!$registration) {
          return;
        }

        $serviceConnector = new \Packback\Lti1p3\LtiServiceConnector(
          $cache,
          new \GuzzleHttp\Client([
              'timeout' => 30,
          ])
      );
        $messageFactory = new MessageFactory($database, $serviceConnector, $cache, new LTI13Cookie);
        $launch = $messageFactory->createMessage($registration, ['body' => $launchData]);
        if (!($launch instanceof DeepLinkingRequest)) {
          return;
        }
      
        $deepLink = $launch->getDeepLink();
        $deepLinkResource = new LtiDeepLinkResource();
        $deepLinkResource->setType("link");
        $deepLinkResource->setUrl($embedLink);
        $deepLinkResource->setTitle("test");
        $deepLinkResource->setIframe(new LtiDeepLinkResourceIframe($embedLink, 640, 480));

        $jwt = $deepLink->getResponseJwt([$deepLinkResource]);
        $returnUrl = htmlspecialchars($deepLink->returnUrl(), ENT_QUOTES, 'UTF-8');
        $jwtValue = htmlspecialchars($jwt, ENT_QUOTES, 'UTF-8');

        echo '<form id="lti13DeepLinkResponse" action="'.$returnUrl.'" method="POST">';
        echo '<input type="hidden" name="JWT" value="'.$jwtValue.'" />';
        echo '<input type="submit" value="Return to LMS" />';
        echo '</form>';
        echo '<script>document.getElementById("lti13DeepLinkResponse").submit();</script>';
        return;
    }
}

/* End of file  */
/* Location: ./application/controllers/ */