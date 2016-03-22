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
			<?=$this->load->view("errors/loginForPermissions")?>
			<?endif?>
		</p>

		<?else:?>

			<?if(array_key_exists("tiled", $fileContainers)):?>
			    <script type="text/javascript" src='/assets/seadragon/openseadragon.js'></script>
			    <style type="text/css">

      			.openseadragon1 {
          			width: 100%;
          			height: 600px;
      			}

    			</style>
			    <div id="contentDiv" class="openseadragon1"></div>
			    <script type="text/javascript">
			    	var zoomLevelCache = {};
			    	zoomLevelCache[0] = <?=json_encode($fileObject->getSignedURLs("tiled", true, "tiledBase_files/0/"))?>;
			    	zoomLevelCache[1] = <?=json_encode($fileObject->getSignedURLs("tiled", true, "tiledBase_files/1/"))?>;
			    	zoomLevelCache[2] = <?=json_encode($fileObject->getSignedURLs("tiled", true, "tiledBase_files/2/"))?>;
			    	zoomLevelCache[3] = <?=json_encode($fileObject->getSignedURLs("tiled", true, "tiledBase_files/3/"))?>;
			    	zoomLevelCache[4] = <?=json_encode($fileObject->getSignedURLs("tiled", true, "tiledBase_files/4/"))?>;
			    	zoomLevelCache[5] = <?=json_encode($fileObject->getSignedURLs("tiled", true, "tiledBase_files/5/"))?>;
			    	zoomLevelCache[6] = <?=json_encode($fileObject->getSignedURLs("tiled", true, "tiledBase_files/6/"))?>;
			    	zoomLevelCache[7] = <?=json_encode($fileObject->getSignedURLs("tiled", true, "tiledBase_files/7/"))?>;
			    	zoomLevelCache[8] = <?=json_encode($fileObject->getSignedURLs("tiled", true, "tiledBase_files/8/"))?>;
			    	zoomLevelCache[9] = <?=json_encode($fileObject->getSignedURLs("tiled", true, "tiledBase_files/9/"))?>;
			    	zoomLevelCache[10] = <?=json_encode($fileObject->getSignedURLs("tiled", true, "tiledBase_files/10/"))?>;
			    	zoomLevelCache[11] = <?=json_encode($fileObject->getSignedURLs("tiled", true, "tiledBase_files/11/"))?>;
			    	var tileCache = {};

			    	var attemptedLoad = {};
			    	var preFetchZoom = function(zoom, x) {
			    		if(tileCache[zoom] === undefined || tileCache[zoom][x] === undefined) {
							$.post(basePath + 'fileManager/getSignedChildrenForObject',
								{fileId: '<?=$fileObjectId?>', derivative: 'tiled', path: "tiledBase_files/" + zoom + "/" + x + "_" },
								function(data, textStatus, xhr) {

									var signedURLs;
			    					try {
			        					signedURLs = $.parseJSON(data);
			    					}
			    					catch(e) {
			        					console.log("error occurred: " + zoom + " " + x);
			        					return;
			    					}

							    	var localZoomCache = [];

			    					$.each(signedURLs, function(index, el) {
			    						localZoomCache.push(el);
			    					});
			    					if(tileCache[zoom] === undefined) {
	    								tileCache[zoom] = [];
	    							}
	    							tileCache[zoom][x] = localZoomCache;
			    				}
			    			);
						}
					}


			        var viewer = OpenSeadragon({
			            // debugMode: true,
			            id: "contentDiv",
			            prefixUrl: "/assets/seadragon/images/",
			            tileSources: {
							height: <?=$fileObject->sourceFile->metadata["dziHeight"]?>,
      						width:  <?=$fileObject->sourceFile->metadata["dziWidth"]?>,
      						tileSize: 256,
      						OverLap: 1,
							getTileUrl: function(zoom, x, y ) {
								var maxScaleWidth = Math.ceil(viewer.tileSources.width / viewer.tileSources.tileSize);
								var maxScaleHeight = Math.ceil(viewer.tileSources.height / viewer.tileSources.tileSize);
								var maxScale = Math.min(maxScaleHeight, maxScaleWidth);
								if(zoomLevelCache[zoom] == undefined && (tileCache[zoom] === undefined || tileCache[zoom][x] === undefined)) {
									attemptedLoad[zoom] = [];
									attemptedLoad[zoom][x] = true;
									// fetch and cache signed URLs for this zoom level
									$.ajaxSetup({async: false});
									preFetchZoom(zoom,x);
									$.ajaxSetup({async: true});
								}

								var zoomLevel;
								if(zoomLevelCache[zoom] !== undefined) {
									zoomLevel = zoomLevelCache[zoom];
								}
								else {
									zoomLevel = tileCache[zoom][x];
								}

								var foundElement = null;

								// console.log(zoomLevel);
								$.each(zoomLevel, function(index, el) {
									if(el.indexOf("/tiledBase_files/" + zoom + "/" + x + "_" + y) !== -1) {
										foundElement = el;
										return false;
									}
								});

								// cache the next two zooms
								// if(zoomLevel +1 <= maxScale && attemptedLoad[zoom+1] === undefined) {
								// 	// don't spawn a million prefetches
								// 	attemptedLoad[zoom+1] = true;
								// 	preFetchZoom(zoom+1);
								// }
								// if(zoomLevel +2 <= maxScale && attemptedLoad[zoom+2] === undefined) {
								// 	// don't spawn a million prefetches
								// 	attemptedLoad[zoom+2] = true;
								// 	preFetchZoom(zoom+2);
								// }
								return foundElement;
							}
			        	},
			            showNavigator:true,
			            animationTime: 0.5,
			        });

			    </script>


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

	<?endif?>
