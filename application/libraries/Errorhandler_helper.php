<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Errorhandler_helper {

	public function callError($error, $inline=false) {
		$CI =& get_instance();
		$e = new Exception;
		$CI->logging->logError($error, $e->getTraceAsString());
		$CI->useUnauthenticatedTemplate = true;
		if(file_exists("application/views/errors/". $error . ".php")) {
			$view = "errors/" . $error;
		}
		else {
			$view = "errors/genericError";
		}
		if($inline) {
			echo $CI->load->view($view, array(), true);
		}
		else {
			header('HTTP/1.0 500 Internal Server Error');
			$CI->template->content->view($view);
			$CI->template->publish();
			$CI->output->_display();
		}
		exit();

	}

}

/* End of file  */
/* Location: ./application/controllers/ */
