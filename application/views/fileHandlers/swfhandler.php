<?
$fileObjectId = $fileObject->getObjectId();
$originalFilename = $fileObject->sourceFile->originalFilename;
// override the filename so flash isn't unhappy. (so we don't serve a content disposition);
$fileObject->sourceFile->originalFilename = null;
$embedLink = instance_url("asset/getEmbed/" . $fileObjectId . "/null/true");
$embedLink = str_replace("http:", "", $embedLink);
$embedLink = str_replace("https:", "", $embedLink);

$embed = htmlentities('<iframe width="560" height="480" src="' . $embedLink . '" frameborder="0" allowfullscreen></iframe>', ENT_QUOTES);

?>

<div class="row assetViewRow">
	<div class="col-md-12">
		<?if($allowOriginal):?>
		<object type="application/x-shockwave-flash" data="<?=stripHTTP($fileObject->sourceFile->getProtectedURLForFile())?>" width="100%" height="480px">
  	<param name="movie" value="<?=stripHTTP($fileObject->sourceFile->getProtectedURLForFile())?>" />
  <param name="quality" value="high"/>
</object>

				<?else:?>
		<p class="alert alert-info">No derivatives found.
			<?if(!$this->user_model->userLoaded):?>
			<?=$this->load->view("errors/loginForPermissions")?>
			<?endif?>
		</p>

		<?endif?>
	</div>
</div>

<div class="row infoRow">
  <div class="col-md-12">
  	 <span class="glyphicon glyphicon-info-sign infoPopover" data-placement="bottom" data-toggle="popover" title="File Info" data-html=true data-content='<ul class="list-group">
           <li class="list-group-item"><strong>File Type: </strong> SWF </li>
      <li class="list-group-item assetDetails"><strong>Original Name: </strong><?=$originalFilename?></li>

      <li class="list-group-item assetDetails"><strong>Description: </strong><?=htmlentities($widgetObject->fileDescription, ENT_QUOTES)?></li>
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
      <?if($allowOriginal):?>
        <li class="list-group-item assetDetails"><strong>Embed: </strong><input class="form-control embedControl" value="<?=htmlspecialchars($embed, ENT_QUOTES)?>"></li>
       <?endif?>
      </ul>'></span>

		</ul>
	</div>
</div>

<script>
$(document).ready(function() {

  $(".infoPopover").popover({trigger: "focus | click"});
  $(".infoPopover").tooltip({ placement: 'top'});
  $(".excerptTooltip").tooltip({ placement: 'top'});
});

</script>