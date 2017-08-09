<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Instance_Controller extends MY_Controller
{
    public $instance = null;
    public $instanceType;
    public $noRedirect = false;
    public $useUnauthenticatedTemplate;

    function __construct()
    {
        parent::__construct();

        if($this->config->item('site_open') === FALSE)
        {
            show_error('Elevator is Temporarily Unavailable.');
        }


        if(php_sapi_name() == 'cli') {
            $this->config->set_item("instance_name", "defaultinstance");
            return;
        }

        $this->useUnauthenticatedTemplate = false;

        $this->setInstance();

        $this->writeOutAssets();

        
        $this->template->relativePath = $this->getRelativePath();
        $this->config->set_item("instance_relative", $this->getRelativePath());
        $this->config->set_item("instance_absolute", $this->getAbsolutePath());

        
        if(!$this->instance && !$this->noRedirect) {
            if($this->config->item('missingSiteURL') != '') {
                redirect($this->config->item('missingSiteURL'));
            }
            else {
                redirect("/errorHandler/error/specifyInstance");
            }
        }



    }

    function setInstance() {
        
        if(!isset($this)) {
            $CI =& get_instance();
        }
        else {
            $CI = $this;
        }
        $instanceName = $CI->config->item("instance_name");
        if($instanceName != FALSE) {
            $CI->instance = $CI->doctrine->em->getRepository("Entity\Instance")->findOneBy(array('domain' => $instanceName));
            if(!$CI->instance && !$CI->noRedirect) {
                if($CI->config->item('missingSiteURL') != '') {
                    redirect($CI->config->item('missingSiteURL'));
                }
                else {
                    redirect("/errorHandler/error/specifyInstance");
                }
            }
            $CI->instanceType = "subdirectory";
            // HACK HACK HACK
            // Close the session if we're not going to be doing a login, prevent se$
            if(strtolower($CI->uri->segment(2)) !== "loginmanager") {
                session_write_close();
            }
            return;
        }

        if(isset($_SERVER['HTTP_HOST'])) {
            $subdomain_arr = $_SERVER['HTTP_HOST'];
            $instanceName = $subdomain_arr;
            $CI->instance = $CI->doctrine->em->getRepository("Entity\Instance")->findOneBy(array('domain' => $instanceName));
            if(!$CI->instance && !$CI->noRedirect) {
                if($CI->config->item('missingSiteURL') != '') {
                   redirect($CI->config->item('missingSiteURL'));
                }
                else {
                    redirect("/errorHandler/error/specifyInstance");
                }
            }
            $CI->instanceType = "subdomain";
            // HACK HACK HACK
            // Close the session if we're not going to be doing a login, prevent session locks in case of hung urls
            if(strtolower($CI->uri->segment(1)) !== "loginmanager") {
                session_write_close();
            }
            return;
        }

    }

    function writeOutAssets() {

        if($this->instance->getUseCustomHeader()) {
            if(!file_exists("assets/instanceAssets/" . $this->instance->getId() . ".html")) {
                file_put_contents("assets/instanceAssets/" . $this->instance->getId() . ".html", $this->instance->getCustomHeaderText());
            }
        }

        if($this->instance->getUseCustomCSS()) {
            if(!file_exists("assets/instanceAssets/" . $this->instance->getId() . ".css")) {
                file_put_contents("assets/instanceAssets/" . $this->instance->getId() . ".css", $this->instance->getCustomHeaderCSS());
            }
        }

        if($this->instance->getUseHeaderLogo()) {
            if(!file_exists("assets/instanceAssets/" . $this->instance->getId() . ".png")) {
                file_put_contents("assets/instanceAssets/" . $this->instance->getId() . ".png", $this->instance->getCustomHeaderImage());
            }
        }

    }

    function getAbsolutePath() {
        if($this->instanceType == "subdirectory") {
            return site_url($this->instance->getDomain() . "/") ."/";
        }
        else {
            return site_url();
        }
    }

    public function getRelativePath() {
        if($this->instanceType == "subdirectory") {
            return "/". $this->instance->getDomain() . "/";
        }
        else {
            return "/";
        }

    }




}
