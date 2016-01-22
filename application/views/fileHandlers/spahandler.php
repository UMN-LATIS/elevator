<?
$fileObjectId = $fileObject->getObjectId();
$embedLink = stripHTTP(instance_url("asset/getEmbed/" . $fileObjectId . "/null/true"));
$embed = htmlentities('<iframe width="560" height="480" src="' . $embedLink . '" frameborder="0" allowfullscreen></iframe>', ENT_QUOTES);


?>

<?if($embedded):?>
<style>
/* don't constrain the height of the element when embedded */
.fullscreenImageContainer {
	max-height: 100%;
	height: 100%;
}
.outerContainerForFirefox {
	height: 100%;
}
</style>
<?endif?>

<?if(!$embedded):?>
<div class="row assetViewRow">
	<div class="col-md-12">
<?endif?>
		<? if(!isset($fileContainers) || count($fileContainers) == 1):?>
		<p class="alert alert-info">No derivatives found.
			<?if(!$this->user_model->userLoaded):?>
			You may have access to additional derivatives if you log in.
			<?endif?>
		</p>

		<?else:?>

			 <div class="fullscreenImageContainer">

	    			<div class="imageContainer panzoom-element">
					<?if(count($fileContainers)>0):?>
							<img class="img-responsive embedImage imageContent" data-no-retina="true" src="<?=stripHTTP(array_values($fileContainers)[0]->getProtectedURLForFile())?>" />
					<?endif?>
					</div>
					<?if($embedded):?>
						<div class="hoverSlider">
							<span></span><input type="range" class="zoom-range">
							<span class="canFullscreen glyphicon glyphicon-resize-full" data-toggle="tooltip" title="Fullscreen"></span>
						</div>
					<?endif?>
			</div>

		<?endif?>
<?if(!$embedded):?>
	</div>
</div>
<?endif?>

<?if(!$embedded):?>
<div class="row infoRow">
	<div class="col-md-12 imageControls">
		<span class="glyphicon glyphicon-info-sign infoPopover" data-placement="bottom" data-toggle="popover" title="File Info" data-html=true data-content='<ul class="list-group">
			<li class="list-group-item"><strong>File Type: </strong> SPA  </li>
			<li class="list-group-item"><strong>Original Name: </strong> <?=$fileObject->sourceFile->originalFilename?></li>
			<li class="list-group-item assetDetails"><strong>File Size: </strong><?=byte_format($fileObject->sourceFile->metadata["filesize"])?></li>
			<?foreach($fileObject->sourceFile->metadata as $key=>$value):?>
			<?if($key == "filesize") continue;?>
			<?if(!$value) continue;?>
			<li class="list-group-item assetDetails"><strong><?=(!is_numeric($key))?$key:null?>: </strong><?=$value?></li>
			<?endforeach?>
		</ul>'></span>

      	<span class="glyphicon glyphicon-download infoPopover" data-placement="bottom" data-toggle="popover" title="Download" data-html="true" data-content='
      		<ul>
      		<?if(isset($fileContainers['screen'])):?>
      			<li class="list-group-item assetDetails"><a href="<?=instance_url("fileManager/getDerivativeById/". $fileObjectId . "/screen")?>">Download Derivative</a></li>
      		<?endif?>
			<?if($allowOriginal):?>
			<li class="list-group-item assetDetails"><a href="<?=instance_url("fileManager/getOriginal/". $fileObjectId)?>">Download Original</a></li>
			<?endif?>
			</ul>'></span>
			<?if(count($fileContainers)>0):?>
		<span class="glyphicon glyphicon-share infoPopover" data-placement="bottom" data-toggle="popover" title="Share" data-html="true" data-content='<ul>
			 <li class="list-group-item assetDetails"><strong>Embed: </strong><input class="form-control embedControl" value="<?=htmlspecialchars($embed, ENT_QUOTES)?>"></li>
			</ul>'></span>
		<?endif?>
		<?if(count($fileContainers)>0 && !array_key_exists("tiled", $fileContainers)):?>
			<span></span><input type="range" class="zoom-range">
			<span class="canFullscreen glyphicon glyphicon-resize-full" data-toggle="tooltip" title="Fullscreen"></span>
		<?endif?>

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

<?if(count($fileContainers)>0):?>
	<script>
	$(document).on("click", ".canFullscreen", function() {
		if($.fullscreen.isNativelySupported()) {
			$(".imageContainer").first().fullscreen({ "toggleClass": "imageFullscreen"});
		}
	});
	$(document).ready(function(){

		$(".panzoom-element").panzoom({
		    contain: 'invert',
		    minScale: 1,
		    $zoomRange: $(".zoom-range")
		});

	});
	</script>
<?endif?>
