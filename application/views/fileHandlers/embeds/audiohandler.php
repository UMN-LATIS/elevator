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

$playlist = [];

$playlist["image"] = isset($fileContainers['thumbnail2x']) 
  ? stripHTTP(instance_url("fileManager/previewImageByFileId/" . $fileObjectId . "/true")) 
  : $this->asset_model->getIconPath() . "mp3.png";

$playlist["sources"] = [];

$playlist["sources"][] = [
    "type"=>"mp3",
    "file"=>isset($fileContainers['mp3'])?stripHTTP($fileContainers['mp3']->getProtectedURLForFile(null, "+240 minutes", "audio/mp3")):null
  ];

$playlist["tracks"] = [];
$captionPath = null;

if(isset($fileContainers['vtt'])) {
  $playlist["tracks"][] = [
    "file" => isset($fileContainers['vtt'])?stripHTTP($fileContainers['vtt']->getProtectedURLForFile(".vtt")):null,
    "kind" => "thumbnails"
  ];
}

$captionPath = null;
if(isset($widgetObject->sidecars) && array_key_exists("captions", $widgetObject->sidecars) && strlen($widgetObject->sidecars['captions'])>5) {
  $captionPath = stripHTTP(instance_url("fileManager/getSidecar/" . $fileObjectId . "/captions"));
  $playlist["tracks"][] = [
    "file" => $captionPath,
    "label" => "English",
    "kind" => "captions"
  ];
}
else {
  $interactiveTranscript = false;
}

$chapterPath=  null;
if(isset($widgetObject->sidecars) && array_key_exists("chapters", $widgetObject->sidecars) && strlen($widgetObject->sidecars['chapters'])>5) {
  $chapterPath = stripHTTP(instance_url("fileManager/getSidecar/" . $fileObjectId . "/chapters"));
  $playlist["tracks"][] = [
    "file" => $chapterPath,
    "kind" => "chapters"
  ];
  
}


?>

<script src="https://cdn.jwplayer.com/libraries/pTP0K0kA.js"></script>
<script src="/assets/js/excerpt.js"></script>


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
.jw-cue {
  background-color: yellow !important;
  height: 15px !important;
}

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
  playlist: <?=json_encode($playlist) ?>,
  width: "100%",
  height: "100%",
  stretching: "<?=$stretchingSetting?>"
});

$(".audioColumn").on("remove", function() {
  jwplayer("videoElement").remove();
});

</script>
<?endif?>



