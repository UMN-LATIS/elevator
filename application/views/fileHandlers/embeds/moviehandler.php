<?
$fileObjectId = $fileObject->getObjectId();

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

?>
<script src="/assets/jwplayer/jwplayer.js"></script>
<script type="text/javascript">jwplayer.key="<?=$this->config->item("jwplayer")?>";</script>
<script>

  if(typeof objectId == 'undefined') {
    objectId = "<?=$fileObjectId?>";
  }
</script>

<? if(!isset($fileContainers) || count($fileContainers) == 1):?>
  <p class="alert alert-info">No derivatives found.
  <?if(!$this->user_model->userLoaded):?>
    <?$this->load->view("errors/loginForPermissions")?>
    <?if($embedded):?>
      <?$this->load->view("login/login")?>
    <?endif?>
  <?endif?>
  </p>
<?elseif(isset($fileObject->sourceFile->metadata["spherical"])):?>
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
