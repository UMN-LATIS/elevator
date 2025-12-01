/*
 * @class GridLayer.GL
 * @inherits GridLayer
 *
 * This `GridLayer` runs some WebGL code on each grid cell, and puts an image
 * with the result back in place.
 *
 * The contents of each cell can be purely synthetic (based only on the cell
 * coordinates), or be based on some remote tiles (used as textures in the WebGL
 * shaders).
 *
 * The fragment shader is assumed to receive two `vec2` attributes, with the CRS
 * coordinates and the texture coordinates: `aCRSCoords` and `aTextureCoords`.
 * If textures are used, they are accesed through the uniforms `uTexture0` through `uTexture7`
 * There will always be four vertices forming two triangles (a quad).
 *
 */

var kernels = {
    normal: [
      0, 0, 0,
      0, 1, 0,
      0, 0, 0
    ],
    gaussianBlur: [
      0.045, 0.122, 0.045,
      0.122, 0.332, 0.122,
      0.045, 0.122, 0.045
    ],
    gaussianBlur2: [
      1, 2, 1,
      2, 4, 2,
      1, 2, 1
    ],
    gaussianBlur3: [
      0, 1, 0,
      1, 1, 1,
      0, 1, 0
    ],
    unsharpen: [
      -1, -1, -1,
      -1,  9, -1,
      -1, -1, -1
    ],
    sharpness: [
       0,-1, 0,
      -1, 5,-1,
       0,-1, 0
    ],
    sharpen: [
       -1, -1, -1,
       -1, 16, -1,
       -1, -1, -1
    ],
    edgeDetect: [
       -0.125, -0.125, -0.125,
       -0.125,  1,     -0.125,
       -0.125, -0.125, -0.125
    ],
    edgeDetect2: [
       -1, -1, -1,
       -1,  8, -1,
       -1, -1, -1
    ],
    edgeDetect3: [
       -5, 0, 0,
        0, 0, 0,
        0, 0, 5
    ],
    edgeDetect4: [
       -1, -1, -1,
        0,  0,  0,
        1,  1,  1
    ],
    edgeDetect5: [
       -1, -1, -1,
        2,  2,  2,
       -1, -1, -1
    ],
    edgeDetect6: [
       -5, -5, -5,
       -5, 39, -5,
       -5, -5, -5
    ],
    sobelHorizontal: [
        1,  2,  1,
        0,  0,  0,
       -1, -2, -1
    ],
    sobelVertical: [
        1,  0, -1,
        2,  0, -2,
        1,  0, -1
    ],
    previtHorizontal: [
        1,  1,  1,
        0,  0,  0,
       -1, -1, -1
    ],
    previtVertical: [
        1,  0, -1,
        1,  0, -1,
        1,  0, -1
    ],
    boxBlur: [
        0.111, 0.111, 0.111,
        0.111, 0.111, 0.111,
        0.111, 0.111, 0.111
    ],
    triangleBlur: [
        0.0625, 0.125, 0.0625,
        0.125,  0.25,  0.125,
        0.0625, 0.125, 0.0625
    ],
    emboss: [
       -2, -1,  0,
       -1,  1,  1,
        0,  1,  2
    ]
  };

  var effects = [
    { name: "gaussianBlur3", on: true },
    { name: "gaussianBlur3", on: true },
    { name: "gaussianBlur3", on: true },
    { name: "sharpness", },
    { name: "sharpness", },
    { name: "sharpness", },
    { name: "sharpen", },
    { name: "sharpen", },
    { name: "sharpen", },
    { name: "unsharpen", },
    { name: "unsharpen", },
    { name: "unsharpen", },
    { name: "emboss", on: true },
    { name: "edgeDetect", },
    { name: "edgeDetect", },
    { name: "edgeDetect3", },
    { name: "edgeDetect3", },
  ];

