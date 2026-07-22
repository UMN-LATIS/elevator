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

/* Leaflet 1.9 sets font-size: 0.75rem on .leaflet-container.
   bootstrap_stthomas.css sets html { font-size: 10px }, which causes
   0.75rem to resolve to 7.5px instead of 12px, shrinking all controls. */
#mapElement.leaflet-container {
    font-size: 12px;
}

/* Leaflet 1.9 added mix-blend-mode: plus-lighter to img.leaflet-tile.
   For opaque tiled images with overlap this causes visible seams. */
#mapElement img.leaflet-tile {
    mix-blend-mode: normal;
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

// Collect sibling file data for multilayer support
$enableMultilayer = isset($widgetObject->parentWidget->enableMultilayer) && $widgetObject->parentWidget->enableMultilayer;
$multilayerLayers = [];

if ($enableMultilayer && isset($widgetObject->parentWidget->fieldContentsArray)) {
	$currentFileId = $fileObject->getObjectId();
	foreach ($widgetObject->parentWidget->fieldContentsArray as $fileEntry) {
		if ($fileEntry->fileId == $currentFileId) {
			continue;
		}
		$siblingHandler = $fileEntry->getFileHandler();
		if (!$siblingHandler) {
			continue;
		}

		$siblingTiledKey = null;
		if (array_key_exists('tiled-iiif', $siblingHandler->derivatives) && $siblingHandler->derivatives['tiled-iiif']->ready) {
			$siblingTiledKey = 'tiled-iiif';
		} elseif (array_key_exists('tiled', $siblingHandler->derivatives) && $siblingHandler->derivatives['tiled']->ready) {
			$siblingTiledKey = 'tiled';
		}

		if (!$siblingTiledKey) {
			continue;
		}

		$siblingContainer = $siblingHandler->derivatives[$siblingTiledKey];
		$siblingToken = $siblingHandler->getSecurityToken($siblingTiledKey);
		$multilayerLayers[] = [
			'type'          => $siblingTiledKey,
			'url'           => $siblingContainer->getProtectedURLForFile(),
			'compositeName' => $siblingContainer->getCompositeName(),
			'fileSize'      => $siblingTiledKey === 'tiled-iiif' ? (int)$siblingContainer->getFileSize() : 0,
			'bucket'        => $siblingHandler->collection->getBucket(),
			'region'        => $siblingHandler->collection->getBucketRegion(),
			'credentials'   => [
				'accessKeyId'     => $siblingToken['AccessKeyId'],
				'secretAccessKey' => $siblingToken['SecretAccessKey'],
				'sessionToken'    => $siblingToken['SessionToken'],
			],
			'width'         => $siblingHandler->sourceFile->metadata['dziWidth'] ?? 0,
			'height'        => $siblingHandler->sourceFile->metadata['dziHeight'] ?? 0,
			'tileSize'      => $siblingHandler->sourceFile->metadata['dziTilesize'] ?? 256,
			'maxNativeZoom' => ($siblingHandler->sourceFile->metadata['dziMaxZoom'] ?? 16) - 1,
			'overlap'       => $siblingHandler->sourceFile->metadata['dziOverlap'] ?? 1,
			'title'         => $siblingHandler->sourceFile->originalFilename,
		];
	}
}

$hasSiblingIIIF = !empty(array_filter($multilayerLayers, fn($l) => $l['type'] === 'tiled-iiif'));
// Load the pyramid TIFF reader for sibling iiif layers when the base handler didn't
// already pull it in (the partial only includes it when the base is itself tiled-iiif).
$needsPyramidScript = $hasSiblingIIIF && !isset($fileContainers['tiled-iiif']);

?>

