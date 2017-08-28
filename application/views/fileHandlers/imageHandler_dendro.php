<?

// time to get hacky, but this is special case code
$innerYear = null;
if($widgetObject->parentWidget->dendroField) {
	$targetField = $widgetObject->parentWidget->dendroField;
	if(isset($fileObject->parentObject->assetObjects[$targetField])) {

		$result = $fileObject->parentObject->assetObjects[$targetField]->getAsText();
		$innerYear = $result[0];
	}
}

?>


<link rel="stylesheet" type="text/css" href="/assets/leaflet/leaflet.css">
<link rel="stylesheet" type="text/css" href="/assets/leaflet-treering/node_modules/font-awesome-4.7.0/css/font-awesome.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
<link rel="stylesheet" href="/assets/leaflet-treering/node_modules/leaflet-fullscreen/dist/leaflet.fullscreen.css">
<link rel="stylesheet" href="/assets/leaflet-treering/node_modules/leaflet-minimap/dist/Control.MiniMap.min.css" />
<link rel="stylesheet" href="/assets/leaflet-treering/node_modules/leaflet-easybutton/src/easy-button.css" />
<link rel="stylesheet" href="/assets/leaflet-treering/node_modules/leaflet-dialog/Leaflet.Dialog.css">
<link rel="stylesheet" type="text/css" href="/assets/leaflet-treering/style.css">

<script src="/assets/js/aws-s3.js"></script>
<script src="/assets/leaflet-treering/node_modules/jszip/dist/jszip.min.js"></script>
<script src="/assets/leaflet-treering/node_modules/file-saver/FileSaver.js"></script>


<script type="text/javascript" src='/assets/leaflet/leaflet.js'></script>
<script src="/assets/leaflet-treering/node_modules/leaflet-fullscreen/dist/Leaflet.fullscreen.js"></script>
<script src="/assets/leaflet-treering/node_modules/leaflet-minimap/dist/Control.MiniMap.min.js"></script>
<script src="/assets/leaflet-treering/node_modules/leaflet-easybutton/src/easy-button.js"></script>
<script src="/assets/leaflet-treering/node_modules/leaflet-dialog/Leaflet.Dialog.js"></script>
<script src="/assets/leaflet-treering/leaflet-treering.js"></script>
    
    <script src="/assets/leaflet/Leaflet.elevator.js"></script>

<style type="text/css">

.leaflet-top {
	z-index: 400;
}
.fixedHeightContainer {
	margin-left: 50px;
}

.easy-button-container button {
	margin-right: 5px;
}
.easy-button-container .disabled {
	padding: 0px;
}

</style>

<? $token = $fileObject->getSecurityToken("tiled")?>

<div id="button_area">
        <a href="#" id="save-local" download="data.json"><i class="material-icons md-18">save</i></a>
        <div id="admin-save"></div>
        <div class="file-upload">
            <label for="file">
                <i id="upload_button" class="material-icons md-18">file_upload</i>
            </label>

            <input type="file" id="file" onchange="treering.loadFile()"/>
        </div>
    </div>
<div class="fixedHeightContainer"><div style="height:100%; width:100%" id="map"></div></div>

<script type="application/javascript">

	var map;
	var s3;
	var AWS;
	var layer;
	var sideCar = {};

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
		map = L.map('map', {
			fullscreenControl: true,
			zoomSnap: 0,
   	     	crs: L.CRS.Simple //Set a flat projection, as we are projecting an image
   	     }).setView([0, 0], 0);

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

		}, {
			width: <?=$fileObject->sourceFile->metadata["dziWidth"]?>,
			height: <?=$fileObject->sourceFile->metadata["dziHeight"]?>,
			tileSize :<?=isset($fileObject->sourceFile->metadata["dziTilesize"])?$fileObject->sourceFile->metadata["dziTilesize"]:255?>,
			maxZoom: <?=isset($fileObject->sourceFile->metadata["dziMaxZoom"])?$fileObject->sourceFile->metadata["dziMaxZoom"]:16?> - 1,
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
		
		miniLayer = L.tileLayer.elevator(function(coords, tile, done) {
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
		width: 231782,
        height: 4042,
        tileSize: 254,
        maxZoom: 13,
        overlap: 1,
		});
		
		var innerYear = "";
		<?if($innerYear):?>
		innerYear = <?=$innerYear?>;
		<?endif?>

		if(sideCar.points !== undefined && sideCar.points.length < 2) {
			sideCar = {};
		}
		var saveURL = "";
		<?if($this->user_model->getAccessLevel("instance",$this->instance) >= PERM_ADDASSETS):?>
		saveURL = basePath + "/assetManager/setSidecarForFile/<?=$fileObject->getObjectId()?>/dendro";
		<?endif?>

		var treering = new leafletTreering(map, basePath, saveURL, sideCar, '<?=$fileObject->parentObject->getAssetTitle(true)?>', innerYear);
    	treering.loadInterface();
    	if(saveURL != "") {
    		treering.addSaveButton();
    	}
	};

</script>
