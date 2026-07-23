/**
 * pyramidTiff.js — minimal (Big)TIFF pyramid reader for Elevator's tiled-iiif derivatives.
 *
 * Replaces geotiff.js (~134KB) for the narrow job we actually need: reading each
 * pyramid level's TileOffsets[] / TileByteCounts[] / JPEGTables so the viewer can
 * range-fetch individual JPEG tiles out of a single S3 object.
 *
 * Why a custom reader:
 *   The derivatives are BigTIFF pyramids written by `vips tiffsave --pyramid
 *   --compression jpeg --depth onepixel`. geotiff.js walks the IFD linked-list one
 *   blocking 64KB range request at a time, so on a multi-hundred-MB/GB file stored on
 *   S3 it issues ~10+ SEQUENTIAL round-trips (~1.2s of latency) before a single tile
 *   is drawn.
 *
 * Key layout fact this reader exploits:
 *   vips lays the pyramid out largest-level-first, so the small overview levels the
 *   initial viewport actually uses — together with their tile data and JPEGTables —
 *   all live in the last couple of MB of the file. We therefore fetch the 16-byte
 *   header and an ~N-MB tail slice in parallel, then resolve every IFD whose offset
 *   falls inside that tail slice straight from memory (zero extra network). Only the
 *   handful of large zoom-in levels near the front require an on-demand range read,
 *   and only when the user actually zooms to them.
 *
 * Public API (intentionally geotiff-compatible for a drop-in swap):
 *   const tiff = await PyramidTiff.fromUrl(url, fileSize [, { tailBytes }]);
 *   const level = await tiff.getImage(index);   // index 0 = largest level
 *   level.getWidth() / getHeight() / getTileWidth() / getTileHeight()
 *   level.fileDirectory.TileOffsets      // Array<number>
 *   level.fileDirectory.TileByteCounts   // Array<number>
 *   level.fileDirectory.JPEGTables       // Uint8Array
 */
