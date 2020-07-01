<style>
/* don't constrain the height of the element when embedded */
.fullscreenImageContainer {
	max-height: 100%;
	height: 100%;
}
.imageContainer {
	display: flex;
	align-items:center;
}
.fixedHeightContainer {
	height: 100%;
	max-height: 100%
}
.outerContainerForFirefox {
	height: 100%;
}
</style>

		<? if(!isset($fileContainers) || count($fileContainers) == 1):?>
		<p class="alert alert-info">No derivatives found.
			<?if(!$this->user_model->userLoaded):?>
			<?$this->load->view("errors/loginForPermissions")?>
			<?endif?>
		</p>

		<?else:?>
			<?$uploadWidget = $fileObject->getUploadWidget();?>

			<?if($uploadWidget->parentWidget->enableIframe && isset($uploadWidget->sidecars) && array_key_exists("iframe", $uploadWidget->sidecars)  && strlen($uploadWidget->sidecars["iframe"]) > 0):
				echo $this->load->view("fileHandlers/imageHandler_iframe", ["fileObject"=>$fileObject], true);
			elseif(isWholeSlideImage($fileObject->sourceFile) || (isset($uploadWidget->parentWidget->enableAnnotation) && $uploadWidget->parentWidget->enableAnnotation)):
			?>
				<?=$this->load->view("fileHandlers/embeds/imageHandler_svs", ["fileObject"=>$fileObject], true)?>
			<?elseif(array_key_exists("tiled", $fileContainers)):?>
				<?
				if(isset($uploadWidget->parentWidget->enableDendro) && $uploadWidget->parentWidget->enableDendro) {
					echo $this->load->view("fileHandlers/embeds/imageHandler_dendro", ["fileObject"=>$fileObject], true);
				}
				else {
					echo $this->load->view("fileHandlers/embeds/imageHandler_tiled", ["fileObject"=>$fileObject], true);
				}
				?>

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
								<div class="hoverSlider">
									<span></span><input type="range" class="zoom-range">
									<span class="canFullscreen glyphicon glyphicon-fullscreen" data-toggle="tooltip" title="Fullscreen"></span>
								</div>
					
					</div>

				<?endif?>

			<?endif?>

	<?endif?>






<?if(count($fileContainers)>0):?>
	<script>

	function runningFromElevatorHost() {
		$(".hoverSlider").hide();
	}

	function zoom(target) {
		$(".panzoom-element").panzoom("zoom", parseInt(target)/2/10);
	}


	$(document).on("click", ".canFullscreen", function() {
		if($.fullscreen.isNativelySupported()) {
			$(".imageContainer").first().fullscreen({ "toggleClass": "imageFullscreen"});
		}
	});
	$(document).ready(function(){
		// attach zoom handler after load, or chrome gets grumpy
		$(".embedImage").on("load",function() {
			$(".panzoom-element").panzoom({
		    	contain: 'invert',
		    	minScale: 1,
		    	$zoomRange: $(".zoom-range")
			});
		
		}).each(function(){
  			if(this.complete) {
    			$(this).trigger('load');
  			}
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
	

	</script>
<?endif?>
