<?
$fileObjectId = $fileObject->getObjectId();
$embedLink = stripHTTP(instance_url("asset/getEmbed/" . $fileObjectId . "/null/true"));

$embed = htmlentities('<iframe width="560" height="480" src="' . $embedLink . '" frameborder="0" allowfullscreen></iframe>', ENT_QUOTES);


?>

<script type="text/javascript">
var zoomController = zoomController || {};

var tileCache = {};
var attemptedLoad = {};

zoomController.customTile = function (container) {
    this._map = new google.maps.Map($(container).get( 0 ), {
    	zoom: 0,
    	center: new google.maps.LatLng(0, 0),
    	panControl: true,
    	zoomControl: true,
    	mapTypeControl: false,
    	scaleControl: false,
    	streetViewControl: false,
    	overviewMapControl: false
    });

    // Set custom tiles
    this._map.mapTypes.set('customTile', new zoomController.ImgMapType('test', '#FFF'));
    this._map.setMapTypeId('customTile');

};



// ImgMapType class
//////////////////////////////////
zoomController.ImgMapType = function (theme, backgroundColor) {
	this.name = this._theme = theme;
	this._backgroundColor = backgroundColor;
};

zoomController.ImgMapType.prototype.tileSize = new google.maps.Size(256, 256);
zoomController.ImgMapType.prototype.minZoom = 0;
zoomController.ImgMapType.prototype.maxZoom = 7;

zoomController.ImgMapType.prototype.getTile = function (coord, zoom, ownerDocument) {
	var tilesCount = Math.pow(2, zoom);

	if (coord.x >= tilesCount || coord.x < 0 || coord.y >= tilesCount || coord.y < 0) {
		var div = ownerDocument.createElement('div');
		div.style.width = this.tileSize.width + 'px';
		div.style.height = this.tileSize.height + 'px';
		div.style.backgroundColor = this._backgroundColor;
		return div;
	}

	var img = ownerDocument.createElement('IMG');
	img.width = this.tileSize.width;
	img.height = this.tileSize.height;
	var imageSource = zoomController.Utils.GetImageUrl(zoom, coord.y, coord.x);
	if(!imageSource) {
		var div = ownerDocument.createElement('div');
		div.style.width = this.tileSize.width + 'px';
		div.style.height = this.tileSize.height + 'px';
		div.style.backgroundColor = this._backgroundColor;
		return div;
	}
	img.src = imageSource

	return img;
};


zoomController.Utils = zoomController.Utils || {};

zoomController.Utils.preFetchZoom = function(zoom) {
	if(tileCache[zoom] === undefined) {

		$.post(basePath + 'fileManager/getSignedChildrenForObject', {fileId: '<?=$fileObjectId?>', derivative: 'svs', path: zoom }, function(data, textStatus, xhr) {

			var signedURLs;
	    	try {
	        	signedURLs = $.parseJSON(data);
	    	}
	    	catch(e) {
	        	console.log("error occurred");
	    	}

	    	var localZoomCache = [];
	    	$.each(signedURLs, function(index, el) {
	    		localZoomCache.push(el);
	    	});

	    	tileCache[zoom] = localZoomCache;
		});

	}

}



zoomController.Utils.GetImageUrl = function (zoom, x, y) {
	// cache the zoom tiles for this zoom level

	if(tileCache[zoom] === undefined) {
		attemptedLoad[zoom] = true;
		// fetch and cache signed URLs for this zoom level
		$.ajaxSetup({async: false});
		zoomController.Utils.preFetchZoom(zoom);
		$.ajaxSetup({async: true});
	}

	var zoomLevel = tileCache[zoom];
	var foundElement = null;
	$.each(zoomLevel, function(index, el) {
		if(el.indexOf("/" + zoom + "/" + x + "/" + y) !== -1) {
			foundElement = el;
			return false;
		}
	});

	// cache the next two zooms
	if(zoom+1 <= zoomController.ImgMapType.prototype.maxZoom && attemptedLoad[zoom+1] === undefined) {
		// don't spawn a million prefetches
		attemptedLoad[zoom+1] = true;
		zoomController.Utils.preFetchZoom(zoom+1);
	}
	if(zoom+2 <= zoomController.ImgMapType.prototype.maxZoom && attemptedLoad[zoom+2] === undefined) {
		// don't spawn a million prefetches
		attemptedLoad[zoom+2] = true;
		zoomController.Utils.preFetchZoom(zoom+2);
	}


	return foundElement;
};

var localZoomController;

var loadedCallback = function() {
	 localZoomController = new zoomController.customTile($(".zoomTileContainer"));
};

</script>


