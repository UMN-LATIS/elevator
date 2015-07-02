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

        // if($this->config->item('site_open') === FALSE)
        // {
        //     show_error('Sorry the site is shut for now.');
        // }
        //
        //

        if(php_sapi_name() == 'cli') {


            $this->config->set_item("instance_name", "defaultinstance");
            return;
        }

        $this->useUnauthenticatedTemplate = false;

        $instanceName = $this->config->item("instance_name");

        if($instanceName != FALSE) {
            $this->instance = $this->doctrine->em->getRepository("Entity\Instance")->findOneBy(array('domain' => $instanceName));
            if(!$this->instance && !$this->noRedirect) {
                redirect("/errorHandler/error/specifyInstance");
            }

            // if(!$this->noAuth && $this->user_model->getAccessLevel("instance", $this->instance)==PERM_NOPERM) {
            //     redirect("/errorHandler/error/noAccess");
            // }

            $this->instanceType = "subdirectory";
            $this->template->relativePath = $this->getRelativePath();
            $this->config->set_item("instance_relative", $this->getRelativePath());
            $this->config->set_item("instance_absolute", $this->getAbsolutePath());
            return;
        }



        if(isset($_SERVER['HTTP_HOST'])) {
            $subdomain_arr = $_SERVER['HTTP_HOST'];
            $instanceName = $subdomain_arr;
            $this->instance = $this->doctrine->em->getRepository("Entity\Instance")->findOneBy(array('domain' => $instanceName));
            if(!$this->instance && !$this->noRedirect) {
                redirect("/errorHandler/error/specifyInstance");
            }


            // TODO: what should we do here?
            // if(!$this->noAuth && $this->user_model->getAccessLevel("instance", $this->instance)==PERM_NOPERM) {
            //     redirect("/errorHandler/error/noAccess");
            // }

            $this->instanceType = "subdomain";
            $this->template->relativePath = $this->getRelativePath();
            $this->config->set_item("instance_relative", $this->getRelativePath());
            $this->config->set_item("instance_absolute", $this->getAbsolutePath());

        }

        if(!$this->instance && !$this->noRedirect) {
            redirect("/errorHandler/error/specifyInstance");
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