L.TileLayer.GL = L.GridLayer.extend({
	options: {
		// @option tileUrls: Array
		// Array of tile URL templates (as in `L.TileLayer`), between zero and 8 elements. Each URL template will be converted into a plain `L.TileLayer` and pushed in the `tileLayers` option.
		tileUrls: [],

		// @option tileLayers: Array
		// Array of instances of `L.TileLayer` (or its subclasses, like `L.TileLayer.WMS`), between zero and 8 elements.
		tileLayers: [],

		// @option fragmentShader: String
		// A string representing the GLSL fragment shader to be run.
		// This must NOT include defining the variants, nor the texture uniforms,
		// nor user-defined uniforms.
		fragmentShader: "void main(void) {gl_FragColor = vec4(0.2,0.2,0.2,1.0);}",
		// @option uniforms: Object
		// A map of names and initial values for the user-defined uniforms.
		// Values must be `Number` or an `Array` of up to four `Number`s.
		// e.g. `{ uTarget: 2.0, uOffset: [0.0, 5.0] }`.
		uniforms: {},

		subdomains: ["a", "b", "c", "d"],
	},
	kernelSettings: [
		
	],
	// On instantiating the layer, it will initialize all the GL context
	//   and upload the shaders to the GPU, along with the vertex buffer
	//   (the vertices will stay the same for all tiles).
	initialize: function(options) {
		options = L.setOptions(this, options);

		this._renderer = L.DomUtil.create("canvas");
		this._renderer.width = this._renderer.height = options.tileSize;
		this._glError = false;

		var gl = (this._gl =
			this._renderer.getContext("webgl", {
				premultipliedAlpha: false,
			}) ||
			this._renderer.getContext("experimental-webgl", {
				premultipliedAlpha: false,
			}));
		gl.viewportWidth = options.tileSize;
		gl.viewportHeight = options.tileSize;

		// Create `TileLayer`s from the tileUrls option.
		this._tileLayers = Array.from(options.tileLayers);
		for (var i = 0; i < options.tileUrls.length && i < 8; i++) {
			this._tileLayers.push(L.tileLayer(options.tileUrls[i]));
		}

		this.options.bounds = this._tileLayers[0].options.bounds;
		this.options.maxNativeZoom = this._tileLayers[0].options.maxNativeZoom;
        this.options.maxZoom =this._tileLayers[0].options.maxZoom;
		this.options.minZoom = this._tileLayers[0].options.minZoom;
		this.options._imageSize = this._tileLayers[0]._imageSize;
		this.options.overlap = this._tileLayers[0].options.overlap;
		this._loadGLProgram();

		// Init textures
		this._textures = [];
		for (i = 0; i < this._tileLayers.length && i < 8; i++) {
			this._textures[i] = gl.createTexture();
			gl.uniform1i(gl.getUniformLocation(this._glProgram, "uTexture" + i), i);
		}

		this.textures = [];
		this.framebuffers = [];

		for (var ii = 0; ii < 6; ++ii) {
			this.texture = this.createAndSetupTexture(gl);
			this.textures.push(this.texture);
		
			// make the texture the same size as the image
			gl.texImage2D(
				gl.TEXTURE_2D, 0, gl.RGBA, 256, 256, 0,
				gl.RGBA, gl.UNSIGNED_BYTE, null);
		
			// Create a framebuffer
			var fbo = gl.createFramebuffer();
			this.framebuffers.push(fbo);
			gl.bindFramebuffer(gl.FRAMEBUFFER, fbo);
		
			// Attach a texture to it.
			gl.framebufferTexture2D(
				gl.FRAMEBUFFER, gl.COLOR_ATTACHMENT0, gl.TEXTURE_2D, this.texture, 0);
		}
	},
  createAndSetupTexture: function(gl) {
    var texture = gl.createTexture();
    gl.bindTexture(gl.TEXTURE_2D, texture);
 
    // Set up texture so we can render any size image and so we are
    // working with pixels.
gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_S, gl.CLAMP_TO_EDGE);
    gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_T, gl.CLAMP_TO_EDGE);
    gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MIN_FILTER, gl.NEAREST);
    gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MAG_FILTER, gl.NEAREST);
 
 
    return texture;
  },
	_setFragment: function(fragment){
		this.options.fragmentShader = fragment;
		this._loadGLProgram();
	},

    _isValidTile: function(coords) {
        return (coords.x == 0 && coords.y == 0 && coords.z == 0) ||
            coords.x >= 0 && coords.y >= 0 && coords.z > 0 && coords.z &&
            L.TileLayer.prototype._isValidTile.call(this, coords)
    },

	// @method getGlError(): String|undefined
	// If there was any error compiling/linking the shaders, returns a string
	// with information about that error. If there was no error, returns `undefined`.
	getGlError: function() {
		return this._glError;
	},

	_loadGLProgram: function() {
		var gl = this._gl;

		// Force using this vertex shader.
		// Just copy all attributes to predefined variants and set the vertex positions
		var vertexShaderCode =
			"attribute vec2 aVertexCoords;  " +
			"attribute vec2 aTextureCoords;  " +
			"attribute vec2 aCRSCoords;  " +
			"attribute vec2 aLatLngCoords;  " +
			"uniform float u_flipY;  " +
			"varying vec2 vTextureCoords;  " +
			"varying vec2 vCRSCoords;  " +
			"varying vec2 vLatLngCoords;  " +
			"void main(void) {  " +
			"	gl_Position = vec4(aVertexCoords  * vec2(1, u_flipY), 1.0, 1.0);  " +
			"	vTextureCoords = aTextureCoords;  " +
			"	vCRSCoords = aCRSCoords;  " +
			"	vLatLngCoords = aLatLngCoords;  " +
			"}";

		// Force using this bit for the fragment shader. All fragment shaders
		// will use the same predefined variants, and
		var fragmentShaderHeader =
			"precision highp float;\n" +
			"uniform float uNow;\n" +
			"uniform vec3 uTileCoords;\n" +
			"varying vec2 vTextureCoords;\n" +
			"varying vec2 vCRSCoords;\n" +
			"varying vec2 vLatLngCoords;\n";

		for (var i = 0; i < this._tileLayers.length && i < 8; i++) {
			fragmentShaderHeader += "uniform sampler2D uTexture" + i + ";\n";
		}

		fragmentShaderHeader += this._getUniformSizes();

		var program = (this._glProgram = gl.createProgram());
		var vertexShader = gl.createShader(gl.VERTEX_SHADER);
		var fragmentShader = gl.createShader(gl.FRAGMENT_SHADER);
		gl.shaderSource(vertexShader, vertexShaderCode);
		gl.shaderSource(fragmentShader, fragmentShaderHeader + this.options.fragmentShader);
		gl.compileShader(vertexShader);
		gl.compileShader(fragmentShader);

		// @event shaderError
		// Fired when there was an error creating the shaders.
		if (!gl.getShaderParameter(vertexShader, gl.COMPILE_STATUS)) {
			this._glError = gl.getShaderInfoLog(vertexShader);
			console.error(this._glError);
			return null;
		}
		if (!gl.getShaderParameter(fragmentShader, gl.COMPILE_STATUS)) {
			this._glError = gl.getShaderInfoLog(fragmentShader);
			console.error(this._glError);
			return null;
		}

		gl.attachShader(program, vertexShader);
		gl.attachShader(program, fragmentShader);
		gl.linkProgram(program);
		gl.useProgram(program);

		// There will be four vec2 vertex attributes per vertex:
		// aVertexCoords (always from -1 to +1), aTextureCoords (always from 0 to +1),
		// aLatLngCoords and aCRSCoords (both geographical and per-tile).
		this._aVertexPosition = gl.getAttribLocation(program, "aVertexCoords");
		this._uFlipPosition = gl.getUniformLocation(program, "u_flipY");
		this._aTexPosition = gl.getAttribLocation(program, "aTextureCoords");
		this._aCRSPosition = gl.getAttribLocation(program, "aCRSCoords");
		this._aLatLngPosition = gl.getAttribLocation(program, "aLatLngCoords");

		this._initUniforms(program);

		// If the shader is time-dependent (i.e. animated), or has custom uniforms,
		// init the texture cache
		if (this._isReRenderable) {
			this._fetchedTextures = {};
			this._2dContexts = {};
		}

		// 		console.log('Tex position: ', this._aTexPosition);
		// 		console.log('CRS position: ', this._aCRSPosition);
		// 		console.log("uNow position: ", this._uNowPosition);

		// Create three data buffer with 8 elements each - the (easting,northing)
		// CRS coords, the (s,t) texture coords and the viewport coords for each
		// of the 4 vertices
		// Data for the texel and viewport coords is totally static, and
		// needs to be declared only once.
		this._CRSBuffer = gl.createBuffer();
		gl.bindBuffer(gl.ARRAY_BUFFER, this._CRSBuffer);
		gl.bufferData(gl.ARRAY_BUFFER, new Float32Array(8), gl.STATIC_DRAW);
		if (this._aCRSPosition !== -1) {
			gl.enableVertexAttribArray(this._aCRSPosition);
			gl.vertexAttribPointer(this._aCRSPosition, 2, gl.FLOAT, false, 8, 0);
		}

		this._LatLngBuffer = gl.createBuffer();
		gl.bindBuffer(gl.ARRAY_BUFFER, this._LatLngBuffer);
		gl.bufferData(gl.ARRAY_BUFFER, new Float32Array(8), gl.STATIC_DRAW);
		if (this._aLatLngPosition !== -1) {
			gl.enableVertexAttribArray(this._aLatLngPosition);
			gl.vertexAttribPointer(this._aLatLngPosition, 2, gl.FLOAT, false, 8, 0);
		}

		this._TexCoordsBuffer = gl.createBuffer();
		gl.bindBuffer(gl.ARRAY_BUFFER, this._TexCoordsBuffer);

		// prettier-ignore
		gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([
			1.0, 0.0,
			0.0, 0.0,
			1.0, 1.0,
			0.0, 1.0,
		]), gl.STATIC_DRAW);
		if (this._aTexPosition !== -1) {
			gl.enableVertexAttribArray(this._aTexPosition);
			gl.vertexAttribPointer(this._aTexPosition, 2, gl.FLOAT, false, 8, 0);
		}

		this._VertexCoordsBuffer = gl.createBuffer();
		gl.bindBuffer(gl.ARRAY_BUFFER, this._VertexCoordsBuffer);

		// prettier-ignore
		gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([
			 1,  1,
			-1,  1,
			 1, -1,
			-1, -1
		]), gl.STATIC_DRAW);
		if (this._aVertexPosition !== -1) {
			gl.enableVertexAttribArray(this._aVertexPosition);
			gl.vertexAttribPointer(this._aVertexPosition, 2, gl.FLOAT, false, 8, 0);
		}
	},

	// Looks at the size of the default values given for the uniforms.
	// Returns a string valid for defining the uniforms in the shader header.
	_getUniformSizes() {
		var defs = "";
		this._uniformSizes = {};
		for (var uniformName in this.options.uniforms) {
			var defaultValue = this.options.uniforms[uniformName];
			if (typeof defaultValue === "number") {
				this._uniformSizes[uniformName] = 0;
				defs += "uniform float " + uniformName + ";\n";
			} else if (defaultValue instanceof Array) {
				if (defaultValue.length > 4) {
					throw new Error("Max size for uniform value is 4 elements");
				}
				this._uniformSizes[uniformName] = defaultValue.length;
				if (defaultValue.length === 1) {
					defs += "uniform float " + uniformName + ";\n";
				} else {
					defs += "uniform vec" + defaultValue.length + " " + uniformName + ";\n";
				}
			} else {
				throw new Error(
					"Default value for uniforms must be either number or array of numbers"
				);
			}
		}
		return defs;
	},

	// Inits the uNow uniform, and the user-provided uniforms, given the current GL program.
	// Sets the _isReRenderable property if there are any set uniforms.
	_initUniforms(program) {
		var gl = this._gl;
		this._uTileCoordsPosition = gl.getUniformLocation(program, "uTileCoords");
		this._uNowPosition = gl.getUniformLocation(program, "uNow");
		this._currentKernel = gl.getUniformLocation(program, "u_kernel[0]");
		this.kernelWeightLocation = gl.getUniformLocation(program, "u_kernelWeight");
		this._isReRenderable = false;

		if (this._uNowPosition) {
			gl.uniform1f(this._uNowPosition, performance.now());
			this._isReRenderable = true;
		}

		this._uniformLocations = {};
		for (var uniformName in this.options.uniforms) {
			this._uniformLocations[uniformName] = gl.getUniformLocation(program, uniformName);
			this.setUniform(uniformName, this.options.uniforms[uniformName]);
			this._isReRenderable = true;
		}
	},

	// This is called once per tile - uses the layer's GL context to
	//   render a tile, passing the complex space coordinates to the
	//   GPU, and asking to render the vertexes (as triangles) again.
	// Every pixel will be opaque, so there is no need to clear the scene.
	_render: function(coords) {
		var gl = this._gl;

		// this._currentKernel = kernels["unsharpen"];
		
		
		gl.viewport(0, 0, gl.drawingBufferWidth, gl.drawingBufferHeight);
		gl.clearColor(0.5, 0.5, 0.5, 0);
		gl.enable(gl.BLEND);
		var tileBounds = this._tileCoordsToBounds(coords);
		var west = tileBounds.getWest(),
			east = tileBounds.getEast(),
			north = tileBounds.getNorth(),
			south = tileBounds.getSouth();

		// Create data array for LatLng buffer
		// prettier-ignore
		var latLngData = [
			// Vertex 0
			east, north,

			// Vertex 1
			west, north,

			// Vertex 2
			east, south,

			// Vertex 3
			west, south,
		];

		// ...upload them to the GPU...
		gl.bindBuffer(gl.ARRAY_BUFFER, this._LatLngBuffer);
		gl.bufferData(gl.ARRAY_BUFFER, new Float32Array(latLngData), gl.STATIC_DRAW);

		// ...also create data array for CRS buffer...
		// Kinda inefficient, but doesn't look performance-critical
		var crs = this._map.options.crs,
			min = crs.project(L.latLng(south, west)),
			max = crs.project(L.latLng(north, east));

		// prettier-ignore
		var crsData = [
			// Vertex 0
			max.x, max.y,

			// Vertex 1
			min.x, max.y,

			// Vertex 2
			max.x, min.y,

			// Vertex 3
			min.x, min.y,
		];

		// ...and also upload that to the GPU...
		gl.bindBuffer(gl.ARRAY_BUFFER, this._CRSBuffer);
		gl.bufferData(gl.ARRAY_BUFFER, new Float32Array(crsData), gl.STATIC_DRAW);


		// ...and also set the uTileCoords uniform for this tile
		gl.uniform3f(this._uTileCoordsPosition, coords.x, coords.y, coords.z);

		gl.uniform1f(this._uFlipPosition, -1);

		var frameBufferNumber = 0;

		for (let index = 0; index < this.kernelSettings.length; index++) {
			const kernel = this.kernelSettings[index];
			if(kernel.strength === 0) {
				continue;
			}
			gl.uniform1fv(this._currentKernel, kernels[kernel.name]);
			gl.uniform1f(this.kernelWeightLocation, this.computeKernelWeight(kernels[kernel.name]));
			gl.bindFramebuffer(gl.FRAMEBUFFER, this.framebuffers[frameBufferNumber]);
			this.setUniform("uSharpenStrength", kernel.strength);
			gl.drawArrays(gl.TRIANGLE_STRIP, 0, 4);
			gl.bindTexture(gl.TEXTURE_2D, this.textures[frameBufferNumber]);
			frameBufferNumber++;
		}
	
		
		gl.uniform1f(this._uFlipPosition, 1);

		gl.uniform1fv(this._currentKernel, kernels["normal"]);
		gl.uniform1f(this.kernelWeightLocation, this.computeKernelWeight(kernels["normal"]));
 
		// clear the framebuffer
		gl.bindFramebuffer(gl.FRAMEBUFFER, null);
		

		gl.drawArrays(gl.TRIANGLE_STRIP, 0, 4);
		
	},

	computeKernelWeight: function(kernel) {
		var weight = kernel.reduce(function(prev, curr) {
			return prev + curr;
		});
		return weight <= 0 ? 1 : weight;
	},

	_bindTexture: function(index, imageData) {
		// Helper function. Binds a ImageData (HTMLImageElement, HTMLCanvasElement or
		// ImageBitmap) to a texture, given its index (0 to 7).
		// The image data is assumed to be in RGBA format.
		var gl = this._gl;

		gl.activeTexture(gl.TEXTURE0 + index);
		gl.bindTexture(gl.TEXTURE_2D, this._textures[index]);
		if (imageData.width < this.options.tileSize || imageData.height < this.options.tileSize) {
			// Scale up the texture to the next highest power of two dimensions.
			var canvas = document.createElement("canvas");
			canvas.width = this.options.tileSize;
			canvas.height = this.options.tileSize;
			var ctx = canvas.getContext("2d");
			ctx.fillStyle = "rgba(0,0,0,0.0)";
			ctx.fillRect(0, 0, canvas.width, canvas.height);
			ctx.drawImage(imageData, 0, 0, imageData.width, imageData.height);
			imageData = canvas;
		}
		gl.texImage2D(gl.TEXTURE_2D, 0, gl.RGBA, gl.RGBA, gl.UNSIGNED_BYTE, imageData);
		canvas = null;
		gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MIN_FILTER, gl.LINEAR_MIPMAP_NEAREST);
		gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_MAG_FILTER, gl.LINEAR);
		gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_S, gl.MIRRORED_REPEAT);
		gl.texParameteri(gl.TEXTURE_2D, gl.TEXTURE_WRAP_T, gl.MIRRORED_REPEAT);
		gl.generateMipmap(gl.TEXTURE_2D);
	},