<?if($embedded):?>



<?endif?>

<?if(!$embedded):?>
<div class="row assetViewRow">
	<div class="col-md-12">
<?endif?>
		<? if(!isset($fileContainers) || count($fileContainers) == 1):?>
		<p class="alert alert-info">No derivatives found.
			<?if(!$this->user_model->userLoaded):?>
			You may have access to additional derivatives if you log in.
			<?endif?>
		</p>

		<?else:?>
		<div class="fullscreenImageContainer">
    			<div class="imageContainer">
    				<div class="zoomTileContainer" style="width: 100%; min-height: 550px">

    				</div>
    			</div>
    		</div>
		</div>

	<?endif?>
<?if(!$embedded):?>
	</div>
</div>
<?endif?>

<?if(!$embedded):?>
<div class="row infoRow">
	<div class="col-md-12 imageControls">
		<span class="glyphicon glyphicon-info-sign infoPopover" data-placement="bottom" data-toggle="popover" title="File Info" data-html=true data-content='<ul class="list-group">
			<li class="list-group-item"><strong>File Type: </strong> SVS  </li>
			<li class="list-group-item"><strong>Original Name: </strong> <?=$fileObject->sourceFile->originalFilename?></li>
			<li class="list-group-item assetDetails"><strong>Width: </strong><?=$fileObject->sourceFile->metadata["width"]?></li>
			<li class="list-group-item assetDetails"><strong>Height: </strong><?=$fileObject->sourceFile->metadata["height"]?></li>
			<?if(isset($fileObject->sourceFile->metadata["exif"])):?>
			<li class="list-group-item assetDetails"><strong>EXIF: </strong><a href="" class="exifToggle" data-fileobject="<?=$fileObjectId?>">View EXIF</a></li>
			<?endif?>
			<?if($widgetObject && $widgetObject->fileDescription ):?>
			<li class="list-group-item assetDetails"><strong>Description: </strong><?=htmlentities($widgetObject->fileDescription, ENT_QUOTES)?></li>
			<?endif?>
			<?if($widgetObject && $widgetObject->locationData):?>
			<li class="list-group-item assetDetails"><strong>Location: </strong><A href="#mapModal"  data-toggle="modal" data-latitude="<?=$widgetObject->locationData[1]?>" data-longitude="<?=$widgetObject->locationData[0]?>">View Location</a></li>
			<?endif?>
			<?if($widgetObject && $widgetObject->dateData):?>
			<li class="list-group-item assetDetails"><strong>Date: </strong><?=$widgetObject->dateData?></li>
			<?endif?>
			<li class="list-group-item assetDetails"><strong>File Size: </strong><?=byte_format($fileObject->sourceFile->metadata["filesize"])?></li>
		</ul>'></span>

      	<span class="glyphicon glyphicon-download infoPopover" data-placement="bottom" data-toggle="popover" title="Download" data-html="true" data-content='
      		<ul>
      		<?if(isset($fileContainers['screen'])):?>
      			<li class="list-group-item assetDetails"><a href="<?=instance_url("fileManager/getDerivativeById/". $fileObjectId . "/screen")?>">Download Derivative</a></li>
      		<?endif?>
			<?if($allowOriginal):?>
			<li class="list-group-item assetDetails"><a href="<?=instance_url("fileManager/getOriginal/". $fileObjectId)?>">Download Original</a></li>
			<?endif?>
			</ul>'></span>
			<?if(count($fileContainers)>0):?>
		<span class="glyphicon glyphicon-share infoPopover" data-placement="bottom" data-toggle="popover" title="Share" data-html="true" data-content='<ul>
			 <li class="list-group-item assetDetails"><strong>Embed: </strong><input class="form-control embedControl" value="<?=htmlspecialchars($embed, ENT_QUOTES)?>"></li>
			</ul>'></span>
		<?endif?>
		<?if(count($fileContainers)>0):?>
			<span class="canFullscreen glyphicon glyphicon-resize-full" data-toggle="tooltip" title="Fullscreen"></span>
		<?endif?>

	</div>
</div>


<script>

$(function ()
{
	$(".infoPopover").popover({trigger: "focus | click"});
	$(".infoPopover").tooltip({ placement: 'top'});
	$(document).on("click", ".canFullscreen", function() {
		if($.fullscreen.isNativelySupported()) {
			$(".zoomTileContainer").first().fullscreen({ "toggleClass": "canvasFullscreen"});
			google.maps.event.trigger(localZoomController._map, 'resize');
		}
	});

});
</script>
<?endif?>

<?if(count($fileContainers)>0):?>
	<script>

	</script>
<?endif?>