(function (global) {
	'use strict';

	// TIFF field type -> byte size
	var TYPE_SIZE = { 1: 1, 2: 1, 3: 2, 4: 4, 5: 8, 6: 1, 7: 1, 8: 2, 9: 4, 10: 8, 11: 4, 12: 8, 16: 8, 17: 8, 18: 8 };

	var TAG_IMAGE_WIDTH = 256;
	var TAG_IMAGE_HEIGHT = 257;
	var TAG_TILE_WIDTH = 322;
	var TAG_TILE_HEIGHT = 323;
	var TAG_TILE_OFFSETS = 324;
	var TAG_TILE_BYTE_COUNTS = 325;
	var TAG_JPEG_TABLES = 347;

	function PyramidTiff(url, fileSize, opts) {
		opts = opts || {};
		this.url = url;
		this.fileSize = Number(fileSize) || 0;
		// Tail slice size. Correctness never depends on this — a level whose data falls
		// outside the tail is simply fetched over the network — it only trades bytes for
		// round-trips. 4MB comfortably covers the overview cluster of very large images.
		this.tailBytes = opts.tailBytes || (4 * 1024 * 1024);
		this._levels = null;          // parsed IFD directory, largest level first
		this._images = {};            // index -> materialized level (cache)
		this._imagePromises = {};     // index -> in-flight materialize promise (coalescing)
	}

	PyramidTiff.fromUrl = function (url, fileSize, opts) {
		var t = new PyramidTiff(url, fileSize, opts);
		return t._init().then(function () { return t; });
	};

	PyramidTiff.prototype._readRange = function (start, end) {
		// end is inclusive
		return fetch(this.url, { headers: { Range: 'bytes=' + start + '-' + end } }).then(function (r) {
			if (r.status !== 206 && r.status !== 200) {
				throw new Error('pyramidTiff: range request failed (' + r.status + ')');
			}
			return r.arrayBuffer();
		});
	};

	// Is [off, off+len) fully inside the fetched tail slice?
	PyramidTiff.prototype._inTail = function (off, len) {
		return off >= this._tailStart && (off + len) <= this.fileSize;
	};

	// DataView over an absolute file range that lives inside the tail slice.
	PyramidTiff.prototype._tailView = function (off, len) {
		return new DataView(this._tail, off - this._tailStart, len);
	};

	PyramidTiff.prototype._init = function () {
		var self = this;
		var haveSize = self.fileSize > 0;
		self._tailStart = haveSize ? Math.max(0, self.fileSize - self.tailBytes) : Infinity;

		// Kick off the tail read immediately, but DON'T block the chain walk on it. The
		// walk starts the moment the tiny header arrives so the unavoidable sequential
		// reads of the few large front-level IFDs overlap with the tail download; by the
		// time the walk reaches the overview levels near EOF, the tail buffer is ready and
		// those levels resolve from memory with no further requests.
		self._tailReady = haveSize
			? self._readRange(self._tailStart, self.fileSize - 1).then(function (buf) { self._tail = buf; })
			: Promise.resolve();

		return self._readRange(0, 15).then(function (buf) {
			var header = new DataView(buf);
			var le = String.fromCharCode(header.getUint8(0), header.getUint8(1)) === 'II';
			var magic = header.getUint16(2, le);
			if (magic !== 42 && magic !== 43) {
				throw new Error('pyramidTiff: not a TIFF (magic ' + magic + ')');
			}
			self._le = le;
			self._big = magic === 43;             // 43 = BigTIFF
			self._offsetSize = self._big ? 8 : 4;
			self._entrySize = self._big ? 20 : 12;
			self._countSize = self._big ? 8 : 2;

			var firstIFD = self._big ? Number(header.getBigUint64(8, le)) : header.getUint32(4, le);
			return self._walkChain(firstIFD);
		});
	};

	// Read an unsigned integer of the file's native offset width.
	PyramidTiff.prototype._readOffset = function (dv, pos) {
		return this._big ? Number(dv.getBigUint64(pos, this._le)) : dv.getUint32(pos, this._le);
	};

	// Read one scalar value of a given TIFF type from a DataView position.
	PyramidTiff.prototype._readScalar = function (dv, pos, type) {
		var le = this._le;
		switch (type) {
			case 3: return dv.getUint16(pos, le);           // SHORT
			case 4: case 9: case 13: return dv.getUint32(pos, le); // LONG / SLONG / IFD
			case 16: case 17: case 18: return Number(dv.getBigUint64(pos, le)); // LONG8 / SLONG8 / IFD8
			case 1: case 6: case 7: return dv.getUint8(pos); // BYTE / SBYTE / UNDEFINED
			default: return dv.getUint32(pos, le);
		}
	};

	/**
	 * Walk the IFD linked-list from `firstIFD`, building a lightweight directory for
	 * every level: scalar dimensions plus *descriptors* (not yet materialized arrays)
	 * for TileOffsets / TileByteCounts / JPEGTables. IFDs that fall inside the tail
	 * slice are parsed from memory; the few large front levels are fetched on the fly.
	 */
	PyramidTiff.prototype._walkChain = function (firstIFD) {
		var self = this;
		self._levels = [];
		var next = firstIFD;

		function step() {
			if (!next) return Promise.resolve();
			if (self._levels.length > 64) return Promise.resolve(); // guard against a corrupt chain

			var ifdOffset = next;
			// Window big enough for the entry table: count field + N*entrySize + next pointer.
			var windowLen = self._countSize + 64 * self._entrySize + self._offsetSize;

			var viewP;
			if (self._inTail(ifdOffset, self._countSize)) {
				// IFD lives in the tail slice — wait for that (usually already-complete)
				// download, then parse straight from memory. No per-level request.
				viewP = self._tailReady.then(function () {
					var avail = self.fileSize - ifdOffset;
					return { dv: self._tailView(ifdOffset, Math.min(windowLen, avail)), base: 0, abs: ifdOffset };
				});
			} else {
				var end = Math.min(ifdOffset + windowLen - 1, (self.fileSize ? self.fileSize - 1 : ifdOffset + windowLen - 1));
				viewP = self._readRange(ifdOffset, end).then(function (buf) {
					return { dv: new DataView(buf), base: 0, abs: ifdOffset };
				});
			}

			return viewP.then(function (w) {
				var dv = w.dv, base = w.base;
				var nEntries = self._big ? Number(dv.getBigUint64(base, self._le)) : dv.getUint16(base, self._le);
				var p = base + self._countSize;
				var level = { ifdOffset: ifdOffset, width: 0, height: 0, tileWidth: 256, tileHeight: 256, descriptors: {} };

				for (var i = 0; i < nEntries; i++) {
					var tag = dv.getUint16(p, self._le);
					var type = dv.getUint16(p + 2, self._le);
					var count = self._big ? Number(dv.getBigUint64(p + 4, self._le)) : dv.getUint32(p + 4, self._le);
					var valueFieldPos = p + 4 + self._countSize; // start of the 4/8-byte value-or-offset field
					var valueFieldAbs = w.abs + (valueFieldPos - base);

					switch (tag) {
						case TAG_IMAGE_WIDTH: level.width = self._readScalar(dv, valueFieldPos, type); break;
						case TAG_IMAGE_HEIGHT: level.height = self._readScalar(dv, valueFieldPos, type); break;
						case TAG_TILE_WIDTH: level.tileWidth = self._readScalar(dv, valueFieldPos, type); break;
						case TAG_TILE_HEIGHT: level.tileHeight = self._readScalar(dv, valueFieldPos, type); break;
						case TAG_TILE_OFFSETS:
						case TAG_TILE_BYTE_COUNTS:
						case TAG_JPEG_TABLES:
							level.descriptors[tag] = self._makeDescriptor(dv, valueFieldPos, valueFieldAbs, type, count);
							break;
						default: break;
					}
					p += self._entrySize;
				}

				next = self._readOffset(dv, p);
				self._levels.push(level);
				return step();
			});
		}

		return step();
	};

	/**
	 * Capture where a field's value lives so it can be materialized later without the
	 * IFD window still being in scope. Small inline values are copied immediately; large
	 * out-of-line arrays record their absolute file offset for a lazy tail-slice read or
	 * range request.
	 */
	PyramidTiff.prototype._makeDescriptor = function (dv, valueFieldPos, valueFieldAbs, type, count) {
		var byteLen = (TYPE_SIZE[type] || 1) * count;
		var desc = { type: type, count: count, byteLen: byteLen };
		if (byteLen <= this._offsetSize) {
			// Value is stored inline in the entry — copy the bytes now.
			desc.inline = new Uint8Array(byteLen);
			for (var i = 0; i < byteLen; i++) { desc.inline[i] = dv.getUint8(valueFieldPos + i); }
		} else {
			desc.dataOffset = this._readOffset(dv, valueFieldPos);
		}
		return desc;
	};

	// Resolve a descriptor's raw bytes as a DataView (from the tail slice or a range read).
	PyramidTiff.prototype._resolveBytes = function (desc) {
		var self = this;
		if (desc.inline) {
			return Promise.resolve(new DataView(desc.inline.buffer, desc.inline.byteOffset, desc.inline.byteLength));
		}
		if (self._inTail(desc.dataOffset, desc.byteLen)) {
			return self._tailReady.then(function () { return self._tailView(desc.dataOffset, desc.byteLen); });
		}
		return self._readRange(desc.dataOffset, desc.dataOffset + desc.byteLen - 1).then(function (buf) {
			return new DataView(buf);
		});
	};

	// Materialize a numeric array (TileOffsets / TileByteCounts).
	PyramidTiff.prototype._resolveArray = function (desc) {
		var self = this;
		if (!desc) return Promise.resolve(null);
		return self._resolveBytes(desc).then(function (dv) {
			var ts = TYPE_SIZE[desc.type] || 4;
			var out = new Array(desc.count);
			for (var i = 0; i < desc.count; i++) { out[i] = self._readScalar(dv, i * ts, desc.type); }
			return out;
		});
	};

	// Materialize JPEGTables as a Uint8Array (byte-for-byte the tag value).
	PyramidTiff.prototype._resolveBytesArray = function (desc) {
		if (!desc) return Promise.resolve(null);
		return this._resolveBytes(desc).then(function (dv) {
			return new Uint8Array(dv.buffer, dv.byteOffset, dv.byteLength);
		});
	};

	PyramidTiff.prototype.getImageCount = function () {
		return this._levels ? this._levels.length : 0;
	};

	/**
	 * Return a geotiff-compatible "image" for pyramid level `index` (0 = largest),
	 * materializing its offset tables and JPEG tables on first request and caching them.
	 */
	PyramidTiff.prototype.getImage = function (index) {
		var self = this;
		index = index || 0;
		if (self._images[index]) return Promise.resolve(self._images[index]);
		if (self._imagePromises[index]) return self._imagePromises[index];

		var level = self._levels[index];
		if (!level) return Promise.reject(new Error('pyramidTiff: no level ' + index));

		var p = Promise.all([
			self._resolveArray(level.descriptors[TAG_TILE_OFFSETS]),
			self._resolveArray(level.descriptors[TAG_TILE_BYTE_COUNTS]),
			self._resolveBytesArray(level.descriptors[TAG_JPEG_TABLES])
		]).then(function (parts) {
			var image = {
				getWidth: function () { return level.width; },
				getHeight: function () { return level.height; },
				getTileWidth: function () { return level.tileWidth; },
				getTileHeight: function () { return level.tileHeight; },
				fileDirectory: {
					TileOffsets: parts[0],
					TileByteCounts: parts[1],
					JPEGTables: parts[2]
				}
			};
			self._images[index] = image;
			delete self._imagePromises[index];
			return image;
		});

		self._imagePromises[index] = p;
		return p;
	};

	global.PyramidTiff = PyramidTiff;
})(typeof window !== 'undefined' ? window : this);
