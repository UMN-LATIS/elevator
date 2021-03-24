// Based on Leaflet zoomify and Mia's tile loader

if(typeof require !== "undefined") var L = require('leaflet')

	L.TileLayer.Elevator = L.TileLayer.extend({
		options: {
			crs: L.CRS.Simple,
			infinite: false,
			noWrap: true,
			attributionControl: false,
			detectRetina: true,
			edgeBufferTiles: 1,
		},

		initialize: function(tileLoadFunction, options) {
		// this.options.crs.wrapLat = this.options.crs.wrapLng =  null
		L.TileLayer.prototype.initialize.call(this, null, options)
		this._loadFunction = tileLoadFunction;
		this._adjustForRetina = this.options.detectRetina && L.Browser.retina


		if(!options.maxZoom) {
			this.options.maxZoom = null;
		}
		
		this.on('tileload', this._adjustNonSquareTile)
	},

	// todo decide how to handle this and make sure to backport it
	 getTileUrl: function(coords){
        var error;
        var params = {Bucket: 'elevator-assets', Key: "testasset7/tiledBase_files/" + coords.z + "/" + coords.x + "_" + coords.y + ".jpeg"};
        //var params = {Bucket: 'elevator-assets', Key: "pmc14b_files/" + coords.z + "/" + coords.x + "_" + coords.y + ".jpeg"};
        var src = "https://s3.amazonaws.com/" + params.Bucket + "/" + params.Key;
        //tile.src = params.Key;
        return src;
	},
	

	createTile: function(coords, done) {
		var error;
		var tile = L.DomUtil.create('img', 'elevatorTile');
		coords.z = coords.z  + this.options.zoomOffset;
		this._loadFunction(coords, tile, done);
		return tile;
	},

	getTileSize: function() {
		var map = this._map,
		tileSize = L.GridLayer.prototype.getTileSize.call(this),
		zoom = this._tileZoom,
		zoomN = this.options.maxNativeZoom;

		tileSize.x = tileSize.x + this.options.overlap; // with deepzoom, our tile size removes the overlap, but leaflet needs it.
		tileSize.y = tileSize.y + this.options.overlap;
		// increase tile size when overscaling
		var outputSize= zoomN !== null && zoom > zoomN ?
		tileSize.divideBy(map.getZoomScale(zoomN, zoom)).round() :
		tileSize;
		
		return outputSize;
	},

_computeImageAndGridSize: function () { // thanks https://github.com/turban/Leaflet.Zoomify
	var map = this._map;
	var options = this.options,
	tileSize = options.tileSize || 256

	if(this._adjustForRetina) tileSize = tileSize*2 // Don't build the grid off half-sized retina tiles

		var imageSize = L.point(this.options.width, this.options.height);

	// Build the zoom sizes of the pyramid and cache them in an array
	this._imageSize = [imageSize];
	this._gridSize = [this._getGridSize(imageSize)];

	// Register the image size in pixels and the grid size in # of tiles for each zoom level
	while (imageSize.x > 0 || imageSize.y > 0) {
		imageSize = imageSize.divideBy(2).floor();
		this._imageSize.push(imageSize);
		this._gridSize.push(this._getGridSize(imageSize));
	}

	// We built the cache from bottom to top, but leaflet uses a top to bottom index for the zoomlevel,
	// so reverse it for easy indexing by current zoomlevel
	this._imageSize.reverse();
	this._gridSize.reverse();
	// Register our max supported zoom level
	var maxNativeZoom = this._gridSize.length - 1;
	if(maxNativeZoom !== this.options.maxNativeZoom) {
		// our metadata and our computed disagree. Let's trust the metadata?
		console.log("Overriding computed max zoom");
		maxNativeZoom = this.options.maxNativeZoom;
	}

	var maxZoomGrid = this._gridSize[maxNativeZoom],
	maxX = maxZoomGrid.x * this.options.tileSize,
	maxY = maxZoomGrid.y * this.options.tileSize,
	southEast = map.unproject([maxX, maxY], maxNativeZoom);

	this.options.bounds = new L.LatLngBounds([[0, 0], southEast]);

	this.options.maxNativeZoom = maxNativeZoom - this.options.zoomOffset;
	if(!this.options.maxZoom) {
		this.options.maxZoom = this.options.maxNativeZoom;
	}
	this.options.maxAdjustedZoom = maxNativeZoom;


},

_getGridSize: function (imageSize) {

	var tileSize = this.options.tileSize * 2;
	return L.point(Math.ceil(imageSize.x / tileSize), Math.ceil(imageSize.y / tileSize));
},

_adjustNonSquareTile: function (data) {
	var tile = data.tile
	, pad = 1
	, tileSize = L.point(tile.naturalWidth, tile.naturalHeight)

	if(this._adjustForRetina) tileSize = tileSize.divideBy(2)

	tile.style.width = tileSize.x + pad + 'px';
	tile.style.height = tileSize.y + pad + 'px';

},

_isValidTile: function(coords) {
	return (coords.x == 0 && coords.y == 0 && coords.z == 0) ||
	coords.x >= 0 && coords.y >= 0 && coords.z > 0 && coords.z &&
	L.TileLayer.prototype._isValidTile.call(this, coords)
},

onAdd: function (map) {
	var self = this
	this.adjustAttribution()
	map.options.maxBoundsViscosity = 0.8
	L.TileLayer.prototype.onAdd.call(this, map);
	this._computeImageAndGridSize();
	this.fitImage()
	
	map.on('resize', self._mapResized.bind(self))
},

_getImageBounds: function () {

	var map = this._map
	, options = this.options
	, imageSize = L.point(options.width, options.height)
	, zoom = this.options.maxAdjustedZoom
	, nw = map.unproject([0, 0], zoom)
	var se = map.unproject(imageSize, zoom)

	return L.latLngBounds(nw, se)
},

fitImage: function () {
	var map = this._map
	, bounds = this._getImageBounds()

	this.options.bounds = bounds // used by `GridLayer.js#_isValidTile`
	
	map.setMaxBounds(bounds)
	this.fitBoundsExactly()
},

// Determine the minimum zoom to fit the entire image exactly into
// the container. Set that as the minZoom of the map.
//
// If the image is 'wider' than its container, its zoom is set based on the
// ratio of image width to container width. For 'taller' images, height
// is compared. 'Wide' and 'tall' refer to the aspect ratio of the image and
// container, `width/height`.
//
// Two zooms are computed: 'fit' and 'fill'. Fit is the default, it fits an
// entire image into the available container. Fill fills the container with a 
// zoomed portion of the image. These two zooms are stored in `this.options.zooms`
fitBoundsExactly: function() {
	var i, c
	, imageSize = i = this._imageSize[this._imageSize.length-1]
	, map = this._map
	, containerSize = c =  map.getSize()

	var iAR, cAR
	, imageAspectRatio = iAR = imageSize.x/imageSize.y
	, containerAspectRatio = cAR = containerSize.x/containerSize.y
	, imageDimensions = ['container is', cAR <= 1, 'image is', iAR <= 1].join(' ').replace(/true/g, 'tall').replace(/false/g, 'wide');
	var zooms = this.options.zooms = iAR < cAR ?{fit: c.y/i.y, fill: c.x/i.x} : {fit: c.x/i.x, fill: c.y/i.y};
	var zoom = map.getScaleZoom(zooms.fit, this.options.maxAdjustedZoom) ;
	if(zoom > this.options.maxZoom) {
		return;
	}
	this.options.minZoom = Math.floor(zoom);
	map._addZoomLimit(this);
	var fill = map.getScaleZoom(zooms.fill, this.options.maxAdjustedZoom);
	
	if(map.getZoom() < fill) {
		map.setZoom(zoom);
	}
},

fillContainer: function() {
	var map = this._map;

	map.setZoom(map.getScaleZoom(this.options.zooms.fill, this.options.maxAdjustedZoom + this.options.Zoom));
},

// Remove the 'Leaflet' attribution from the map.
// With no `attribution` option, remove `attributionControl` all together.
adjustAttribution: function() {
	L.Control.Attribution.prototype.options.prefix = false

	if(!this.options.attribution) {
		this._map.options.attributionControl = false
		// this._map.attributionControl.remove()
	}
},

_mapResized: function() {
	var self = this
	clearTimeout(self._throttleResize)
	self._throttleResize = setTimeout(L.bind(self.fitBoundsExactly, self), 100)
},
})

L.tileLayer.elevator = function (tileLoadFunction, options) {
	return new L.TileLayer.Elevator(tileLoadFunction, options);
};

// https://github.com/TolonUK/Leaflet.EdgeBuffer
var unbufferedGetTiledPixelBounds = L.GridLayer.prototype._getTiledPixelBounds
L.GridLayer.include({
	_getTiledPixelBounds: function(center, zoom, tileZoom) {
		var pixelBounds = unbufferedGetTiledPixelBounds.call(this, center, zoom, tileZoom)

		if (this.options.edgeBufferTiles > 0) {
			var pixelEdgeBuffer = this.options.edgeBufferTiles * this._tileSize.x
			pixelBounds = new L.Bounds(pixelBounds.min.subtract([pixelEdgeBuffer, pixelEdgeBuffer]), pixelBounds.max.add([pixelEdgeBuffer, pixelEdgeBuffer]))
		}

		return pixelBounds;
	}
})

if(typeof module !== "undefined" && typeof require !== "undefined") {
	module.exports = L
}
