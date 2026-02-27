<link rel="stylesheet" type="text/css" href="/assets/leaflet/leaflet.css">
<link rel="stylesheet" type="text/css" href="/assets/leaflet/leaflet.fullscreen.css">
<link rel="stylesheet" type="text/css" href="/assets/leaflet/Control.MiniMap.min.css">
<link rel="stylesheet" type="text/css" href="/assets/leaflet/leaflet-measure.css">
<script type="text/javascript" src='/assets/leaflet/leaflet.js'></script>
<script type="text/javascript" src='/assets/leaflet/Control.MiniMap.min.js'></script>
<script type="text/javascript" src='/assets/leaflet/leaflet-measure.min.js'></script>
<script src="/assets/leaflet/Leaflet.elevator.js"></script>
<script src="/assets/js/aws-s3.js"></script>

<style type="text/css">

.leaflet-top {
	z-index: 400;
}
</style>

<?

if(isset($fileContainers['tiled'])) {
	$token = $fileObject->getSecurityToken("tiled");	
}
elseif(isset($fileContainers['tiled-tar'])) {
	$token = $fileObject->getSecurityToken("tiled-tar");
}
elseif(isset($fileContainers['tiled-iiif'])) {
	$token = $fileObject->getSecurityToken("tiled-iiif");
}

?>

<div class="fixedHeightContainer"><div style="height:100%; width:100%" id="mapElement"></div></div>
<?=$this->load->view("fileHandlers/embeds/imageHandler_partial.php",array("fileContainers"=>$fileContainers),true)?>

<script type="application/javascript">
	var imageMap;
	var s3;
	var AWS;
	var pixelsPerMillimeter = <?=((isset($widgetObject->sidecars) && array_key_exists("ppm", $widgetObject->sidecars) && strlen($widgetObject->sidecars['ppm'])>0))?$widgetObject->sidecars['ppm']:0?>;

	
	
	var actualLoad = async function() {

		if(typeof AWS === 'undefined') {
			console.log("pausing for aws");
			setTimeout(loadedCallback, 200);
			return;
		}

		await loadIndex();
		console.log("entry");
		
		AWS.config = new AWS.Config();
		AWS.config.update({accessKeyId: "<?=$token['AccessKeyId']?>", secretAccessKey: "<?=$token['SecretAccessKey']?>", sessionToken: "<?=$token['SessionToken']?>"});

		AWS.config.region = '<?=$fileObject->collection->getBucketRegion()?>';
		s3 = new AWS.S3({Bucket: '<?=$fileObject->collection->getBucket()?>'});

		imageMap = new L.map('mapElement', {
			fullscreenControl: true,
			zoomSnap: 0,
			detectRetina: false,
   	     	crs: L.CRS.Simple //Set a flat projection, as we are projecting an image
   	     }).setView([0, 0], 0);

		var layer = new L.tileLayer.elevator(tileLoadFunction, {
			width: <?=$fileObject->sourceFile->metadata["dziWidth"]?>,
			height: <?=$fileObject->sourceFile->metadata["dziHeight"]?>,
			detectRetina: false,
			tileSize :<?=isset($fileObject->sourceFile->metadata["dziTilesize"])?$fileObject->sourceFile->metadata["dziTilesize"]:255?>,
			maxNativeZoom: <?=isset($fileObject->sourceFile->metadata["dziMaxZoom"])?$fileObject->sourceFile->metadata["dziMaxZoom"]:16?> - 1,
			overlap: <?=isset($fileObject->sourceFile->metadata["dziOverlap"])?$fileObject->sourceFile->metadata["dziOverlap"]:1?>,
			pixelsPerMillimeter: pixelsPerMillimeter,
			tileType: tileType
		});
		layer.addTo(imageMap);

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
		var miniLayer = L.tileLayer.elevator(tileLoadFunction, {
			width: <?=$fileObject->sourceFile->metadata["dziWidth"]?>,
			height: <?=$fileObject->sourceFile->metadata["dziHeight"]?>,
			tileSize: 254,
			maxZoom: <?=isset($fileObject->sourceFile->metadata["dziMaxZoom"])?$fileObject->sourceFile->metadata["dziMaxZoom"]:16?> - 1,
			maxNativeZoom: <?=isset($fileObject->sourceFile->metadata["dziMaxZoom"])?$fileObject->sourceFile->metadata["dziMaxZoom"]:16?> - 1,
			overlap: 1,
		});
		var miniMap = new L.Control.MiniMap(miniLayer, {
			width: 140 * widthScale,
			height: 140 * heightScale,
						//position: "topright",
						toggleDisplay: true,
						zoomAnimation: false,
						zoomLevelOffset: -3,
						zoomLevelFixed: -3,
						detectRetina: false
					});
		miniMap.addTo(imageMap);

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

		measureControl.addTo(imageMap);
			
		}
		
		

	};

	var loadedCallback = setTimeout(actualLoad, 300);

</script>
