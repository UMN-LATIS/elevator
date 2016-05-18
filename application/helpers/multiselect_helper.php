<?php

/*
* cascading multiselects are kind of a pain.  This tries to help with it a bit
*/

function flatten(array $array) {
    $return = array();
    array_walk_recursive($array, function($a) use (&$return) { $return[] = $a; });
    return $return;
}



// Our data layout is Label: categories : label : categories etc.  We want to extract just the labels
function recurseThing($array, $skip) {

	$outputArray = array();
	foreach($array as $key=>$value) {
		if(!$skip) {
			$outputArray[] = $key;
		}
		if(is_array($value) || is_object($value)) {
			$outputArray[] = recurseThing($value, !$skip);
		}


	}
	return $outputArray;
}


/**
 * Strip any special characters so we can use these in form names.
 * @param  [string] $sourceName
 * @return [string] sanitized name
 */
function makeSafeForTitle($sourceName) {
	return preg_replace("/[^a-zA-Z0-9]+/", "", $sourceName);
}

function getTopLevels($sourceData) {
	$topLevels = array_unique(flatten(recurseThing($sourceData,0)));
	return $topLevels;
}