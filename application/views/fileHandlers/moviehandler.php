<?
$fileObjectId = $fileObject->getObjectId();
$drawerArray = array();
if($this->user_model->userLoaded) {
    foreach($this->user_model->getDrawers(true) as $drawer) {
        $drawerArray[] = $drawer;
    }
}

$mediaArray = array();

if(isset($fileContainers['streaming'])) {
  $entry["type"] = "hls";
  $entry["file"] = stripHTTP($fileContainers['streaming']->getProtectedURLForFile());
  $entry["label"] = "Streaming";
  $mediaArray["streaming"] = $entry;
}

if(isset($fileContainers['mp4sd'])) {
  $entry["type"] = "mp4";
  $entry["file"] = stripHTTP($fileContainers['mp4sd']->getProtectedURLForFile());
  $entry["label"] = "SD";
  $mediaArray["mp4sd"] = $entry;
}

if(isset($fileContainers['mp4hd'])) {
  $entry["type"] = "mp4";
  $entry["file"] = stripHTTP($fileContainers['mp4hd']->getProtectedURLForFile());
  $entry["label"] = "HD";
  $mediaArray["mp4hd"] = $entry;
}

if(isset($fileContainers['mp4hd1080'])) {
  $entry["type"] = "mp4";
  $entry["file"] = stripHTTP($fileContainers['mp4hd1080']->getProtectedURLForFile());
  $entry["label"] = "HD1080";
  $mediaArray["mp4hd1080"] = $entry;
}

$derivatives = array();
if($fileObject->sourceFile->metadata["duration"] < 300) {

  if(array_key_exists("mp4sd", $mediaArray)) {
    $derivatives[] = $mediaArray["mp4sd"];
  }
  if(array_key_exists("mp4hd", $mediaArray)) {
    $derivatives[] = $mediaArray["mp4hd"];
  }
  if(array_key_exists("streaming", $mediaArray)) {
    $derivatives[] = $mediaArray["streaming"];
  }

}
else {
  if(array_key_exists("mp4sd", $mediaArray)) {
    $derivatives[] = $mediaArray["mp4sd"];
  }
  if(array_key_exists("mp4hd", $mediaArray)) {
    $derivatives[] = $mediaArray["mp4hd"];
  }
  if(array_key_exists("streaming", $mediaArray)) {
    $derivatives[] = $mediaArray["streaming"];
  }

}


$embedLink = instance_url("asset/getEmbed/" . $fileObjectId . "/null/true");
$embedLink = str_replace("http:", "", $embedLink);
$embedLink = str_replace("https:", "", $embedLink);

$embed = htmlentities('<iframe width="560" height="480" src="' . $embedLink . '" frameborder="0" allowfullscreen></iframe>', ENT_QUOTES);

?>
<script src="/assets/jwplayer/jwplayer.js"></script>
<script type="text/javascript">jwplayer.key="<?=$this->config->item("jwplayer")?>";</script>
<script>

if(typeof objectId == 'undefined') {
    objectId = "<?=$fileObjectId?>";
}
</script>



<?if(!$embedded):?>
<div class="row assetViewRow" >
  <div class="col-md-12 videoColumn">

