<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Shibboleth extends CI_Controller {


    /**
     * Create the session, send the user away to the IDP
     * for authentication.
     */
    public function login()
    {

        $targetURL = $this->config->item('shibbolethLogin');
        return Redirect::to(url('/') . $this->getLoginURL()
            . '?target=' . action('\\' . __CLASS__ . '@idpAuthenticate'));
    }


}