<div class="fixedHeightContainer"><div style="height:100%; width:100%" id="mapElement"></div></div>
<?=$this->load->view("fileHandlers/embeds/imageHandler_partial.php",array("fileContainers"=>$fileContainers),true)?>
<?php if ($needsPyramidScript): ?><script src="/assets/leaflet/pyramidTiff.js"></script><?php endif; ?>

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

		<?php if ($enableMultilayer && count($multilayerLayers) > 0): ?>
		var multilayerData = <?=json_encode($multilayerLayers)?>;

		<?php if ($needsPyramidScript): ?>
		// hexStringToUint8Array is only defined by imageHandler_partial when the base is tiled-iiif;
		// define it here when siblings need it but the base doesn't.
		if (typeof hexStringToUint8Array === 'undefined') {
			window.hexStringToUint8Array = function(hexString) {
				if (hexString.length % 2 !== 0) { throw 'Invalid hexString'; }
				var arr = new Uint8Array(hexString.length / 2);
				for (var i = 0; i < hexString.length; i += 2) { arr[i / 2] = parseInt(hexString.substr(i, 2), 16); }
				return arr;
			};
		}
		<?php endif; ?>

		// Custom layer control with per-layer visibility toggle and opacity slider
		var MultilayerControl = L.Control.extend({
			options: { position: 'topright' },
			onAdd: function(map) {
				var c = L.DomUtil.create('div', 'leaflet-control-layers leaflet-control-layers-expanded');
				c.style.cssText = 'padding:6px 10px;min-width:190px;';
				L.DomEvent.disableClickPropagation(c);
				L.DomEvent.disableScrollPropagation(c);
				var h = L.DomUtil.create('strong', '', c);
				h.textContent = 'Layers';
				h.style.cssText = 'display:block;margin-bottom:5px;font-size:12px;';
				this._list = c;
				return c;
			},
			addLayerRow: function(lyr, name, map, startEnabled) {
				if (startEnabled === undefined) { startEnabled = true; }
				var row = L.DomUtil.create('div', '', this._list);
				row.style.cssText = 'display:flex;align-items:center;gap:5px;margin:3px 0;';

				var cb = L.DomUtil.create('input', '', row);
				cb.type = 'checkbox';
				cb.checked = startEnabled;

				var lbl = L.DomUtil.create('span', '', row);
				lbl.textContent = name;
				lbl.title = name;
				lbl.style.cssText = 'flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:11px;max-width:110px;cursor:default;';

				var sl = L.DomUtil.create('input', '', row);
				sl.type = 'range';
				sl.min = '0';
				sl.max = '1';
				sl.step = '0.05';
				sl.value = '1';
				sl.style.cssText = 'width:65px;cursor:pointer;';
				sl.title = 'Opacity';

				var layerAdded = startEnabled;
				var forceLoad = function(l) {
					// The elevator onAdd calls L.TileLayer.prototype.onAdd (which fires _resetView)
					// BEFORE _computeImageAndGridSize() sets up bounds/grid. If fitBoundsExactly
					// doesn't trigger a zoom change, tiles from the premature _resetView are stale
					// and nothing re-triggers loading. Fix: call _resetView again after onAdd
					// fully completes. Listen for the zoomend fitBoundsExactly may fire; if no
					// zoom change occurs, two rAFs are enough to be past the synchronous onAdd.
					var done = false;
					var doReset = function() {
						if (done) { return; }
						done = true;
						if (l._map) { l._resetView(); }
					};
					map.once('zoomend', doReset);
					requestAnimationFrame(function() {
						requestAnimationFrame(doReset);
					});
				};
				L.DomEvent.on(cb, 'change', function() {
					if (cb.checked) {
						map.addLayer(lyr);
						lyr.setOpacity(parseFloat(sl.value));
						if (!layerAdded) { forceLoad(lyr); layerAdded = true; }
					} else {
						map.removeLayer(lyr);
					}
				});
				L.DomEvent.on(sl, 'input', function() {
					if (!map.hasLayer(lyr)) {
						cb.checked = true;
						map.addLayer(lyr);
						if (!layerAdded) { forceLoad(lyr); layerAdded = true; }
					}
					lyr.setOpacity(parseFloat(sl.value));
				});
			}
		});

		var multilayerControl = new MultilayerControl().addTo(imageMap);
		multilayerControl.addLayerRow(layer, <?=json_encode($fileObject->sourceFile->originalFilename)?>, imageMap);

		multilayerData.forEach(function(layerDef) {
			// Each sibling has its own STS token scoped to its S3 key — pass credentials directly
			var layerS3 = new AWS.S3({
				accessKeyId: layerDef.credentials.accessKeyId,
				secretAccessKey: layerDef.credentials.secretAccessKey,
				sessionToken: layerDef.credentials.sessionToken,
				region: layerDef.region
			});

			if (layerDef.type === 'tiled-iiif') {
				var layerTiff = null;
				var layerSubimages = {};
				var layerMaxZoom = layerDef.maxNativeZoom;
				// Note: sibling tiles are still range-fetched via the sibling's own STS-signed
				// S3 URLs below; layerTiff is only used to read per-level tile offsets/tables.
				var layerTileLoadFunction = async function(coords, tile, done) {
					if (layerSubimages[coords.z] === undefined) {
						layerSubimages[coords.z] = await layerTiff.getImage(layerMaxZoom - coords.z);
					}
					var subimage = layerSubimages[coords.z];
					async function getData() {
						var numTilesPerRow = Math.ceil(subimage.getWidth() / subimage.getTileWidth());
						var index = (coords.y * numTilesPerRow) + coords.x;
						var offset = Number(subimage.fileDirectory.TileOffsets[index]);
						var byteCount = Number(subimage.fileDirectory.TileByteCounts[index]);
						if (!Number.isFinite(offset) || !Number.isFinite(byteCount) || offset < 0 || byteCount <= 0) { return; }
						var params = {
							Bucket: layerDef.bucket,
							Key: 'derivative/' + layerDef.compositeName,
							Range: 'bytes=' + offset + '-' + (offset + byteCount)
						};
						var url = layerS3.getSignedUrl('getObject', params);
						var response = await fetch(url, {headers: {Range: 'bytes=' + offset + '-' + (offset + byteCount)}});
						return await response.arrayBuffer();
					}
					getData().then(function(data) {
						if (!data) { return; }
						var uintRaw = new Uint8Array(data);
						var rawAdobeHeader = hexStringToUint8Array("FFD8FFEE000E41646F626500640000000000");
						var merged = new Uint8Array(rawAdobeHeader.length + uintRaw.length + subimage.fileDirectory.JPEGTables.length - 2 - 2 - 2);
						merged.set(rawAdobeHeader);
						merged.set(subimage.fileDirectory.JPEGTables.slice(2, -2), rawAdobeHeader.length);
						merged.set(uintRaw.slice(2), rawAdobeHeader.length + subimage.fileDirectory.JPEGTables.length - 2 - 2);
						tile.src = URL.createObjectURL(new Blob([merged], {type: 'image/jpeg'}));
					});
				};
				PyramidTiff.fromUrl(layerDef.url, layerDef.fileSize).then(function(tiffObj) {
					layerTiff = tiffObj;
					var newLayer = new L.tileLayer.elevator(layerTileLoadFunction, {
						width: layerDef.width,
						height: layerDef.height,
						detectRetina: false,
						tileSize: layerDef.tileSize,
						maxNativeZoom: layerDef.maxNativeZoom,
						overlap: layerDef.overlap,
						tileType: 'iiif'
					});
					multilayerControl.addLayerRow(newLayer, layerDef.title, imageMap, false);
				});
			} else if (layerDef.type === 'tiled') {
				var layerTileLoadFunction = function(coords, tile, done) {
					var params = {
						Bucket: layerDef.bucket,
						Key: 'derivative/' + layerDef.compositeName + '/tiledBase_files/' + coords.z + '/' + coords.x + '_' + coords.y + '.jpeg'
					};
					tile.src = layerS3.getSignedUrl('getObject', params);
					return tile;
				};
				var newLayer = new L.tileLayer.elevator(layerTileLoadFunction, {
					width: layerDef.width,
					height: layerDef.height,
					detectRetina: false,
					tileSize: layerDef.tileSize,
					maxNativeZoom: layerDef.maxNativeZoom,
					overlap: layerDef.overlap,
					tileType: 'tiled'
				});
				multilayerControl.addLayerRow(newLayer, layerDef.title, imageMap, false);
			}
		});
		<?php endif; ?>

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
