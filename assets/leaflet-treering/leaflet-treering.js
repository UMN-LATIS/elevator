/**
 * @file Leaflet Treering
 * @author Malik Nusseibeh <nusse007@umn.edu>
 * @version 1.0.0
 */

// 'use strict';
  
/**
 * A leaflet treering object
 * @constructor
 * @param {Leaflet Map Object} viewer - the leaflet map object that will used
 *   as a viewer for treering image.
 * @param {string} basePath - this is a path to the treering image folder
 * @param {object} options -
 */
function LTreering (viewer, basePath, options) {
  this.viewer = viewer;
  this.basePath = basePath;

  //options
  this.meta = {
    'ppm': options.ppm || 468,
    'saveURL': options.saveURL || '',
    'savePermission': options.savePermission || false,
    'popoutUrl': options.popoutUrl || null,
    'assetName': options.assetName || 'N/A',
    'hasLatewood': options.hasLatewood,
  }

  if (options.ppm === 0) {
    alert('Please set up PPM in asset metadata. PPM will default to 468.');
  }

  this.data = new MeasurementData(options.initialData);
  this.aData = new AnnotationData(options.initialData.annotations);
  this.autoscroll = new Autoscroll(this.viewer);
  this.mouseLine = new MouseLine(this);
  this.visualAsset = new VisualAsset(this);
  this.annotationAsset = new AnnotationAsset(this);
  this.panhandler = new Panhandler(this);
  
  this.popout = new Popout(this);
  this.undo = new Undo(this);
  this.redo = new Redo(this);

  this.viewData = new ViewData(this);

  this.imageAdjustment = new ImageAdjustment(this);
  this.calibration = new Calibration(this);

  this.createAnnotation = new CreateAnnotation(this);
  this.deleteAnnotation = new DeleteAnnotation(this);
  this.editAnnotation = new EditAnnotation(this);

  this.dating = new Dating(this);

  this.createPoint = new CreatePoint(this);
  this.zeroGrowth = new CreateZeroGrowth(this);
  this.createBreak = new CreateBreak(this);

  this.deletePoint = new DeletePoint(this);
  this.cut = new Cut(this);
  this.insertPoint = new InsertPoint(this);
  this.insertZeroGrowth = new InsertZeroGrowth(this);
  this.insertBreak = new InsertBreak(this);

  this.saveLocal = new SaveLocal(this);
  this.loadLocal = new LoadLocal(this);
  var ioBtns = [this.saveLocal.btn, this.loadLocal.btn];
  if (options.savePermission) {
    this.saveCloud = new SaveCloud(this);
    ioBtns.push(this.saveCloud.btn);
  }

  this.undoRedoBar = new L.easyBar([this.undo.btn, this.redo.btn]);
  this.annotationTools = new ButtonBar(this, [this.createAnnotation.btn, this.deleteAnnotation.btn, this.editAnnotation.btn], 'comment', 'Manage annotations');
  this.createTools = new ButtonBar(this, [this.createPoint.btn, this.zeroGrowth.btn, this.createBreak.btn], 'straighten', 'Create new measurement point');
  this.editTools = new ButtonBar(this, [this.deletePoint.btn, this.cut.btn, this.insertPoint.btn, this.insertZeroGrowth.btn, this.insertBreak.btn], 'edit', 'Edit and delete data points from the series');
  this.ioTools = new ButtonBar(this, ioBtns, 'folder_open', 'View and download data');
  if (window.name === 'popout')
    this.settings = new ButtonBar(this, [this.imageAdjustment.btn, this.calibration.btn], 'settings', 'Change image and calibration settings');
  else
    this.settings = new ButtonBar(this, [this.imageAdjustment.btn], 'settings', 'Change image settings');

  this.tools = [this.viewData, this.calibration, this.createAnnotation, this.deleteAnnotation, this.editAnnotation, this.dating, this.createPoint, this.createBreak, this.deletePoint, this.cut, this.insertPoint, this.insertZeroGrowth, this.insertBreak, this.imageAdjustment];

  this.baseLayer = {
    'Tree Ring': layer
  };

  this.overlay = {
    'Points': this.visualAsset.markerLayer,
    'H-bar': this.mouseLine.layer,
    'Lines': this.visualAsset.lineLayer,
    'Annotations': this.annotationAsset.markerLayer
  };
  
  /**
   * Load the interface of the treering viewer
   * @function loadInterface
   */
  LTreering.prototype.loadInterface = function() {
    console.log(this);

    this.autoscroll.on();
    this.viewer.on('resize', () => {
      this.autoscroll.reset();
    });

    $('#map').css('cursor', 'default');

    // if popout is opened display measuring tools
    if (window.name === 'popout') {
      this.viewData.btn.addTo(this.viewer);
      this.annotationTools.bar.addTo(this.viewer);
      this.dating.btn.addTo(this.viewer);
      this.createTools.bar.addTo(this.viewer);
      this.editTools.bar.addTo(this.viewer);
      this.ioTools.bar.addTo(this.viewer);
      this.settings.bar.addTo(this.viewer);
      this.undoRedoBar.addTo(this.viewer);
    } else {
      this.popout.btn.addTo(this.viewer);
      this.viewData.btn.addTo(this.viewer);
      this.ioTools.bar.addTo(this.viewer);
      this.settings.bar.addTo(this.viewer);
    }

    L.control.layers(this.baseLayer, this.overlay).addTo(this.viewer);
    
        // right and left click controls
    this.viewer.on('contextmenu', () => {
      if (!this.createPoint.active && this.data.points[0] !== undefined &&
          this.createTools.btn._currentState.stateName === 'expand') {
        this.disableTools();
        this.createPoint.startPoint = false;
        this.createPoint.active = true;
        this.createPoint.enable();
        this.mouseLine.from(points[index - 1].latLng);
      } else {
        this.disableTools();
      }
    });
    
    if ( this.meta.savePermission ) {
      // initialize cloud save
      this.saveCloud.initialize();
    }

    this.loadData();

  };

  /**
   * Load the JSON data attached to the treering image
   * @function loadData
   */
  LTreering.prototype.loadData = function() {
    this.visualAsset.reload();
    this.annotationAsset.reload();
    if ( this.meta.savePermission ) {
      // load the save information in buttom left corner
      this.saveCloud.displayDate();
    }
  };

  /**
   * Disable any tools
   * @function disableTools
   */
  LTreering.prototype.disableTools = function() {
    this.tools.forEach(e => { e.disable() });
  };
  
  LTreering.prototype.collapseTools = function() {
    this.annotationTools.collapse();
    this.createTools.collapse();
    this.editTools.collapse();
    this.ioTools.collapse();
    this.settings.collapse();
  };
}

/*******************************************************************************/
  
/**
 * A measurement data object
 * @constructor
 * @param {object} dataObject -
 */
function MeasurementData (dataObject) {
  this.saveDate = dataObject.saveDate || dataObject.SaveDate || {};
  this.index = dataObject.index || 0;
  this.year = dataObject.year || 0;
  this.earlywood = dataObject.earlywood || true;
  this.points = dataObject.points || {};
  this.annotations = dataObject.annotations || {};
  
 /**
  * Add a new point into the measurement data
  * @function newPoint
  */
  MeasurementData.prototype.newPoint = function(start, latLng, hasLatewood) {
    if (start) {
      this.points[this.index] = {'start': true, 'skip': false, 'break': false, 'latLng': latLng};
    } else {
      this.points[this.index] = {'start': false, 'skip': false, 'break': false, 'year': this.year, 'earlywood': this.earlywood, 'latLng': latLng};
      if (hasLatewood) {
        if (this.earlywood) {
          this.earlywood = false;
        } else {
          this.earlywood = true;
          this.year++;
        }
      } else {
        this.year++;
      }
    }
    this.index++;
  };
  
  /**
   * delete a point from the measurement data
   * @function deletePoint
   */
  MeasurementData.prototype.deletePoint = function(i, hasLatewood) {
    var second_points;
    var shift;
    if (this.points[i].start) {
      if (this.points[i - 1] != undefined && this.points[i - 1].break) {
        i--;
        second_points = Object.values(this.points).splice(i + 2, this.index - 1);
        shift = this.points[i + 2].year - this.points[i - 1].year - 1;
        second_points.map(e => {
          e.year -= shift;
          this.points[i] = e;
          i++;
        });
        this.year -= shift;
        this.index -= 2;
        delete this.points[this.index];
        delete this.points[this.index + 1];
      } else {
        second_points = Object.values(this.points).splice(i + 1, this.index - 1);
        second_points.map(e => {
          if (!i) {
            this.points[i] = {'start': true, 'skip': false, 'break': false,
              'latLng': e.latLng};
          } else {
            this.points[i] = e;
          }
          i++;
        });
        this.index--;
        delete this.points[this.index];
      }
    } else if (this.points[i].break) {
      second_points = Object.values(this.points).splice(i + 2, this.index - 1);
      shift = this.points[i + 2].year - this.points[i - 1].year - 1;
      second_points.map(e => {
        e.year -= shift;
        this.points[i] = e;
        i++;
      });
      this.year -= shift;
      this.index -= 2;
      delete this.points[this.index];
      delete this.points[this.index + 1];
    } else {
      var new_points = this.points;
      var k = i;
      second_points = Object.values(this.points).splice(i + 1, this.index - 1);
      second_points.map(e => {
        if (!e.start && !e.break) {
          if (hasLatewood) {
            e.earlywood = !e.earlywood;
            if (!e.earlywood) {
              e.year--;
            }
          } else {
            e.year--;
          }
        }
        new_points[k] = e;
        k++;
      });

      this.points = new_points;
      this.index--;
      delete this.points[this.index];
      this.earlywood = !this.earlywood;
      if (this.points[this.index - 1].earlywood) {
        this.year--;
      }
    }
  };
  
  /**
   * remove a range of points from the measurement data
   * @function cut
   */
  MeasurementData.prototype.cut = function(i, j) {
    if (i > j) {
      var trimmed_points = Object.values(this.points).splice(i, this.index - 1);
      var k = 0;
      this.points = {};
      trimmed_points.map(e => {
        if (!k) {
          this.points[k] = {'start': true, 'skip': false, 'break': false,
            'latLng': e.latLng};
        } else {
          this.points[k] = e;
        }
        k++;
      });
      this.index = k;
    } else if (i < j) {
      this.points = Object.values(this.points).splice(0, i);
      this.index = i;
    } else {
      alert('You cannot select the same point');
    }
  };
  
  /**
   * insert a point in the middle of the measurement data
   * @function insertPoint
   */
  MeasurementData.prototype.insertPoint = function(latLng, hasLatewood) {
    var i = 0;
    while (this.points[i] != undefined &&
        this.points[i].latLng.lng < latLng.lng) {
      i++;
    }
    if (this.points[i] == null) {
      alert('New point must be within existing points.' +
          'Use the create toolbar to add new points to the series.');
      return;
    }

    var new_points = this.points;
    var second_points = Object.values(this.points).splice(i, this.index - 1);
    var k = i;
    var year_adjusted = this.points[i].year;
    var earlywood_adjusted = true;

    if (this.points[i - 1].earlywood && hasLatewood) {
      year_adjusted = this.points[i - 1].year;
      earlywood_adjusted = false;
    } else if (this.points[i - 1].start) {
      year_adjusted = this.points[i + 1].year;
    } else {
      year_adjusted = this.points[i - 1].year + 1;
    }
    new_points[k] = {'start': false, 'skip': false, 'break': false,
      'year': year_adjusted, 'earlywood': earlywood_adjusted,
      'latLng': latLng};

    var tempK = k;
    
    //visualAsset.newLatLng(new_points, k, latLng);
    k++;

    second_points.map(e => {
      if (!e.start && !e.break) {
        if (hasLatewood) {
          e.earlywood = !e.earlywood;
          if (e.earlywood) {
            e.year++;
          }
        }
        else {
          e.year++;
        }
      }
      new_points[k] = e;
      k++;
    });

    this.points = new_points;
    this.index = k;
    if (hasLatewood) {
      this.earlywood = !this.earlywood;
    }
    if (!this.points[this.index - 1].earlywood || !hasLatewood) {
      this.year++;
    }
    
    return tempK;
  };
  
  /**
   * insert a zero growth year in the middle of the measurement data
   * @function insertZeroGrowth
   */
  MeasurementData.prototype.insertZeroGrowth = function(i, latLng, hasLatewood) {
    var new_points = this.points;
    var second_points = Object.values(this.points).splice(i + 1, this.index - 1);
    var k = i + 1;

    var year_adjusted = this.points[i].year + 1;

    new_points[k] = {'start': false, 'skip': false, 'break': false,
      'year': year_adjusted, 'earlywood': true, 'latLng': latLng};
    
    k++;

    if (hasLatewood) {
      new_points[k] = {'start': false, 'skip': false, 'break': false,
        'year': year_adjusted, 'earlywood': false, 'latLng': latLng};
      k++;
    }
    
    var tempK = k-1;

    second_points.map(e => {
      if (!e.start && !e.break) {
        e.year++;
      }
      new_points[k] = e;
      k++;
    });

    this.points = new_points;
    this.index = k;
    this.year++;
    
    return tempK;
  };
  
  /**
   * remove any entries in the data
   * @function clean
   */
  MeasurementData.prototype.clean =function() {
    for (var i in this.points) {
      if (this.points[i] === null || this.points[i] === undefined) {
        delete this.points[i];
      }
    }
  };
  
  /**
   * getter for all data
   * @function data
   */
  MeasurementData.prototype.data = function() {
    return {'saveDate': this.saveDate, 'year': this.year,
        'earlywood': this.earlywood, 'index': this.index, 'points': this.points,
        'annotations': this.annotations};
  };
}

