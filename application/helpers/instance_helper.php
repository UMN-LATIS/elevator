<?php


function instance_url($target) {
	$CI =& get_instance();
	if(!$CI->config->item("instance_absolute")) {
		return "/";
	}
	else {
		if(substr($target, 0,1) == "/") {
			$target = substr($target, 1);
		}

		return $CI->config->item("instance_absolute"). $target;
	}

}


function instance_redirect($target) {
	$CI =& get_instance();
	if(substr($target, 0,1) == "/") {
		$target = substr($target, 1);
	}

	redirect($CI->config->item("instance_absolute") . $target);

}

?>
