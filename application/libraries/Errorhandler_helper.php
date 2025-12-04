<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Errorhandler_helper {

	public function callError($error, $inline=false) {
		$CI =& get_instance();
		$e = new Exception;
		$CI->logging->logError($error, $e->getTraceAsString());
		$CI->useUnauthenticatedTemplate = true;
		
		// Sanitize error name - only allow alphanumeric and underscores
		$sanitizedError = preg_replace('/[^a-zA-Z0-9_]/', '', $error);
		
		// Use whitelist approach - only load view if sanitized name matches original
		// This prevents path traversal attacks
		if($sanitizedError === $error && file_exists("application/views/errors/". $sanitizedError . ".php")) {
			$view = "errors/" . $sanitizedError;
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

	public function callJsonError($error) {
		$CI =& get_instance();
		$e = new Exception;
		$CI->logging->logError($error, $e->getTraceAsString());

		header('HTTP/1.0 500 Internal Server Error');
		echo json_encode(["error"=>$error]);
		exit();

	}

}

/* End of file  */
/* Location: ./application/controllers/ */
