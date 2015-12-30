<?
$fileObjectId = $fileObject->getObjectId();
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
		<ul class="list-group">
			<li class="list-group-item"><strong>File Type: </strong> SWF <button class="showDetails btn btn-primary btn-xs pull-right"><span class="glyphicon glyphicon-align-justify"></span>Show Details</button></li>
			<li class="list-group-item assetDetails"><strong>Original Name: </strong><?=$fileObject->sourceFile->originalFilename?></li>
			<?if($widgetObject && $widgetObject->fileDescription ):?>

      <li class="list-group-item assetDetails"><strong>Description: </strong><?=htmlentities($widgetObject->fileDescription, ENT_QUOTES)?></li>
      <?endif?>

			<li class="list-group-item assetDetails"><strong>File Size: </strong><?=byte_format($fileObject->sourceFile->metadata["filesize"])?></li>
			<?if($allowOriginal):?>
			<li class="list-group-item assetDetails"><a href="<?=instance_url("fileManager/getOriginal/". $fileObjectId)?>">Download Original</a></li>
			<?endif?>

		</ul>
	</div>
</div>

