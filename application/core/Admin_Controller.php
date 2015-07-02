<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Admin_Controller extends Instance_Controller
{
    function __construct()
    {
        parent::__construct();

        // TODO: check for admin status
		$this->load->model("user_model");

    }
}
