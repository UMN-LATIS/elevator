<?php

define("imagick_internal_ORIENTATION_BOTTOMRIGHT", 3);
define("imagick_internal_ORIENTATION_RIGHTTOP", 6);
define("imagick_internal_ORIENTATION_LEFTBOTTOM", 8);

/**
 * [compressImageAndSave description]
 * @param  [fileContainer] $sourceImage [description]
 * @param  [fileContainer] $targetImage [description]
 * @param  [type] $width       [description]
 * @param  [type] $height      [description]
 * @return [type]              [description]
 */
function compressImageAndSave($sourceImage, $targetImage, $width, $height, $compressionQuality=80, $forceRotation=null) {
	//$im->setResolution(300,300); could do this for pdf
	//
	$append = "";
	if($sourceImage->getType() == "pdf") {
		// get the first page
		$append = "[0]";
	}

	$CI =& get_instance();

	$inputSwitches = [];
	$inputSwitches[] = "-define heic:preserve-orientation=true"; // don't rotate during decode so we can do it ourselves
	$outputSwitches = [];

	if($sourceImage->getType() == "dcm") {
		$inputSwitches[] = "-define dcm:rescale=true";
	}

	$outputSwitches[] = "-compress JPEG";
	$outputSwitches[] = "-quality " . $compressionQuality;
	$outputSwitches[] = "-background white";
	$outputSwitches[] = "-alpha remove";
	$outputSwitches[] = "-flatten";
	$outputSwitches[] = "-format jpeg";

	
	// $image = $image->flattenImages();

	$rotation = null;
	if($forceRotation) {
		$rotation = $forceRotation;
	}
	else if(isset($sourceImage->metadata["rotation"])) {
		$rotation = $sourceImage->metadata["rotation"];
	}

	if($rotation) {
		
		switch($rotation) {
        case imagick_internal_ORIENTATION_BOTTOMRIGHT:
			$outputSwitches[] = "-rotate 180";
        	break;

		case imagick_internal_ORIENTATION_RIGHTTOP:
			$outputSwitches[] = "-rotate 90";
        	break;

		case imagick_internal_ORIENTATION_LEFTBOTTOM:
			$outputSwitches[] = "-rotate -90";
        	break;
		}
		$outputSwitches[] = "-orient undefined -strip";

	}
	if ((isset($sourceImage->metadata["width"]) && isset($sourceImage->metadata["height"])) && ($sourceImage->metadata["width"]<= $width && $sourceImage->metadata["height"] <= $height)) {
		// don't upscale
    } else {
		$outputSwitches[] = "-resize '" . $width . "x" . $height . "'";
		$outputSwitches[] = "-filter Lanczos";
	}
	$inputName = $sourceImage->getType().":".$sourceImage->getPathToLocalFile().$append;
	$outputName = $targetImage->getPathToLocalFile();
	$commandline = $CI->config->item("convert") . " " . implode(" ", $inputSwitches) . " " . escapeshellarg($inputName) . " " . implode(" ", $outputSwitches) . " " . escapeshellarg("jpg:".$outputName);
	exec($commandline, $results);
	if(file_exists($outputName) && filesize($outputName) > 0) {
		return true;
	}
	else {
		return false;
	}

}

function toDecimal($deg, $min, $sec, $hem)
{

    $d = $deg + fractionToDecimal($min)/60 + fractionToDecimal($sec)/3600;

    return ($hem=='S' || $hem=='W') ? $d*=-1 : $d;
}
function fractionToDecimal($q) {
	//check for a space, signifying a whole number with a fraction
	if(strstr($q, ' ')){
		$wa = strrev($q);
		$wb = strrev(strstr($wa, ' '));
		$whole = true;//this is a whole number
	}
	//now check the fraction part
	if(strstr($q, '/')){
		if($whole==true){//if whole number, then remove the whole number and space from the calculations
			$q = strstr($q, ' ');
		}
		$b = str_replace("/","",strstr($q, '/'));//this is the divisor
		//isolate the numerator
		$c = strrev($q);
		$d = strstr($c, '/');
		$e = strrev($d);
		$a = str_replace("/","",$e);//the pre-final numerator
		if($whole==true){//add the whole number to the calculations
			$a = $a+($wb*$b);//new numerator is whole number multiplied by denominator plus original numerator
		}
		$q = $a/$b;//this is now your decimal
		return $q;
	}else{
		return $q;//not a fraction, just return the decimal
	}
}

