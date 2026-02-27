<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

use OneLogin\Saml2\Auth as OneLogin_Saml2_Auth;
use OneLogin\Saml2\Error as OneLogin_Saml2_Error;
use OneLogin\Saml2\Utils;

class Shibboleth extends MY_Controller {


    public function localSPLogin($passive=false) {
        $auth = new OneLogin_Saml2_Auth($this->config->item('shib_local_settings'));
        $target=null;
        if($this->input->get("target")) {
            $target = urldecode($this->input->get("target"));
        }

        $auth->login($target,array(),false,$passive=="true"?true:false,false,false);
    }

    public function localSPLogout() {
        $auth = new OneLogin_Saml2_Auth($this->config->item('shib_local_settings'));
        $auth->logout();
    }

    public function localSPACS() {
        
        Utils::setProxyVars(true);
        $auth = new OneLogin_Saml2_Auth($this->config->item('shib_local_settings'));
        $auth->processResponse();
        $lastResponse = $auth->getLastResponseXML();
    
        if(str_contains($lastResponse, "NoPassive")) {
            if($_REQUEST['RelayState']) {
                redirect($_REQUEST['RelayState']);
            }
            else {
                redirect("/");
            }
            return;
        }
        $errors = $auth->getErrors();
        if (!empty($errors)) {
            return render_json(array('error' => $errors, 'last_error_reason' => $auth->getLastErrorReason()));
        }

        if (!$auth->isAuthenticated()) {
            return render_json(array('error' => 'Could not authenticate', 'last_error_reason' => $auth->getLastErrorReason()));
        }
        
        
        $shibAttributes = $auth->getAttributes();

        foreach ($this->config->item('shib_user') as $local => $server) {

            $map[$local] = $this->getServerVariable($server, $shibAttributes);
            if($server == 'NameID') {
                $map[$local] = $auth->getNameId();
            }
        }


        if (empty($map[$this->config->item('shib_authfield')])) {
            return show_error('User map not found', 403);
        }
        $userAuthField = $map[$this->config->item('shib_authfield')];

        $this->session->set_userdata('userAuthField', $userAuthField);
        $this->session->set_userdata('userAttributesCache', $map);
        if($_REQUEST['RelayState']) {
            redirect($_REQUEST['RelayState']);
        }
        else {
            instance_redirect("/");
        }

    }

    /**
     * Wrapper function for getting server variables.
     * Since Shibalike injects $_SERVER variables Laravel
     * doesn't pick them up. So depending on if we are
     * using the emulated IdP or a real one, we use the
     * appropriate function.
     */
    private function getServerVariable($variableName, $shibAttributes=null)
    {
        
        
        if($shibAttributes) {
            if(isset($shibAttributes[$variableName])) {
                if(is_array($shibAttributes[$variableName])) {
                    if(count($shibAttributes[$variableName])>1) {
                        return $shibAttributes[$variableName];
                    }
                    return $shibAttributes[$variableName][0];
                }
                return $shibAttributes[$variableName];
            }
            return null;
        }
    }


    public function getMetadata() {
        $auth = new OneLogin_Saml2_Auth($this->config->item('shib_local_settings'));
        $settings = $auth->getSettings();
        $metadata = $settings->getSPMetadata();
        $errors = $settings->validateMetadata($metadata);

        if (empty($errors)) {
            echo $metadata;
        } else {

            throw new InvalidArgumentException(
                'Invalid SP metadata: ' . implode(', ', $errors),
                OneLogin_Saml2_Error::METADATA_SP_INVALID
            );
        }
    }
}