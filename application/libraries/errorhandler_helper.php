<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Errorhandler_helper {

	public function callError($error) {
		$CI =& get_instance();
		$CI->useUnauthenticatedTemplate = true;
		if(file_exists("application/views/errors/". $error . ".php")) {
			$CI->template->content->view("errors/" . $error);
		}
		else {
			$CI->template->content->view("errors/genericError");
		}
		$CI->template->publish();
		$CI->output->_display();
		exit();

	}

}

/* End of file  */
/* Location: ./application/controllers/ */
