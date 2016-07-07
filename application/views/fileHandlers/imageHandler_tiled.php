<link rel="stylesheet" type="text/css" href="/assets/leaflet/leaflet.css">
<link rel="stylesheet" type="text/css" href="/assets/leaflet/leaflet.fullscreen.css">
<script type="text/javascript" src='/assets/leaflet/leaflet.js'></script>
<script type="text/javascript" src='/assets/leaflet/Leaflet.fullscreen.min.js'></script>
<script type="text/javascript" src='/assets/leaflet/Leaflet.elevator.js'></script>
<style type="text/css">


</style>
<div class="fixedHeightContainer"><div style="height:100%; width:100%" id="map"></div></div>


<script type="application/javascript">
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


	var tileCompletionCache = [];
	var prefetchAttempted = [];
	var tileLoadCache = [];

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

	var performFetch =  debounce(function() {
		var localTileCache = tileLoadCache.slice(0);
		tileLoadCache = [];
		var tileURLs = [];
		var zoomLevel;
		$.each(localTileCache, function(index, val) {
			coords = val.coords;
			var skipValue = false;
			$.each(tileURLs, function(tileIndex, tileVal) {
				if(tileVal.indexOf("tiledBase_files/" + coords.z + "/" + coords.x + "_") !== -1) {
					skipValue = true;
				}
			});
			if(!skipValue) {
				tileURLs.push("tiledBase_files/" + coords.z + "/" + coords.x + "_");	
			}

			zoomLevel = coords.z;
		});


		$.post(basePath + 'fileManager/getSignedChildrenForObject',
			{fileId: '<?=$fileObject->getObjectId()?>', derivative: 'tiled', paths: tileURLs },
			function(data, textStatus, xhr) {
				var signedURLs;
				try {
					signedURLs = $.parseJSON(data);
				}
				catch(e) {
					console.log("error occurred: " + zoomLevel + " " + x);
					return;
				}

				var localZoomCache = [];

				$.each(signedURLs, function(index, el) {
					localZoomCache.push(el);
				});
				if(zoomLevelCache[zoomLevel] === undefined) {
					zoomLevelCache[zoomLevel] = [];
				}
				$.each(localZoomCache, function(index,el) {
					zoomLevelCache[zoomLevel].push(el);
				});

				for(tilec in localTileCache) {
					tile = localTileCache[tilec];

					foundElement = null;
					$.each(signedURLs, function(index, el) {
						if(el.indexOf("tiledBase_files/" + tile.coords.z + "/" + tile.coords.x + "_" + tile.coords.y + ".") !== -1) {
							foundElement = el;
							return false;
						}
					});
					if(foundElement == null) {
					}
					else {
						var error;
						var localTile = tile.tile;
						var done = tile.done;
						localTile.onload = (function(done, error, localTile) {
							return function() {
								done(error, localTile);
							}
						})(done, error, localTile);
						localTile.src=foundElement;
						
						
					}

				}

				
			});


	}, 100);

	
	

	var map = L.map('map', {
		fullscreenControl: true,
		zoomSnap: 0,
   	     	crs: L.CRS.Simple //Set a flat projection, as we are projecting an image
   	     }).setView([0, 0], 0);
	
	var layer = L.tileLayer.elevator(function(coords, tile, done) {
		var error;
		var loadStarted = false;
		if(zoomLevelCache[coords.z] && zoomLevelCache[coords.z][coords.x] && zoomLevelCache[coords.z][coords.x][coords.y]) {
			foundElement = null;
			$.each(zoomLevelCache[coords.z], function(index, el) {
				if(el.indexOf("/tiledBase_files/" + coords.z + "/" + coords.x + "_" + coords.y + ".") !== -1) {
					foundElement = el;
					return false;
				}
			});
			if(foundElement !== null) {
				loadStarted = true;
			}
			tile.onload = (function(done, error, tile) {
				return function() {
					done(error, tile);
				}
			})(done, error, tile);
			tile.src=foundElement;
		}
		if(!loadStarted) {

			tileLoadCache.push({coords: coords, tile: tile, done: done});
			performFetch();
		}

		return tile;

	}, {
		width: <?=$fileObject->sourceFile->metadata["dziWidth"]?>,
		height: <?=$fileObject->sourceFile->metadata["dziHeight"]?>,
		tileSize :<?=isset($fileObject->sourceFile->metadata["dziTilesize"])?$fileObject->sourceFile->metadata["dziTilesize"]:255?>,
		maxZoom: <?=isset($fileObject->sourceFile->metadata["dziMaxZoom"])?$fileObject->sourceFile->metadata["dziMaxZoom"]:16?> - 1,
		overlap: <?=isset($fileObject->sourceFile->metadata["dziOverlap"])?$fileObject->sourceFile->metadata["dziOverlap"]:1?>
	});
	layer.addTo(map);

</script>
