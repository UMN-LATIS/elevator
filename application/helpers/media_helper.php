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
	$image=new Imagick($sourceImage->getType().":".$sourceImage->getPathToLocalFile().$append);
	$image->setImageCompression(Imagick::COMPRESSION_JPEG);
	$image->setImageCompressionQuality($compressionQuality);
	$image = $image->flattenImages();

	$image->setImageFormat('jpeg');
	if(isset($sourceImage->metadata["rotation"])) {
		//$image->setImageOrientation($sourceImage->metadata["rotation"]);
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


function getImageMetadata($sourceImage) {
	try {
		$image=new Imagick($sourceImage->getType().":".$sourceImage->getPathToLocalFile());
	}
	catch (Exception $e) {
		$CI =& get_instance();
		$CI->logging->logError("Error reading metadata", (string)$e,  $sourceImage->storageKey);
		return false;
	}

	$metadata["width"] = $image->getImageWidth();
	$metadata["height"] = $image->getImageHeight();
	$metadata["exif"] = $image->getImageProperties();
	$metadata["rotation"] = $image->getImageOrientation();


	array_walk_recursive($metadata['exif'],"convert_before_json");

	if(isset($metadata['exif']['exif:GPSLatitude'])) {
		$egeoLong = explode(",",$metadata['exif']['exif:GPSLongitude']);
		$egeoLat = explode(",",$metadata['exif']['exif:GPSLatitude']);
		$egeoLongR = $metadata['exif']['exif:GPSLongitudeRef'];
		$egeoLatR = $metadata['exif']['exif:GPSLatitudeRef'];
		$geoLong = toDecimal($egeoLong[0], $egeoLong[1], $egeoLong[2], $egeoLongR);
		$geoLat = toDecimal($egeoLat[0], $egeoLat[1], $egeoLat[2], $egeoLatR);
		$metadata["coordinates"] = [floatval($geoLong), floatval($geoLat)]; // lonlat cause that's what we need for elastic
	}

	if(isset($metadata['exif']['exif:DateTime'])) {
		$dateString = $metadata['exif']['exif:DateTime'];
		$metadata["creationDate"] = $dateString;
	}

	return $metadata;
}
