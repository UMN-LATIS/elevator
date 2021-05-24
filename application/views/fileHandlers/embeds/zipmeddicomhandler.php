<?
$fileObjectId = $fileObject->getObjectId();


$outputJson = array();

if(isset($fileContainers['dicom'])) {


  $masterExif = $fileObject->sourceFile->metadata['exif'];
  $seriesList = $fileContainers['dicom']->metadata;

  if(is_array($masterExif)) {
    $outputJson['patientName'] = array_key_exists("dcm:Patient\'sName", $masterExif)?$masterExif['dcm:Patient\'sName']:null;
    $outputJson['patientId'] = array_key_exists("dcm:Patient\'sID", $masterExif)?$masterExif['dcm:Patient\'sID']:null;
    $outputJson['studyDate'] = array_key_exists("dcm:StudyDate", $masterExif)?$masterExif['dcm:StudyDate']:null;
    $outputJson['modality'] = array_key_exists("dcm:Modality", $masterExif)?$masterExif['dcm:Modality']:null;
    $outputJson['studyDescription'] = array_key_exists("dcm:StudyDescription", $masterExif)?$masterExif['dcm:StudyDescription']:null;
    $outputJson['studyId'] = array_key_exists("dcm:StudyID", $masterExif)?$masterExif['dcm:StudyID']:null;
  }
  
  foreach($seriesList as $key=>$series) {
    if(!array_key_exists("instanceList", $series)) {
      continue;
    }
    $instanceList = array();
    $i = 0;
    foreach($series["instanceList"] as $instanceEntry) {
      $assetURL = $fileContainers['dicom']->getProtectedURLForFile($instanceEntry["imageId"], "+30 minutes");
      $assetURL = "wadouri:" . stripHTTP($assetURL);

      $instanceList[] = ["url"=>$assetURL, "rows"=>1, "sopInstanceUid"=>$i . "_uid", "instanceNumber"=>$i];
      $i++;
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


    <? if(!isset($fileContainers) || count($fileContainers) == 1):?>
      <p class="alert alert-info">No derivatives found.
        <?if(!$this->user_model->userLoaded):?>
        You may have access to additional derivatives if you log in.
        <?endif?>
      </p>
    <?else:?>
   
   <iframe class="dicomViewer" frameborder=0 width="100%" height=100% scrolling="no" allowfullscreen src="/assets/dicom/index.html"></iframe>
   
  <?endif?>


<script>
$(document).on("click", ".canFullscreen", function() {
    if($.fullscreen.isNativelySupported()) {
      $(".dicomViewer").first().fullscreen({ "toggleClass": "imageFullscreen"});
    }
  });
</script>


<style>
html {
    height:100%;
}

body {
    height: 100%;
    overflow: hidden;
}
</style>