function AnnotationData (annotations) {
  if (annotations !== undefined) {
    this.annotations = annotations.annotations || annotations;
    this.index = annotations.index || 0;
  } else {
    this.annotations = {};
    this.index = 0;
  }
  
  AnnotationData.prototype.deleteAnnotation = function(i) {
    delete this.annotations[i];
  }
}

/**
 * Autoscroll feature for mouse
 * @constructor
 * @param {Leaflet Map Object} viewer - a refrence to the leaflet map object
 */
function Autoscroll (viewer) {

  /**
   * Turn on autoscroll based on viewer dimmensions
   * @function on
   */
  Autoscroll.prototype.on = function() {
    var mapSize = viewer.getSize();   // Map size used for map scrolling
    var mousePos = 0;                 // An initial mouse position

    viewer.on('mousemove', (e) => {
      var oldMousePos = mousePos;     // Save the old mouse position
      mousePos = e.containerPoint;    // Container point of the mouse

      //left bound of the map
      if (mousePos.x <= 40 && mousePos.y > 60 && oldMousePos.x > mousePos.x) {
        viewer.panBy([-200, 0]);
      }
      //right bound of the map
      if (mousePos.x + 40 > mapSize.x && mousePos.y > 100 && oldMousePos.x < mousePos.x) {
        viewer.panBy([200, 0]);
      }
      //upper bound of the map
      if (mousePos.x > 300 && mousePos.x + 60 < mapSize.x && mousePos.y < 40 && oldMousePos.y > mousePos.y) {
        viewer.panBy([0, -70]);
      }
      //lower bound of the map
      if (mousePos.x >= 40 && mousePos.y > mapSize.y - 40 && oldMousePos.y < mousePos.y) {
        viewer.panBy([0, 70]);
      }
    });
  };

  /**
   * Turn off autoscroll
   * @function off
   */
  Autoscroll.prototype.off = function() {
    viewer.off('mousemove');
  };

  /**
   * Reset autoscroll when the viewer's dimmensions are resized
   * @function reset
   */
  Autoscroll.prototype.reset = function() {
    this.off();
    this.on();
  };
}

/**
 * A function that returns a leaflet icon given a particular color
 * @function
 * @param {string} color - a color string
 * @param {string} LtBasePath - the base path of the asset
 */
function MarkerIcon(color, LtBasePath) {

  var colors = {
    'light_blue': { 'path': '/assets/leaflet-treering/images/light_blue_tick_icon.png',
                    'size': [32, 48] },
    'dark_blue' : { 'path': '/assets/leaflet-treering/images/dark_blue_tick_icon.png',
                    'size': [32, 48] },
    'white'     : { 'path': '/assets/leaflet-treering/images/white_tick_icon.png',
                    'size': [32, 48] },
    'red'       : { 'path': '/assets/leaflet-treering/images/red_dot_icon.png',
                    'size': [12, 12] }
  };

  return L.icon({
    iconUrl : colors[color].path,
    iconSize: colors[color].size
  });
}
  
/**
 * The mouse line created between a click location and the cursor
 * @constructor
 * @param {LTreering} Lt - a refrence to the leaflet treering object
 */
function MouseLine (Lt) {
  this.layer = L.layerGroup().addTo(Lt.viewer);
  this.active = false;
   
  /**
   * Enable the mouseline
   * @function enable
   */
  MouseLine.prototype.enable = function() {
    this.active = true;
  }
  
  /**
   * Disable the mouseline
   * @function disable
   */
  MouseLine.prototype.disable = function() {
    this.active = false;
    $(Lt.viewer._container).off('mousemove');
    this.layer.clearLayers();
  }
  
  /**
   * A method to create a new line from a given latLng
   * @function from
   * @param {Leatlet LatLng Object} latLng - the latLng coordinate on the viewer 
   *   to create a line from
   */
  MouseLine.prototype.from = function(latLng) {
    var newX, newY;
    $(Lt.viewer._container).mousemove(e => {
      if (this.active) {
        this.layer.clearLayers();
        var mousePoint = Lt.viewer.mouseEventToLayerPoint(e);
        var mouseLatLng = Lt.viewer.mouseEventToLatLng(e);
        var point = Lt.viewer.latLngToLayerPoint(latLng);

        /* Getting the four points for the h bars,
      this is doing 90 degree rotations on mouse point */
        newX = mousePoint.x +
            (point.x - mousePoint.x) * Math.cos(Math.PI / 2) -
            (point.y - mousePoint.y) * Math.sin(Math.PI / 2);
        newY = mousePoint.y +
            (point.x - mousePoint.x) * Math.sin(Math.PI / 2) +
            (point.y - mousePoint.y) * Math.cos(Math.PI / 2);
        var topRightPoint = Lt.viewer.layerPointToLatLng([newX, newY]);

        newX = mousePoint.x +
            (point.x - mousePoint.x) * Math.cos(Math.PI / 2 * 3) -
            (point.y - mousePoint.y) * Math.sin(Math.PI / 2 * 3);
        newY = mousePoint.y +
            (point.x - mousePoint.x) * Math.sin(Math.PI / 2 * 3) +
            (point.y - mousePoint.y) * Math.cos(Math.PI / 2 * 3);
        var bottomRightPoint = Lt.viewer.layerPointToLatLng([newX, newY]);

        //doing rotations 90 degree rotations on latlng
        newX = point.x +
            (mousePoint.x - point.x) * Math.cos(Math.PI / 2) -
            (mousePoint.y - point.y) * Math.sin(Math.PI / 2);
        newY = point.y +
            (mousePoint.x - point.x) * Math.sin(Math.PI / 2) +
            (mousePoint.y - point.y) * Math.cos(Math.PI / 2);
        var topLeftPoint = Lt.viewer.layerPointToLatLng([newX, newY]);

        newX = point.x +
            (mousePoint.x - point.x) * Math.cos(Math.PI / 2 * 3) -
            (mousePoint.y - point.y) * Math.sin(Math.PI / 2 * 3);
        newY = point.y +
            (mousePoint.x - point.x) * Math.sin(Math.PI / 2 * 3) +
            (mousePoint.y - point.y) * Math.cos(Math.PI / 2 * 3);
        var bottomLeftPoint = Lt.viewer.layerPointToLatLng([newX, newY]);

        var color;
        if (Lt.data.earlywood || !Lt.meta.hasLatewood) {
          color = '#00BCD4';
        } else {
          color = '#00838f';
        }

        this.layer.addLayer(L.polyline([latLng, mouseLatLng],
            {interactive: false, color: color, opacity: '.75',
              weight: '3'}));
        this.layer.addLayer(L.polyline([topLeftPoint, bottomLeftPoint],
            {interactive: false, color: color, opacity: '.75',
              weight: '3'}));
        this.layer.addLayer(L.polyline([topRightPoint, bottomRightPoint],
            {interactive: false, color: color, opacity: '.75',
              weight: '3'}));
      }
    });
  }
}

