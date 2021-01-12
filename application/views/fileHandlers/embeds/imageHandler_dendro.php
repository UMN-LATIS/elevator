<?

// time to get hacky, but this is special case code
$innerYear = null;
$haveLateWood = false;
if($widgetObject->parentWidget->dendroFields) {
	$innerYearField = $widgetObject->parentWidget->dendroFields["innerYear"];
	if(isset($fileObject->parentObject->assetObjects[$innerYearField])) {

		$result = $fileObject->parentObject->assetObjects[$innerYearField]->getAsArray();
		if(isset($result[0]['start']['text']) && is_numeric($result[0]['start']['text'])) {
			$innerYear = $result[0]['start']['text'];	
		}
		
	}

	$latewoodField = $widgetObject->parentWidget->dendroFields["lateWood"];
	if(isset($fileObject->parentObject->assetObjects[$latewoodField])) {
		$result = $fileObject->parentObject->assetObjects[$latewoodField]->getAsArray();
		$haveLateWood = $result[0]["fieldContents"];
	}
}

?>


<link rel="stylesheet" type="text/css" href="/assets/leaflet/leaflet.css">
<link rel="stylesheet" type="text/css" href="/assets/leaflet-treering/node_modules/font-awesome-4.7.0/css/font-awesome.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
<link rel="stylesheet" href="https://unpkg.com/material-components-web@latest/dist/material-components-web.min.css">
<link rel="stylesheet" href="/assets/leaflet-treering/node_modules/leaflet-fullscreen/dist/leaflet.fullscreen.css">
<link rel="stylesheet" href="/assets/leaflet-treering/node_modules/leaflet-minimap/dist/Control.MiniMap.min.css" />
<link rel="stylesheet" href="/assets/leaflet-treering/node_modules/leaflet-easybutton/src/easy-button.css" />
<link rel="stylesheet" href="/assets/leaflet-treering/node_modules/leaflet-dialog/Leaflet.Dialog.css">
<link rel="stylesheet" type="text/css" href="/assets/leaflet-treering/style.css">
<link rel="stylesheet" type="text/css" href="/assets/leaflet-treering/leaflet.magnifyingglass.css">

<script src="/assets/js/aws-s3.js"></script>
<script src="/assets/leaflet-treering/node_modules/jszip/dist/jszip.min.js"></script>
<script src="/assets/leaflet-treering/node_modules/file-saver/FileSaver.js"></script>


<script type="text/javascript" src='/assets/leaflet/leaflet.js'></script>
<script src="/assets/leaflet-treering/node_modules/leaflet-fullscreen/dist/Leaflet.fullscreen.js"></script>
<script src="/assets/leaflet-treering/node_modules/leaflet-minimap/dist/Control.MiniMap.min.js"></script>
<script src="/assets/leaflet-treering/node_modules/leaflet-easybutton/src/easy-button.js"></script>
<script src="/assets/leaflet-treering/node_modules/leaflet-dialog/Leaflet.Dialog.js"></script>
<script src="/assets/leaflet-treering/leaflet-treering.js"></script>
<script src="/assets/leaflet-treering/leaflet.magnifyingglass.js"></script>
    
    <script src="/assets/leaflet/Leaflet.elevator.js"></script>

<style type="text/css">

.leaflet-top {
	z-index: 400;
}


.leaflet-control {
	clear: none;
}

.leaflet-left {
	margin-left: 5px;
	margin-top: 3px;
}

</style>

<? $token = $fileObject->getSecurityToken("tiled")?>
<div class="fixedHeightContainer"><div style="height:100%; width:100%" id="imageMap"></div></div>

