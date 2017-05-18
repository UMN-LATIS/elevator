// Leaflet Arrows by Meteotest
// https://github.com/meteotest/leaflet-arrows

// Module Loader Boilerplate
(function(factory, window) {

  // define an AMD module that relies on 'leaflet'
  if (typeof define === 'function' && define.amd) {
    define(['leaflet'], factory);

    // define a Common JS module that relies on 'leaflet'
  } else if (typeof exports === 'object') {
    module.exports = factory(require('leaflet'));
  }

  // attach your plugin to the global 'L' variable
  if (typeof window !== 'undefined' && window.L) {
    window.L.Arrow = factory(L);
  }
}(function(L) {
  // beware! the arrow factory
  var Arrow = L.FeatureGroup.extend({
    options: {
      distanceUnit: 'km', // can be [px,km]
      stretchFactor: 1, // should the distance be stretched?
      arrowheadLength: 4, // in distance unit
      arrowheadClosingLine: false, // should a closing third line be drawn?
      arrowheadDegree: 155, // degree of arrowhead
      clickableWidth: 10, // defines the width in pixels of the "phantom" path to capture click events on a line

      //optional: popupContent: function(data) {},

      // add your own custom validator, probably not needed...
      // the validator gets the bound data object as argument
      validator: function(data) {
        return typeof data.latlng !== "undefined" &&
          typeof data.distance !== "undefined" && !isNaN(data.distance);
      }, // validator is a callback function that takes the data object of the current point and returns whether it is 'valid'. Invalid arrows will be drawn gray

      colorScheme: function() {
        return this.color;
      }, // add own colorscheme callback

      circleRadiusInvalidPoint: 1000, // Radius of the circle to display missing or '0'-value

      // path options
      color: '#333',
      opacity: 0.9, // stroke opacity
      fillOpacity: 0.9,
      weight: 4, // the width of the arrow
      smoothFactor: 0,
      radius: 5, // default radius, when distance is 0

      invalidPointOptions: {
        stroke: false,
        fillOpacity: 0.8,
        fillColor: '#111',
        radius: 7
      },

      drawSourceMarker: false,
      sourceMarkerOptions: {
        stroke: false,
        fillOpacity: 1,
        fillColor: '#333',
        radius: 5
      }
    },

    /**
     * The data opject needs at least following properties:
     *   - latlng : L.LatLng
     *   - degree : float
     *   - distance: float
     */
    initialize: function(data, options) {
      L.Util.setOptions(this, options);
      this.setData(data);
    },

    onAdd: function(map) {

      this._map = map;
      this.redraw();
      // add a viewreset event listener for updating layer's position
      map.on('viewreset', this._reset, this);
    },

    onRemove: function() {
      // remove layer's DOM elements and listeners
      for (var i in this._layers) {
        this._map.removeLayer(this._layers[i]);
      }

      if (typeof this._sourceMarker !== "undefined") {
        this._map.removeLayer(this._sourceMarker);
      }

      this._map.off('viewreset', this._reset, this);
    },

    // draw
    redraw: function() {
      // only draw when on map
      if (!this._map) {
        return;
      }

      this._layers = {};

      // bind popup to the whole feature group
      if (typeof this.options.popupContent === 'function') {
        this.bindPopup(this.options.popupContent(this._data));
      }

      // is current arrow valid according to the validator callback?
      // change color if not
      if (typeof this.options.validator === "function" ||
        this.options.validator(this._data)) {

        this.options.color = typeof this.options.colorScheme === "function" ?
          this.options.colorScheme(this._data) : this.options.color;
        this._data.distance = parseFloat(this._data.distance);
      } else {
        this._data.distance = 0;
        this._data.angle = 0;
      }
      // if distance or degree is 0  then draw a point instead of an arrow
      if (this._data.distance === 0 || this._data.angle === 0) {
        var circle,
          pathOptions = this.options.invalidPointOptions;

        if (this.options.distanceUnit.toLowerCase() === 'km') {
          // use a tenth of the supplied radius (usally used in the circlemarker)
          circle = L.circle(this._data.latlng, this.options.radius / 10, pathOptions);
        } else {
          circle = L.circleMarker(this._data.latlng, pathOptions);
        }

        this.addLayer(circle);

      } else {
        var theLine = [
          this._data.latlng,
          this._calculateEndPoint(
            this._data.latlng,
            this._data.distance, this._data.angle
          )
        ];
        var theArrow = this._calculateArrowArray(theLine[1]);

        var backgroundPathOption = L.Util.setOptions({}, this.pathOptions);

        backgroundPathOption.opacity = 0;
        backgroundPathOption.weight = this.options.clickableWidth;
        var invisibleBackgroundPolyline = new L.Polyline([theLine, theArrow],
          backgroundPathOption);
        var polyline = new L.Polyline([theLine, theArrow], this.options);

        this.addLayer(invisibleBackgroundPolyline);
        this.addLayer(polyline);


        // that special case, where a circle has to be drawn on the source of the arrow
        if (this.options.drawSourceMarker) {
          if (typeof this._sourceMarker === 'undefined') {
            // use the same coloar as the arrow does
            this.options.sourceMarkerOptions.fillColor = this.color;
            this._sourceMarker = L.circleMarker(this._data.latlng, this.options.sourceMarkerOptions);
          } else {
            // there is a chance, that the latlng values have been changed by the setData-function
            this._sourceMarker.setLatLng(this._data.latlng);
          }
        }
      }
    },

    // custom methods

    // just change the angle of the arrow
    setAngle: function(angle) {
      if (isNaN(angle)) {
        this._data.angle = 0; // undefined direction.
      } else {
        if (this.options.isWindDegree) {
          this._data.angle = angle - 180;
          if (this.options.distanceUnit.toLowerCase() === 'px') {
            this._data.angle -= 90;
          }
        } else {
          this._data.angle = angle;
        }
      }
      this.redraw();
    },

    // just change the length of the arrow
    setDistance: function(distance) {
      if (isNaN(distance)) {
        this._data.distance = 0;
      } else {
        this._data.distance = parseFloat(distanceUnit);
      }
      this.redraw();
    },

    // use this method to update the whole dataset that corresponds to the arrow
    setData: function(data) {
      this._data = data;
      this.setAngle(data.degree);
    },


    // private methods

    // When using Screen Pixels as unit, the arrows have to be
    // recalculated after the zoom level has changed
    _reset: function() {
      if (this.options.distanceUnit.toLowerCase() === 'px') {
        this.redraw();
      }
    },

    _calculateEndPoint: function(latlng, dist, degree) {
      /*
       * http://www.codeguru.com/cpp/cpp/algorithms/article.php/c5115/Geographic-Distance-and-Azimuth-Calculations.htm
       */
      // don't use this._data here.
      // this function is also used to find the points of the arrow

      var distance = dist * this.options.stretchFactor,
        d2r = (Math.PI / 180), // degree 2 radius
        r2d = (180 / Math.PI);

      if (this.options.distanceUnit.toLowerCase() === 'km') {
        var R = 6378.137, // earth radius in kmeters
          bearing = degree * d2r;

        distance = distance / R;
        var a = Math.acos(Math.cos(distance) *
          Math.cos((90 - latlng.lat) * d2r) +
          Math.sin((90 - latlng.lat) * d2r) *
          Math.sin(distance) *
          Math.cos(bearing));
        var B = Math.asin(Math.sin(distance) * Math.sin(bearing) / Math.sin(a));
        return new L.LatLng(90 - a * r2d, B * r2d + latlng.lng);

      } else if (this.options.distanceUnit.toLowerCase() === 'px') {
        var sourcePoint = this._map.latLngToLayerPoint(latlng);
        var rad = degree * d2r;
        var vector = L.point(Math.cos(rad) * distance, Math.sin(rad) * distance);
        var targetPoint = sourcePoint.add(vector);
        return this._map.layerPointToLatLng(targetPoint);

      } else {
        throw Error("calculate end point undefined for distanceUnit: " + this.options.distanceUnit);
      }
    },

    _calculateArrowArray: function(latlng) {
      // calculates the Array for the arrow
      // latlng is the position, where the arrow is added
      var degree = this._data.angle;

      if (latlng.length !== undefined) {
        latlng = new L.LatLng(latlng);
      }
      var firstEdge = this._calculateEndPoint(latlng,
        this.options.arrowheadLength,
        degree - this.options.arrowheadDegree);

      var arr = [firstEdge, latlng,
        this._calculateEndPoint(latlng, this.options.arrowheadLength, degree + this.options.arrowheadDegree)
      ];

      if (this.options.arrowheadClosingLine) {
        arr.push(firstEdge);
      }
      return arr;
    }
  });
  return Arrow;
}, window));
