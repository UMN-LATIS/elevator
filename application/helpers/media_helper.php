<?php

/**
 * [compressImageAndSave description]
 * @param  [fileContainer] $sourceImage [description]
 * @param  [fileContainer] $targetImage [description]
 * @param  [type] $width       [description]
 * @param  [type] $height      [description]
 * @return [type]              [description]
 */
function compressImageAndSave($sourceImage, $targetImage, $width, $height, $compressionQuality=80) {
	//$im->setResolution(300,300); could do this for pdf
	//
	$append = "";
	if($sourceImage->getType() == "pdf") {
		// get the first page
		$append = "[0]";
	}

	$CI =& get_instance();
	putenv("MAGICK_TMPDIR=" . $CI->config->item("scratchSpace"));
	$image=new Imagick();
	if($sourceImage->getType() == "dcm") {
		$image->setOption('dcm:rescale', "true");
	}
	$image->readImage($sourceImage->getType().":".$sourceImage->getPathToLocalFile().$append);
	$image->setImageCompression(Imagick::COMPRESSION_JPEG);
	$image->setImageCompressionQuality($compressionQuality);
	// $image = $image->flattenImages();
	$image->setImageBackgroundColor('white');
	$image->setImageAlphaChannel(imagick::ALPHACHANNEL_REMOVE);
	$image = $image->mergeImageLayers(imagick::LAYERMETHOD_FLATTEN);

	$image->setImageFormat('jpeg');
	if(isset($sourceImage->metadata["rotation"])) {
		
		switch($sourceImage->metadata["rotation"]) {
        case imagick::ORIENTATION_BOTTOMRIGHT:
            $image->rotateimage(new ImagickPixel('#00000000'), 180); // rotate 180 degrees
        break;

        case imagick::ORIENTATION_RIGHTTOP:
            $image->rotateimage(new ImagickPixel('#00000000'), 90); // rotate 90 degrees CW
        break;

        case imagick::ORIENTATION_LEFTBOTTOM:
            $image->rotateimage(new ImagickPixel('#00000000'), -90); // rotate 90 degrees CCW
        break;
    	}
    	$image->setImageOrientation(0);

	}
	if ((isset($sourceImage->metadata["width"]) && isset($sourceImage->metadata["height"])) && ($sourceImage->metadata["width"]<= $width && $sourceImage->metadata["height"] <= $height)) {
		// don't upscale
    } else {
    	$image->resizeImage($width,$height,imagick::FILTER_LANCZOS,1,true);
    }
	if($image->writeImage($targetImage->getPathToLocalFile())) {
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
		$image=new Imagick($sourceImage->getPathToLocalFile());
	}
	catch(Exception $e) {
		return false;
	}
	return $image->getImageFormat();
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
	$commandline = "exiftool -n -j " . $sourceImage->getPathToLocalFile();
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
	$commandline = "exiftool -j -g " . $sourceImage->getPathToLocalFile();
	exec($commandline, $results);
	if(isset($results) && is_array($results) && count($results)> 0) {
		$extractedParsed = json_decode(implode("\n", $results), true);
		$extractedParsed = $extractedParsed[0];
	}
	else {
		$CI->logging->logError("Error reading parsed metadata", (string)$e,  $sourceImage->storageKey);
		return false;
	}



	$metadata["width"] = $extractedRaw["ImageWidth"];
	$metadata["height"] = $extractedRaw["ImageHeight"];

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