<?if(!$embedded):?>
	</div>
</div>
<?endif?>

<?if(!$embedded):?>
<div class="row infoRow">
	<div class="col-md-12 imageControls">
		<span class="glyphicon glyphicon-info-sign infoPopover" data-placement="bottom" data-toggle="popover" title="File Info" data-html=true data-content='<ul class="list-group">
			<li class="list-group-item"><strong>File Type: </strong> Image  </li>
			<li class="list-group-item"><strong>Original Name: </strong> <?=$fileObject->sourceFile->originalFilename?></li>
			<li class="list-group-item assetDetails"><strong>Width: </strong><?=$fileObject->sourceFile->metadata["width"]?></li>
			<li class="list-group-item assetDetails"><strong>Height: </strong><?=$fileObject->sourceFile->metadata["height"]?></li>
			<?if(isset($fileObject->sourceFile->metadata["exif"])):?>
			<li class="list-group-item assetDetails"><strong>EXIF: </strong><a href="" class="exifToggle" data-fileobject="<?=$fileObjectId?>">View EXIF</a></li>
			<?endif?>
			<?if($widgetObject && $widgetObject->fileDescription ):?>
			<li class="list-group-item assetDetails"><strong>Description: </strong><?=htmlentities($widgetObject->fileDescription, ENT_QUOTES)?></li>
			<?endif?>
			<?if($widgetObject && $widgetObject->getLocationData()):?>
			<li class="list-group-item assetDetails"><strong>Location: </strong><A href="#mapModal"  data-toggle="modal" data-latitude="<?=$widgetObject->getLocationData()[1]?>" data-longitude="<?=$widgetObject->getLocationData()[0]?>">View Location</a></li>
			<?endif?>
			<?if($widgetObject && $widgetObject->getDateData()):?>
			<li class="list-group-item assetDetails"><strong>Date: </strong><?=$widgetObject->getDateData()?></li>
			<?endif?>
			<li class="list-group-item assetDetails"><strong>File Size: </strong><?=byte_format($fileObject->sourceFile->metadata["filesize"])?></li>
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
