<?
$fileObjectId = $fileObject->getObjectId();
$drawerArray = array();
if($this->user_model->userLoaded) {
  foreach($this->user_model->getDrawers(true) as $drawer) {
    $drawerArray[] = $drawer;
  }
}

$embed = htmlentities('<iframe width="560" height="480" src="' . $fileObject->getEmedURL() . '" frameborder="0" allowfullscreen></iframe>', ENT_QUOTES);

$ratio = $fileObject->sourceFile->metadata["width"] / $fileObject->sourceFile->metadata["height"];

$menuArray = [];
if(count($fileContainers)>0) {
  $menuArray['embed'] = $embed;
  $menuArray['embedLink'] = $fileObject->getEmedURL();
  if(count($drawerArray)>0) {
    $menuArray['excerpt'] = true;  
  }
}

$fileInfo = [];
$fileInfo["File Type"] = "Movie";
$fileInfo["Original Name"] = $fileObject->sourceFile->originalFilename;
$fileInfo["File Size"] = $fileObject->sourceFile->metadata["filesize"];
$fileInfo["Video Size"] = $fileObject->sourceFile->metadata["width"] . "x" . $fileObject->sourceFile->metadata["height"];
$fileInfo["Duration"] = gmdate("H:i:s", $fileObject->sourceFile->metadata["duration"]);

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

$menuArray['fileInfo'] = $fileInfo;

$downloadArray = [];
if(!$this->instance->getHideVideoAudio() || $allowOriginal) {
  if(isset($fileContainers['mp4hd']) && $fileContainers['mp4hd']->ready) {
    $downloadArray["Download HD MP4"] = instance_url("fileManager/getDerivativeById/". $fileObjectId . "/mp4hd");
  }
  if(isset($fileContainers['mp4sd']) && $fileContainers['mp4sd']->ready) {
    $downloadArray["Download SD MP4"] = instance_url("fileManager/getDerivativeById/". $fileObjectId . "/mp4sd");
  }
}
if($allowOriginal) {
  $downloadArray['Download Original'] = instance_url("fileManager/getOriginal/". $fileObjectId);
}

$menuArray['download'] = $downloadArray;

?>
<script>

  if(typeof objectId == 'undefined') {
    objectId = "<?=$fileObjectId?>";
  }
</script>

<div class="row assetViewRow" >
  <div class="col-md-12 videoColumn">
    <iframe width="100%" height="480" data-ratio="<?=$ratio?>" title="Embedd video" src="<?=$fileObject->getEmedURL()?>" frameborder="0" allowfullscreen class="videoEmbedFrame embedAsset"></iframe>
  </div>
</div>


<?=renderFileMenu($menuArray)?>

<?$this->load->view("fileHandlers/chrome/moviehandler_excerpt", ["drawerArray"=>$drawerArray])?>

<script>
$(document).ready(function() {

    $(".infoPopover").popover({trigger: "focus | click"});
    $(".infoPopover").tooltip({ placement: 'top'});
    $(".excerptTooltip").tooltip({ placement: 'top'});
    resizeElement();
});



$(document).ready(function() {
  $(document).on("click", ".setStart", function() {
    currentTime = $(".videoEmbedFrame")[0].contentWindow.getTime();
    $("#startTime").val(currentTime);
    $("#startTimeVisible").val(currentTime);
  });
  $(document).on("click", ".setEnd", function() {
    currentTime = $(".videoEmbedFrame")[0].contentWindow.getTime();
    $("#endTime").val(currentTime);
    $("#endTimeVisible").val(currentTime);
  });
  $(document).on("submit", ".excerptForm", function () {
    $.post(basePath+ "drawers/addToDrawer", $("#excerptForm").serialize(), function(data, textStatus, xhr) {
      $("#excerptGroup").collapse('hide');
      $("#endTime").val();
      $("#endTimeVisible").val();
      $("#startTime").val();
      $("#startTimeVisible").val();
      $("#label").val();
    });

    return false;
  });

});

</script>
