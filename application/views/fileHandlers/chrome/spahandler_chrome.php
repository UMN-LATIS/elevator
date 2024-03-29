<?
$fileObjectId = $fileObject->getObjectId();
$embedLink = stripHTTP(instance_url("asset/getEmbed/" . $fileObjectId . "/null/true"));
$embed = htmlentities('<iframe width="560" height="480" src="' . $embedLink . '" frameborder="0" allowfullscreen></iframe>', ENT_QUOTES);

$embedHeight = 480;





$menuArray = [];
if(count($fileContainers)>0) {
  $menuArray['embed'] = $embed;
  $menuArray['embedLink'] = $embedLink;
}

$fileInfo = [];
$fileInfo["File Type"] = "Image";
$fileInfo["Original Name"] = $fileObject->sourceFile->originalFilename;
$fileInfo["File Size"] = $fileObject->sourceFile->metadata["filesize"];
$fileInfo["Image Size"] = ($fileObject->sourceFile->metadata["width"]??"unknown") . " x " . ($fileObject->sourceFile->metadata["height"]??"unknown");

if($widgetObject) {
  if($widgetObject->fileDescription) {
    $fileInfo["Description"] = $widgetObject->fileDescription;
  }
  if($widgetObject->getLocationData()) {
    $fileInfo["Location"] = ["latitude"=>$widgetObject->getLocationData()[1], "longitude"=>$widgetObject->getLocationData()[0]];
  }
  if($widgetObject->getDateData()) {
    $fileInfo["Date"] = $widgetObject->getDateData();
  }

	
}

foreach($fileObject->sourceFile->metadata as $key=>$value) {
    if($key == "filesize") continue;
    if(!$value) continue;
    $fileInfo[$key] = $value;
}


$menuArray['fileInfo'] = $fileInfo;

$downloadArray = [];

if(isset($fileContainers['screen']) && $fileContainers['screen']->ready) {
$downloadArray["Download Derivative (jpg)"] = instance_url("fileManager/getDerivativeById/". $fileObjectId . "/screen");
}

if($allowOriginal) {
  $downloadArray['Download Original'] = instance_url("fileManager/getOriginal/". $fileObjectId);
}

$menuArray['download'] = $downloadArray;



if(count($fileContainers)>0 && !array_key_exists("tiled", $fileContainers) && !isset($fileObject->sourceFile->metadata["spherical"])) {
	$menuArray['zoom'] = true;	
}

if($fileObject->sourceFile->getType() == "svs") {
  // SVS files get taller for menu stuff
  $embedHeight = 600;
}



?>
<div class="row assetViewRow">
	<div class="col-md-12">
        <iframe width="100%" height="<?=$embedHeight?>" title="Embedded Spectroscopy Chart" src="<?=$fileObject->getEmbedURL(true)?>" frameborder="0" allowfullscreen class="imageEmbedFrame embedAsset"></iframe>
    </div>
</div>
<?=renderFileMenu($menuArray)?>
<script>

$(document).on("click", ".canFullscreen", function() {
    if($.fullscreen.isNativelySupported()) {
        $(".imageEmbedFrame").first().fullscreen({ "toggleClass": "imageFullscreen"});
    }
});

$(function ()
{
	$(".infoPopover").popover({trigger: "focus | click"});
	$(".infoTooltip").tooltip({ placement: 'top'});

});


$(".imageEmbedFrame").on("load", function(event) {

    event.target.contentWindow.runningFromElevatorHost();

});

$(".zoom-range").on("input", function(e) {
    $(".imageEmbedFrame")[0].contentWindow.zoom($(e.target).val());
});

</script>