/**
 * Visual assets on the map such as markers and lines
 * @constructor
 * @param {LTreering} Lt - a refrence to the leaflet treering object
 */
function VisualAsset (Lt) {
  this.markers = new Array();
  this.lines = new Array();
  this.markerLayer = L.layerGroup().addTo(Lt.viewer);
  this.lineLayer = L.layerGroup().addTo(Lt.viewer);
  this.previousLatLng = undefined;
  
  /**
   * Reload all visual assets on the viewer
   * @function reload
   */
  VisualAsset.prototype.reload = function() {
    //erase the markers
    this.markerLayer.clearLayers();
    this.markers = new Array();
    //erase the lines
    this.lineLayer.clearLayers();
    this.lines = new Array();

    //plot the data back onto the map
    if (Lt.data.points !== undefined) {
      Object.values(Lt.data.points).map((e, i) => {
        if (e != undefined) {
          this.newLatLng(Lt.data.points, i, e.latLng);
        }
      });
    }
  }
  
  /**
   * A method used to create new markers and lines on the viewer
   * @function newLatLng
   * @param {Array} points - 
   * @param {int} i - index of points
   * @param {Leaflet LatLng Object} latLng -
   */
  VisualAsset.prototype.newLatLng = function(pts, i, latLng) {
    var leafLatLng = L.latLng(latLng);

    var draggable = false;
    if (window.name === 'popout') {
      draggable = true;
    }

    var marker;

    //check if index is the start point
    if (pts[i].start) {
      marker = L.marker(leafLatLng, {
        icon: new MarkerIcon('white', Lt.basePath),
        draggable: draggable,
        title: 'Start Point',
        riseOnHover: true
      });
    } else if (pts[i].break) { //check if point is a break
      marker = L.marker(leafLatLng, {
        icon: new MarkerIcon('white', Lt.basePath),
        draggable: draggable,
        title: 'Break Point',
        riseOnHover: true
      });
    } else if (Lt.meta.hasLatewood) { //check if point is earlywood
      if (pts[i].earlywood) {
        marker = L.marker(leafLatLng, {
          icon: new MarkerIcon('light_blue', Lt.basePath),
          draggable: draggable,
          title: 'Year ' + pts[i].year + ', earlywood',
          riseOnHover: true
        });
      } else { //otherwise it's latewood
        marker = L.marker(leafLatLng, {
          icon: new MarkerIcon('dark_blue', Lt.basePath),
          draggable: draggable,
          title: 'Year ' + pts[i].year + ', latewood',
          riseOnHover: true
        });
      }
    } else {
      marker = L.marker(leafLatLng, {
        icon: new MarkerIcon('light_blue', Lt.basePath),
        draggable: draggable,
        title: 'Year ' + pts[i].year,
        riseOnHover: true
      });
    }

    this.markers[i] = marker;   //add created marker to marker_list

    //tell marker what to do when being dragged
    this.markers[i].on('drag', (e) => {
      if (!pts[i].start) {
        this.lineLayer.removeLayer(this.lines[i]);
        this.lines[i] =
            L.polyline([this.lines[i]._latlngs[0], e.target._latlng],
            { color: this.lines[i].options.color,
              opacity: '.75', weight: '3'});
        this.lineLayer.addLayer(this.lines[i]);
      }
      if (this.lines[i + 1] !== undefined) {
        this.lineLayer.removeLayer(this.lines[i + 1]);
        this.lines[i + 1] =
            L.polyline([e.target._latlng, this.lines[i + 1]._latlngs[1]],
            { color: this.lines[i + 1].options.color,
              opacity: '.75',
              weight: '3'
            });
        this.lineLayer.addLayer(this.lines[i + 1]);
      } else if (this.lines[i + 2] !== undefined && !pts[i + 1].start) {
        this.lineLayer.removeLayer(this.lines[i + 2]);
        this.lines[i + 2] =
            L.polyline([e.target._latlng, this.lines[i + 2]._latlngs[1]],
            { color: this.lines[i + 2].options.color,
              opacity: '.75',
              weight: '3' });
        this.lineLayer.addLayer(this.lines[i + 2]);
      }
    });

    //tell marker what to do when the draggin is done
    this.markers[i].on('dragend', (e) => {
      Lt.undo.push();
      pts[i].latLng = e.target._latlng;
    });

    //tell marker what to do when clicked
    this.markers[i].on('click', (e) => {
      if (Lt.deletePoint.active) {
        Lt.deletePoint.action(i);
      }
      
      if (Lt.cut.active) {
        if (Lt.cut.point != -1) {
          Lt.cut.action(i);
        } else {
          Lt.cut.fromPoint(i);
        }
      }
      if (Lt.insertZeroGrowth.active) {
        if ((pts[i].earlywood && Lt.meta.hasLatewood) || pts[i].start ||
            pts[i].break) {
          alert('Missing year can only be placed at the end of a year!');
        } else {
          Lt.insertZeroGrowth.action(i);
        }
      }
      if (Lt.insertBreak.active) {
        Lt.insertBreak.action(i);
      }
      if (Lt.dating.active) {
        Lt.dating.action(i);
      }
    });

    //drawing the line if the previous point exists
    if (pts[i - 1] != undefined && !pts[i].start) {
      if (pts[i].earlywood || !Lt.meta.hasLatewood || 
          (!pts[i - 1].earlywood && pts[i].break)) {
        var color = '#00BCD4';
      } else {
        var color = '#00838f';
      }
      this.lines[i] =
          L.polyline([pts[i - 1].latLng, leafLatLng],
          {color: color, opacity: '.75', weight: '3'});
      this.lineLayer.addLayer(this.lines[i]);
    }

    this.previousLatLng = leafLatLng;
    //add the marker to the marker layer
    this.markerLayer.addLayer(this.markers[i]);   
  };
}

function AnnotationAsset(Lt) {
  this.markers = new Array();
  this.markerLayer = L.layerGroup().addTo(Lt.viewer);
  
  AnnotationAsset.prototype.reload = function() {
    this.markerLayer.clearLayers();
    this.markers = new Array();
    Lt.aData.index = 0;
    if (Lt.aData.annotations != undefined) {
      var reduced = Object.values(Lt.aData.annotations).filter(e => e != undefined);
      Lt.aData.annotations = {};
      reduced.map((e, i) => Lt.aData.annotations[i] = e);

      Object.values(Lt.aData.annotations).map((e, i) => {
        this.newAnnotation(Lt.aData.annotations, i);
        Lt.aData.index++;
      });
    }
  };
  
  AnnotationAsset.prototype.popupMouseover = function(e) {
    this.openPopup();
  };
  
  AnnotationAsset.prototype.popupMouseout = function(e) {
    this.closePopup();
  };
  
  AnnotationAsset.prototype.newAnnotation = function(ants, i) {
    var ref = ants[i];
    
    if (ref.text == '') {
      Lt.aData.deleteAnnotation(i);
      return;
    }

    var circle = L.circle(ref.latLng, {radius: .0001, color: 'red',
      weight: '6'});
    circle.bindPopup(ref.text, {closeButton: false});
    this.markers[i] = circle;
    this.markers[i].clicked = false;

    $(this.markers[i]).click(e => {
      if (Lt.editAnnotation.active) {
        this.editAnnotation(i);
      } else if (Lt.deleteAnnotation.active) {
        Lt.deleteAnnotation.action(i);
      }
    });

    $(this.markers[i]).mouseover(this.popupMouseover);
    $(this.markers[i]).mouseout(this.popupMouseout);

    this.markerLayer.addLayer(this.markers[i]);
  };
  
  AnnotationAsset.prototype.editAnnotation = function(i) {
    let marker = this.markers[i];

    $(marker).off('mouseover');
    $(marker).off('mouseout');

    marker.setPopupContent('<textarea id="comment_input" name="message"' +
        'rows="2" cols="15">' + Lt.aData.annotations[i].text + '</textarea>');
    marker.openPopup();
    document.getElementById('comment_input').select();

    $(document).keypress(e => {
      var key = e.which || e.keyCode;
      if (key === 13) {
        if ($('#comment_input').val() != undefined) {
          let string = ($('#comment_input').val()).slice(0);

          if (string != '') {
            Lt.aData.annotations[i].text = string;
            marker.setPopupContent(string);
            $(marker).mouseover(this.popupMouseover);
            $(marker).mouseout(this.popupMouseout);
          } else {
            Lt.deleteAnnotation.action(i);
          }
        }
        Lt.editAnnotation.disable();
      }
    });
  };
} 

/*****************************************************************************/

/**
 * A wrapper object around leaflet buttons
 * @constructor
 * @param {string} icon - a material design icon name
 * @param {string} toolTip - a tool tip message
 * @param {function} enable - the function for onClick events
 * @param {function} disable - this is an option function for stateful buttons
 */
function Button(icon, toolTip, enable, disable) {
  var states = [];
  states.push({
    stateName: 'inactive',
    icon: '<i class="material-icons md-18">'+icon+'</i>',
    title: toolTip,
    onClick: enable
  });
  if (disable !== null) {
    states.push({
      stateName: 'active',
      icon: '<i class="material-icons md-18">clear</i>',
      title: 'Cancel',
      onClick: disable
    })
  }
  return L.easyButton({states: states});
}

/**
 * A collapsable button bar
 * @constructor
 * @param {LTreering} Lt - a leaflet treering object
 * @param {Button[]} btns - a list of Buttons that belong to the button bar
 * @param {string} icon - a material design icon name
 * @param {string} toolTip - a tool tip message
 */
function ButtonBar(Lt, btns, icon, toolTip) {
  this.btns = btns;

  this.btn = L.easyButton({
    states: [
      {
        stateName: 'collapse',
        icon: '<i class="material-icons md-18">'+icon+'</i>',
        title: toolTip,
        onClick: () => {
          Lt.disableTools();
          Lt.collapseTools();
          this.expand();
        }
      },
      {
        stateName: 'expand',
        icon: '<i class="material-icons md-18">expand_less</i>',
        title: 'Collapse',
        onClick: () => {
          this.collapse();
        }
      }]
  });
  
  this.bar = L.easyBar([this.btn].concat(this.btns));
  
  /**
   * Expand the menu bar
   * @function expand
   */
  ButtonBar.prototype.expand = function() {
    this.btn.state('expand');
    this.btns.forEach(e => { e.enable() });
  }
  
  /**
   * Collapse the menu bar
   * @function collapse
   */
  ButtonBar.prototype.collapse = function() {
    this.btn.state('collapse');
    this.btns.forEach(e => { e.disable() });
  }
  
  this.collapse();
}

