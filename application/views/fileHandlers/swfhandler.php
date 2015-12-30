<?
$fileObjectId = $fileObject->getObjectId();
$originalFilename = $fileObject->sourceFile->originalFilename;
// override the filename so flash isn't unhappy. (so we don't serve a content disposition);
$fileObject->sourceFile->originalFilename = null;
$embedLink = instance_url("asset/getEmbed/" . $fileObjectId . "/null/true");
$embedLink = str_replace("http:", "", $embedLink);
$embedLink = str_replace("https:", "", $embedLink);

$embed = htmlentities('<iframe width="560" height="480" src="' . $embedLink . '" frameborder="0" allowfullscreen></iframe>', ENT_QUOTES);

?
?>

<div class="row assetViewRow">
	<div class="col-md-12">
		<?if($allowOriginal):?>
		<OBJECT classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0" WIDTH="100%" HEIGHT="480px" id="swf_embed" ALIGN="">
			<PARAM NAME=movie VALUE="<?=instance_url("fileManager/getOriginal/". $fileObjectId)?>">
			<PARAM NAME=quality VALUE=high>
			<PARAM NAME=bgcolor VALUE=#333399>
			<EMBED src="<?=instance_url("fileManager/getOriginal/". $fileObjectId)?>" quality=high bgcolor=#333399 WIDTH="100%" HEIGHT="480px" NAME="swf_embed" ALIGN="" TYPE="application/x-shockwave-flash" PLUGINSPAGE="http://www.macromedia.com/go/getflashplayer"></EMBED>
		</OBJECT>
				<?else:?>
		<p class="alert alert-info">No derivatives found.
			<?if(!$this->user_model->userLoaded):?>
			You may have access to additional derivatives if you log in.
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
      <?if(count($fileContainers)>0):?>
        <li class="list-group-item assetDetails"><strong>Embed: </strong><input class="form-control embedControl" value="<?=htmlspecialchars($embed, ENT_QUOTES)?>"></li>
       <?endif?>
      </ul>'></span>

		</ul>
	</div>
</div>

