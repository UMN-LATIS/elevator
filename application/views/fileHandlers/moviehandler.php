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
<?$this->load->view("errors/loginForPermissions")?>
<?if($embedded):?>
<?$this->load->view("login/login")?>
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
        jwplayer().onQualityLevels(function(event) {
          if(event.levels.length > 1 && screen.width > 767) {
            jwplayer().setCurrentQuality(1);
          }
          
        });
        
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
          if((haveSeeked || havePaused) && isChrome) {
            console.log("rebuilding");
            rebuilding = true;
            haveSeeked=false;
            havePaused=false;
            weAreHosed = true;
            firstPlay = false;
            currentPosition= jwplayer().getPosition();
            buildPlayer();
            registerJWHandlers();
            jwplayer().play();
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
    
    <?if(!$embedded):?>
    </div>
    
    </div>
    <?endif?>
    
    <?if(!$embedded):?>
    
    <?=renderFileMenu($menuArray)?>
    
    <?$this->load->view("fileHandlers/excerpt", ["drawerArray"=>$drawerArray])?>
    
    <script>
    $(document).ready(function() {
      
      $(".infoPopover").popover({trigger: "focus | click"});
      $(".infoPopover").tooltip({ placement: 'top'});
      $(".excerptTooltip").tooltip({ placement: 'top'});
    });
    
    </script>
    
    <?endif?>