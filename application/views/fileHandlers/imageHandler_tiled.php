<link rel="stylesheet" type="text/css" href="/assets/leaflet/leaflet.css">
<link rel="stylesheet" type="text/css" href="/assets/leaflet/leaflet.fullscreen.css">
<script type="text/javascript" src='/assets/leaflet/leaflet.js'></script>
<script type="text/javascript" src='/assets/leaflet/Leaflet.fullscreen.js'></script>
<script type="text/javascript" src='/assets/leaflet/Leaflet.elevator.js'></script>
<style type="text/css">


</style>
<div id="map" style="height: 600px; width:100%;"></div>

 
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


		var map = L.map('map', {
            fullscreenControl: true,
   	     	crs: L.CRS.Simple //Set a flat projection, as we are projecting an image
    	});
    	
        var tileCompletionCache = [];
        var prefetchAttempted = [];
        var prefetchTiles = function(zoomLevel, x) {
            $.post(basePath + 'fileManager/getSignedChildrenForObject',
				{fileId: '<?=$fileObject->getObjectId()?>', derivative: 'tiled', path: "tiledBase_files/" + zoomLevel + "/" + x + "_" },
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

					setTimeout(function() {
		                for(tilec in tileCompletionCache[zoomLevel][x]) {
		                    tile = tileCompletionCache[zoomLevel][x][tilec];

		                    foundElement = null;
		                    $.each(zoomLevelCache[tile.coords.z], function(index, el) {
								if(el.indexOf("/tiledBase_files/" + tile.coords.z + "/" + tile.coords.x + "_" + tile.coords.y) !== -1) {
									foundElement = el;
									return false;
								}
							});
		                    tile.tile.src=foundElement;
		                    var error;

		                    tile.done(error, tile.tile);                    
		                }
		            }, 1);
				});
		};


    	//Loading the Zoomify tile layer, notice the URL
    	var layer = L.tileLayer.zoomify(function(coords, tile, done) {
            var error;
            // tile.src="tiledBase_files/" + coords.z + "/" + coords.x + "_" + coords.y + ".jpeg";
            var loadStarted = false;
            if(zoomLevelCache[coords.z] && zoomLevelCache[coords.z][coords.x] && zoomLevelCache[coords.z][coords.x][coords.y]) {
               foundElement = null;
               $.each(zoomLevelCache[coords.z], function(index, el) {
					if(el.indexOf("/tiledBase_files/" + coords.z + "/" + coords.x + "_" + coords.y) !== -1) {
						foundElement = el;
						return false;
					}
				});
               if(foundElement !== null) {
               	loadStarted = true;
               }
               tile.src = foundElement;
               setTimeout(function() { done(error, tile)}, 1);
            }
            if(!loadStarted) {
                if(!tileCompletionCache[coords.z]) {
                    tileCompletionCache[coords.z] = [];
                }
                if(!tileCompletionCache[coords.z][coords.x]) {
                    tileCompletionCache[coords.z][coords.x] = [];
                }

                tileCompletionCache[coords.z][coords.x][coords.y] = { tile: tile, done: done, coords: coords};
                if(prefetchAttempted[coords.z] && prefetchAttempted[coords.z][coords.x]) {
                    // console.log("prefetch underway for " + coords.z + " " + coords.x);
                }
                else {
                    // console.log("attempting prefetch for" +  coords.z + " " + coords.x);
                    if(!prefetchAttempted[coords.z]) {
                        prefetchAttempted[coords.z] = [];
                    }
                    prefetchAttempted[coords.z][coords.x] = true;
                    prefetchTiles(coords.z, coords.x);
                }
            }

            return tile;

        }, {
			width: <?=$fileObject->sourceFile->metadata["dziWidth"]?>,
			height: <?=$fileObject->sourceFile->metadata["dziHeight"]?>,
            tileSize :<?=$fileObject->sourceFile->metadata["dziTilesize"]?>,
            maxZoom: <?=$fileObject->sourceFile->metadata["dziMaxZoom"]?> - 1,
            overlap: <?=$fileObject->sourceFile->metadata["dziOverlap"]?> * 2
		}).addTo(map);

		//Setting the view to our layer bounds, set by our Zoomify plugin
		map.fitBounds(layer.getBounds());

    </script>
