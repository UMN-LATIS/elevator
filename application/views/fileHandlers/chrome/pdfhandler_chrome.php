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
$fileInfo["File Type"] = "PDF";
$fileInfo["Original Name"] = $fileObject->sourceFile->originalFilename;
$fileInfo["File Size"] = $fileObject->sourceFile->metadata["filesize"];

if($widgetObject) {
  if($widgetObject->fileDescription) {
    $fileInfo["Description"] = $widgetObject->fileDescription;
  }
}

$menuArray['fileInfo'] = $fileInfo;

$downloadArray = [];

if(isset($fileContainers['shrunk_pdf']) && $fileContainers['shrunk_pdf']->ready) {
	$downloadArray["Download Optimized PDF"] = instance_url("fileManager/getDerivativeById/". $fileObjectId . "/shrunk_pdf");
}
if(isset($fileContainers['pdf']) && $fileContainers['pdf']->ready) {
	$downloadArray["Download PDF"] = instance_url("fileManager/getDerivativeById/". $fileObjectId . "/pdf");
}
if($allowOriginal) {
 	$downloadArray['Download Original'] = instance_url("fileManager/getOriginal/". $fileObjectId);
}

$menuArray['download'] = $downloadArray;




?>
<div class="row assetViewRow">
	<div class="col-md-12">
        <iframe width="100%" height="480" data-ratio="0.75" title="Embedded PDF" src="<?=$fileObject->getEmedURL(true)?>" frameborder="0" allowfullscreen class="pdfEmbedFrame embedAsset"></iframe>
    </div>
</div>
<?=renderFileMenu($menuArray)?>

<script>

$(function ()
{
	$(".infoPopover").popover({trigger: "focus | click"});
	$(".infoPopover").tooltip({ placement: 'top'});
  resizeElement();
});

</script>
