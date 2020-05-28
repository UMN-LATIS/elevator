<link rel="stylesheet" type="text/css" href="/assets/leaflet/leaflet.css">
<link rel="stylesheet" type="text/css" href="/assets/leaflet/leaflet.fullscreen.css">
<link rel="stylesheet" type="text/css" href="/assets/leaflet/Control.MiniMap.min.css">
<link rel="stylesheet" type="text/css" href="/assets/leaflet/leaflet-measure.css">
<script src="/assets/js/aws-s3.js"></script>

<style type="text/css">

.leaflet-top {
	z-index: 400;
}
</style>

<? $token = $fileObject->getSecurityToken("tiled")?>


<div class="fixedHeightContainer"><div style="height:100%; width:100%" id="mapElement"></div></div>

<script type="application/javascript">

	var s3;
	var AWS;
	var pixelsPerMillimeter = <?=((isset($widgetObject->sidecars) && array_key_exists("ppm", $widgetObject->sidecars) && strlen($widgetObject->sidecars['ppm'])>0))?$widgetObject->sidecars['ppm']:0?>;

	
	
	var actualLoad = function() {

		if(typeof AWS === 'undefined') {
			console.log("pausing for aws");
			setTimeout(loadedCallback, 200);
			return;
		}
		console.log("entry");
		if(map !== null){
			console.log("teardown");
			map.remove(); // tear down any existing leaflets so we clear handlers.
		}
		
		AWS.config = new AWS.Config();
		AWS.config.update({accessKeyId: "<?=$token['AccessKeyId']?>", secretAccessKey: "<?=$token['SecretAccessKey']?>", sessionToken: "<?=$token['SessionToken']?>"});

		AWS.config.region = '<?=$fileObject->collection->getBucketRegion()?>';
		s3 = new AWS.S3({Bucket: '<?=$fileObject->collection->getBucket()?>'});

		map = new L.map('mapElement', {
			fullscreenControl: true,
			zoomSnap: 0,
   	     	crs: L.CRS.Simple //Set a flat projection, as we are projecting an image
   	     }).setView([0, 0], 0);

		var layer = new L.tileLayer.elevator(function(coords, tile, done) {
			var error;

			var params = {Bucket: '<?=$fileObject->collection->getBucket()?>', Key: "derivative/<?=$fileContainers['tiled']->getCompositeName()?>/tiledBase_files/" + coords.z + "/" + coords.x + "_" + coords.y + ".jpeg"};

			s3.getSignedUrl('getObject', params, function (err, url) {
				tile.onload = (function(done, error, tile) {
					return function() {
						done(error, tile);
					}
				})(done, error, tile);
				tile.src=url;
			});

			return tile;

		}, {
			width: <?=$fileObject->sourceFile->metadata["dziWidth"]?>,
			height: <?=$fileObject->sourceFile->metadata["dziHeight"]?>,
			detectRetina: false,
			tileSize :<?=isset($fileObject->sourceFile->metadata["dziTilesize"])?$fileObject->sourceFile->metadata["dziTilesize"]:255?>,
			maxNativeZoom: <?=isset($fileObject->sourceFile->metadata["dziMaxZoom"])?$fileObject->sourceFile->metadata["dziMaxZoom"]:16?> - 1,
			overlap: <?=isset($fileObject->sourceFile->metadata["dziOverlap"])?$fileObject->sourceFile->metadata["dziOverlap"]:1?>,
			pixelsPerMillimeter: pixelsPerMillimeter
		});
		layer.addTo(map);

		var minimapRatio = <?=$fileObject->sourceFile->metadata["dziWidth"] / $fileObject->sourceFile->metadata["dziHeight"]?>;
		if(minimapRatio > 4) {
			minimapRatio = 1;
		}

		if(minimapRatio > 1) {
			heightScale = 1/minimapRatio;
			widthScale = 1;
		}
		else {
			heightScale = 1;
			widthScale = minimapRatio;
		}
		var miniLayer = L.tileLayer.elevator(function(coords, tile, done) {
			var error;

			var params = {Bucket: '<?=$fileObject->collection->getBucket()?>', Key: "derivative/<?=$fileContainers['tiled']->getCompositeName()?>/tiledBase_files/" + coords.z + "/" + coords.x + "_" + coords.y + ".jpeg"};

			s3.getSignedUrl('getObject', params, function (err, url) {
				tile.onload = (function(done, error, tile) {
					return function() {
						done(error, tile);
					}
				})(done, error, tile);
				tile.src=url;
			});

			return tile;

		}, {
			width: <?=$fileObject->sourceFile->metadata["dziWidth"]?>,
			height: <?=$fileObject->sourceFile->metadata["dziHeight"]?>,
			tileSize: 254,
			maxZoom: <?=isset($fileObject->sourceFile->metadata["dziMaxZoom"])?$fileObject->sourceFile->metadata["dziMaxZoom"]:16?> - 1,
			overlap: 1,
		});
		var miniMap = new L.Control.MiniMap(miniLayer, {
			width: 140 * widthScale,
			height: 140 * heightScale,
						//position: "topright",
						toggleDisplay: true,
						zoomAnimation: false,
						zoomLevelOffset: -3,
						zoomLevelFixed: -3
					});
		miniMap.addTo(map);

		if(pixelsPerMillimeter > 10) {

			var measureControl = new L.Control.Measure(
			{
				units: {
					m: {
	                    factor: (1 / pixelsPerMillimeter) / 1000, //calculateConversionFactor() returns a conversion ratio in terms of millimeters
	                    display: 'meters',
	                    decimals: 2
	                },
	                cm: {
	                	factor: (1 / pixelsPerMillimeter) / 10,
	                	display: 'centimeters',
	                	decimals: 2
	                },
	                sqm: {
	                  //factor: conversionFactor(44568, 20000) / 50000,
	                  factor: (1 /  Math.pow(pixelsPerMillimeter,2)) / 1000000,
	                  display: 'square meters',
	                  decimals: 2
	              },
	              sqcm: {
	                  //factor: conversionFactor(44568, 20000) / 500,
	                  factor: (1 / Math.pow(pixelsPerMillimeter,2)) / 100,
	                  display: 'square centimeters',
	                  decimals: 2
	              }
	          },
	          primaryLengthUnit: 'cm',
	          secondaryLengthUnit: 'pixels',
	          primaryAreaUnit: 'sqcm',
	          secondaryAreaUnit: 'sqm'
			});

		measureControl.addTo(map);
			
		}
		
		

	};

	var loadedCallback = setTimeout(actualLoad, 300);

</script>