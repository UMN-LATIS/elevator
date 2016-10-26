<?
$fileObjectId = $fileObject->getObjectId();
$drawerArray = array();
if($this->user_model->userLoaded) {
    foreach($this->user_model->getDrawers(true) as $drawer) {
        $drawerArray[] = $drawer;
    }
}



$embedLink = instance_url("asset/getEmbed/" . $fileObjectId . "/null/true");
$embedLink = str_replace("http:", "", $embedLink);
$embedLink = str_replace("https:", "", $embedLink);

$embed = htmlentities('<iframe width="560" height="480" src="' . $embedLink . '" frameborder="0" allowfullscreen></iframe>', ENT_QUOTES);

$outputJson = array();

if(isset($fileContainers['dicom'])) {


  $masterExif = $fileObject->sourceFile->metadata['exif'];
  $seriesList = $fileContainers['dicom']->metadata;

  $outputJson['patientName'] = array_key_exists("dcm:Patient\'sName", $masterExif)?$masterExif['dcm:Patient\'sName']:null;
  $outputJson['patientId'] = array_key_exists("dcm:Patient\'sID", $masterExif)?$masterExif['dcm:Patient\'sID']:null;
  $outputJson['studyDate'] = array_key_exists("dcm:StudyDate", $masterExif)?$masterExif['dcm:StudyDate']:null;
  $outputJson['modality'] = array_key_exists("dcm:Modality", $masterExif)?$masterExif['dcm:Modality']:null;
  $outputJson['studyDescription'] = array_key_exists("dcm:StudyDescription", $masterExif)?$masterExif['dcm:StudyDescription']:null;
  $outputJson['studyId'] = array_key_exists("dcm:StudyID", $masterExif)?$masterExif['dcm:StudyID']:null;

  foreach($seriesList as $key=>$series) {
    if(!array_key_exists("instanceList", $series)) {
      continue;
    }
    $instanceList = array();

    foreach($series["instanceList"] as $instanceEntry) {
      $assetURL = $fileContainers['dicom']->getProtectedURLForFile($instanceEntry["imageId"], "+30 minutes");
      $assetURL = "wadouri:" . stripHTTP($assetURL);

      $instanceList[] = ["url"=>$assetURL, "rows"=>1, "sopInstanceUid"=>"test"];
    }
    $series['instances'] = $instanceList;
    $seriesList[$key] = $series;


  }
  $finalOutput["transactionId"] = "Elevator";
  $outputJson["seriesList"] = $seriesList;
  $outputJson["seriesList"] = $seriesList;
  $outputJson["seriesList"] = $seriesList;
  $finalOutput["studies"] = [$outputJson];
}



?>



<script>

if(typeof objectId === 'undefined') {
    objectId = "<?=$fileObjectId?>";
}

var globalData = <?=json_encode($finalOutput)?>;

</script>



<?if(!$embedded):?>
<div class="row assetViewRow" >
  <div class="col-md-12">
  <div class="dicomContainer">
<?endif?>
    <? if(!isset($fileContainers) || count($fileContainers) == 1):?>
      <p class="alert alert-info">No derivatives found.
        <?if(!$this->user_model->userLoaded):?>
        You may have access to additional derivatives if you log in.
        <?endif?>
      </p>
    <?else:?>
   
   <iframe class="dicomViewer" frameborder=0 width="100%" height=100% scrolling="no" allowfullscreen src="/assets/dicom/index.html"></iframe>
   
  <?endif?>
<?if(!$embedded):?>
</div>
  </div>
</div>
<?endif?>

<script>
$(document).on("click", ".canFullscreen", function() {
    if($.fullscreen.isNativelySupported()) {
      $(".dicomViewer").first().fullscreen({ "toggleClass": "imageFullscreen"});
    }
  });
</script>

<?if(!$embedded):?>

<div class="row infoRow">
  <div class="col-md-12">
    <span class="glyphicon glyphicon-info-sign infoPopover" data-placement="bottom" data-toggle="popover" title="File Info" data-html=true data-content='<ul class="list-group">
           <li class="list-group-item"><strong>File Type: </strong> Dicom Set </li>
      <li class="list-group-item assetDetails"><strong>Original Name: </strong><?=$fileObject->sourceFile->originalFilename?></li>
      <?if($widgetObject && $widgetObject->fileDescription ):?>
      <li class="list-group-item assetDetails"><strong>Description: </strong><?=htmlentities($widgetObject->fileDescription, ENT_QUOTES)?></li>
      <?endif?>
      <?if(isset($fileObject->sourceFile->metadata["exif"])):?>
      <li class="list-group-item assetDetails"><strong>EXIF: </strong><a href="" class="exifToggle" data-fileobject="<?=$fileObjectId?>">View EXIF</a></li>
      <?endif?>
      <li class="list-group-item assetDetails"><strong>File Size: </strong><?=byte_format($fileObject->sourceFile->metadata["filesize"])?></li>
    </ul>
      '></span>

        <span class="glyphicon glyphicon-download infoPopover" data-placement="bottom" data-toggle="popover" title="Download" data-html="true" data-content='
          <ul>
      <?if($allowOriginal):?>
      <li class="list-group-item assetDetails"><a href="<?=instance_url("fileManager/getOriginal/". $fileObjectId)?>">Download Original</a></li>
      <?endif?>
      </ul>'></span>
    <span class="glyphicon glyphicon-share infoPopover" data-placement="bottom" data-toggle="popover" title="Share" data-html="true" data-content='<ul>
      <?if(count($fileContainers)>0):?>
        <li class="list-group-item assetDetails"><strong>Embed: </strong><input class="form-control embedControl" value="<?=htmlspecialchars($embed, ENT_QUOTES)?>"></li>
       <?endif?>
      </ul>'></span>
      <span class="canFullscreen glyphicon glyphicon-resize-full" data-toggle="tooltip" title="Fullscreen"></span>
  </div>
</div>

<script>
$(document).ready(function() {

  $(".infoPopover").popover({trigger: "focus | click"});
  $(".infoPopover").tooltip({ placement: 'top'});
  $(".excerptTooltip").tooltip({ placement: 'top'});
});

</script>

<?endif?>
