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

	<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
	<link rel="stylesheet" href="https://unpkg.com/material-components-web@latest/dist/material-components-web.min.css">
	<link rel="stylesheet" href="/assets/leaflet-treering/node_modules/font-awesome/css/font-awesome.css">

	<link rel="stylesheet" href="/assets/leaflet-treering/node_modules/bootstrap/dist/css/bootstrap.min.css">
	<link rel="stylesheet" href="/assets/leaflet-treering/node_modules/bootstrap/dist/css/bootstrap-theme.min.css" >

	<link rel="stylesheet" href="/assets/leaflet-treering/node_modules/leaflet/dist/leaflet.css">
	<link rel="stylesheet" href="/assets/leaflet-treering/node_modules/leaflet-fullscreen/dist/leaflet.fullscreen.css">
	<link rel="stylesheet" href="/assets/leaflet-treering/node_modules/leaflet-minimap/dist/Control.MiniMap.min.css" />
	<link rel="stylesheet" href="/assets/leaflet-treering/node_modules/leaflet-easybutton/src/easy-button.css" />
	<link rel="stylesheet" href="/assets/leaflet-treering/node_modules/leaflet-dialog/Leaflet.Dialog.css">
<link rel="stylesheet" type="text/css" href="/assets/leaflet-treering/leaflet.magnifyingglass.css">

	<link rel="stylesheet" href="/assets/leaflet-treering/style.css">
	<link rel="stylesheet" href="/assets/leaflet-treering/Style.AreaCapture.css">

	<!-- <script src="/assets/leaflet-treering/node_modules/jquery/dist/jquery.min.js"></script> -->
	<script src="/assets/leaflet-treering/node_modules/jszip/dist/jszip.min.js"></script>
	<script src="/assets/leaflet-treering/node_modules/file-saver/FileSaver.min.js"></script>

	<script src="/assets/leaflet-treering/node_modules/leaflet/dist/leaflet.js"></script>
	<script src="/assets/leaflet-treering/node_modules/leaflet-fullscreen/dist/Leaflet.fullscreen.js"></script>
	<script src="/assets/leaflet-treering/node_modules/leaflet-minimap/dist/Control.MiniMap.min.js"></script>
	<script src="/assets/leaflet-treering/node_modules/leaflet-easybutton/src/easy-button.js"></script>
	<script src="/assets/leaflet-treering/node_modules/leaflet-dialog/Leaflet.Dialog.js"></script>
    <script src="/assets/leaflet-treering/Leaflet.TileLayer.GL.js"></script>
	
<script src="/assets/leaflet/Leaflet.elevator.js"></script>
<script src="/assets/leaflet-treering/leaflet.magnifyingglass.js"></script>
<script type="application/javascript" src="/assets/leaflet-treering/leaflet-treering.js"></script>
<script type="application/javascript" src="/assets/leaflet-treering/Leaflet.AreaCapture.js"></script>
<script type="application/javascript" src="/assets/leaflet-treering/node_modules/leaflet-ellipse/l.ellipse.js"></script>
<script src="https://unpkg.com/leaflet-lasso@2.2.12/dist/leaflet-lasso.umd.min.js"></script>

<script src="/assets/js/aws-s3.js"></script>

    

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

<? 


if(isset($fileContainers['tiled'])) {
	$token = $fileContainers['tiled']->getSecurityToken("tiled");	
}
elseif(isset($fileContainers['tiled-tar'])) {
	$token = $fileObject->getSecurityToken("tiled-tar");
}
elseif(isset($fileContainers['tiled-iiif'])) {
	$token = $fileObject->getSecurityToken("tiled-iiif");
}

?>

