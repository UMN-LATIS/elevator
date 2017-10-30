<?
$fileObjectId = $fileObject->getObjectId();
$embedLink = instance_url("asset/getEmbed/" . $fileObjectId . "/null/true");
$embedLink = str_replace("http:", "", $embedLink);
$embedLink = str_replace("https:", "", $embedLink);

$embed = htmlentities('<iframe width="560" height="480" src="' . $embedLink . '" frameborder="0" allowfullscreen></iframe>', ENT_QUOTES);

$targetFile = null;
if(isset($fileContainers['shrunk_pdf'])) {
	$targetFile = $fileContainers['shrunk_pdf']->getProtectedURLForFile();
}
else if(isset($fileContainers['ocr_pdf'])) {
	$targetFile = $fileContainers['ocr_pdf']->getProtectedURLForFile();
}
else if(isset($fileContainers['pdf'])) {
	$targetFile = $fileContainers['pdf']->getProtectedURLForFile();
}
else if($allowOriginal) {
	$targetFile = $fileObject->sourceFile->getProtectedURLForFile();
}

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

<?if(!$embedded):?>
<div class="row assetViewRow">
	<div class="col-md-12">
<?endif?>
<?if($targetFile):?>
	<iframe class="vrview" frameborder=0 width="100%" height=<?=$embedded?"100%":"480px"?> scrolling="no" allowfullscreen src="/assets/pdf/web/viewer.html?file=<?=urlencode(striphttp($targetFile))?>#zoom=page-fit&page=0"></iframe>
<?else:?>
	<img src="<?=isset($fileContainers['thumbnail2x'])?stripHTTP($fileContainers['thumbnail2x']->getProtectedURLForFile()):"/assets/icons/512px/pdf.png"?>" class="img-responsive embedImage" style="width: 50%; margin-left:auto; margin-right:auto"/>
<?endif?>

<?if(!$embedded):?>
	</div>
</div>
<?endif?>

<?if(!$embedded):?>

<?=renderFileMenu($menuArray)?>


<script>
$(function ()
{
	$(".infoPopover").popover({trigger: "focus | click"});
	$(".infoPopover").tooltip({ placement: 'top'});

});
</script>
<?endif?>
