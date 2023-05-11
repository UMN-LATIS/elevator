<?php


function instance_url($target) {
	$CI =& get_instance();
	$queryString = null;
	if($CI->instance && isset($CI->instance->queryHandoff)) {
		$queryString = "?" . http_build_query($CI->instance->queryHandoff);
	}
	if(!$CI->config->item("instance_absolute")) {
		return "/";
	}
	else {
		if(substr($target, 0,1) == "/") {
			$target = substr($target, 1);
		}

		return $CI->config->item("instance_absolute"). $target . $queryString;
	}

}


function instance_redirect($target) {
	$CI =& get_instance();
	if(substr($target, 0,1) == "/") {
		$target = substr($target, 1);
	}
	$queryString = null;
	if($CI->instance && isset($CI->instance->queryHandoff)) {
		$queryString = "?" . http_build_query($CI->instance->queryHandoff);
	}

	redirect($CI->config->item("instance_absolute") . $target . $queryString);

}

/**
 * Get the absolute path to an icon based on the interface version
 * @return string 
 */
function getIconPath($size = 'default') {
	$pathsByInterfaceVersion = [
		// classic
		0 => [
			'tiny' => '/assets/icons/48px/',
			'thumbnail' => '/assets/icons/512px/',
			'default' => '/assets/icons/512px/',
		],

		// vue template interface
		1 => [
			'tiny' => '/assets/icons/160x90/',
			'thumbnail' => '/assets/icons/800x450/',
			'default' => '/assets/icons/800x450/',
		],
	];

	$CI =& get_instance();
	$interfaceVersion = $CI->instance->getInterfaceVersion();
	return $pathsByInterfaceVersion[$interfaceVersion][$size]	?? $pathsByInterfaceVersion[0]['default'];
}

if (!function_exists('isUsingVueUI')) {
	function isUsingVueUI(): bool {
		$CI =& get_instance();
		return $CI->instance->getInterfaceVersion() == 1;
	}
}