/*****************************************************************************/

/**
 * A popout of the leaflet viewer
 * @constructor
 * @param {Ltreering} Lt - Leaflet treering object
 */
function Popout(Lt) {
  this.btn = new Button('launch', 'Open a popout window', () => {
    window.open(Lt.meta.popoutUrl, 'popout',
                'location=yes,height=600,width=800,scrollbars=yes,status=yes');
  });
}

/**
 * Undo actions
 * @constructor
 * @param {Ltreering} Lt - Leaflet treering object
 */
function Undo(Lt) {
  this.stack = new Array();
  this.btn = new Button('undo', 'Undo', () => { this.pop() });
  this.btn.disable();
  
  /**
   * Push the current state into stack to retrieve in the case of an undo event
   * @function push
   */
  Undo.prototype.push = function() {
    this.btn.enable();
    Lt.redo.btn.disable();
    Lt.redo.stack.length = 0;
    var restore_points = JSON.parse(JSON.stringify(Lt.data.points));
    this.stack.push({'year': Lt.data.year, 'earlywood': Lt.data.earlywood,
      'index': Lt.data.index, 'points': restore_points });
  };
  
  /**
   * Pop the last state from the stack, update the data, and push to the redo stack
   * @function pop
   */
  Undo.prototype.pop = function() {
    if (this.stack.length > 0) {
      if (Lt.data.points[Lt.data.index - 1].start) {
        Lt.createPoint.disable();
      } else {
        Lt.mouseLine.from(Lt.data.points[Lt.data.index - 2].latLng);
      }

      Lt.redo.btn.enable();
      var restore_points = JSON.parse(JSON.stringify(Lt.data.points));
      Lt.redo.stack.push({'year': Lt.data.year, 'earlywood': Lt.data.earlywood,
        'index': Lt.data.index, 'points': restore_points});
      var dataJSON = this.stack.pop();

      Lt.data.points = JSON.parse(JSON.stringify(dataJSON.points));

      Lt.data.index = dataJSON.index;
      Lt.data.year = dataJSON.year;
      Lt.data.earlywood = dataJSON.earlywood;

      Lt.visualAsset.reload();

      if (this.stack.length == 0) {
        this.btn.disable();
      }
    }
  };
}

/**
 * Redo actions
 * @constructor
 * @param {Ltreering} Lt - Leaflet treering object
 */
function Redo(Lt) {
  this.stack = new Array(); 
  this.btn = new Button('redo', 'Redo', () => { this.pop()});
  this.btn.disable();

  /**
   * Pop the last state in the stack and update data
   * @function pop
   */
  Redo.prototype.pop = function() {
    Lt.undo.btn.enable();
    var restore_points = JSON.parse(JSON.stringify(Lt.data.points));
    Lt.undo.stack.push({'year': Lt.data.year, 'earlywood': Lt.data.earlywood,
      'index': Lt.data.index, 'points': restore_points});
    var dataJSON = this.stack.pop();

    Lt.data.points = JSON.parse(JSON.stringify(dataJSON.points));

    Lt.data.index = dataJSON.index;
    Lt.data.year = dataJSON.year;
    Lt.data.earlywood = dataJSON.earlywood;

    Lt.visualAsset.reload();

    if (this.stack.length == 0) {
      this.btn.disable();
    }
  };
}

/**
 * Calibrate the ppm using a known measurement
 * @constructor
 * @param {Ltreering} Lt - Leaflet treering object
 */
function Calibration(Lt) {
  this.active = false;
  this.popup = L.popup({closeButton: false}).setContent(
              '<input type="number" style="border:none; width:50px;"' +
              'value="10" id="length"></input> mm')
  this.btn = new Button(
    'space_bar', 
    'Calibrate the ppm using a known measurement on the image', 
    () => { Lt.disableTools(); this.enable() },
    () => { this.disable() }
  );
  
  Calibration.prototype.calculatePPM = function(p1, p2, length) {
    var startPoint = Lt.viewer.project(p1, Lt.viewer.getMaxZoom());
    var endPoint = Lt.viewer.project(p2, Lt.viewer.getMaxZoom());
    var pixel_length = Math.sqrt(Math.pow(Math.abs(startPoint.x - endPoint.x), 2) +
        Math.pow(Math.abs(endPoint.y - startPoint.y), 2));
    var pixelsPerMillimeter = pixel_length / length;
    var retinaFactor = 1;
    // if (L.Browser.retina) {
    //   retinaFactor = 2; // this is potentially incorrect for 3x+ devices
    // }
    Lt.meta.ppm = pixelsPerMillimeter / retinaFactor;
    console.log(Lt.meta.ppm);
  }
  
  Calibration.prototype.enable = function() {
    this.btn.state('active');
    Lt.mouseLine.enable();
    
    
    document.getElementById('map').style.cursor = 'pointer';

    $(document).keyup(e => {
      var key = e.which || e.keyCode;
      if (key === 27) {
        this.disable();
      }
    });

    var latLng_1 = null;
    var latLng_2 = null;
    $(Lt.viewer._container).click(e => {
      document.getElementById('map').style.cursor = 'pointer';
      
      
      if (latLng_1 === null) {
        latLng_1 = Lt.viewer.mouseEventToLatLng(e);
        Lt.mouseLine.from(latLng_1);
      } else if (latLng_2 === null) {
        latLng_2 = Lt.viewer.mouseEventToLatLng(e);
        
        this.popup.setLatLng(latLng_2).openOn(Lt.viewer);
        Lt.mouseLine.disable();

        document.getElementById('length').select();

        $(document).keypress(e => {
          var key = e.which || e.keyCode;
          if (key === 13) {
            var length = parseInt(document.getElementById('length').value);
            this.calculatePPM(latLng_1, latLng_2, length);
            this.disable();
          }
        });
      } else {
        var length = parseInt(document.getElementById('length').value);
        this.calculatePPM(latLng_1, latLng_2, length);
        this.disable();
      }
    });
  };
  
  Calibration.prototype.disable = function() {
    $(document).off('keyup');
    // turn off the mouse clicks from previous function
    $(Lt.viewer._container).off('click');
    this.btn.state('inactive');
    this.active = false;
    Lt.mouseLine.disable();
    document.getElementById('map').style.cursor = 'default';
    this.popup.remove(Lt.viewer);
  };
}

/**
 * Set date of chronology
 * @constructor
 * @param {Ltreering} Lt - Leaflet treering object
 */
function Dating(Lt) {
  this.active = false;
  this.btn = new Button(
    'access_time', 
    'Set the year of any point and adjust all other points',
    () => { Lt.disableTools(); Lt.collapseTools(); this.enable() },
    () => { this.disable() }
  );
  
  /**
   * Open a text container for user to input date
   * @function action
   */
  Dating.prototype.action = function(i) {
    if (Lt.data.points[i].year != undefined) {
      var popup = L.popup({closeButton: false})
          .setContent(
          '<input type="number" style="border:none;width:50px;" value="' +
          Lt.data.points[i].year + '" id="year_input"></input>')
          .setLatLng(Lt.data.points[i].latLng)
          .openOn(Lt.viewer);

      document.getElementById('year_input').select();

      $(Lt.viewer._container).click(e => {
        popup.remove(Lt.viewer);
        this.disable();
      });

      $(document).keypress(e => {
        var key = e.which || e.keyCode;
        if (key === 13) {
          var new_year = parseInt(document.getElementById('year_input').value);
          popup.remove(Lt.viewer);

          var date = new Date();
          var max = date.getFullYear();

          if (new_year > max) {
            alert('Year cannot exceed ' + max + '!');
          } else {
            Lt.undo.push();

            var shift = new_year - Lt.data.points[i].year;

            Object.values(Lt.data.points).map((e, i) => {
              if (Lt.data.points[i].year != undefined) {
                Lt.data.points[i].year += shift;
              }
            });
            Lt.data.year += shift;
            Lt.visualAsset.reload();
          }
          this.disable();
        }
      });
    }
  };
  
  /**
   * Enable dating
   * @function enable
   */
  Dating.prototype.enable = function() {
    this.btn.state('active');
    this.active = true;
  };

  /**
   * Disable dating
   * @function disable
   */
  Dating.prototype.disable = function() {
    this.btn.state('inactive');
    $(Lt.viewer._container).off('click');
    $(document).off('keypress');
    this.active = false;
  };
}

/**
 * Create measurement points
 * @constructor
 * @param {Ltreering} Lt - Leaflet treering object
 */
function CreatePoint(Lt) {
  this.active = false;
  this.startPoint = true;
  this.btn = new Button(
    'linear_scale',
    'Create measurable points (Control-m)',
    () => { Lt.disableTools(); this.enable() },
    () => { this.disable() }
  );
  
  L.DomEvent.on(window, 'keydown', (e) => {
     if (e.keyCode == 77 && e.getModifierState("Control")) {
       if (!this.active) {
         Lt.disableTools();
         this.enable();
       } else {
         this.disable();
       }
     }
  }, this);


  /**
   * Enable creating new points on click events
   * @function enable
   */
  CreatePoint.prototype.enable = function() {
    this.btn.state('active');
    Lt.mouseLine.enable();

    document.getElementById('map').style.cursor = 'pointer';

    $(document).keyup(e => {
      var key = e.which || e.keyCode;
      if (key === 27) {
        this.disable();
      }
    });

    $(Lt.viewer._container).click(e => {
      document.getElementById('map').style.cursor = 'pointer';

      var latLng = Lt.viewer.mouseEventToLatLng(e);

      Lt.undo.push();

      if (this.startPoint) {
        var popup = L.popup({closeButton: false}).setContent(
            '<input type="number" style="border:none; width:50px;"' +
            'value="' + Lt.data.year + '" id="year_input"></input>')
            .setLatLng(latLng)
            .openOn(Lt.viewer);

        document.getElementById('year_input').select();

        $(document).keypress(e => {
          var key = e.which || e.keyCode;
          if (key === 13) {
            Lt.data.year = parseInt(document.getElementById('year_input').value);
            popup.remove(Lt.viewer);
          }
        });
        Lt.data.newPoint(this.startPoint, latLng, Lt.meta.hasLatewood);
        this.startPoint = false;
      } else {
        Lt.data.newPoint(this.startPoint, latLng, Lt.meta.hasLatewood);
      }

      //call newLatLng with current index and new latlng
      Lt.visualAsset.newLatLng(Lt.data.points, Lt.data.index-1, latLng);

      //create the next mouseline from the new latlng
      Lt.mouseLine.from(latLng);

      this.active = true;   //activate dataPoint after one point is made
    });
  };
  
  /**
   * Disable creating new points
   * @function disable
   */
  CreatePoint.prototype.disable = function() {
    $(document).off('keyup');
    // turn off the mouse clicks from previous function
    $(Lt.viewer._container).off('click');
    this.btn.state('inactive');
    this.active = false;
    Lt.mouseLine.disable();
    document.getElementById('map').style.cursor = 'default';
    this.startPoint = true;
  };
}

