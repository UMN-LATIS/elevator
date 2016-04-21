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
				{fileId: '<?=$fileObject->getObjectId()?>', derivative: 'tiled', path: "tiledBase_files/" + zoom + "/" + x + "_" },
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
