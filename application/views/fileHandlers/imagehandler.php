<?
$fileObjectId = $fileObject->getObjectId();
$embedLink = stripHTTP(instance_url("asset/getEmbed/" . $fileObjectId . "/null/true"));
$embed = htmlentities('<iframe width="560" height="480" src="' . $embedLink . '" frameborder="0" allowfullscreen></iframe>', ENT_QUOTES);






$menuArray = [];
if(count($fileContainers)>0) {
  $menuArray['embed'] = $embed;
  $menuArray['embedLink'] = $embedLink;
}

$fileInfo = [];
$fileInfo["File Type"] = "Image";
$fileInfo["Original Name"] = $fileObject->sourceFile->originalFilename;
$fileInfo["File Size"] = $fileObject->sourceFile->metadata["filesize"];
$fileInfo["Image Size"] = $fileObject->sourceFile->metadata["width"] . "x" . $fileObject->sourceFile->metadata["height"];

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

if(isset($fileObject->sourceFile->metadata["exif"])) {
	$fileInfo["Exif"] = $fileObjectId;
}



$menuArray['fileInfo'] = $fileInfo;

$downloadArray = [];

if(isset($fileContainers['screen']) && $fileContainers['screen']->ready) {
$downloadArray["Download Derivative"] = instance_url("fileManager/getDerivativeById/". $fileObjectId . "/screen");
}

if($allowOriginal) {
  $downloadArray['Download Original'] = instance_url("fileManager/getOriginal/". $fileObjectId);
}

$menuArray['download'] = $downloadArray;



if(count($fileContainers)>0 && !array_key_exists("tiled", $fileContainers) && !isset($fileObject->sourceFile->metadata["spherical"])) {
	$menuArray['zoom'] = true;	
}



?>

<?if($embedded):?>
<style>
/* don't constrain the height of the element when embedded */
.fullscreenImageContainer {
	max-height: 100%;
	height: 100%;
}
.fixedHeightContainer {
	height: 100%;
	max-height: 100%
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
			<?$this->load->view("errors/loginForPermissions")?>
			<?endif?>
		</p>

		<?else:?>
			<?if($fileObject->sourceFile->getType() == "svs"):?>
				<?=$this->load->view("fileHandlers/imageHandler_svs", ["fileObject"=>$fileObject], true)?>
			<?elseif(array_key_exists("tiled", $fileContainers)):?>
				<?=$this->load->view("fileHandlers/imageHandler_tiled", ["fileObject"=>$fileObject], true)?>
			<?else:?>
				<?if(isset($fileObject->sourceFile->metadata["spherical"])):?>
					<div style="height:500px">
					 	<iframe frameborder=0 width="100%" height=100% scrolling="no" allowfullscreen src="/assets/vrview/index.html?image=<?=urlencode(stripHTTP(array_values($fileContainers)[0]->getProtectedURLForFile()))?>&is_stereo=<?=isset($fileObject->sourceFile->metadata["stereo"])?"true":"false"?>"></iframe>
					</div>
				<?else:?>
				<?if(array_values($fileContainers)[0]->derivativeType == "thumbnail"):?>
					<p class="alert alert-info">Displaying thumbnail image.
						<?if(!$this->user_model->userLoaded):?>
						<?$this->load->view("errors/loginForPermissions")?>
						<?if($embedded):?>
						<?$this->load->view("login/login")?>
						<?endif?>
						<?endif?>
					</p>
					<?endif?>
					 <div class="fullscreenImageContainer">

			    			<div class="imageContainer panzoom-element">
							<?if(count($fileContainers)>0):?>
									<img class="img-responsive embedImage imageContent" src="<?=stripHTTP(array_values($fileContainers)[0]->getProtectedURLForFile())?>" alt="<?=($widgetObject && $widgetObject->fileDescription )?htmlspecialchars($widgetObject->fileDescription, ENT_COMPAT):null?>" />
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

			<?endif?>

	<?endif?>
<?if(!$embedded):?>
	</div>
</div>
<?endif?>

<?if(!$embedded):?>
<?=renderFileMenu($menuArray)?>


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

	function debounce(func, wait, immediate) {
		var timeout;
		return function() {
			var context = this, args = arguments;
			var later = function() {
				timeout = null;
				if (!immediate) func.apply(context, args);
			};
			var callNow = immediate && !timeout;
			clearTimeout(timeout);
			timeout = setTimeout(later, wait);
			if (callNow) func.apply(context, args);
		};
	};

	// chrome has an issue in which it won't re-draw an image after scaling, so it's blurry. This forces a redraw.
	$('.panzoom-element').on('panzoomzoom', debounce( function () {
	    this.style.display='none';
	    this.offsetHeight;
	    this.style.display='';
	}, 100));

	</script>
<?endif?>