/**
 * Add a zero growth measurement
 * @constructor
 * @param {Ltreering} Lt - Leaflet treering object
 */
function CreateZeroGrowth(Lt) {
  this.btn = new Button('exposure_zero', 'Add a zero growth year', () => {
    this.add()
  });
  
  /**
   * Use previous point to add point in the same location to mimic zero growth
   * @function add
   */
  CreateZeroGrowth.prototype.add = function() {
    if (Lt.data.index) {
      var latLng = Lt.data.points[Lt.data.index - 1].latLng;

      Lt.undo.push();

      Lt.data.points[Lt.data.index] = {'start': false, 'skip': false, 'break': false,
        'year': Lt.data.year, 'earlywood': true, 'latLng': latLng};
      Lt.visualAsset.newLatLng(Lt.data.points, Lt.data.index, latLng);
      Lt.data.index++;
      if (Lt.meta.hasLatewood) {
        Lt.data.points[Lt.data.index] = {'start': false, 'skip': false, 'break': false,
          'year': Lt.data.year, 'earlywood': false, 'latLng': latLng};
        Lt.visualAsset.newLatLng(Lt.data.points, Lt.data.index, latLng);
        Lt.data.index++;
      }
      Lt.data.year++;
    } else {
      alert('First year cannot be missing!');
    }
  };      
}

/**
 * Add a break in a measurement
 * @constructor
 * @param {Ltreering} Lt - Leaflet treering object
 */
function CreateBreak(Lt) {
  this.btn = new Button(
    'broken_image',
    'Create a break point',
    () => {
      Lt.disableTools();
      this.enable();
      Lt.mouseLine.from(Lt.data.points[Lt.data.index - 1].latLng);
    },
    () => { this.disable }
  );
  
  /**
   * Enable adding a break point from the last point
   * @function enable
   */
  CreateBreak.prototype.enable = function() {
    this.btn.state('active');

    Lt.mouseLine.enable();

    document.getElementById('map').style.cursor = 'pointer';

    $(Lt.viewer._container).click(e => {
      document.getElementById('map').style.cursor = 'pointer';

      var latLng = Lt.viewer.mouseEventToLatLng(e);

      Lt.mouseLine.from(latLng);

      Lt.undo.push();

      Lt.viewer.dragging.disable();
      Lt.data.points[Lt.data.index] = {'start': false, 'skip': false, 'break': true,
        'latLng': latLng};
      Lt.visualAsset.newLatLng(Lt.data.points, Lt.data.index, latLng);
      Lt.data.index++;
      this.disable();

      Lt.createPoint.enable();
    });
  };
  
  /**
   * Disable adding breaks
   * @function disable
   */
  CreateBreak.prototype.disable = function() {
    $(Lt.viewer._container).off('click');
    this.btn.state('inactive');
    Lt.viewer.dragging.enable();
    Lt.mouseLine.disable();
  };
      
}

/**
 * Delete a measurement point
 * @constructor
 * @param {Ltreering} Lt - Leaflet treering object
 */
function DeletePoint(Lt) {
  this.active = false;
  this.btn = new Button(
    'delete',
    'Delete a point',
    () => { Lt.disableTools(); this.enable() },
    () => { this.disable() }
  );
  
  /**
   * Delete a point
   * @function action
   * @param i int - delete the point at index i
   */
  DeletePoint.prototype.action = function(i) {
    Lt.undo.push();
    
    Lt.data.deletePoint(i, Lt.meta.hasLatewood);

    Lt.visualAsset.reload();
  };
  
  /**
   * Enable deleting points on click
   * @function enable
   */
  DeletePoint.prototype.enable = function() {
    this.btn.state('active');
    this.active = true;
    document.getElementById('map').style.cursor = 'pointer';
  };
  
  /**
   * Disable deleting points on click
   * @function disable
   */
  DeletePoint.prototype.disable = function() {
    $(Lt.viewer._container).off('click');
    this.btn.state('inactive');
    this.active = false;
    document.getElementById('map').style.cursor = 'default';
  };
}

/**
 * Delete several points on either end of a chronology
 * @constructor
 * @param {Ltreering} Lt - Leaflet treering object
 */
function Cut(Lt) {
  this.active = false;
  this.point = -1;
  this.btn = new Button(
    'content_cut',
    'Cut a portion of the series',
    () => { Lt.disableTools(); this.enable() },
    () => { this.disable() }
  );
  
  /**
   * Defined the point to cut from
   * @function fromPoint
   * @param i int - index of the point to cut from
   */
  Cut.prototype.fromPoint = function(i) {
    this.point = i;
  };
  
  /**
   * Remove all points from the side of point i
   * @funciton action
   * @param i int - index of a point that will decide which side to cut
   */
  Cut.prototype.action = function(i) {
    Lt.undo.push();
    
    Lt.data.cut(this.point, i);

    Lt.visualAsset.reload();
    this.disable();
  };

  /**
   * Enable cutting
   * @function enable
   */
  Cut.prototype.enable = function() {
    this.btn.state('active');
    this.active = true;
    document.getElementById('map').style.cursor = 'pointer';
    this.point = -1;
  };
  
  /**
   * Disable cutting
   * @function disable
   */
  Cut.prototype.disable = function() {
    $(Lt.viewer._container).off('click');
    this.btn.state('inactive');
    this.active = false;
    document.getElementById('map').style.cursor = 'default';
    this.point = -1;
  };
            
}

/**
 * Insert a new measurement point in the middle of chronology
 * @constructor
 * @param {Ltreering} Lt - Leaflet treering object
 */
function InsertPoint(Lt) {
  this.active = false;
  this.btn = new Button(
    'add_circle_outline',
    'Add a point in the middle of the series',
    () => { Lt.disableTools(); this.enable() },
    () => { this.disable() }
  );
  
  /**
   * Insert a point on click event
   * @function action
   */
  InsertPoint.prototype.action = function() {
    document.getElementById('map').style.cursor = 'pointer';

    $(Lt.viewer._container).click(e => {
      var latLng = Lt.viewer.mouseEventToLatLng(e);

      Lt.undo.push();
      
      var k = Lt.data.insertPoint(latLng, Lt.meta.hasLatewood);
      if (k != null) {
        Lt.visualAsset.newLatLng(Lt.data.points, k, latLng);
        Lt.visualAsset.reload();
      }
      
      this.disable();
    });
  };
  
  /**
   * Enable inserting points
   * @function enable
   */
  InsertPoint.prototype.enable = function() {
    this.btn.state('active');
    this.action();
    this.active = true;
  };
  
  /**
   * Disable inserting points
   * @function disable
   */
  InsertPoint.prototype.disable = function() {
    $(Lt.viewer._container).off('click');
    this.btn.state('inactive');
    this.active = false;
    document.getElementById('map').style.cursor = 'default';
  };
}
  
/**
 * Insert a zero growth measurement in the middle of a chronology
 * @constructor
 * @param {Ltrering} Lt - Leaflet treering object
 */
function InsertZeroGrowth(Lt) {
  this.active = false;
  this.btn = new Button(
    'exposure_zero',
    'Add a zero growth year in the middle of the series',
    () => { Lt.disableTools(); this.enable() },
    () => { this.disable() }
  );
  
  /**
   * Insert a zero growth year after point i
   * @function action
   * @param i int - index of a point to add a zero growth year after
   */
  InsertZeroGrowth.prototype.action = function(i) {
    var latLng = Lt.data.points[i].latLng;

    Lt.undo.push();
    
    var k = Lt.data.insertZeroGrowth(i, latLng, Lt.meta.hasLatewood);
    if (k !== null) {
      if (Lt.meta.hasLatewood) Lt.visualAsset.newLatLng(Lt.data.points, k-1, latLng);
      Lt.visualAsset.newLatLng(Lt.data.points, k, latLng);
      Lt.visualAsset.reload();
    }
    
    this.disable();
  };
  
  /**
   * Enable adding a zero growth year
   * @function enable
   */
  InsertZeroGrowth.prototype.enable = function() {
    this.btn.state('active');
    this.active = true;
    document.getElementById('map').style.cursor = 'pointer';
  };
  
  /**
   * Disable adding a zero growth year
   * @function disable
   */
  InsertZeroGrowth.prototype.disable = function() {
    $(Lt.viewer._container).off('click');
    this.btn.state('inactive');
    document.getElementById('map').style.cursor = 'default';
    this.active = false;
    Lt.viewer.dragging.enable();
    Lt.mouseLine.disable();
  };

}

/**
 * Insert a break in the middle of a chronology
 * @constructor
 * @param {Ltreering} Lt - Leaflet treering object
 */
