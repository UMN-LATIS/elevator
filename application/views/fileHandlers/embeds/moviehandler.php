<?
$fileObjectId = $fileObject->getObjectId();

$mediaArray = array();
if(isset($fileContainers['stream'])) {
  $entry["type"] = "hls";
  $entry["file"] = stripHTTP(instance_url("/fileManager/getStream/" . $fileObjectId . "/base"));
  $entry["label"] = "Streaming";
  $mediaArray["stream"] = $entry;
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
  if(array_key_exists("stream", $mediaArray)) {
    $derivatives[] = $mediaArray["stream"];
  }
  if(array_key_exists("mp4sd", $mediaArray)) {
    $derivatives[] = $mediaArray["mp4sd"];
  }
  if(array_key_exists("mp4hd", $mediaArray)) {
    $derivatives[] = $mediaArray["mp4hd"];
  }

  
}
else {
  if(array_key_exists("stream", $mediaArray)) {
    $derivatives[] = $mediaArray["stream"];
  }
  if(array_key_exists("mp4sd", $mediaArray)) {
    $derivatives[] = $mediaArray["mp4sd"];
  }
  if(array_key_exists("mp4hd", $mediaArray)) {
    $derivatives[] = $mediaArray["mp4hd"];
  }

  
}

?>
<script src="https://cdn.jwplayer.com/libraries/pTP0K0kA.js"></script>

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

  var haveSeeked = false;
  var havePaused = false;
  var currentPosition = null;
  var firstPlay = false;
  var isChrome = /Chrome/.test(navigator.userAgent) && /Google Inc/.test(navigator.vendor); 
  var weAreHosed = false;
  var firstPlay = false;
  var seekTime = null;
  var needSeek = false;
  function buildPlayer() {
    jwplayer("videoElement").setup({
      ga: { label:"label"},
      playlist: [{
        image: '<?=isset($fileContainers['imageSequence'])?stripHTTP($fileContainers['imageSequence']->getProtectedURLForFile("/2")):null?>',
        <?=(isset($fileObject->sourceFile->metadata["spherical"])?("stereomode:".(isset($fileObject->sourceFile->metadata["stereo"])?"'stereoscopicLeftRight',":"'monoscopic',")):null)?>
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
        preload: 'none'
      });
    }
    
    function registerJWHandlers() {
      jwplayer().onReady(function(event) {
        // jwplayer().onQualityLevels(function(event) {
        //   if(event.levels.length > 1 && screen.width > 767) {
        //     jwplayer().setCurrentQuality(1);
        //   }
          
        // });
        
        jwplayer().on('seek', function(event) {
          haveSeeked=true;
          if(jwplayer().getState('paused') == 'paused') {
            seekTime = event.offset;
            needSeek = true;
          }
          
        });
        jwplayer().on('pause', function(event) {
          havePaused=true;
        });
        jwplayer().on('play', function(event) {
          if(!firstPlay) {
            firstPlay = true;
            return;
          }
          else {
            console.log("on second play");
          }

          if(event.playReason == "external") {
            return;
          }

          if((haveSeeked || havePaused) && isChrome) {
            var playlist = jwplayer().getPlaylist();

            if(playlist[0].label == "Streaming" || playlist[0].sources[0].label == "Streaming") {
              return;
            }

            rebuilding = true;
            haveSeeked=false;
            havePaused=false;
            weAreHosed = true;
            firstPlay = false;
            currentPosition= jwplayer().getPosition();
            buildPlayer();
            registerJWHandlers();
            // jwplayer().play();
            if(needSeek) {
              console.log("seeking to existing location" + seekTime)
              jwplayer().seek(seekTime);
              needSeek = false;
            }
            else {
              console.log("seeking to new position:" + currentPosition)
              jwplayer().seek(currentPosition);
            }
            needSeek = false;
            currentPosition = null;
            
            
            
          }
          
        })
        
      });
      
    }

    buildPlayer();
    registerJWHandlers();

        // JW player is dumb about default to HD footage so we do it manually if possible
    
    
    $(".videoColumn").on("remove", function() {
      jwplayer("videoElement").remove();
    });

  </script>
  <?endif?>
