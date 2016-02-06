<?
$fileObjectId = $fileObject->getObjectId();

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

?>
<script src="/assets/jwplayer/jwplayer.js"></script>
<script type="text/javascript">jwplayer.key="<?=$this->config->item("jwplayer")?>";</script>
<?if(!$embedded):?>
<div class="row assetViewRow">
  <div class="col-md-12 audioColumn">
<?endif?>

  <? if(!isset($fileContainers) || count($fileContainers) == 0):?>
      <p class="alert alert-info">No derivatives found.
        <?if(!$this->user_model->userLoaded):?>
        You may have access to additional derivatives if you log in.
        <?endif?>
      </p>

    <?else:?>
    <div id="videoElement">Loading the player...</div>

    <style>
    .jwplayer.jw-flag-audio-player .jw-preview {
      display:block;
    }
    </style>

    <script type="text/javascript">
    jwplayer("videoElement").setup({
      image: "<?=isset($fileContainers['thumbnail2x'])?stripHTTP(instance_url("fileManager/previewImageByFileId/" . $fileObjectId . "/true")):"/assets/icons/512px/mp3.png"?>",
        <?if(isset($fileContainers['mp3'])):?>
      file: "<?=isset($fileContainers['mp3'])?stripHTTP(instance_url("fileManager/getDerivativeById/". $fileObjectId . "/mp3")):null?>",
      type: "mp3",
        <?endif?>
      width: "100%",
      <?if($embedded):?>
      height: "100%",
      <?else:?>
      height: "480px",
      <?endif?>
      stretching: "exactfit"
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


<div class="row infoRow">
  <div class="col-md-12">
    <span class="glyphicon glyphicon-info-sign infoPopover" data-placement="bottom" data-toggle="popover" title="File Info" data-html=true data-content='<ul class="list-group">
      <li class="list-group-item"><strong>File Type: </strong> Movie  </li>
      <li class="list-group-item"><strong>Original Name: </strong> <?=$fileObject->sourceFile->originalFilename?></li>
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
        <?if(isset($fileContainers['mp3']) && $fileContainers['mp3']->ready && (!$this->instance->getHideVideoAudio() || $allowOriginal)):?>
      <li class="list-group-item assetDetails"><a href="<?=instance_url("fileManager/getDerivativeById/". $fileObjectId . "/mp3")?>">Download MP3</a></li>
      <?endif?>
      <?if($allowOriginal):?>
      <li class="list-group-item assetDetails"><a href="<?=instance_url("fileManager/getOriginal/". $fileObjectId)?>">Download Original</a></li>
      <?endif?>
      </ul>'></span>
    <span class="glyphicon glyphicon-share infoPopover" data-placement="bottom" data-toggle="popover" title="Share" data-html="true" data-content='
          <ul>
      <?if(count($fileContainers)>0):?>
       <li class="list-group-item assetDetails"><strong>Embed: </strong><input class="form-control embedControl" value="<?=htmlspecialchars($embed, ENT_QUOTES)?>"></li>
       <?endif?>
      </ul>'></span>
      <?if(count($fileContainers)>0):?>

        <?if(count($drawerArray)>0):?><span data-toggle="collapse" data-target="#excerptGroup" class="glyphicon glyphicon-time excerptTooltip" data-toggle="tooltip" title="Create Excerpt"></span><?endif?>

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
$(function ()
{
  $(".infoPopover").popover({trigger: "focus | click"});
  $(".infoPopover").tooltip({ placement: 'top'});
  $(".excerptTooltip").tooltip({ placement: 'top'});

});
</script>

<?endif?>
