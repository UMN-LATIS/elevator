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

$fileInfo["Type"] = $fileObject->sourceFile->metadata["type"];
$fileInfo["Width"] = $fileObject->sourceFile->metadata["width"];
$fileInfo["Height"] = $fileObject->sourceFile->metadata["height"];
$fileInfo["Scale"] = $fileObject->sourceFile->metadata["scale"];
$fileInfo["Bias"] = $fileObject->sourceFile->metadata["bias"];


$menuArray['fileInfo'] = $fileInfo;


$downloadArray = [];

if($allowOriginal) {
  $downloadArray['Download Original'] = instance_url("fileManager/getOriginal/". $fileObjectId);
}

$menuArray['download'] = $downloadArray;




?>
<div class="row assetViewRow">
	<div class="col-md-12">
        <iframe width="100%" height="<?=$embedHeight?>" title="Embedded RTI Image" src="<?=$fileObject->getEmedURL()?>" frameborder="0" allowfullscreen class=""></iframe>
    </div>
</div>
<?=renderFileMenu($menuArray)?>
<script>

$(function ()
{
	$(".infoPopover").popover({trigger: "focus | click"});
	$(".infoPopover").tooltip({ placement: 'top'});

});

</script>
