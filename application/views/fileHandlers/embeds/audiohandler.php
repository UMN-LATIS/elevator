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

<script src="/assets/jwplayer/jwplayer.js"></script>
<script src="/assets/js/excerpt.js"></script>
<script type="text/javascript">jwplayer.key="<?=$this->config->item("jwplayer")?>";</script>


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
  height: "100%",
  stretching: "<?=$stretchingSetting?>"
});

$(".audioColumn").on("remove", function() {
  jwplayer("videoElement").remove();
});

</script>
<?endif?>



