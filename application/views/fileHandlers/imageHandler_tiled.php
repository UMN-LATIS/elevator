<link rel="stylesheet" type="text/css" href="/assets/leaflet/leaflet.css">
<link rel="stylesheet" type="text/css" href="/assets/leaflet/leaflet.fullscreen.css">
<script src="/assets/js/aws-s3.min.js"></script>
<script type="text/javascript" src='/assets/leaflet/leaflet.js'></script>
<script type="text/javascript" src='/assets/leaflet/Leaflet.fullscreen.min.js'></script>
<script type="text/javascript" src='/assets/leaflet/Leaflet.elevator.js'></script>
<style type="text/css">


</style>

<? $token = $fileObject->getSecurityToken("tiled")?>


<div class="fixedHeightContainer"><div style="height:100%; width:100%" id="map"></div></div>

<script type="application/javascript">

var map;
var s3;
var AWS;

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
	
		var layer = L.tileLayer.elevator(function(coords, tile, done) {
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
		overlap: <?=isset($fileObject->sourceFile->metadata["dziOverlap"])?$fileObject->sourceFile->metadata["dziOverlap"]:1?>
	});
	layer.addTo(map);
};

</script>
