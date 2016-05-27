/*
 * L.TileLayer.Zoomify display Zoomify tiles with Leaflet
 *
 * Based on the Leaflet.Zoomify (https://github.com/turban/Leaflet.Zoomify)
 * from turban (https://github.com/turban)
 *
 */

L.TileLayer.Zoomify = L.TileLayer.extend({
	options: {
		width: -1, // Must be set by user, max zoom image width
		height: -1, // Must be set by user, max zoom image height
		overlap: 0
	},

	initialize: function (loadFunction, options) {
		L.TileLayer.prototype.initialize.call(this, null, options);
		this._loadFunction = loadFunction;
		// Replace with automatic loading from ImageProperties.xml
		if (this.options.width < 0 || this.options.height < 0) {
			throw new Error('The user must set the Width and Height of the Zoomify image');
		}
	},

	beforeAdd: function (map) {
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
		this.options.maxNativeZoom = maxNativeZoom;
		// Register our bounds for this zoomify layer based on the maximum zoom
		var maxZoomGrid = this._gridSize[maxNativeZoom],
		maxX = maxZoomGrid.x * this.options.tileSize,
		maxY = maxZoomGrid.y * this.options.tileSize,
		southEast = map.unproject([maxX, maxY], maxNativeZoom);
		this.options.bounds = new L.LatLngBounds([[0, 0], southEast]);

		L.TileLayer.prototype.beforeAdd.call(this, map);
	},

	// Calculate the grid size for a given image size (based on tile size)
	_getGridSize: function (imageSize) {
		var tileSize = this.options.tileSize;
		return L.point(Math.ceil(imageSize.x / tileSize), Math.ceil(imageSize.y / tileSize));
	},

	getTileSize: function () {
		var s = this.options.tileSize;
		return s instanceof L.Point ? s : new L.Point(s-this.options.overlap, s-this.options.overlap);
	},

	createTile: function(coords, done) {
		// console.log(coords);
		var error;
		 var tile = L.DomUtil.create('img', 'test');
        // setup tile width and height according to the options
        var size = this.getTileSize();
        tile.width = size.x;
        tile.height = size.y;
        // tile.src="tiledBase_files/" + coords.z + "/" + coords.x + "_" + coords.y + ".jpeg";
		this._loadFunction(coords, tile, done);
        return tile;
	},
	// Extend the add tile function to update our arbitrary sized border tiles
	_addTile: function (coords, container) {
	// 	// Load the tile via the original leaflet code
	// 	console.log(coords);
		L.TileLayer.prototype._addTile.call(this, coords, container);

		// Get out imagesize in pixels for this zoom level and our grid size
		var imageSize = this._imageSize[this._getZoomForUrl()],
		    gridSize = this._gridSize[this._getZoomForUrl()];

		// The real tile size (default:256) and the display tile size (if zoom > maxNativeZoom)
		var realTileSize = L.GridLayer.prototype.getTileSize.call(this),
		    displayTileSize = L.TileLayer.prototype.getTileSize.call(this);

	// 	// Get the current tile to adjust
		var key = this._tileCoordsToKey(coords);

		var tile = this._tiles[key].el;

	// 	// Calculate the required size of the border tiles
		var scaleFactor = L.point(	(imageSize.x % realTileSize.x),
									(imageSize.y % realTileSize.y)).unscaleBy(realTileSize);


		tile.style.width = displayTileSize.x + "px";
		tile.style.height  = displayTileSize.y + "px";
	// 	// Update tile dimensions if we are on a border
		if ((imageSize.x % realTileSize.x) > 0 && coords.x === gridSize.x - 1) {
			tile.style.width = displayTileSize.scaleBy(scaleFactor).x + 'px';
		}

		if ((imageSize.y % realTileSize.y) > 0 && coords.y === gridSize.y - 1) {
			tile.style.height = displayTileSize.scaleBy(scaleFactor).y + 'px';
		}

	// },

	// // Construct the tile url, by inserting our tilegroup before we template the url
	// getTileUrl: function (coords) {

	// 	// Call the original templater
	// 	return L.TileLayer.prototype.getTileUrl.call(this, coords);
	},

	getBounds: function () {
		return this.options.bounds;
	}

});

L.tileLayer.zoomify = function (url, options) {
	return new L.TileLayer.Zoomify(url, options);
};