isPowerOfTwo: function (x) {
    return (x & (x - 1)) == 0;
},

nextHighestPowerOfTwo: function(x) {
    --x;
    for (var i = 1; i < 32; i <<= 1) {
        x = x | x >> i;
    }
    return x + 1;
},

	// Gets called by L.GridLayer before createTile(), just before coord wrapping happens.
	// Needed to store the context of each <canvas> tile when the tile coords is wrapping.
	_addTile(coords, container) {
		// This is quite an ugly hack, but WTF.
		this._unwrappedKey = this._tileCoordsToKey(coords);
		L.GridLayer.prototype._addTile.call(this, coords, container);
	},
	//createTile Leaflet Documentation: https://leafletjs.com/reference-1.7.1.html#tilelayer-createtile
	createTile: function(coords, done) {
		if(coords.y < 0) {
			coords.y = 0;
		}

		var tile = L.DomUtil.create("canvas", "leaflet-tile");
		tile.width = tile.height = this.options.tileSize;
		tile.onselectstart = tile.onmousemove = L.Util.falseFn;
		
		var ctx = tile.getContext("2d");
		var unwrappedKey = this._unwrappedKey;
		var texFetches = [];
		for (var i = 0; i < this._tileLayers.length && i < 8; i++) {
			// 				this.options.tileUrls[i]
			texFetches.push(this._getNthTile(i, coords));
		}

		Promise.all(texFetches).then(
			function(textureImages) {
				if (!this._map) {
					return;
				}
				// If the shader is time-dependent (i.e. animated),
				// save the textures for later access
				if (this._isReRenderable) {
					var key = this._tileCoordsToKey(coords);
					this._fetchedTextures[key] = textureImages;
					this._2dContexts[unwrappedKey] = ctx;
				}

				var gl = this._gl;
				for (var i = 0; i < this._tileLayers.length && i < 8; i++) {
					this._bindTexture(i, textureImages[i]);
				}

				this._render(coords);
				ctx.drawImage(this._renderer, 0, 0);
				done();
			}.bind(this),
			function(err) {
				L.TileLayer.prototype._tileOnError.call(this, done, tile, err);
			}.bind(this)
		);
		tile.width = this.options.tileSize;
		tile.height = this.options.tileSize;
		tile.style.width = this.options.tileSize + this.options.overlap;
		tile.style.height =  this.options.tileSize + this.options.overlap;

		if(this.options._imageSize !== undefined) {
			if(this.options._imageSize[coords.z+1] !== undefined) {

				// console.log("Clipping tile")
				var xPercentage = 100;
				
				if(coords.x* this.options.tileSize +  this.options.tileSize > this.options._imageSize[coords.z+1].x) {
					xPercentage = 100 - 100 * ((coords.x * this.options.tileSize + this.options.tileSize - this.options._imageSize[coords.z+1].x) / this.options.tileSize);
				}
				var yPercentage = 100;
				if(coords.y* this.options.tileSize +  this.options.tileSize > this.options._imageSize[coords.z+1].y) {
					yPercentage =100 - 100*((coords.y* this.options.tileSize +  this.options.tileSize - this.options._imageSize[coords.z+1].y) / this.options.tileSize);
				}
				if(xPercentage < 1) xPercentage = 100;
				if(yPercentage < 1) yPercentage = 100;
				tile.style.clipPath = "polygon(0% 0%," + xPercentage  + "% 0%," + xPercentage  + "% " + yPercentage  + "%,  0% " + yPercentage  + "%)";

			}
		}

		
		return tile;
	},

	_initTile: function (tile) {
		L.GridLayer.prototype._initTile(tile);
		// DomUtil.addClass(tile, 'leaflet-tile');

		var tileSize = this.getTileSize();
		tile.style.width = tileSize.x + 1 + 'px';
		tile.style.height = tileSize.y + 1 + 'px';

		// tile.onselectstart = Util.falseFn;
		// tile.onmousemove = Util.falseFn;

		// // update opacity on tiles in IE7-8 because of filter inheritance problems
		// if (Browser.ielt9 && this.options.opacity < 1) {
		// 	DomUtil.setOpacity(tile, this.options.opacity);
		// }

		// // without this hack, tiles disappear after zoom on Chrome for Android
		// // https://github.com/Leaflet/Leaflet/issues/2078
		// if (Browser.android && !Browser.android23) {
		// 	tile.style.WebkitBackfaceVisibility = 'hidden';
		// }
	},

	// getTileSize: function() {
    //     // var map = this._map,
    //     //     tileSize = L.GridLayer.prototype.getTileSize.call(this),
    //     //     zoom = this._tileZoom + this.options.zoomOffset,
    //     //     zoomN = this.options.maxNativeZoom;

    //     // tileSize.x = tileSize.x + this.options.overlap; // with deepzoom, our tile size removes the overlap, but leaflet needs it.
    //     // tileSize.y = tileSize.y + this.options.overlap;
    //     // // increase tile size when overscaling
    //     // var outputSize= zoomN !== null && zoom > zoomN ?
    //     //     tileSize.divideBy(map.getZoomScale(zoomN, zoom)).round() :
    //     //     tileSize;

    //     return L.point([255, 255]);
    // },

	_removeTile: function(key) {
		if (this._isReRenderable) {
			delete this._fetchedTextures[key];
			delete this._2dContexts[key];
		}
		L.TileLayer.prototype._removeTile.call(this, key);
	},
	//onAdd Leaflet Documentation: https://leafletjs.com/reference-1.7.1.html#layer-onadd
	onAdd: function(map) {
		// If the shader is time-dependent (i.e. animated), start an animation loop.
		if (this._uNowPosition) {
			L.Util.cancelAnimFrame(this._animFrame);
			this._animFrame = L.Util.requestAnimFrame(this._onFrame, this);
		}
		
		L.TileLayer.prototype.onAdd.call(this);
	},
	//onRemove Leaflet Documentation: https://leafletjs.com/reference-1.7.1.html#layer-onremove
	onRemove: function(map) {
		// Stop the animation loop, if any.
		L.Util.cancelAnimFrame(this._animFrame);
		L.TileLayer.prototype.onRemove.call(this, map);
	},

	_onFrame: function() {
		if (this._uNowPosition && this._map) {
			this.reRender();
			this._animFrame = L.Util.requestAnimFrame(this._onFrame, this, false);
		}
	},

	// Runs the shader (again) on all tiles
	reRender: function() {
		if (!this._isReRenderable) {
			return;
		}
		var gl = this._gl;

		gl.uniform1f(this._uNowPosition, performance.now());

		for (var key in this._tiles) {
			var tile = this._tiles[key];
			var coords = this._keyToTileCoords(key);
			var wrappedKey = this._tileCoordsToKey(this._wrapCoords(coords));

			if (!tile.current || !tile.loaded || !this._fetchedTextures[wrappedKey]) {
				continue;
			}

			for (var i = 0; i < this._tileLayers.length && i < 8; i++) {
				this._bindTexture(i, this._fetchedTextures[wrappedKey][i]);
			}
			this._render(coords);

			this._2dContexts[key].drawImage(this._renderer, 0, 0);
		}
	},

	// Sets the value(s) for a uniform.
	setUniform(name, value) {
		switch (this._uniformSizes[name]) {
			case 0:
				this._gl.uniform1f(this._uniformLocations[name], value);
				break;
			case 1:
				this._gl.uniform1fv(this._uniformLocations[name], value);
				break;
			case 2:
				this._gl.uniform2fv(this._uniformLocations[name], value);
				break;
			case 3:
				this._gl.uniform3fv(this._uniformLocations[name], value);
				break;
			case 4:
				this._gl.uniform4fv(this._uniformLocations[name], value);
				break;
		}
	},
	
	setKernelsAndStrength(kernelArray) {
		this.kernelSettings = kernelArray;
		this.reRender();
	},

	getTileSize: function() {
        var map = this._map,
            tileSize = L.GridLayer.prototype.getTileSize.call(this),
            zoom = this._tileZoom + this.options.zoomOffset,
            zoomN = this.options.maxNativeZoom;

        tileSize.x = tileSize.x - this.options.overlap; // with deepzoom, our tile size removes the overlap, but leaflet needs it.
        tileSize.y = tileSize.y - this.options.overlap;


        // increase tile size when overscaling
        var outputSize= zoomN !== null && zoom > zoomN ?
            tileSize.divideBy(map.getZoomScale(zoomN, zoom)).round() :
            tileSize;

        return outputSize;
    },


	// Gets the tile for the Nth `TileLayer` in `this._tileLayers`,
	// for the given tile coords, returns a promise to the tile.
	_getNthTile: function(n, coords) {
		var layer = this._tileLayers[n];
		// Monkey-patch a few things, both for TileLayer and TileLayer.WMS
		layer._tileZoom = this._tileZoom;
		layer._map = this._map;
		layer._crs = this._map.options.crs;
		layer._globalTileRange = this._globalTileRange;
		return new Promise(
			function(resolve, reject) {
				var tile = document.createElement("img");
				tile.crossOrigin = "";
				tile = layer.getTileUrl(coords, tile);
				L.DomEvent.on(tile, "load", resolve.bind(this, tile));
				L.DomEvent.on(tile, "error", reject.bind(this, tile));
			}.bind(this)
		);
	},

	//Gets the rgb data over a given area, can be rotated by the given angle (make angle optional in future?)
	//Input is an array with 4 latlngs, and the angle to rotate the area to be perpendicular
	getImageData: async function(corners, angle, zoom, cssFilters) {
		//Overall process is to find which tiles are included in the collection area, paste them to a canvas and collect the data
		//Based on separating axis theorem https://en.wikipedia.org/wiki/Hyperplane_separation_theorem#Use_in_collision_detection

		//Create diolog to back out of image data collection if needed
		let content = document.getElementById("image-data-collection-dialog").innerHTML;

		let exitDialog = L.control.dialog({
			'size': [385, 60],
			'maxSize': [Number.MAX_SAFE_INTEGER, Number.MAX_SAFE_INTEGER],
			'anchor': [25, 600],
			'initOpen': true,
			'position': 'topleft',
			'minSize': [0, 0]
		}).setContent(content).addTo(this._map).lock()

		//Set up variables for later
        // let canvas = document.getElementById("ard-canvas"); //For getting canvas visually if bug testing
		let canvas = document.createElement("canvas");

        let ctx = canvas.getContext("2d");
		let tileSize = this.getTileSize();
		this._map.setZoom(zoom, {animate: false}) //Zoom in to desired level so projections work properly

		for (let i = 0; i < 4; i++) {
			corners[i] = this._map.project(corners[i], zoom) //change latlngs to x & y coords
		}

		//find location of points if they were rotated about origin to find location of each corner relative to each other
		let postRotationCoords = [], xCoords = [], yCoords = [];
		for (let i = 0; i < 4; i++) {
			let p = corners[i];
			let x = Math.round(p.x * Math.cos(angle) - p.y * Math.sin(angle));
			let y = Math.round(p.x * Math.sin(angle) + p.y * Math.cos(angle));

			postRotationCoords.push(L.point(x, y));
			xCoords.push(x);
			yCoords.push(y);
		}

		//Get around for rounding/integer overflow errors
		xCoords.sort((a, b) => a - b);
		yCoords.sort((a, b) => a - b);
		xCoords = xCoords.slice(1,3)
		yCoords = yCoords.slice(1,3)

		//Assign each given point a corner of rectangle
		let bottomLeft, bottomRight, topLeft, topRight
		for (let i = 0; i < 4; i++) {
			let corner = postRotationCoords[i];

			if (corner.x <= Math.min(...xCoords) && corner.y >= Math.max(...yCoords)) {
				bottomLeft = corners[i];
			}
			if (corner.x <= Math.min(...xCoords) && corner.y <= Math.min(...yCoords)) {
				topLeft = corners[i];
			}
			if (corner.x >= Math.max(...xCoords) && corner.y <= Math.min(...yCoords)) {
				topRight = corners[i];
			}
			if (corner.x >= Math.max(...xCoords) && corner.y >= Math.max(...yCoords)) {
				bottomRight = corners[i];
			}
		}

		//Find center coords of rectangle by finding midpoint of the diagonal
		let xc = bottomLeft.x + 0.5 * (topRight.x - bottomLeft.x);
		let yc = bottomLeft.y + 0.5 * (topRight.y - bottomLeft.y);

		//Find width and height of rectangle
		let w = Math.sqrt((bottomRight.x - bottomLeft.x)**2 + (bottomRight.y - bottomLeft.y)**2);
		let h = Math.sqrt((topLeft.x - bottomLeft.x)**2 + (topLeft.y - bottomLeft.y)**2)

		//half diagonals of area
		let halfWidth = (w/2)*Math.abs(Math.cos(angle)) + (h/2)*Math.abs(Math.sin(angle));
		let halfHeight = (w/2)*Math.abs(Math.sin(angle)) + (h/2)*Math.abs(Math.cos(angle));

		//AABB Coords (axis alligned bounding box)
		let xMinAABB = xc - halfWidth, xMaxAABB = xc + halfWidth;
		let yMinAABB = yc - halfHeight, yMaxAABB = yc + halfHeight;

		//Visualize AABB (uncomment for bug testing)
		// let p1 = this._map.unproject(L.point(xMaxAABB, yMaxAABB), zoom);
		// let p4 = this._map.unproject(L.point(xMinAABB, yMinAABB), zoom);
		// let bounds = L.latLngBounds(p1, p4)
		// L.rectangle(bounds, {color: "blue"}).addTo(this._map)

		//Tile Range in AABB
		let imin = Math.floor(xMinAABB/tileSize.x), imax = Math.ceil(xMaxAABB/tileSize.x) - 1;
		let jmin = Math.floor(yMinAABB/tileSize.y), jmax = Math.ceil(yMaxAABB/tileSize.y) - 1;

		//projections of collection area
		let rumin = (xc*Math.cos(angle) - yc*Math.sin(angle) - w/2);
		let rumax = (xc*Math.cos(angle) - yc*Math.sin(angle) + w/2);
		let rvmin = (-xc*Math.sin(angle) - yc*Math.cos(angle) - h/2);
		let rvmax = (-xc*Math.sin(angle) - yc*Math.cos(angle) + h/2);

		let tileHalfExtent = tileSize.x/2 * (Math.abs(Math.cos(angle)) + Math.abs(Math.sin(angle)));

		let startPoint = topLeft
		let startTileCoords = startPoint.unscaleBy(tileSize).floor();
		let offset = startPoint.subtract(startTileCoords.scaleBy(tileSize))
		offset = L.point(offset.x + tileSize.x*(startTileCoords.x - imin), offset.y + tileSize.y*(startTileCoords.y - jmin))

		canvas.height = h + 500; //Over estimate canvas size so no tiles are cut off
		canvas.width = w + 500;
		
		//Allow for any area in future; currently just return an error
		let sizeError = true;
		try {
			ctx.translate(0, -tileSize.y*(startTileCoords.y - jmin)) //Shift start upward if collection area starts below jmin
			sizeError = !sizeError
		} catch {
			exitDialog.remove();
			return "sizeError"
		}

		// if (sizeError == true) {
		// 	return false
		// }

		// let subAreaCount = 1;
		// while (sizeError) {
		// 	try {
		// 		// canvas.width = w / subAreaCount + 1000;
		// 		ctx.translate(0, -tileSize.y*(startTileCoords.y - jmin)) //Shift start upward if collection area starts below jmin


		// 		!sizeError;
		// 		break;
		// 	} catch {
		// 		subAreaCount++;
		// 		return false
		// 		// continue;
		// 	}
		// }

		//The built-in function to get image data is only over a non-rotated rectangle, so rotate tiles instead
		ctx.translate(offset.x, offset.y)
		ctx.rotate(angle)
		ctx.translate(-offset.x, -offset.y)

		//Break from getting image data
		let exit = false
		$("#image-data-collection-exit").on("click", () => {
			exit = true;
		})

		//Recursive function to grab and paste tiles onto canvas, then collect data
		let pasteTilesToCanvas = function(i, j, GLLayerObject, resolveCallback) {
			$("#image-data-collection-exit").on("click", () => {
				resolveCallback(false);
				exit = true;
			})
			for (i; i <= imax; i++) {
				for (j; j <= jmax; j++) {
					if (exit) { return }
					let tileCenter = L.point(tileSize.x * (i + 0.5), tileSize.y * (j + 0.5));

					//Find projections of tile to check for collisions
					let tu = tileCenter.x * Math.cos(angle) - tileCenter.y * Math.sin(angle);
					let tv = -tileCenter.x * Math.sin(angle) - tileCenter.y * Math.cos(angle);

					let tuMin = tu - tileHalfExtent, tuMax = tu + tileHalfExtent;
					let tvMin = tv - tileHalfExtent, tvMax = tv + tileHalfExtent;
					if ((tuMin <= rumax && rumin <= tuMax) && (tvMin <= rvmax && rvmin <= tvMax)) {
						let coords = L.point(i, j);
						coords.z = zoom;
						let tile = GLLayerObject._tiles[GLLayerObject._tileCoordsToKey(coords)];

						if (!tile || !tile.active) { //assume that all tiles in the AABB exist, will fail to finish function if not
							let pt = L.point((i) * tileSize.x, j * tileSize.y);
							let ll = GLLayerObject._map.unproject(pt, zoom)
							GLLayerObject._map.on("moveend", function placeholder() {
								GLLayerObject._map.removeEventListener("moveend", placeholder);
								setTimeout(() => {
									tile = GLLayerObject._tiles[GLLayerObject._tileCoordsToKey(coords)]
									if (tile !== undefined && tile.loaded) {
										pasteTilesToCanvas(i, j, GLLayerObject, resolveCallback);
									} else {
										GLLayerObject.addEventListener("load", function placeholder2() {
											GLLayerObject.removeEventListener("load", placeholder2);
											pasteTilesToCanvas(i, j, GLLayerObject, resolveCallback);
										});
									}
								}, 300)
								
								// setTimeout(pasteTilesToCanvas, 750, i, j, GLLayerObject, resolveCallback) //Create a delay to allow tiles to load (can cause issues)
								// pasteTilesToCanvas(i, j, GLLayerObject, resolveCallback)
							})
							GLLayerObject._map.flyTo(ll, zoom, {animate: false})
							return
						}
						else {
							if (cssFilters && cssFilters != "") {
								ctx.filter = cssFilters
							}
							ctx.drawImage(tile.el, (i-imin)*tileSize.x,(j-jmin)*tileSize.y)
							//visualize tiles on canvas (bug testing)
							// ctx.beginPath()
							// ctx.rect((i-imin)*tileSize.x, (j-jmin)*tileSize.y,tileSize.x, tileSize.y)
							// ctx.stroke()				
							
							//visualize tiles on map (bug testing)
							// let q = GLLayerObject._map.unproject(L.point(i*tileSize.x, j*tileSize.y)), r = GLLayerObject._map.unproject(L.point((i+1)*tileSize.x, (j+1)*tileSize.y));
							// let b = L.latLngBounds(q, r);
							// L.rectangle(b, {color: "black"}).addTo(GLLayerObject._map)
						}

					}			
				}
				j = jmin
			}

			// Visualize collection area
			// ctx.resetTransform();
			// ctx.beginPath()
			// ctx.strokeStyle = "blue"
			// ctx.rect(offset.x, offset.y -tileSize.x*(startTileCoords.y - jmin), w, h)
			// ctx.stroke();

			//getImageData doesn't use ctx transformations
			resolveCallback(ctx.getImageData(offset.x, offset.y -tileSize.y*(startTileCoords.y - jmin), w, h));
		}

		//Go to top left corner
		let pt = L.point((imin + imax)/2 * tileSize.x, (jmin + jmax)/2 * tileSize.y);
		let ll = this._map.unproject(pt, zoom);
		this._map.flyTo(ll, zoom, {animate: false})

		let collectData = function(GLLayerObject) { //GLLayer Object is just 'this', need to input as parameter to get around scope/async issues
			const promise = new Promise((resolve) => {
				pasteTilesToCanvas(imin, jmin, GLLayerObject, (result) => {resolve(result)});
			})
			return promise;
		}

		//Get the raw data
		let rawData = await collectData(this);
		exitDialog.remove();
		if (rawData === false) {
			return "exitError";
		}

		//Organize data into a h x w matrix, with each entry [r, g, b]
		let r,g,b;
		let width = rawData.width;
		let height = rawData.height;
		let colorMatrix = [];

		let index = 0;
		let data = rawData.data;
		for (let row = 0; row < height; row++) {
			if (!colorMatrix[row]) { colorMatrix[row] = []};
			for (let col = 0; col < width; col++) {
				r = data[index];
				g = data[index + 1];
				b = data[index + 2];
				index += 4;
				colorMatrix[row].push([r,g,b])
			}
		}
		

		// document.getElementById("ard-canvas").append(canvas)
		// document.getElementById("imageMap").append(canvas)
		return colorMatrix
	},
});

L.tileLayer.gl = function(opts) {
	return new L.TileLayer.GL(opts);
};