<?
$fileObjectId = $fileObject->getObjectId();

$embedLink = instance_url("asset/getEmbed/" . $fileObjectId . "/null/true");
$embedLink = str_replace("http:", "", $embedLink);
$embedLink = str_replace("https:", "", $embedLink);

$embed = htmlentities('<iframe width="560" height="480" src="' . $embedLink . '" frameborder="0" allowfullscreen></iframe>', ENT_QUOTES);


?>

<div class="row assetViewRow">
	<div class="col-md-12">
		<?if($allowOriginal):?>
		<a href="<?=instance_url("fileManager/getOriginal/". $fileObjectId)?>">
		<img src="/assets/icons/512px/_blank.png" class="img-responsive embedImage" style="width: 50%; margin-left:auto; margin-right:auto"/>
		</a>
		<p>No preview available for this asset.  Click the icon above to download a copy.</p>
				<?else:?>
		<p class="alert alert-info">No derivatives found.
			<?if(!$this->user_model->userLoaded):?>
			You may have access to additional derivatives if you log in.
			<?endif?>
		</p>

		<?endif?>
	</div>
</div>

<?if(!$embedded):?>
<div class="row infoRow">
	<div class="col-md-12">
		<span class="glyphicon glyphicon-info-sign infoPopover" data-placement="bottom" data-toggle="popover" title="File Info" data-html=true data-content='<ul class="list-group">
			<li class="list-group-item"><strong>File Type: </strong> Zip <button class="showDetails btn btn-primary btn-xs pull-right"><span class="glyphicon glyphicon-align-justify"></span>Show Details</button></li>
			<li class="list-group-item assetDetails"><strong>Original Name: </strong><?=$fileObject->sourceFile->originalFilename?></li>
			<?if($widgetObject && $widgetObject->fileDescription ):?>

      			<li class="list-group-item assetDetails"><strong>Description: </strong><?=htmlentities($widgetObject->fileDescription, ENT_QUOTES)?></li>
      		<?endif?>
			<li class="list-group-item assetDetails"><strong>File Size: </strong><?=byte_format($fileObject->sourceFile->metadata["filesize"])?></li>
		</ul>'></span>
		<span class="glyphicon glyphicon-download infoPopover" data-placement="bottom" data-toggle="popover" title="Download" data-html="true" data-content='
      		<ul>
			<?if($allowOriginal):?>
			<li class="list-group-item assetDetails"><a href="<?=instance_url("fileManager/getOriginal/". $fileObjectId)?>">Download Original</a></li>
			<?endif?>
		</ul>'></span>
		<span class="glyphicon glyphicon-share infoPopover" data-placement="bottom" data-toggle="popover" title="Share" data-html="true" data-content='
		<ul class="list-group">
     		<?if($allowOriginal):?>
      			<li class="list-group-item assetDetails"><strong>Embed: </strong><input class="form-control embedControl" value="<?=htmlspecialchars($embed, ENT_QUOTES)?>"></li>
      		<?endif?>
		</ul>'></span>
	</div>
</div>
<script>
$(function ()
{
	$(".infoPopover").popover({trigger: "focus | click"});
	$(".infoPopover").tooltip({ placement: 'top'});

});
</script>
<?endif?>