<?endif?>
    <? if(!isset($fileContainers) || count($fileContainers) == 1):?>
      <p class="alert alert-info">No derivatives found.
        <?if(!$this->user_model->userLoaded):?>
        <?=$this->load->view("errors/loginForPermissions")?>
        <?if($embedded):?>
        <?=$this->load->view("login/login")?>
        <?endif?>
        <?endif?>
      </p>

    <?elseif(isset($fileObject->sourceFile->metadata["spherical"])):?>
    <?# this file must be uploaded to s3 for these to work in safari as of 2016 ?>
            <script src="http://s3.amazonaws.com/elevator-assets/vrview/build/device-motion-sender.min.js"></script>
            <iframe class="vrview" frameborder=0 width="100%" height=480px scrolling="no" allowfullscreen src="http://s3.amazonaws.com/elevator-assets/vrview/index.html?video=<?=urlencode(striphttp($fileContainers['mp4hd1080']->getProtectedURLForFile()))?>&is_stereo=<?=isset($fileObject->sourceFile->metadata["stereo"])?"true":"false"?>"></iframe>
    <?else:?>
    <div id="videoElement">Loading the player...</div>

    <script type="text/javascript">
    jwplayer("videoElement").setup({
      ga: { label:"label"},
      playlist: [{
        image: '<?=isset($fileContainers['imageSequence'])?stripHTTP($fileContainers['imageSequence']->getProtectedURLForFile("/2")):null?>',
        sources: [
        <?foreach($derivatives as $entry):?>
        {
          type: "<?=$entry["type"]?>",
          file: "<?=$entry["file"]?>",
          label: "<?=$entry["label"]?>"
        },
        <?endforeach?>
        ],
        tracks: [
        <?if(isset($fileContainers['vtt'])):?>
        {
          file: "<?=isset($fileContainers['vtt'])?stripHTTP($fileContainers['vtt']->getProtectedURLForFile(".vtt")):null?>",
          kind: "thumbnails"
        }
        <?endif?>
        <?if(isset($widgetObject->sidecars) && array_key_exists("captions", $widgetObject->sidecars) && strlen($widgetObject->sidecars['captions'])>5):?>
        <?if(isset($fileContainers['vtt'])):?>
        ,
        <?endif?>
        {
            file: "<?=stripHTTP(instance_url("fileManager/getSidecar/" . $fileObjectId . "/captions"))?>",
            label: "English",
            kind: "captions"
        }
        <?endif?>
        ]
      }],
      width: "100%",
      height: "100%",
    });

    // JW player is dumb about default to HD footage so we do it manually if possible
    jwplayer().onReady(function(event) {
      jwplayer().onQualityLevels(function(event) {
        if(event.levels.length > 1 && screen.width > 767) {
          jwplayer().setCurrentQuality(1);
        }

      });
    });

    $(".videoColumn").on("remove", function() {
      jwplayer("videoElement").remove();
    });

    </script>
  <?endif?>
<?if(!$embedded):?>
  </div>

</div>
<?endif?>

<?if(!$embedded):?>

<div class="row infoRow">
  <div class="col-md-12">
    <span class="glyphicon glyphicon-info-sign infoPopover" data-placement="bottom" data-toggle="popover" title="File Info" data-html=true data-content='<ul class="list-group">
           <li class="list-group-item"><strong>File Type: </strong> Movie </li>
      <li class="list-group-item assetDetails"><strong>Original Name: </strong><?=htmlentities($fileObject->sourceFile->originalFilename, ENT_QUOTES)?></li>
      <li class="list-group-item assetDetails"><strong>Video Size: </strong><?=$fileObject->sourceFile->metadata["width"]?> x <?=$fileObject->sourceFile->metadata["height"]?></li>
      <li class="list-group-item assetDetails"><strong>Duration: </strong><?= gmdate("H:i:s", $fileObject->sourceFile->metadata["duration"])?></li>
      <?if($widgetObject && $widgetObject->fileDescription ):?>

      <li class="list-group-item assetDetails"><strong>Description: </strong><?=htmlentities($widgetObject->fileDescription, ENT_QUOTES)?></li>
      <?endif?>
      <?if($widgetObject && $widgetObject->getLocationData()):?>
      <li class="list-group-item assetDetails"><strong>Location: </strong><A href="#mapModal"  data-toggle="modal" data-latitude="<?=$widgetObject->getLocationData()[1]?>" data-longitude="<?=$widgetObject->getLocationData()[0]?>">View Location</a></li>
      <?endif?>
      <?if($widgetObject && $widgetObject->getDateData()):?>
      <li class="list-group-item assetDetails"><strong>Date: </strong><?=$widgetObject->getDateData()?></li>
      <?endif?>
      <li class="list-group-item assetDetails"><strong>File Size: </strong><?=byte_format($fileObject->sourceFile->metadata["filesize"])?></li>
    </ul>
      '></span>

        <span class="glyphicon glyphicon-download infoPopover" data-placement="bottom" data-toggle="popover" title="Download" data-html="true" data-content='
          <ul>
    <?if(isset($fileContainers['mp4hd']) && $fileContainers['mp4hd']->ready && (!$this->instance->getHideVideoAudio() || $allowOriginal)):?>
      <li class="list-group-item assetDetails"><a href="<?=instance_url("fileManager/getDerivativeById/". $fileObjectId . "/mp4hd")?>">Download HD MP4</a></li>
      <?endif?>
      <?if(isset($fileContainers['mp4sd']) && $fileContainers['mp4sd']->ready && (!$this->instance->getHideVideoAudio() || $allowOriginal)):?>
      <li class="list-group-item assetDetails"><a href="<?=instance_url("fileManager/getDerivativeById/". $fileObjectId . "/mp4sd")?>">Download SD MP4</a></li>
      <?endif?>
      <?if($allowOriginal):?>
      <li class="list-group-item assetDetails"><a href="<?=instance_url("fileManager/getOriginal/". $fileObjectId)?>">Download Original</a></li>
      <?endif?>
      </ul>'></span>
    <span class="glyphicon glyphicon-share infoPopover" data-placement="bottom" data-toggle="popover" title="Share" data-html="true" data-content='<ul>
      <?if(count($fileContainers)>0):?>
        <li class="list-group-item assetDetails"><strong>Embed: </strong><input class="form-control embedControl" value="<?=htmlspecialchars($embed, ENT_QUOTES)?>"></li>
       <?endif?>
      </ul>'></span>
      <?if(count($fileContainers)>0):?>
        <?if(count($drawerArray)>0):?>
        <span data-toggle="collapse" data-target="#excerptGroup" class="glyphicon glyphicon-time excerptTooltip" data-toggle="tooltip" title="Create Excerpt"></span><?endif?>

      <?endif?>
  </div>