function InsertBreak(Lt) {
  this.active = false;
  this.btn = new Button(
    'broken_image',
    'Add a break in the series',
    () => { Lt.disableTools(); this.enable() },
    () => { this.disable() }
  );
  
  /**
   * Insert a break after point i
   * @function action
   * @param i int - add the break point after index i
   */
  InsertBreak.prototype.action = function(i) {
    var new_points = Lt.data.points;
    var second_points = Object.values(Lt.data.points).splice(i + 1, Lt.data.index - 1);
    var first_point = true;
    var second_point = false;
    var k = i + 1;

    Lt.mouseLine.enable();
    Lt.mouseLine.from(Lt.data.points[i].latLng);

    $(Lt.viewer._container).click(e => {
      var latLng = Lt.viewer.mouseEventToLatLng(e);
      Lt.viewer.dragging.disable();

      if (first_point) {
        Lt.mouseLine.from(latLng);
        new_points[k] = {'start': false, 'skip': false, 'break': true,
          'latLng': latLng};
        Lt.visualAsset.newLatLng(new_points, k, latLng);
        k++;
        first_point = false;
        second_point = true;
      } else if (second_point) {
        this.disable();
        second_point = false;
        this.active = false;
        Lt.mouseLine.layer.clearLayers();

        new_points[k] = {'start': true, 'skip': false, 'break': false,
          'latLng': latLng};
        Lt.visualAsset.newLatLng(new_points, k, latLng);
        k++;

        var popup = L.popup({closeButton: false}).setContent(
            '<input type="number" style="border:none;width:50px;"' +
            'value="' + second_points[0].year +
            '" id="year_input"></input>').setLatLng(latLng)
            .openOn(Lt.viewer);

        document.getElementById('year_input').select();

        $(document).keypress(e => {
          var key = e.which || e.keyCode;
          if (key === 13) {
            var new_year = parseInt(document.getElementById('year_input').value);
            popup.remove(Lt.viewer);

            var shift = new_year - second_points[0].year;

            second_points.map(e => {
              e.year += shift;
              new_points[k] = e;
              k++;
            });
            Lt.data.year += shift;

            $(Lt.viewer._container).off('click');

            Lt.undo.push();

            Lt.data.points = new_points;
            Lt.data.index = k;

            Lt.visualAsset.reload();
            this.disable();
          }
        });
      } else {
        this.disable();
        Lt.visualAsset.reload();
      }
    });
  };
  
  /**
   * Enable inserting a break point
   * @function enable
   */
  InsertBreak.prototype.enable = function() {
    this.btn.state('active');
    this.active = true;
    document.getElementById('map').style.cursor = 'pointer';
  };
  
  /**
   * Disable inserting a break point
   * @function disable
   */
  InsertBreak.prototype.disable = function() {
    $(Lt.viewer._container).off('click');
    this.btn.state('inactive');
    this.active = false;
    document.getElementById('map').style.cursor = 'default';
    Lt.viewer.dragging.enable();
    Lt.mouseLine.disable();
  };
}

/**
 * View data and download data
 * @constructor
 * @param {Ltreering} Lt - Leaflet treering object
 */
function ViewData(Lt) {
  this.btn = new Button(
    'view_list',
    'View and download data',
    () => { Lt.disableTools(); this.enable() },
    () => { this.disable() }
  );
  
  this.dialog = L.control.dialog({'size': [350, 400], 'anchor': [50, 0], 'initOpen': false})
    .setContent('<h3>There are no data points to measure</h3>')
    .addTo(Lt.viewer);
  
  /**
   * Calculate distance from p1 to p2
   * @function distance
   * @param p1 leaflet point - first point
   * @param p2 leaflet point - second point
   */
  ViewData.prototype.distance = function(p1, p2) {
    var lastPoint = Lt.viewer.project(p1, Lt.viewer.getMaxZoom());
    var newPoint = Lt.viewer.project(p2, Lt.viewer.getMaxZoom());
    var length = Math.sqrt(Math.pow(Math.abs(lastPoint.x - newPoint.x), 2) +
        Math.pow(Math.abs(newPoint.y - lastPoint.y), 2));
    var pixelsPerMillimeter = 1;
    Lt.viewer.eachLayer((layer) => {
      if (layer.options.pixelsPerMillimeter > 0 || Lt.meta.ppm > 0) {
        pixelsPerMillimeter = Lt.meta.ppm;
      }
    });
    length = length / pixelsPerMillimeter;
    var retinaFactor = 1;
    // if (L.Browser.retina) {
    //   retinaFactor = 2; // this is potentially incorrect for 3x+ devices
    // }
    return length * retinaFactor;
  }
  
  /**
   * Format and download data in Dan's archaic format
   * @function download
   */
  ViewData.prototype.download = function() {
    
    var toFourCharString = function(n) {
      var string = n.toString();

      if (string.length == 1) {
        string = '   ' + string;
      } else if (string.length == 2) {
        string = '  ' + string;
      } else if (string.length == 3) {
        string = ' ' + string;
      } else if (string.length == 4) {
        string = string;
      } else if (string.length >= 5) {
        alert('Value exceeds 4 characters');
        throw 'error in toFourCharString(n)';
      } else {
        alert('toSixCharString(n) unknown error');
        throw 'error';
      }
      return string;
    };

    var toSixCharString = function(n) {
      var string = n.toString();

      if (string.length == 1) {
        string = '     ' + string;
      } else if (string.length == 2) {
        string = '    ' + string;
      } else if (string.length == 3) {
        string = '   ' + string;
      } else if (string.length == 4) {
        string = '  ' + string;
      } else if (string.length == 5) {
        string = ' ' + string;
      } else if (string.length >= 6) {
        alert('Value exceeds 5 characters');
        throw 'error in toSixCharString(n)';
      } else {
        alert('toSixCharString(n) unknown error');
        throw 'error';
      }
      return string;
    };

    var toEightCharString = function(n) {
      var string = n.toString();
      if (string.length == 0) {
        string = string + '        ';
      } else if (string.length == 1) {
        string = string + '       ';
      } else if (string.length == 2) {
        string = string + '      ';
      } else if (string.length == 3) {
        string = string + '     ';
      } else if (string.length == 4) {
        string = string + '    ';
      } else if (string.length == 5) {
        string = string + '   ';
      } else if (string.length == 6) {
        string = string + '  ';
      } else if (string.length == 7) {
        string = string + ' ';
      } else if (string.length >= 8) {
        alert('Value exceeds 7 characters');
        throw 'error in toEightCharString(n)';
      } else {
        alert('toSixCharString(n) unknown error');
        throw 'error';
      }
      return string;
    };
    
    if (Lt.data.points != undefined && Lt.data.points[1] != undefined) {
      
      var sum_points;
      var sum_string = '';
      var last_latLng;
      var break_length;
      var length_string;
      
      if (Lt.meta.hasLatewood) {

        var sum_string = '';
        var ew_string = '';
        var lw_string = '';

        y = Lt.data.points[1].year;
        var sum_points = Object.values(Lt.data.points).filter(e => {
          if (e.earlywood != undefined) {
            return !(e.earlywood);
          } else {
            return true;
          }
        });

        if (sum_points[1].year % 10 > 0) {
          sum_string = sum_string.concat(
              toEightCharString(Lt.meta.assetName) +
              toFourCharString(sum_points[1].year));
        }

        var break_point = false;
        sum_points.map((e, i, a) => {
          if (e.start) {
            last_latLng = e.latLng;
          } else if (e.break) {
            break_length = 
              Math.round(this.distance(last_latLng, e.latLng) * 1000);
              break_point = true;
          } else {
            if (e.year % 10 == 0) {
              sum_string = sum_string.concat('\r\n' +
                  toEightCharString(Lt.meta.assetName) +
                  toFourCharString(e.year));
            }
            while (e.year > y) {
              sum_string = sum_string.concat('    -1');
              y++;
              if (y % 10 == 0) {
                sum_string = sum_string.concat('\r\n' +
                    toFourCharString(e.year));
              }
            }

            var length = Math.round(this.distance(last_latLng, e.latLng) * 1000);
            if (break_point) {
              length += break_length;
              break_point = false;
            }
            if (length == 9999) {
              length = 9998;
            }
            if (length == 999) {
              length = 998;
            }

            length_string = toSixCharString(length);

            sum_string = sum_string.concat(length_string);
            last_latLng = e.latLng;
            y++;
          }
        });
        sum_string = sum_string.concat(' -9999');

        y = Lt.data.points[1].year;

        if (Lt.data.points[1].year % 10 > 0) {
          ew_string = ew_string.concat(
              toEightCharString(Lt.meta.assetName) +
              toFourCharString(Lt.data.points[1].year));
          lw_string = lw_string.concat(
              toEightCharString(Lt.meta.assetName) +
              toFourCharString(Lt.data.points[1].year));
        }

        break_point = false;
        Object.values(Lt.data.points).map((e, i, a) => {
          if (e.start) {
            last_latLng = e.latLng;
          } else if (e.break) {
            break_length = 
              Math.round(this.distance(last_latLng, e.latLng) * 1000);
            break_point = true;
          } else {
            if (e.year % 10 == 0) {
              if (e.earlywood) {
                ew_string = ew_string.concat('\r\n' +
                    toEightCharString(Lt.meta.assetName) +
                    toFourCharString(e.year));
              } else {
                lw_string = lw_string.concat('\r\n' +
                    toEightCharString(Lt.meta.assetName) +
                    toFourCharString(e.year));
              }
            }
            while (e.year > y) {
              ew_string = ew_string.concat('    -1');
              lw_string = lw_string.concat('    -1');
              y++;
              if (y % 10 == 0) {
                ew_string = ew_string.concat('\r\n' +
                    toEightCharString(Lt.meta.assetName) +
                    toFourCharString(e.year));
                lw_string = lw_string.concat('\r\n' +
                    toEightCharString(Lt.meta.assetName) +
                    toFourCharString(e.year));
              }
            }

            length = Math.round(this.distance(last_latLng, e.latLng) * 1000);
            if (break_point) {
              length += break_length;
              break_point = false;
            }
            if (length == 9999) {
              length = 9998;
            }
            if (length == 999) {
              length = 998;
            }

            length_string = toSixCharString(length);

            if (e.earlywood) {
              ew_string = ew_string.concat(length_string);
              last_latLng = e.latLng;
            } else {
              lw_string = lw_string.concat(length_string);
              last_latLng = e.latLng;
              y++;
            }
          }
        });
        ew_string = ew_string.concat(' -9999');
        lw_string = lw_string.concat(' -9999');

        console.log(sum_string);
        console.log(ew_string);
        console.log(lw_string);

        var zip = new JSZip();
        zip.file((Lt.meta.assetName + '.raw'), sum_string);
        zip.file((Lt.meta.assetName + '.lwr'), lw_string);
        zip.file((Lt.meta.assetName + '.ewr'), ew_string);

      } else {

        var y = Lt.data.points[1].year;
        sum_points = Object.values(Lt.data.points);

        if (sum_points[1].year % 10 > 0) {
          sum_string = sum_string.concat(
              toEightCharString(Lt.meta.assetName) +
              toFourCharString(sum_points[1].year));
        }
        sum_points.map((e, i, a) => {
          if (!e.start) {
            if (e.year % 10 == 0) {
              sum_string = sum_string.concat('\r\n' +
                  toEightCharString(Lt.meta.assetName) +
                  toFourCharString(e.year));
            }
            while (e.year > y) {
              sum_string = sum_string.concat('    -1');
              y++;
              if (y % 10 == 0) {
                sum_string = sum_string.concat('\r\n' +
                    toFourCharString(e.year));
              }
            }

            length = Math.round(this.distance(last_latLng, e.latLng) * 1000);
            if (length == 9999) {
              length = 9998;
            }
            if (length == 999) {
              length = 998;
            }

            length_string = toSixCharString(length);

            sum_string = sum_string.concat(length_string);
            last_latLng = e.latLng;
            y++;
          } else {
            last_latLng = e.latLng;
          }
        });
        sum_string = sum_string.concat(' -9999');

        var zip = new JSZip();
        zip.file((Lt.meta.assetName + '.raw'), sum_string);
      }

      zip.generateAsync({type: 'blob'})
          .then((blob) => {
            saveAs(blob, (Lt.meta.assetName + '.zip'));
          });
    } else {
      alert('There is no data to download');
    }
  };
  
  /**
   * Open the data viewer box
   * @function enable
   */
  ViewData.prototype.enable = function() {
    this.btn.state('active');
    var string;
    if (Lt.data.points[0] != undefined) {
      var y = Lt.data.points[1].year;
      string = '<div><button id="download-button"' +
          'class="mdc-button mdc-button--unelevated mdc-button-compact"' +
          '>download</button><button id="refresh-button"' +
          'class="mdc-button mdc-button--unelevated mdc-button-compact"' +
          '>refresh</button><button id="delete-button"' +
          'class="mdc-button mdc-button--unelevated mdc-button-compact"' +
          '>delete all</button></div><table><tr>' +
          '<th style="width: 45%;">Year</th>' +
          '<th style="width: 70%;">Length</th></tr>';

      var break_point = false;
      var last_latLng;
      var break_length;
      var break_point;
      var length;
      Lt.data.clean();
      Object.values(Lt.data.points).map((e, i, a) => {
        
        if (e.start) {
          last_latLng = e.latLng;
        } else if (e.break) {
          break_length =
            Math.round(this.distance(last_latLng, e.latLng) * 1000) / 1000;
          break_point = true;
        } else {
          while (e.year > y) {
            string = string.concat('<tr><td>' + y +
                '-</td><td>N/A</td></tr>');
            y++;
          }
          length = Math.round(this.distance(last_latLng, e.latLng) * 1000) / 1000;
          if (break_point) {
            length += break_length;
            length = Math.round(length * 1000) / 1000;
            break_point = false;
          }
          if (length == 9.999) {
            length = 9.998;
          }
          if (Lt.meta.hasLatewood) {
            var wood;
            var row_color;
            if (e.earlywood) {
              wood = 'E';
              row_color = '#00d2e6';
            } else {
              wood = 'L';
              row_color = '#00838f';
              y++;
            }
            string =
                string.concat('<tr style="color:' + row_color + ';">');
            string = string.concat('<td>' + e.year + wood + '</td><td>'+
                length + ' mm</td></tr>');
          } else {
            y++;
            string = string.concat('<tr style="color: #00d2e6;">');
            string = string.concat('<td>' + e.year + '</td><td>' +
                length + ' mm</td></tr>');
          }
          last_latLng = e.latLng;
        }
      });
      this.dialog.setContent(string + '</table>');
    } else {
      string = '<div><button id="download-button"' +
          'class="mdc-button mdc-button--unelevated mdc-button-compact"' +
          'disabled>download</button>' +
          '<button id="refresh-button"' +
          'class="mdc-button mdc-button--unelevated mdc-button-compact"' +
          '>refresh</button><button id="delete-button"' +
          'class="mdc-button mdc-button--unelevated mdc-button-compact"' +
          '>delete all</button></div>' +
          '<h3>There are no data points to measure</h3>';
      this.dialog.setContent(string);
    }
    this.dialog.lock();
    this.dialog.open();
    $('#download-button').click(() => this.download());
    $('#refresh-button').click(() => {
      this.disable();
      this.enable();
    });
    $('#delete-button').click(() => {
      this.dialog.setContent(
          '<p>This action will delete all data points.' +
          'Annotations will not be effected.' +
          'Are you sure you want to continue?</p>' +
          '<p><button id="confirm-delete"' +
          'class="mdc-button mdc-button--unelevated mdc-button-compact"' +
          '>confirm</button><button id="cancel-delete"' +
          'class="mdc-button mdc-button--unelevated mdc-button-compact"' +
          '>cancel</button></p>');

      $('#confirm-delete').click(() => {
        Lt.undo.push();

        Lt.data.points = {};
        Lt.data.year = 0;
        Lt.data.earlywood = true;
        Lt.data.index = 0;

        Lt.visualAsset.reload();

        this.disable();
      });
      $('#cancel-delete').click(() => {
        this.disable();
        this.enable();
      });
    });
  },
  
  /**
   * close the data viewer box
   * @function disable
   */
  ViewData.prototype.disable = function() {
    $(Lt.viewer._container).off('click');
    this.btn.state('inactive');
    $('#confirm-delete').off('click');
    $('#cancel-delete').off('click');
    $('#download-button').off('click');
    $('#refresh-button').off('click');
    $('#delete-button').off('click');
    this.dialog.close();
  };
}