<div class="fixedHeightContainer"><div style="height:100%; width:100%" id="imageMap"></div></div>
<?=$this->load->view("fileHandlers/embeds/imageHandler_partial.php",array("fileContainers"=>$fileContainers),true)?>
	
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
	var baseLayer;
	var layer;

	var getURL = window.location.href;
	var parsedURL = new URL(getURL);
	var urlParams = new URLSearchParams(parsedURL.search);
	var latData = urlParams.get("lat");
	var lngData = urlParams.get("lng");
	
	
	var loadedCallback = async function() {

		if(typeof AWS === 'undefined') {
			console.log("pausing for aws");
			setTimeout(loadedCallback, 200);
			return;
		}
		await loadIndex();
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

		if (latData && lngData) {
			imageMap.setView([latData, lngData], 16); //  max zoom level is 18
		};

		baseLayer = L.tileLayer.elevator(tileLoadFunction, mapOptions);
		baseLayer.addTo(imageMap);
		

var fragmentShader2 = `

uniform float u_kernel[9];
uniform float u_flipY;
uniform float u_kernelWeight;
// all based on https://webglfundamentals.org/webgl/lessons/webgl-image-processing-continued.html
vec3 texSample(const float x, const float y, in vec2 fragCoord)
{
	vec2 uv = fragCoord;
    uv = (uv + vec2((x)/256.0 , (y)/256.0 ));
    // this also fixed the seam by clamping one pixel from the bottom, but it's super hacky
    // if(uv.y > 0.996) {
    //     uv.y = 0.99;
    // }
	return texture2D(uTexture0, uv).xyz;
}


vec3 embossFilter(in vec2 fragCoord, float strength){
	vec3 f =
	texSample(-1.,-1., fragCoord) *  u_kernel[0] +
	texSample( 0.,-1., fragCoord) *  u_kernel[1] +
	texSample( 1.,-1., fragCoord) *  u_kernel[2] +
	texSample(-1., 0., fragCoord) *  u_kernel[3] +
	texSample( 0., 0., fragCoord) *  u_kernel[4] +
	texSample( 1., 0., fragCoord) *  u_kernel[5] +
	texSample(-1., 1., fragCoord) *  u_kernel[6] +
	texSample( 0., 1., fragCoord) *  u_kernel[7] +
	texSample( 1., 1., fragCoord) *  u_kernel[8]
	;
	return mix(texSample( 0., 0., fragCoord), f , strength);
}

void main(void){
    // gl_Position = vec4(clipSpace * vec2(1, u_flipY), 0, 1);

    vec4 targetTexture = texture2D(uTexture0, vec2(vTextureCoords.x, vTextureCoords.y));
    // gl_FragColor = targetTexture;
    vec3 result = embossFilter(vec2(vTextureCoords.x, vTextureCoords.y), uSharpenStrength);

    gl_FragColor = vec4((result / u_kernelWeight).rgb,targetTexture.a);
}
`;

 layer = L.tileLayer.gl({
        uniforms: {
            uSharpenStrength: 0
        },
            crs: L.CRS.Simple,
            noWrap: true,
            infinite: false,
            tileSize: 256,
            detectRetina: false,
			fragmentShader: fragmentShader2,
			tileLayers: [baseLayer],
		}).addTo(imageMap);


		var magnifyLayer = L.tileLayer.elevator(tileLoadFunction, mapOptions);
		
		magnifyingGlass = L.magnifyingGlass({
    		layers: [ magnifyLayer ]
		});

		L.DomEvent.on(window, 'keydown', function(e) {
			
			if(e.keyCode == 76 && e.getModifierState("Control")) {
				e.preventDefault();
				e.stopPropagation();
			
				if (imageMap.hasLayer(magnifyingGlass)) {
					imageMap.removeLayer(magnifyingGlass);
	    		} else {
					magnifyingGlass.addTo(imageMap);
	    		}
			}
		}, this);
		  	    



		
		var innerYear = "";
		<?if($innerYear):?>
		innerYear = <?=$innerYear?>;
		<?endif?>

		if(sideCar == null) {
			sideCar = {};
		}
		var saveURL = "";
		var canSave = false;
		<?if($this->user_model->getAccessLevel("instance",$this->instance) >= PERM_ADDASSETS || $this->user_model->getAccessLevel("collection",$fileObject->collection) >= PERM_ADDASSETS):?>
		saveURL = basePath + "assetManager/setSidecarForFile/<?=$fileObject->getObjectId()?>/dendro";
		canSave = true;
		<?endif?>
		popoutURL = "<?=stripHTTP(instance_url("asset/getEmbed/" . $fileObject->getObjectId() . "/null/true"));?>";

		$.get("/assets/leaflet-treering/templates.html", function (coreassestsData) {
			$.get("/assets/leaflet-treering/Template.AreaCapture.html", function (areaCaptureData) {
				$("body").append(coreassestsData);
				$("body").append(areaCaptureData);
				treering = new LTreering(imageMap, "/assets/leaflet-treering/",{ppm:baseLayer.options.pixelsPerMillimeter, saveURL: saveURL, savePermission:canSave, popoutUrl: popoutURL, 'initialData': sideCar, 'assetName': "<?=$fileObject->parentObject->getAssetTitle(true)?>", 'datingInner': innerYear, 'hasLatewood': <?=$haveLateWood?"true":"false"?>}, baseLayer, layer );
				treering.loadInterface();
			});
		});

    	// if(saveURL != "") {
    	// 	treering.addSaveButton();
    	// }
	};

</script>