</div>
<div class="row rowPadding excerpt collapse out" id="excerptGroup">
<form action="" method="POST" class="excerptForm" id="excerptForm" role="form">
    <input type="hidden" name="fileHandlerId" value="<?=$fileObjectId?>"/>
    <input type="hidden" name="objectId" value="<?=$fileObject->parentObjectId?>"/>
      <div class="form-group col-md-2">
        <label class="sr-only" for="">Excerpt Title</label>
        <input type="text" id="label" name="label" class="form-control" id="" placeholder="Title">
      </div>
      <div class="form-group col-md-3">
      <div class="input-group ">
        <input type="text" name="startTimeVisible" id="startTimeVisible" class="form-control" id="" placeholder="" disabled>
        <input type="hidden" name="startTime" id="startTime" class="form-control" id="" placeholder="">
        <span class="input-group-btn">
          <button type="button" class="btn btn-primary setStart">Start</button>
        </span>
      </div>
    </div>
      <div class="form-group col-md-3">
      <div class="input-group ">
        <input type="text"  name="endTimeVisible" id="endTimeVisible" class="form-control"  id="" placeholder="" disabled>
        <input type="hidden"  name="endTime" id="endTime" class="form-control"  id="" placeholder="">
        <span class="input-group-btn">
          <button type="button"  class="btn btn-primary setEnd">End</button>
        </span>
      </div>
    </div>
      <div class="form-group col-md-2">
          <label class="sr-only" for="">Drawer:</label>
          <select name="drawerList" id="drawerList" class="form-control">
            <?if($this->user_model->userLoaded): foreach($drawerArray as $drawer):?>
              <option value="<?=$drawer->getId()?>"><?=$drawer->getTitle()?></option>
            <?endforeach; endif;?>
          </select>
        </div>
      <div class="col-md-2">
        <button type="submit" class="btn btn-primary btn-block">Save</button>
      </div>
    </form>
    </div>

<script>
$(document).ready(function() {

  $(".infoPopover").popover({trigger: "focus | click"});
  $(".infoPopover").tooltip({ placement: 'top'});
  $(".excerptTooltip").tooltip({ placement: 'top'});
});

</script>

<?endif?>