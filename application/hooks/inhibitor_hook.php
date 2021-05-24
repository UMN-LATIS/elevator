<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * OpenReceptor Inhibitor Hook Class
 *
 * This class contains functions that handles parse erros, fatal errors and exceptions
 *
 * @author		Dimitris Krestos
 * @license		Apache License, Version 2.0 (http://www.opensource.org/licenses/apache2.0.php)
 * @link		http://vdw.staytuned.gr/
 * @package		OpenReceptor CMS
 * @version		Version 1.0
 */

/**
 * Forked for DCL3
 * @todo  deal with backtraces
 * @todo  create error handler
 */

class InhibitorHook {

	/**
	 * Error Catcher
	 *
	 * Sets the user functions for parse errors, fatal errors and exceptions
	 *
	 * @access	public
	 * @return	void
	 */
	public function fatal_error_catcher()
	{


		register_shutdown_function(array($this, 'handle_fatal_errors'));

	}
	public function runtime_error_catcher() {
		// set_error_handler(array($this, 'handle_errors'));
		set_exception_handler(array($this, 'handle_exceptions'));
	}

	/**
	 * Fatal Error Handler
	 *
	 * Accesses output buffers on shutdown, formats the error message and redirects
	 *
	 * @access	public
	 * @return	void
	 */
	public function handle_fatal_errors()
	{
		
		if (($error = error_get_last())) {
			// \Sentry\captureLastError();
			$buffer = ob_get_contents();
			if($buffer) {
				ob_clean();
			}

			// xdebug_break();
			$message = "\nError Type: [".$error['type']."] ".$this->_friendly_error_type($error['type'])."\n";
			$message .= "Error Message: ".$error['message']."\n";
			$message .= "In File: ".$error['file']."\n";
			$message .= "At Line: ".$error['line']."\n";
			$message .= "Platform: " . PHP_VERSION . " (" . PHP_OS . ")\n";

			$message .= "\nBACKTRACE\n";
			$message .= $buffer;
			$message .= "\nEND\n";

			$this->_forward_error($message);

		}

	}

	/**
	 * Exception Handler
	 *
	 * Accesses exception class on shutdown, formats the error message and redirects
	 *
	 * @access	public
	 * @return	void
	 */
	public function handle_exceptions($exception)
	{
		// \Sentry\captureLastError();

		$message = "\nError Type: ".get_class($exception)."\n";
		$message .= "Error Message: ".$exception->getMessage()."\n";
		$message .= "In File: ".$exception->getFile()."\n";
		$message .= "At Line: ".$exception->getLine()."\n";
		$message .= "Platform: " . PHP_VERSION . " (" . PHP_OS . ")\n";

		$message .= "\nBACKTRACE\n";
		$message .= $exception->getTraceAsString();
		$message .= "\nEND\n";

		$this->_forward_error($message);
	}

	/**
	 * Parse Error Handler
	 *
	 * Accesses parse errors, formats the error message and redirects
	 *
	 * @access	public
	 * @param 	int
	 * @param 	string
	 * @param 	string
	 * @param 	int
	 * @return 	void
	 */
	public function handle_errors($errno, $errstr, $errfile, $errline)
	{

		// don't log out strict stuff for now??

		if (!(error_reporting() & $errno) || $errno == 2048)
		{

			return;

		}
		// \Sentry\captureLastError();
		$data = array(
            'errno' => $errno,
            'errtype' => $this->_friendly_error_type($errno) ? $this->_friendly_error_type($errno) : $errno,
            'errstr' => $errstr,
            'errfile' => $errfile,
            'errline' => $errline,
            'time' => date('Y-m-d H:i:s')
        );
		$e = new Exception;
		$errorText1 = $e->getTraceAsString();

		$CI =& get_instance();
		if(!isset($CI->doctrine)) {
			error_log($errstr);
			error_log($errfile);
			error_log($errline);
			return;
		}
		//reset doctrine in case we've lost the DB
		// TODO: doctrine 2.5 should let us move to pingable and avoid this?
		$CI->doctrine->reset();

		$errorText = join("\n", $data);
		$log = new Entity\Log();
		$log->setMessage(substr($errorText, 0, 1000) . "\n" .  $errorText1);
		$log->setCreatedAt(new \DateTime("now"));
		if(isset($CI->instance)) {
			$instance = $CI->doctrine->em->find('Entity\Instance', $CI->instance->getId());
			$log->setInstance($instance);
		}
		if(isset($CI->user)) {
			$log->setUserId($CI->user->getId());
		}
		if(isset($CI->collection)) {
			$log->setCollection($CI->collection->getId());
		}
		if(isset($_SERVER['REQUEST_URI'])) {
			$log->setUrl($_SERVER['REQUEST_URI']);
		}
		$CI->doctrine->em->persist($log);
		$CI->doctrine->em->flush();
		if( (php_sapi_name() === 'cli')) {
			echo "Error, continuing.\n";
		}
		
		
		// echo "<p>Error Logged</p>";
		// $CI =& get_instance();
		// $CI->load->database();
		// var_dump($data);
  //       $log_table_name = ($CI->config->item('log_table_name')) ? $CI->config->item('log_table_name') : 'logs';
        //$CI->db->insert($log_table_name, $data);

	}

	/**
	 * Redirection
	 *
	 * Stores the error message in session and redirects to inhibitor hanlder
	 *
	 * @access	private
	 * @param	string
	 * @return	void
	 */
	private function _forward_error($message)
	{
		$CI =& get_instance();
		session_start();
		if($CI && !$CI->input->is_cli_request()) {
			$CI->load->helper('url');
			$CI->load->library('session');
			$CI->session->set_flashdata('error', substr($message,0,500));
			redirect("/errorHandler/fatalError");

		}
		else {
			var_dump($message);
		}

	}

	/**
	 * Error Type Helper
	 *
	 * Translates error codes to something more human
	 *
	 * @access	private
	 * @param	string
	 * @return	string
	 */
	private function _friendly_error_type($type)
	{

		switch($type)
		{
			case E_ERROR: // 1
				return 'Fatal error';
			case E_WARNING: // 2
				return 'Warning';
			case E_PARSE: // 4
				return 'Parse error';
			case E_NOTICE: // 8
				return 'Notice';
			case E_CORE_ERROR: // 16
				return 'Core fatal error';
			case E_CORE_WARNING: // 32
				return 'Core warning';
			case E_COMPILE_ERROR: // 64
				return 'Compile-time fatal error';
			case E_COMPILE_WARNING: // 128
				return 'Compile-time warning';
			case E_USER_ERROR: // 256
				return 'Fatal user-generated error';
			case E_USER_WARNING: // 512
				return 'User-generated warning';
			case E_USER_NOTICE: // 1024
				return 'User-generated notice';
			case E_STRICT: // 2048
				return 'E_STRICT';
			case E_RECOVERABLE_ERROR: // 4096
				return 'Catchable fatal error';
			case E_DEPRECATED: // 8192
				return 'E_DEPRECATED';
			case E_USER_DEPRECATED: // 16384
				return 'E_USER_DEPRECATED';
		}

		return $type;

	}


}

/* End of file inhibitor_hook.php */
/* Location: ./application/hooks/inhibitor_hook.php */
