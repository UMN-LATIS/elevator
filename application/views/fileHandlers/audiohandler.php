<?
$fileObjectId = $fileObject->getObjectId();

$stretchingSetting = "exactfit";
if($fileObject->getAltWidget()) {
  $stretchingSetting = "uniform";
}

$drawerArray = array();
if($this->user_model->userLoaded) {
    foreach($this->user_model->getDrawers(true) as $drawer) {
        $drawerArray[] = $drawer;
    }
}
?>
<?

$embedLink = instance_url("asset/getEmbed/" . $fileObjectId . "/null/true");
$embedLink = str_replace("http:", "", $embedLink);
$embedLink = str_replace("https:", "", $embedLink);

$embed = htmlentities('<iframe width="560" height="480" src="' . $embedLink . '" frameborder="0" allowfullscreen></iframe>', ENT_QUOTES);

$menuArray = [];
if(count($fileContainers)>0) {
  $menuArray['embed'] = $embed;
  $menuArray['embedLink'] = $embedLink;
  if(count($drawerArray)>0) {
    $menuArray['excerpt'] = true;  
  }
}

$fileInfo = [];
$fileInfo["File Type"] = "Movie";
$fileInfo["Original Name"] = $fileObject->sourceFile->originalFilename;
$fileInfo["File Size"] = $fileObject->sourceFile->metadata["filesize"];
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

$fileInfo["Object ID"] = $fileObject->getObjectId();
$menuArray['fileInfo'] = $fileInfo;

$downloadArray = [];
if(!$this->instance->getHideVideoAudio() || $allowOriginal) {
  if(isset($fileContainers['mp3']) && $fileContainers['mp3']->ready) {
    $downloadArray["Download MP3"] = instance_url("fileManager/getDerivativeById/". $fileObjectId . "/mp3");
  }
  if(isset($fileContainers['m4a']) && $fileContainers['m4a']->ready) {
    $downloadArray["Download M4A"] = instance_url("fileManager/getDerivativeById/". $fileObjectId . "/m4a");
  }
}
if($allowOriginal) {
  $downloadArray['Download Original'] = instance_url("fileManager/getOriginal/". $fileObjectId);
}

$menuArray['download'] = $downloadArray;



?>
<script src="/assets/jwplayer/jwplayer.js"></script>
<script type="text/javascript">jwplayer.key="<?=$this->config->item("jwplayer")?>";</script>
<?if(!$embedded):?>
<div class="row assetViewRow">
  <div class="col-md-12 audioColumn">
<?endif?>

  <? if(!isset($fileContainers) || count($fileContainers) == 2):?>
      <p class="alert alert-info">No derivatives found.
        <?if(!$this->user_model->userLoaded):?>
        <?$this->load->view("errors/loginForPermissions")?>
        <?if($embedded):?>
        <?$this->load->view("login/login")?>
        <?endif?>
        <?endif?>
      </p>

    <?else:?>
    <div id="videoElement">Loading the player...</div>

    <style>
    .jwplayer.jw-flag-audio-player .jw-preview {
      display:block;
    }
    /* jw player 7.6 draws video element over audio post.  move it down the dom */
    .jwplayer .jw-media {
      z-index: -1;
    }
    </style>

    <script type="text/javascript">
    jwplayer("videoElement").setup({
      image: "<?=isset($fileContainers['thumbnail2x'])?stripHTTP(instance_url("fileManager/previewImageByFileId/" . $fileObjectId . "/true")):"/assets/icons/512px/mp3.png"?>",
        <?if(isset($fileContainers['mp3'])):?>
      file: "<?=isset($fileContainers['mp3'])?stripHTTP($fileContainers['mp3']->getProtectedURLForFile(null, "+240 minutes", "audio/mp3")):null?>",
      type: "mp3",
        <?endif?>
      width: "100%",
      <?if($embedded):?>
      height: "100%",
      <?else:?>
      height: "480px",
      <?endif?>
      stretching: "<?=$stretchingSetting?>"
    });

    $(".audioColumn").on("remove", function() {
      jwplayer("videoElement").remove();
    });

    </script>
    <?endif?>
<?if(!$embedded):?>
  </div>
</div>
<?endif?>


<?if(!$embedded):?>

<?=renderFileMenu($menuArray)?>

<?$this->load->view("fileHandlers/excerpt",  ["drawerArray"=>$drawerArray])?>

  

<script>
$(function ()
{
  $(".infoPopover").popover({trigger: "focus | click"});
  $(".infoPopover").tooltip({ placement: 'top'});
  $(".excerptTooltip").tooltip({ placement: 'top'});

});
</script>

<?endif?>