function convert_before_json(&$item, &$key)
{
   $item=utf8_encode($item);
}


function identifyImage($sourceImage) {
	try {
		$commandline = $CI->config->item("identify") . " " . escapeshellarg($sourceImage->getPathToLocalFile());
		exec($commandLine, $result);
	}
	catch(Exception $e) {
		return false;
	}
	if($result) {
		$parsedIdentify = explode(" ", $result[0]);
		return $parsedIdentify[1];
	}
	else {
		return false;
	}
}

function fastImageDimensions($sourceImage) {
	$CI =& get_instance();
	putenv("MAGICK_TMPDIR=" . $CI->config->item("scratchSpace"));
	$commandline = "identify " . $sourceImage->getType().":".$sourceImage->getPathToLocalFile();
	exec($commandline, $results);
	if(isset($results) && is_array($results) && count($results)> 0) {
		$split = explode(" ", $results[0]);
		if(count($split)<3) {
			$CI->logging->logError("identify failure","invalid count, identify returned" . $results[0]);
		}
		$dimensions = $split[2];
		if(stristr($dimensions, "x")) {
			$dimensionsSplit = explode("x", $dimensions);	
			return array("x"=>$dimensionsSplit[0], "y"=>$dimensionsSplit[1]);
		}
	}
	return false;
}

function getImageMetadata($sourceImage) {
	$CI =& get_instance();
	putenv("MAGICK_TMPDIR=" . $CI->config->item("scratchSpace"));
	$commandline = "exiftool -api largefilesupport=1 -n -j " . escapeshellarg($sourceImage->getPathToLocalFile());
	exec($commandline, $results);
	if(isset($results) && is_array($results) && count($results)> 0) {
		$extractedRaw = json_decode(implode("\n", $results), true);
		$extractedRaw = $extractedRaw[0];
	}
	else {
		$CI->logging->logError("Error reading raw metadata", (string)$e,  $sourceImage->storageKey);
		return false;
	}

	$results = null;
	$commandline = "exiftool -api largefilesupport=1 -j -g " . escapeshellarg($sourceImage->getPathToLocalFile());
	exec($commandline, $results);
	if(isset($results) && is_array($results) && count($results)> 0) {
		// strip bad unicode character that will make postgres mad
		$extractedParsed = json_decode(implode("\n",  str_replace("\u0000", "",$results)), true);
		$extractedParsed = $extractedParsed[0];
	}
	else {
		$CI->logging->logError("Error reading parsed metadata", (string)$e,  $sourceImage->storageKey);
		return false;
	}



	$metadata["width"] = $extractedRaw["ImageWidth"] ?: 0;
	$metadata["height"] = $extractedRaw["ImageHeight"] ?: 0;

	if(isset($extractedParsed["SourceFile"])) {
		unset($extractedParsed["SourceFile"]);
	}

	if(isset($extractedParsed["File"])) {
		if(isset($extractedParsed["File"]["FileName"])) {
			unset($extractedParsed["File"]["FileName"]);
		}
		if(isset($extractedParsed["File"]["Directory"])) {
			unset($extractedParsed["File"]["Directory"]);
		}
	}


	$metadata["exif"] = $extractedParsed;
	if(isset($extractedRaw["Orientation"])) {
		$metadata["rotation"] = $extractedRaw["Orientation"];	
	}
	

	if(isset($extractedRaw['GPSLatitude'])) {
		$metadata["coordinates"] = [floatval($extractedRaw["GPSLongitude"]), floatval($extractedRaw["GPSLatitude"])]; // lonlat cause that's what we need for elastic
	}

	if(isset($extractedRaw['DateTimeOriginal'])) {
		$dateString = $extractedRaw['DateTimeOriginal'];
		$metadata["creationDate"] = $dateString;
	}

	return $metadata;
}

function isWholeSlideImage($sourceFile) {
	if($sourceFile->getType() == "svs" || $sourceFile->getType() == "ndpi") {
		return true;
	}
	return false;
}