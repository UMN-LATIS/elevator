<?
$fileObjectId = $fileObject->getObjectId();
?>

<div class="row assetViewRow">
	<div class="col-md-12">
		<?if($allowOriginal):?>
		<a href="<?=instance_url("fileManager/getOriginal/". $fileObjectId)?>">
		<img src="/assets/icons/512px/_blank.png" class="img-responsive embedImage" style="width: 50%; margin-left:auto; margin-right:auto"/>
		</a>
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
			<li class="list-group-item"><strong>File Type: </strong> Zip <button class="showDetails btn btn-primary btn-xs pull-right"><span class="glyphicon glyphicon-align-justify"></span>Show Details</button></li>
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

