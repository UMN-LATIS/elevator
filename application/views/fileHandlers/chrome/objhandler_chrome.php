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

$menuArray['fileInfo'] = $fileInfo;

$downloadArray = [];
$targetAsset = null;
if(isset($fileContainers['nxs']) && $fileContainers['nxs']->ready) {
  $nxsURL = isset($fileContainers['nxs'])?$fileContainers['nxs']->getProtectedURLForFile():null;
  $downloadArray["Download Derivative (nxs)"] = instance_url("fileManager/getDerivativeById/". $fileObjectId . "/nxs");
  $targetAsset = stripHTTP($nxsURL) . "#.nxs";
}

if(isset($fileContainers['ply']) && $fileContainers['ply']->ready) {
  $downloadArray["Download Derivative (ply)"] = instance_url("fileManager/getDerivativeById/". $fileObjectId . "/ply");
  $plyURL = isset($fileContainers['ply'])?$fileContainers['ply']->getProtectedURLForFile():null;
  if(!$targetAsset) {
    $targetAsset = stripHTTP($plyURL) . "#.ply";
  }
}

if(isset($fileContainers['stl']) && $fileContainers['stl']->ready) {
  $downloadArray["Download Derivative (stl)"] = instance_url("fileManager/getDerivativeById/". $fileObjectId . "/stl");
}

if($allowOriginal) {
  $downloadArray['Download Original'] = instance_url("fileManager/getOriginal/". $fileObjectId);
}

$menuArray['download'] = $downloadArray;



?>
<div class="row assetViewRow">
	<div class="col-md-12">
        <iframe width="100%" height="<?=$embedHeight?>" title="Embedded 3D Model" src="<?=$fileObject->getEmbedURL(true)?>" frameborder="0" allowfullscreen class="embedAsset"></iframe>
    </div>
</div>
<?=renderFileMenu($menuArray)?>
<script>

$(function ()
{
	$(".infoPopover").popover({trigger: "focus | click"});
	$(".infoTooltip").tooltip({ placement: 'top'});

});

</script>