/**
 * Create annotations
 * @constructor
 * @param {Ltreering} Lt - Leaflet treering object
 */
function CreateAnnotation(Lt) {
  this.active = false;
  this.input = L.circle([0, 0], {radius: .0001, color: 'red', weight: '6'})
      .bindPopup('<textarea class="comment_input" name="message" rows="2"' +
      'cols="15"></textarea>', {closeButton: false});
  this.btn = new Button(
    'comment',
    'Create annotations (Control-a)',
    () => { Lt.disableTools(); this.enable() },
    () => { this.disable() }
  );

  L.DomEvent.on(window, 'keydown', (e) => {
    if (e.keyCode == 65 && e.getModifierState("Control")) {
      if(!this.active) {
        Lt.disableTools();
        this.enable();
      }
      else {
        this.disable();
      }
    }
  }, this);

  /**
   * Enable creating annotations on click
   * @function enable
   */
  CreateAnnotation.prototype.enable = function() {
    this.btn.state('active');
    this.active = true;
    document.getElementById('map').style.cursor = 'pointer';

    Lt.viewer.doubleClickZoom.disable();
    $(Lt.viewer._container).click(e => {
      $(Lt.viewer._container).click(e => {
        this.disable();
        this.enable();
      });
      var latLng = Lt.viewer.mouseEventToLatLng(e);
      this.input.setLatLng(latLng);
      this.input.addTo(Lt.viewer);
      this.input.openPopup();

      document.getElementsByClassName('comment_input')[0].select();
      
      $(document).keypress(e => {
        var key = e.which || e.keyCode;
        if (key === 13) {
          var string = ($('.comment_input').val()).slice(0);

          this.input.remove();

          if (string != '') {
            Lt.aData.annotations[Lt.aData.index] = {'latLng': latLng, 'text': string};
            Lt.annotationAsset.newAnnotation(Lt.aData.annotations, Lt.aData.index);
            Lt.aData.index++;
          }

          this.disable();
          this.enable();
        }
      });
    });
  };
  
  /**
   * Disable creating annotations on click
   * @function enable
   */
  CreateAnnotation.prototype.disable = function() {
    this.btn.state('inactive');
    Lt.viewer.doubleClickZoom.enable();
    $(Lt.viewer._container).off('dblclick');
    $(Lt.viewer._container).off('click');
    $(document).off('keypress');
    document.getElementById('map').style.cursor = 'default';
    this.input.remove();
    this.active = false;
  };
  
}

/**
 * Delete annotations
 * @constructor
 * @param {Ltreering} Lt - Leaflet treering object
 */
function DeleteAnnotation(Lt) {
  this.btn = new Button(
    'delete',
    'Delete annotations',
    () => { Lt.disableTools(); this.enable() },
    () => { this.disable() }
  );
  this.active = false;
  
    /**
   * Delete a point
   * @function action
   * @param i int - delete the annotation at index i
   */
  DeleteAnnotation.prototype.action = function(i) {
    Lt.undo.push();
    
    Lt.aData.deleteAnnotation(i);

    Lt.annotationAsset.reload();
  };
  
  /**
   * Enable deleting annotations on click
   * @function enable
   */
  DeleteAnnotation.prototype.enable = function() {
    this.btn.state('active');
    this.active = true;
    document.getElementById('map').style.cursor = 'pointer';
  };
  
  /**
   * Disable deleting annotations on click
   * @function disable
   */
  DeleteAnnotation.prototype.disable = function() {
    $(Lt.viewer._container).off('click');
    this.btn.state('inactive');
    this.active = false;
    document.getElementById('map').style.cursor = 'default';
  };
}

/**
 * Edit annotations
 * @constructor
 * @param {Ltreering} Lt - Leaflet treering object
 */
function EditAnnotation(Lt) {
  this.btn = new Button(
    'edit',
    'Edit annotations',
    () => { Lt.disableTools(); this.enable() },
    () => { this.disable() }
  );
  this.active = false;
  
  /**
   * Enable editing annotations on click
   * @function enable
   */
  EditAnnotation.prototype.enable = function() {
    this.btn.state('active');
    this.active = true;
    document.getElementById('map').style.cursor = 'pointer';
  };
  
  /**
   * Disable editing annotations on click
   * @function disable
   */
  EditAnnotation.prototype.disable = function() {
    $(Lt.viewer._container).off('click');
    this.btn.state('inactive');
    this.active = false;
    document.getElementById('map').style.cursor = 'default';
  };
}

/**
 * Change color properties of image
 * @constructor
 * @param {Ltreering} Lt - Leaflet treering object
 */
