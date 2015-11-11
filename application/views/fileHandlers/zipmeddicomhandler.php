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
      $assetURL = "dicomweb:" . stripHTTP($assetURL);

      $instanceList[] = ["imageId"=>$assetURL];
    }
    $series['instanceList'] = $instanceList;
    $seriesList[$key] = $series;


  }
  $outputJson["seriesList"] = $seriesList;

}



?>



<script>

if(typeof objectId === 'undefined') {
    objectId = "<?=$fileObjectId?>";
}

var masterJson = <?=json_encode($outputJson)?>;

</script>



<?if(!$embedded):?>
<div class="row assetViewRow" >
  <div class="col-md-12">
<?endif?>

    <? if(!isset($fileContainers) || count($fileContainers) == 1):?>
      <p class="alert alert-info">No derivatives found.
        <?if(!$this->user_model->userLoaded):?>
        You may have access to additional derivatives if you log in.
        <?endif?>
      </p>

    <?else:?>

    <div class="dicomEnclosure">
    <div class="dicomViewer">

<script>

var viewportTemplate; // the viewport template
var studyViewerTemplate; // the study viewer template

var loadedCallback = function() {
  loadTemplate("/assets/cornerstone/templates/viewport.html", function(element) {
      viewportTemplate = element;
      loadElement($(".dicomViewer"), masterJson);
  });

  loadTemplate("/assets/cornerstone/templates/studyViewer.html", function(element) {
      studyViewerTemplate = element;
      loadElement($(".dicomViewer"), masterJson);
  });

};

</script>
  </div>
</div>







  <?endif?>
<?if(!$embedded):?>
  </div>

</div>
<?endif?>

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

<link rel="stylesheet" href="/assets/cornerstone/font-awesome.min.css">
<link href="/assets/cornerstone/cornerstone.min.css" rel="stylesheet">
<link href="/assets/cornerstone/cornerstoneDemo.css" rel="stylesheet">
<script src="/assets/cornerstone/hammer.js"></script>

<!-- include the cornerstone library -->
<script src="/assets/cornerstone/cornerstone.js"></script>

<!-- include the cornerstone library -->
<script src="/assets/cornerstone/cornerstoneMath.js"></script>

<!-- include the cornerstone tools library -->
<script src="/assets/cornerstone/cornerstoneTools.js"></script>

<script src="/assets/cornerstone/util.js"></script>
<script src="/assets/cornerstone/arithmetic_decoder.js"></script>
<script src="/assets/cornerstone/jpx.js"></script>
<!-- include the cornerstoneWADOImageLoader library -->
<script src="/assets/cornerstone/cornerstoneWADOImageLoader.js"></script>

<!-- include the cornerstoneWebImageLoader library -->
<script src="/assets/cornerstone/cornerstoneWebImageLoader.js"></script>

<!-- include the dicomParser library -->
<script src="/assets/cornerstone/dicomParser.js"></script>

<!-- include cornerstoneDemo.js -->
<script src="/assets/cornerstone/setupViewport.js"></script>
<script src="/assets/cornerstone/displayThumbnail.js"></script>
<script src="/assets/cornerstone/loadStudy.js"></script>
<script src="/assets/cornerstone/setupButtons.js"></script>
<script src="/assets/cornerstone/disableAllTools.js"></script>
<script src="/assets/cornerstone/forEachViewport.js"></script>
<script src="/assets/cornerstone/imageViewer.js"></script>
<script src="/assets/cornerstone/loadTemplate.js"></script>
<script src="/assets/cornerstone/setupViewportOverlays.js"></script>

<script src="/assets/cornerstone/cornerstoneDemo.js"></script>