<script type="application/javascript">


	if(imageMap) {
		imageMap.remove();
	}

	var imageMap;
	var s3;
	var AWS;
	var layer;
	var magnifyingGlass;
	var sideCar = {};
	var treering;
	<?if(isset($widgetObject->sidecars) && array_key_exists("dendro", $widgetObject->sidecars)):?>
	sideCar = <?=json_encode($widgetObject->sidecars['dendro'])?>;
	<?endif?>

	var pixelsPerMillimeter = <?=((isset($widgetObject->sidecars) && array_key_exists("ppm", $widgetObject->sidecars) && strlen($widgetObject->sidecars['ppm'])>0))?$widgetObject->sidecars['ppm']:0?>;
	var miniLayer;
	var loadedCallback = function() {

		if(typeof AWS === 'undefined') {
			console.log("pausing for aws");
			setTimeout(loadedCallback, 200);
			return;
		}

		AWS.config = new AWS.Config();
		AWS.config.update({accessKeyId: "<?=$token['AccessKeyId']?>", secretAccessKey: "<?=$token['SecretAccessKey']?>", sessionToken: "<?=$token['SessionToken']?>"});

		AWS.config.region = '<?=$fileObject->collection->getBucketRegion()?>';
		s3 = new AWS.S3({Bucket: '<?=$fileObject->collection->getBucket()?>'});
		imageMap = L.map('imageMap', {
			fullscreenControl: true,
			trackResize: true,
			zoomControl: false,
			zoomSnap: 0,
			detectRetina: false,
			keyboard: false,
   	     	crs: L.CRS.Simple //Set a flat projection, as we are projecting an image
   	     }).setView([0, 0], 0);

		var mapOptions = {
			width: <?=$fileObject->sourceFile->metadata["dziWidth"]?>,
			height: <?=$fileObject->sourceFile->metadata["dziHeight"]?>,
			tileSize :<?=isset($fileObject->sourceFile->metadata["dziTilesize"])?$fileObject->sourceFile->metadata["dziTilesize"]:255?>,
			maxNativeZoom: <?=isset($fileObject->sourceFile->metadata["dziMaxZoom"])?$fileObject->sourceFile->metadata["dziMaxZoom"]:16?> - 1,
			maxZoom: <?=isset($fileObject->sourceFile->metadata["dziMaxZoom"])?$fileObject->sourceFile->metadata["dziMaxZoom"]:16?> + 0,
			overlap: <?=isset($fileObject->sourceFile->metadata["dziOverlap"])?$fileObject->sourceFile->metadata["dziOverlap"]:1?>,
			pixelsPerMillimeter: pixelsPerMillimeter,
			detectRetina: false,
			renderer: L.canvas()
		};

		layer = L.tileLayer.elevator(function(coords, tile, done) {
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

		}, mapOptions);
		layer.addTo(imageMap);
		
		var magnifyLayer = L.tileLayer.elevator(function(coords, tile, done) {
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

		}, mapOptions);
		
		magnifyingGlass = L.magnifyingGlass({
    		layers: [ magnifyLayer ]
		});

		L.DomEvent.on(window, 'keydown', function(e) {
			if(e.keyCode == 90 && e.getModifierState("Control")) {
				if (imageMap.hasLayer(magnifyingGlass)) {
					imageMap.removeLayer(magnifyingGlass);
	    		} else {
					magnifyingGlass.addTo(imageMap);
	    		}
			}
		}, this);
		  	    





		// var minimapRatio = <?=$fileObject->sourceFile->metadata["dziWidth"] / $fileObject->sourceFile->metadata["dziHeight"]?>;
		// if(minimapRatio > 4) {
		// 	minimapRatio = 1;
		// }

		// if(minimapRatio > 1) {
		// 	heightScale = 1/minimapRatio;
		// 	widthScale = 1;
		// }
		// else {
		// 	heightScale = 1;
		// 	widthScale = minimapRatio;
		// }
		
		// var miniLayer = L.tileLayer.elevator(function(coords, tile, done) {
        //     var error;

        //     var params = {Bucket: '<?=$fileObject->collection->getBucket()?>', Key: "derivative/<?=$fileContainers['tiled']->getCompositeName()?>/tiledBase_files/" + coords.z + "/" + coords.x + "_" + coords.y + ".jpeg"};

        //     s3.getSignedUrl('getObject', params, function (err, url) {
        //         tile.onload = (function(done, error, tile) {
        //             return function() {
        //                 done(error, tile);
        //             }
        //         })(done, error, tile);
        //         tile.src=url;
        //     });

        //     return tile;

        // }, mapOptions);
        
        // var miniMap = new L.Control.MiniMap(miniLayer, {
        //     width: 500,
        //     height: 30,
        //                 //position: "topright",
        //                 toggleDisplay: true,
        //                 zoomAnimation: false,
        //                 zoomLevelOffset: -3,
        //                 zoomLevelFixed: -3
        //             });
        // miniMap.addTo(map);

		
		var innerYear = "";
		<?if($innerYear):?>
		innerYear = <?=$innerYear?>;
		<?endif?>

		if(sideCar == null || (sideCar.points !== undefined && sideCar.points.length < 2 && (sideCar.annotations === undefined || sideCar.annotations.length < 1))) {
			sideCar = {};
		}
		var saveURL = "";
		var canSave = false;
		<?if($this->user_model->getAccessLevel("instance",$this->instance) >= PERM_ADDASSETS || $this->user_model->getAccessLevel("collection",$fileObject->collection) >= PERM_ADDASSETS):?>
		saveURL = basePath + "assetManager/setSidecarForFile/<?=$fileObject->getObjectId()?>/dendro";
		canSave = true;
		<?endif?>
		popoutURL = "<?=stripHTTP(instance_url("asset/getEmbed/" . $fileObject->getObjectId() . "/null/true"));?>";
		treering = new LTreering(imageMap, "/assets/leaflet-treering/",{ppm:layer.options.pixelsPerMillimeter, saveURL: saveURL, savePermission:canSave, popoutUrl: popoutURL, 'initialData': sideCar, 'assetName': "<?=$fileObject->parentObject->getAssetTitle(true)?>", 'datingInner': innerYear, 'hasLatewood': <?=$haveLateWood?"true":"false"?>});
    	treering.loadInterface();
    	// if(saveURL != "") {
    	// 	treering.addSaveButton();
    	// }
	};

</script>