function ImageAdjustment(Lt) {
  this.btn = new Button(
    'brightness_6',
    'Adjust the image exposure, color, and contrast',
    () => { Lt.disableTools(); this.enable() },
    () => { this.disable() }
  );

  this.dialog = L.control.dialog({
    'size': [340, 240],
    'anchor': [50, 5],
    'initOpen': false
  }).setContent(
    '<div><label style="text-align:center;display:block;">Brightness</label> \
    <input class="imageSlider" id="brightness-slider" value=100 min=0 max=300 type=range> \
    <label style="text-align:center;display:block;">Contrast</label> \
    <input class="imageSlider" id="contrast-slider" type=range min=50 max=350 value=100></div> \
    <label style="text-align:center;display:block;">Saturation</label> \
    <input class="imageSlider" id="saturation-slider" type=range min=0 max=350 value=100></div> \
    <label style="text-align:center;display:block;">Hue Rotation</label> \
    <input class="imageSlider" id="hue-slider" type=range min=0 max=360 value=0> \
    <button id="reset-button" style="margin-left:auto; margin-right:auto; margin-top: 5px;display:block;" class="mdc-button mdc-button--unelevated mdc-button-compact">reset</button></div>').addTo(Lt.viewer);
  
  /**
   * Update the image filter to reflect slider values
   * @function updateFilters
   */
  ImageAdjustment.prototype.updateFilters = function() {
    var brightnessSlider = document.getElementById("brightness-slider");
    var contrastSlider = document.getElementById("contrast-slider");
    var saturationSlider = document.getElementById("saturation-slider");
    var hueSlider = document.getElementById("hue-slider");
     
    document.getElementsByClassName("leaflet-pane")[0].style.filter = 
      "contrast(" + contrastSlider.value + "%) " +
      "brightness(" + brightnessSlider.value + "%) " +
      "saturate(" + saturationSlider.value + "%) " +
      "hue-rotate(" + hueSlider.value + "deg)";
  };
  
  /**
   * Open the filter sliders dialog
   * @function enable
   */
  ImageAdjustment.prototype.enable = function() {
    this.dialog.lock();
    this.dialog.open();
    var brightnessSlider = document.getElementById("brightness-slider");
    var contrastSlider = document.getElementById("contrast-slider");
    var saturationSlider = document.getElementById("saturation-slider");
    var hueSlider = document.getElementById("hue-slider");
    this.btn.state('active');
    $(".imageSlider").change(() => {
      this.updateFilters();
    });
    $("#reset-button").click(() => {
      $(brightnessSlider).val(100);
      $(contrastSlider).val(100);
      $(saturationSlider).val(100);
      $(hueSlider).val(0);
      this.updateFilters();
    });
  };
  
  /**
   * Close the filter sliders dialog
   * @function disable
   */
  ImageAdjustment.prototype.disable = function() {
    this.dialog.unlock();
    this.dialog.close();
    this.btn.state('inactive');
  };
  
}

/**
 * Save a local copy of the measurement data
 * @constructor
 * @param {Ltreering} Lt - Leaflet treering object
 */
function SaveLocal(Lt) {
  this.btn = new Button(
    'save',
    'Save a local copy of measurements and annotations',
    () => { this.action() }
  );
  
  /**
   * Save a local copy of the measurement data
   * @function action
   */
  SaveLocal.prototype.action = function() {
    Lt.data.clean();
    var dataJSON = {'SaveDate': Lt.data.saveDate, 'year': Lt.data.year,
      'earlywood': Lt.data.earlywood, 'index': Lt.data.index,
      'points': Lt.data.points, 'annotations': Lt.aData.annotations};
    var file = new File([JSON.stringify(dataJSON)],
        (Lt.meta.assetName + '.json'), {type: 'text/plain;charset=utf-8'});
    saveAs(file);
  };
}

/**
 * Save a copy of the measurement data to the cloud
 * @constructor
 * @param {Ltreering} Lt - Leaflet treering object
 */
function SaveCloud(Lt) {
  this.btn = new Button(
    'cloud_upload',
    'Save to elevator cloud',
    () => { this.action() }
  );

  this.date = new Date(),
    
  /**
   * Update the save date
   * @function updateDate
   */
  SaveCloud.prototype.updateDate = function() {
    var day = this.date.getDate();
    var month = this.date.getMonth() + 1;
    var year = this.date.getFullYear();
    var minute = this.date.getMinutes();
    var hour = this.date.getHours();
    Lt.data.saveDate = {'day': day, 'month': month, 'year': year, 'hour': hour,
      'minute': minute};
  };
  
  /**
   * Display the save date in the bottom left corner
   * @function displayDate
   */
  SaveCloud.prototype.displayDate = function() {
    var date = Lt.data.saveDate;
    console.log(date);
    if (date.day != undefined && date.hour != undefined) {
      var am_pm = 'am';
      if (date.hour >= 12) {
        date.hour -= 12;
        am_pm = 'pm';
      }
      if (date.hour == 0) {
        date.hour += 12;
      }
      var minute_string = date.minute;
      if (date.minute < 10) {
        minute_string = '0' + date.minute;
      }
      document.getElementById('leaflet-save-time-tag').innerHTML =
          'Saved to cloud at ' + date.hour + ':' + minute_string +
          am_pm + ' on ' + date.month + '/' + date.day + '/' +
          date.year;
    } else if (date.day != undefined) {
      document.getElementById('leaflet-save-time-tag').innerHTML =
          'Saved to cloud on ' + date.month + '/' + date.day + '/' +
          date.year;
    } else {
      document.getElementById('leaflet-save-time-tag').innerHTML =
          'No data saved to cloud';
    }
    Lt.data.saveDate;
  };
  
  /**
   * Save the measurement data to the cloud
   * @function action
   */
  SaveCloud.prototype.action = function() {
    if (Lt.meta.savePermission && Lt.meta.saveURL != "") {
      Lt.data.clean();
      var dataJSON = {'saveDate': Lt.data.saveDate, 'year': Lt.data.year,
        'earlywood': Lt.data.earlywood, 'index': Lt.data.index,
        'points': Lt.data.points, 'annotations': Lt.aData.annotations};
      $.post(Lt.meta.saveURL, {sidecarContent: JSON.stringify(dataJSON)})
          .done((msg) => {
            this.updateDate();
            this.displayDate();
          })
          .fail((xhr, status, error) => {
            alert('Error: failed to save changes');
          });
    } else {
      alert(
        'Authentication Error: save to cloud permission not granted');
    }
  };
  
  /**
   * Initialize the display date
   * @function initialize
   */
  SaveCloud.prototype.initialize = function() {
    var saveTimeDiv = document.createElement('div');
    saveTimeDiv.innerHTML =
        '<div class="leaflet-control-attribution leaflet-control">' +
        '<p id="leaflet-save-time-tag"></p></div>';
    document.getElementsByClassName('leaflet-bottom leaflet-left')[0]
        .appendChild(saveTimeDiv);
  };
  
}

/**
 * Load a local copy of the measurement data
 * @constructor
 * @param {Ltreering} Lt - Leaflet treering object
 */
function LoadLocal(Lt) {
  this.btn = new Button(
    'file_upload',
    'Load a local file with measurements and annotations',
    () => { this.input() }
  );
  
  /**
   * Create an input div on the ui and click it
   * @function input
   */
  LoadLocal.prototype.input = function() {
    var input = document.createElement('input');
    input.type = 'file';
    input.id = 'file';
    input.style = 'display: none';
    input.addEventListener('change', () => {this.action(input)});
    input.click();
  };

  /**
   * Load the file selected in the input
   * @function action
   */
  LoadLocal.prototype.action = function(inputElement) {
    var files = inputElement.files;
    console.log(files);
    if (files.length <= 0) {
      return false;
    }

    var fr = new FileReader();

    fr.onload = function(e) {
      let newDataJSON = JSON.parse(e.target.result);

      Lt.data = new MeasurementData(newDataJSON);
      Lt.aData = new AnnotationData(newDataJSON.annotations);
      Lt.loadData();
    };

    fr.readAsText(files.item(0));
  };
  
}

function Panhandler(La) {
  this.panHandler = L.Handler.extend({
    panAmount: 120,
    panDirection: 0,
    isPanning: false,
    slowMotion: false,

    addHooks: function () {
      L.DomEvent.on(window, 'keydown', this._startPanning, this);
      L.DomEvent.on(window, 'keyup', this._stopPanning, this);
    },

    removeHooks: function () {
      L.DomEvent.off(window, 'keydown', this._startPanning, this);
      L.DomEvent.off(window, 'keyup', this._stopPanning, this);
    },

    _startPanning: function (e) {
      if (e.keyCode == '38') {
        this.panDirection = 'up';
      } else if (e.keyCode == '40') {
        this.panDirection = 'down';
      } else if (e.keyCode == '37') {
        this.panDirection = 'left';
      } else if (e.keyCode == '39') {
        this.panDirection = 'right';
      } else {
        this.panDirection = null;
      }

      if (e.getModifierState("Shift")) {
        this.slowMotion = true;
      }
      else {
        this.slowMotion = false;
      }

      if (this.panDirection) {
        e.preventDefault();
      }

      if (this.panDirection && !this.isPanning) {
        this.isPanning = true;
        requestAnimationFrame(this._doPan.bind(this));
      }
      return false;
    },

    _stopPanning: function (ev) {
      // Treat Gamma angle as horizontal pan (1 degree = 1 pixel) and Beta angle as vertical pan
      this.isPanning = false;

    },

    _doPan: function () {

      var panArray = [];

      var adjustedPanAmount = this.panAmount;
      if(this.slowMotion) {
        adjustedPanAmount = 30;
      }

      switch (this.panDirection) {
        case "up":
          panArray = [0, -1 * adjustedPanAmount];
          break;
        case "down":
          panArray = [0, adjustedPanAmount];
          break;
        case "left":
          panArray = [-1 * adjustedPanAmount, 0];
          break;
        case "right":
          panArray = [adjustedPanAmount, 0];
          break;
      }


      map.panBy(panArray, {
        animate: true,
        delay: 0
      });
      if (this.isPanning) {
        requestAnimationFrame(this._doPan.bind(this));
      }

    }
  });

  La.viewer.addHandler('pan', this.panHandler);
  La.viewer.pan.enable();
}