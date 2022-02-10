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
function LTreering (viewer, basePath, options, base_layer, gl_layer) {
  this.viewer = viewer;
  this.basePath = basePath;

  var getURL = window.location.href;
  var parsedURL = new URL(getURL);
  var urlParams = new URLSearchParams(parsedURL.search);
  var latData = urlParams.get("lat");
  var lngData = urlParams.get("lng");
  if (latData && lngData) {
    setTimeout(function() {
      viewer.setView([latData, lngData], 16); //  max zoom level is 18
    }, 500);
  }

  //options
  this.meta = {
    'ppm': options.ppm || 468,
    'saveURL': options.saveURL || '',
    'savePermission': options.savePermission || false,
    'popoutUrl': options.popoutUrl || null,
    'assetName': options.assetName || 'N/A',
    'attributesObjectArray': options.attributesObjectArray || [],
  }

  this.preferences = { // catch for if forwardDirection or subAnnual are undefined/null on line ~2830
    'forwardDirection': options.initialData.forwardDirection,
    'subAnnual': options.initialData.subAnnual
  }

  this.measurementOptions = new MeasurementOptions(this);

  this.data = new MeasurementData(options.initialData, this);
  this.aData = new AnnotationData(options.initialData.annotations);
  if (options.initialData.ppm) {
    this.meta.ppm = options.initialData.ppm;
  };

  /* Current helper tools:
   * closestPointIndex -> will find the absolute closest point and its index or its point[i] value
  */
  this.helper = new Helper(this);

  //error alerts in 'measuring' mode aka popout window
  //will not alert in 'browsing' mode aka DE browser window
  if (window.name.includes('popout') && options.ppm === 0 && !options.initialData.ppm) {
    alert('Calibration needed: set ppm in asset metadata or use calibration tool.');
  }

  this.autoscroll = new Autoscroll(this.viewer);
  this.mouseLine = new MouseLine(this);
  this.visualAsset = new VisualAsset(this);
  this.annotationAsset = new AnnotationAsset(this);
  this.panhandler = new Panhandler(this);

  this.scaleBarCanvas = new ScaleBarCanvas(this);
  this.metaDataText = new MetaDataText(this);

  this.popout = new Popout(this);
  this.undo = new Undo(this);
  this.redo = new Redo(this);

  this.viewData = new ViewData(this);

  this.imageAdjustment = new ImageAdjustment(this);
  //this.PixelAdjustment = new PixelAdjustment(this);
  this.calibration = new Calibration(this);

  this.dating = new Dating(this);

  this.createPoint = new CreatePoint(this);
  this.zeroGrowth = new CreateZeroGrowth(this);
  this.createBreak = new CreateBreak(this);

  this.deletePoint = new DeletePoint(this);
  this.cut = new Cut(this);
  this.insertPoint = new InsertPoint(this);
  this.convertToStartPoint = new ConvertToStartPoint(this);
  this.insertZeroGrowth = new InsertZeroGrowth(this);
  this.insertBreak = new InsertBreak(this);

  this.saveLocal = new SaveLocal(this);
  this.loadLocal = new LoadLocal(this);
  var ioBtns = [this.saveLocal.btn, this.loadLocal.btn];
  if (options.savePermission) {
    this.saveCloud = new SaveCloud(this);
    ioBtns.push(this.saveCloud.btn);
  }

  this.keyboardShortCutDialog = new KeyboardShortCutDialog(this);

  this.popoutPlots = new PopoutPlots(this);

  this.undoRedoBar = new L.easyBar([this.undo.btn, this.redo.btn]);
  this.annotationTools = new ButtonBar(this, [this.annotationAsset.createBtn, this.annotationAsset.deleteBtn], 'comment', 'Manage annotations');
  this.createTools = new ButtonBar(this, [this.createPoint.btn, this.mouseLine.btn, this.zeroGrowth.btn, this.createBreak.btn], 'straighten', 'Create new measurements');
  // add this.insertBreak.btn below once fixed
  this.editTools = new ButtonBar(this, [this.dating.btn, this.insertPoint.btn, this.convertToStartPoint.btn, this.deletePoint.btn, this.insertZeroGrowth.btn, this.cut.btn], 'edit', 'Edit existing measurements');
  this.ioTools = new ButtonBar(this, ioBtns, 'folder_open', 'Save or upload a record of measurements, annotations, etc.');
  this.settings = new ButtonBar(this, [this.measurementOptions.btn, this.calibration.btn, this.keyboardShortCutDialog.btn], 'settings', 'Measurement preferences & distance calibration');

  this.tools = [this.viewData, this.calibration, this.dating, this.createPoint, this.createBreak, this.deletePoint, this.cut, this.insertPoint, this.convertToStartPoint, this.insertZeroGrowth, this.insertBreak, this.annotationAsset, this.imageAdjustment, this.measurementOptions];

  this.baseLayer = {
    'Tree Ring': base_layer,
    'GL Layer': gl_layer
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
    var map = this.viewer;
    $(map.getContainer()).css('cursor', 'default');

    L.control.layers(this.baseLayer, this.overlay).addTo(this.viewer);

    // test placement
    this.popoutPlots.btn.addTo(this.viewer);

    // if popout is opened display measuring tools
    if (window.name.includes('popout')) {
      this.viewData.btn.addTo(this.viewer);
      this.ioTools.bar.addTo(this.viewer);
      this.imageAdjustment.btn.addTo(this.viewer);
      //this.PixelAdjustment.btn.addTo(this.viewer);
      this.createTools.bar.addTo(this.viewer);
      this.editTools.bar.addTo(this.viewer);
      this.annotationTools.bar.addTo(this.viewer);
      this.settings.bar.addTo(this.viewer);
      this.undoRedoBar.addTo(this.viewer);
    } else {
      this.popout.btn.addTo(this.viewer);
      this.viewData.btn.addTo(this.viewer);
      this.ioTools.bar.addTo(this.viewer);
      this.imageAdjustment.btn.addTo(this.viewer);
      //this.PixelAdjustment.btn.addTo(this.viewer);
      //defaults overlay 'points' option to disabled
      map.removeLayer(this.visualAsset.markerLayer);
    }

    // right and left click controls
    this.viewer.on('contextmenu', () => {
      this.disableTools();
    });

    // disable tools w/ esc
    L.DomEvent.on(window, 'keydown', (e) => {
       if (e.keyCode == 27) {
         this.disableTools();
       }
    }, this);

    this.scaleBarCanvas.load();

    this.metaDataText.initialize();

    this.loadData();

  };

  /**
   * Load the JSON data attached to the treering image
   * @function loadData
   */
  LTreering.prototype.loadData = function() {
    this.measurementOptions.preferencesInfo();
    this.visualAsset.reload();
    this.annotationAsset.reload();
    if ( this.meta.savePermission ) {
      // load the save information in buttom left corner
      this.saveCloud.displayDate();
    };
    if (this.popoutPlots.win) {
      this.popoutPlots.sendData();
    }
    this.metaDataText.updateText();
  };

  /**
   * Disable any tools
   * @function disableTools
   */
  LTreering.prototype.disableTools = function() {
    if (this.annotationAsset.dialogAnnotationWindow && this.annotationAsset.createBtn.active) { // if user trying to create annotation, destroy dialog & marker
      this.annotationAsset.dialogAnnotationWindow.destroy();
      this.annotationAsset.annotationIcon.removeFrom(this.viewer);
    } else if (this.annotationAsset.dialogAnnotationWindow) {
      this.annotationAsset.dialogAnnotationWindow.destroy();
    };

    if (this.annotationAsset.dialogAttributesWindow) {
      this.annotationAsset.dialogAttributesWindow.destroy();
      delete this.annotationAsset.dialogAttributesWindow;
    };

    this.tools.forEach(e => { e.disable() });
  };

  LTreering.prototype.collapseTools = function() {
    this.annotationTools.collapse();
    this.createTools.collapse();
    this.editTools.collapse();
    this.ioTools.collapse();
    this.settings.collapse();
  };

  // we need the max native zoom, which is set on the tile layer and not the map. getMaxZoom will return a synthetic value which is no good for measurement
  LTreering.prototype.getMaxNativeZoom = function () {
      var maxNativeZoom = null;
      this.viewer.eachLayer(function (l) {
        if (l.options.maxNativeZoom) {
          maxNativeZoom = l.options.maxNativeZoom;
        }
      });
      return maxNativeZoom;
  };
}

/*******************************************************************************/

/**
 * A measurement data object
 * @constructor
 * @param {object} dataObject
 * @param {object} LTreeRing - Lt
 */
function MeasurementData (dataObject, Lt) {
  var measurementOptions = Lt.measurementOptions
  this.saveDate = dataObject.saveDate || dataObject.SaveDate || {};
  this.index = dataObject.index || 0;
  this.year = dataObject.year || 0;
  this.earlywood = dataObject.earlywood || true;
  this.points = dataObject.points || [];
  this.annotations = dataObject.annotations || {};

  const forwardInTime = 'forward';
  const backwardInTime = 'backward';

  function directionCheck () {
    const forwardString = 'forward';
    const backwardString = 'backward';
    if (measurementOptions.forwardDirection) { // check if years counting up
      return forwardString;
    } else { // otherwise years counting down
      return backwardString;
    };
  }

 /**
  * Add a new point into the measurement data
  * @function newPoint
  */
  MeasurementData.prototype.newPoint = function(start, latLng) {
    let direction = directionCheck();

    if (start) {
      this.points[this.index] = {'start': true, 'skip': false, 'break': false, 'latLng': latLng};
    } else {
      this.points[this.index] = {'start': false, 'skip': false, 'break': false, 'year': this.year, 'earlywood': this.earlywood, 'latLng': latLng};
      // Change year value if lw point (forward) or ew point (backwards) or annual measurements.
      if ((measurementOptions.subAnnual && (direction == forwardInTime && !this.earlywood) ||
                                           (direction == backwardInTime && this.earlywood)) ||
          !measurementOptions.subAnnual) {
        if (direction == forwardInTime) {
          this.year++;
        } else if (direction == backwardInTime) {
          this.year--;
        };
      };
      this.earlywood = (measurementOptions.subAnnual) ? !this.earlywood : true;
    };

    this.index++;

    // update every time a point is placed
    Lt.metaDataText.updateText();
    Lt.annotationAsset.reloadAssociatedYears();
    if (Lt.popoutPlots.win) {
      Lt.popoutPlots.sendData();
    }
  };

  /**
   * delete a point from the measurement data
   * @function deletePoint
   */
  MeasurementData.prototype.deletePoint = function(i) {
    let direction = directionCheck();

    var second_points;
    if (this.points[i].start) {
      if (this.points[i - 1] != undefined && this.points[i - 1].break) {
        i--;
        second_points = this.points.slice().splice(i + 2, this.index - 1);
        second_points.map(e => {
          this.points[i] = e;
          i++;
        });
        this.index -= 2;
        delete this.points[this.index];
        delete this.points[this.index + 1];
      } else {
        second_points = this.points.slice().splice(i + 1, this.index - 1);
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
      second_points = this.points.slice().splice(i + 2, this.index - 1);
      second_points.map(e => {
        this.points[i] = e;
        i++;
      });
      this.index -= 2;
      delete this.points[this.index];
      delete this.points[this.index + 1];
    } else {
      console.log(this.index);
      var new_points = this.points;
      var k = i;
      second_points = this.points.slice().splice(i + 1, this.index - 1);
      second_points.map(e => {
        if (e && !e.start && !e.break) {
          if (measurementOptions.subAnnual) {
            e.earlywood = !e.earlywood;
            if (!e.earlywood && direction == forwardInTime) {
              e.year--;
            } else if (e.earlywood && direction == backwardInTime) {
              e.year++;
            };
          } else {
            if (direction == forwardInTime) {
              e.year--;
            } else if (direction == backwardInTime) {
              e.year++;
            };
          }
        }
        new_points[k] = e;
        k++;
      });

      this.points = new_points;
      this.index--;
      delete this.points[this.index];
      this.earlywood = !this.earlywood;
      console.log(this.index);
      if (this.points[this.index - 1].earlywood) {
        this.year--;
      }
    }

    Lt.metaDataText.updateText(); // updates after a point is deleted
    Lt.annotationAsset.reloadAssociatedYears();
    if (Lt.popoutPlots.win) {
      Lt.popoutPlots.sendData();
    }
  };

  /**
   * remove a range of points from the measurement data
   * @function cut
   */
  MeasurementData.prototype.cut = function(i, j) {
    var twoIncrements = Lt.measurementOptions.subAnnual;
    var yearsAscending = Lt.measurementOptions.forwardDirection;

    if (i > j) {
      this.points.splice(j,i-j+1);
    } else if (i < j) {
      this.points.splice(i,j-i+1);
    } else {
      alert('You cannot select the same point');
    };

    var trimmed_points = this.points.filter(Boolean); // remove null points
    var k = 0;
    this.points = [];
    trimmed_points.map(e => {
      // Set first point as start point.
      if (k === 0) {
        this.points[k] = {'start': true, 'skip': false, 'break': false,
          'latLng': e.latLng};
      } else {
        this.points[k] = e;
      }
      k++;
    });
    this.index = k;

    // If all point except first start point removed, "reset" points.
    if (!this.points[1]) {
      this.earlywood = true;
      this.year = 0;
      return
    }
    // Correct years to delete gap in timeline.
    var year = this.points[1].year;
    var second = this.points[1].earlywood;
    var breakPt = false;
    var c = (yearsAscending) ? 1 : -1;
    this.points.map(e => {
      if (e && !e.start && !e.break) { // If measurement point...
        // Only change year if single increment or second point evaluated when proper (lw for forward, ew for backward).
        year = ((second && yearsAscending) || (!second && !yearsAscending) || !twoIncrements) ? year + c : year;
        second = !second;
        e.year = year;
        e.earlywood = (twoIncrements) ? !second : true;
      }
    });

    // Set current data so next measurement point correct.
    var lastPt = this.points[this.points.length - 1];
    if (twoIncrements) {
      this.earlywood = !lastPt.earlywood;
      // Only increment if previous point the last increment of that year.
      if (yearsAscending && !lastPt.earlywood) {
        this.year = lastPt.year + 1;
      } else if (!yearsAscending && lastPt.earlywood) {
        this.year = lastPt.year - 1;
      } else {
        this.year = lastPt.year;
      }
    } else {
      this.year = yearsAscending ? lastPt.year + 1 : lastPt.year - 1;
    }

    Lt.metaDataText.updateText(); // updates after points are cut
    Lt.annotationAsset.reloadAssociatedYears();
    if (Lt.popoutPlots.win) {
      Lt.popoutPlots.sendData();
    }
  };

  /**
   * insert a point in the middle of the measurement data
   * @function insertPoint
   */
  MeasurementData.prototype.insertPoint = function(latLng) {
    let direction = directionCheck();
    var disList = [];

    // closest point index
    var i = Lt.helper.closestPointIndex(latLng);
    if (!i && i != 0) {
      alert('New point must be within existing points. Use the create toolbar to add new points to the series.');
      return;
    };

    var new_points = this.points;
    var second_points = this.points.slice().splice(i);
    var k = i;
    var year_adjusted;
    var earlywood_adjusted = true;


    if (this.points[i - 1] && this.points[i]) {
      if (this.points[i - 1].earlywood && measurementOptions.subAnnual) { // case 1: subAnnual enabled & previous point ew
        earlywood_adjusted = false;
        if (direction == forwardInTime) {
          year_adjusted = this.points[i - 1].year;
        } else if (direction == backwardInTime) {
          // special case when next point is a break point
          year_adjusted = (this.points[i].year) ? this.points[i].year : this.points[i - 1].year - 1;
        };

      } else if (this.points[i - 1].start || this.points[i].start) { // case 2: previous or closest point is start
          year_adjusted = this.points[i].year;
          if (this.points[i - 2] && this.points[i - 2].earlywood && measurementOptions.subAnnual && direction == forwardInTime) {
            earlywood_adjusted = false;
          } else if (this.points[i - 2] && !this.points[i - 2].break && !this.points[i - 2].start &&
                    !this.points[i - 2].earlywood && measurementOptions.subAnnual && direction == backwardInTime) {
            earlywood_adjusted = true;
          } else if ((this.points[i - 2].break || !this.points[i - 2].start) && measurementOptions.subAnnual && direction == backwardInTime) {
            earlywood_adjusted = !this.points[i - 3].earlywood || false;
          } else if (direction == backwardInTime) {
            earlywood_adjusted = false;
          };

      } else { // case 3: subAnnual disabled or previous point lw
        if (direction == forwardInTime) {
          year_adjusted = this.points[i - 1].year + 1;
        } else if (direction == backwardInTime) {
          // special case when next point is a break point
          year_adjusted = (this.points[i].year) ? this.points[i].year : this.points[i - 1].year;
        };
      };
    } else {
      alert('Please insert new point closer to connecting line.')
    };

    if (year_adjusted === undefined) {
      return;
    };

    new_points[k] = {'start': false, 'skip': false, 'break': false,
      'year': year_adjusted, 'earlywood': earlywood_adjusted,
      'latLng': latLng};

    var tempK = k;

    k++;

    second_points.map(e => {
      if(!e) {
       return;
      }
      if (!e.start && !e.break) {
        if (measurementOptions.subAnnual) { // case 1: subAnnual enabled
          e.earlywood = !e.earlywood;
          if (e.earlywood && direction == forwardInTime) {
            e.year++;
          } else if (!e.earlywood && direction == backwardInTime) {
            e.year--;
          };

        } else { // case 2: subAnnual disabled
          if (direction == forwardInTime) {
            e.year++;
          } else if (direction == backwardInTime) {
            e.year--;
          };
        };
      };
      new_points[k] = e;
      k++;
    });

    this.points = new_points;
    this.index = k;
    if (measurementOptions.subAnnual) {
      this.earlywood = !this.earlywood;
    };
    if (!this.points[this.index - 1].earlywood || !measurementOptions.subAnnual) { // add year if forward
      if (direction == forwardInTime) {
        this.year++
      } else {
        this.year--
      };
    };

    Lt.metaDataText.updateText(); // updates after a single point is inserted
    Lt.annotationAsset.reloadAssociatedYears();
    if (Lt.popoutPlots.win) {
      Lt.popoutPlots.sendData();
    }
    return tempK;
  };

  /**
   * insert a zero growth year in the middle of the measurement data
   * @function insertZeroGrowth
   */
  MeasurementData.prototype.insertZeroGrowth = function(i, latLng) {
    let direction = directionCheck();
    var new_points = this.points;
    var second_points = this.points.slice().splice(i + 1, this.index - 1);
    var k = i + 1;

    var subAnnualIncrement = Lt.measurementOptions.subAnnual == true;
    var annualIncrement = Lt.measurementOptions.subAnnual == false;

    // ensure correct inserted point order
    var firstEWCheck = true;
    var secondEWCheck = false;
    if (direction == forwardInTime) {
      var firstYearAdjusted = this.points[i].year + 1;
      var secondYearAdjusted = firstYearAdjusted;
    } else if (direction == backwardInTime) {
      var firstYearAdjusted = this.points[i].year;
      var secondYearAdjusted = this.points[i].year - 1;
      if (annualIncrement) {
        var firstEWCheck = true;
        var firstYearAdjusted = secondYearAdjusted;
      }
    }

    new_points[k] = {'start': false, 'skip': false, 'break': false,
      'year': firstYearAdjusted, 'earlywood': firstEWCheck, 'latLng': latLng};

    k++;

    if (subAnnualIncrement) {
      new_points[k] = {'start': false, 'skip': false, 'break': false,
        'year': secondYearAdjusted, 'earlywood': secondEWCheck, 'latLng': latLng};
      k++;
    }

    var tempK = k-1;

    second_points.map(e => {
      if (e && !e.start && !e.break) {
        if (direction == forwardInTime) {
          e.year++;
        } else if (direction == backwardInTime) {
          e.year--;
        };
      };
      new_points[k] = e;
      k++;
    });

    this.points = new_points;
    this.index = k;

    if (direction == forwardInTime) {
      this.year++;
    } else if (direction == backwardInTime) {
      this.year--;
    };

    Lt.metaDataText.updateText(); // updates after a single point is inserted
    Lt.annotationAsset.reloadAssociatedYears();
    if (Lt.popoutPlots.win) {
      Lt.popoutPlots.sendData();
    }
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
      var modifierState = event.getModifierState("Shift");
      // Don't autopan if shift is held
      if(modifierState) {
        return;
      }
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
      if (mousePos.x > 390 && mousePos.x + 60 < mapSize.x && mousePos.y < 40 && oldMousePos.y > mousePos.y) {
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
function MarkerIcon(color, imagePath) {

  var colors = {
    'light_blue' : { 'path': imagePath + 'images/light_blue_rect_circle_dot_crosshair.png',
                    'size': [32, 48] },
    'dark_blue'  : { 'path': imagePath + 'images/dark_blue_rect_circle_dot_crosshair.png',
                    'size': [32, 48] },
    'white_start': { 'path': imagePath + 'images/white_tick_icon.png',
                    'size': [32, 48] },
    'white_break': { 'path': imagePath + 'images/white_rect_circle_dot_crosshair.png',
                    'size': [32, 48] },
    'red'        : { 'path': imagePath + 'images/red_dot_icon.png',
                    'size': [12, 12] },
    'light_red'  : { 'path': imagePath + 'images/cb_light_red_tick_icon.png',
                    'size': [32, 48] },
    'pale_red'   : { 'path': imagePath + 'images/cb_pale_red_tick_icon.png',
                    'size': [32, 48] },
    'empty'      : { 'path': imagePath + 'images/empty_marker.png',
                    'size': [0, 0] },
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
  this.pathGuide = false;

  this.btn = new Button ('expand', 'Toggle appearance of measurement h-bar',
             () => { Lt.disableTools; this.btn.state('active'); this.pathGuide = true },
             () => { this.btn.state('inactive'); this.pathGuide = false }
            );

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
    $(Lt.viewer.getContainer()).off('mousemove');
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

    var scalerCoefficient = 1.3; // multiplys length between point & mouse
    function newCoordCalc (pointA, pointB, pointC) {
      return pointA + (scalerCoefficient * (pointB - pointC));
    };

    $(Lt.viewer.getContainer()).mousemove(e => {
      if (this.active) {
        this.layer.clearLayers();
        var mousePoint = Lt.viewer.mouseEventToLayerPoint(e);
        var mouseLatLng = Lt.viewer.mouseEventToLatLng(e);
        var point = Lt.viewer.latLngToLayerPoint(latLng);

        /* Getting the four points for the h bars, this is doing 90 degree rotations on mouse point */
        newX = newCoordCalc(mousePoint.x, mousePoint.y, point.y);
        newY = newCoordCalc(mousePoint.y, point.x, mousePoint.x);
        var topRightPoint = Lt.viewer.layerPointToLatLng([newX, newY]);

        newX = newCoordCalc(mousePoint.x, point.y, mousePoint.y);
        newY = newCoordCalc(mousePoint.y, mousePoint.x, point.x);
        var bottomRightPoint = Lt.viewer.layerPointToLatLng([newX, newY]);

        //doing rotations 90 degree rotations on latlng
        newX = newCoordCalc(point.x, point.y, mousePoint.y);
        newY = newCoordCalc(point.y, mousePoint.x, point.x);
        var topLeftPoint = Lt.viewer.layerPointToLatLng([newX, newY]);

        newX = newCoordCalc(point.x, mousePoint.y, point.y);
        newY = newCoordCalc(point.y, point.x, mousePoint.x);
        var bottomLeftPoint = Lt.viewer.layerPointToLatLng([newX, newY]);

        //color for h-bar
        var color;
        if (Lt.data.earlywood || !Lt.measurementOptions.subAnnual) {
          color = '#00BCD4';
        } else {
          color = '#00838f';
        }

        if (this.pathGuide) {
          // y = mx + b
          var m = (mousePoint.y - point.y) / (mousePoint.x - point.x);
          var b = point.y - (m * point.x);

          // finds x value along a line some distance away
          // found by combining linear equation & distance equation
          // https://math.stackexchange.com/questions/175896/finding-a-point-along-a-line-a-certain-distance-away-from-another-point
          function distanceToX (xNaut, distance) {
            var x = xNaut + (distance / (Math.sqrt(1 + (m ** 2))));
            return x;
          };

          function linearEq (x) {
            var y = (m * x) + b;
            return y;
          };

          var pathLength = 100;
          if (mousePoint.x < point.x) { // mouse left of point
            var pathLengthOne = pathLength;
            var pathLengthTwo = -pathLength;
          } else { // mouse right of point
            var pathLengthOne = -pathLength;
            var pathLengthTwo = pathLength;
          };

          var xOne = distanceToX(point.x, pathLengthOne);
          var xTwo = distanceToX(mousePoint.x, pathLengthTwo);

          if (mousePoint.y < point.y) { // mouse below point
            var verticalFixOne = point.y + pathLength;
            var verticalFixTwo = mousePoint.y - pathLength;
          } else { // mouse above point
            var verticalFixOne = point.y - pathLength;
            var verticalFixTwo = mousePoint.y + pathLength;
          };

          var yOne = linearEq(xOne) || verticalFixOne; // for vertical measurements
          var yTwo = linearEq(xTwo) || verticalFixTwo; // vertical asymptotes: slope = undefined

          var latLngOne = Lt.viewer.layerPointToLatLng([xOne, yOne]);
          var latLngTwo = Lt.viewer.layerPointToLatLng([xTwo, yTwo]);

          // path guide for point
          this.layer.addLayer(L.polyline([latLng, latLngOne],
              {interactive: false, color: color, opacity: '.75',
                weight: '5'}));

          // path guide for mouse
          this.layer.addLayer(L.polyline([mouseLatLng, latLngTwo],
              {interactive: false, color: color, opacity: '.75',
                weight: '5'}));

        };

        this.layer.addLayer(L.polyline([latLng, mouseLatLng],
            {interactive: false, color: color, opacity: '.75',
              weight: '5'}));

        this.layer.addLayer(L.polyline([topLeftPoint, bottomLeftPoint],
            {interactive: false, color: color, opacity: '.75',
              weight: '5'}));
        this.layer.addLayer(L.polyline([topRightPoint, bottomRightPoint],
            {interactive: false, color: color, opacity: '.75',
              weight: '5'}));
      }
    });
  }
}

/**
  * Method to reduce MarkerIcon usage
  * @function getMarker
  * @param {Leaflet latlng} iconLatLng
  * @param {Marker icon} color
  * @param {Icon imagepath} iconImagePath
  * @param {Drag ability} iconDrag
  * @param {Marker title} title
  */
function getMarker(iconLatLng, color, iconImagePath, iconDrag) {
  return L.marker(iconLatLng, {
        icon: new MarkerIcon(color, iconImagePath),
        draggable: iconDrag,
        riseOnHover: true
      })
  };

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
    // erase the markers
    this.markerLayer.clearLayers();
    this.markers = new Array();
    // erase the lines
    this.lineLayer.clearLayers();
    this.lines = new Array();

    var swap = (!Lt.measurementOptions.forwardDirection &&
                 Lt.measurementOptions.subAnnual &&
                 (Lt.data.points[1] && !Lt.data.points[1].earlywood));

    // plot the data back onto the map
    if (Lt.data.points !== undefined) {
      Lt.data.points = Lt.data.points.filter(Boolean);
      Object.values(Lt.data.points).map((e, i) => {
        if (e != undefined) {

          // Old design of measuring backwards had the first point as latewood.
          // Need to swap earlywood values of legacy cores.
          if (swap) {
            e.earlywood = !e.earlywood
          }

          this.newLatLng(Lt.data.points, i, e.latLng, true);

          // Marker tool tips:
          // If measuring forward, point tooltips are "honest". For a start/break pair: start point says...
          // ...Start, break says Break.
          // If measuring backwards, point tooltips "lie". Tooltips will have the text as if the specimin was...
          // ...measured forwards. For a start/break pair: start point says Break, break point says Start.
          var tooltip = "";
          if (Lt.data.points[i].year || Lt.data.points[i].year === 0) {
            var desc = (!Lt.measurementOptions.subAnnual) ? '' :
                       (Lt.data.points[i].earlywood) ? ', early' : ', late';
            tooltip = String(Lt.data.points[i].year) + desc;
            // !!! Start points after break points are visually shown as break points. !!!
            // !!! Refactor so break point is placed instead of start point !!!
          } else if (Lt.data.points[i].start && Lt.data.points[i - 1] && Lt.data.points[i - 1].break) {
            tooltip = 'Break';
          } else if (Lt.data.points[i].start) {
            tooltip = 'Start';
          } else if (Lt.data.points[i].break) {
            tooltip = 'Break';
          }

          // Measuring backwards "lies":
          if (!Lt.measurementOptions.forwardDirection) {
            // Break point pair: start point
            if (Lt.data.points[i].start && Lt.data.points[i - 1] && Lt.data.points[i - 1].break) {
              tooltip = 'Break';
            // Break point pair: break point
            } else if (Lt.data.points[i + 1] && Lt.data.points[i + 1].start && Lt.data.points[i].break) {
              tooltip = 'Break';
            // Start point has year value, previous actual start point has Start tooltip.
            } else if (Lt.data.points[i - 1] && Lt.data.points[i].start) {
              var desc = (!Lt.measurementOptions.subAnnual) ? '' :
                         (Lt.data.points[i - 1].earlywood) ? ', early' : ', late';
              tooltip = String(Lt.data.points[i - 1].year) + desc;
            } else if (Lt.data.points[i + 1] && Lt.data.points[i + 1].start) {
              tooltip = 'Start';
            }

            // First point is treated as a measurement point, not a start point.
            if (i === 0 && Lt.data.points[i + 1]) {
              var desc = (!Lt.measurementOptions.subAnnual) ? '' : ', late';
              var c = (!Lt.measurementOptions.subAnnual) ? 1 : 0;
              tooltip = String(Lt.data.points[i + 1].year + c) + desc;
            // Last point is treated as a start point, not a measurement point.
            } else if (i === Lt.data.points.length - 1) {
              tooltip = 'Start';
            }
          }

          this.markers[i].bindTooltip(tooltip, { direction: 'top' })
        }
      });
    }

    // bind popups to lines if not popped out
    const pts = JSON.parse(JSON.stringify(Lt.data.points)).filter(Boolean); // filter null points

    function create_tooltips_annual () {
      pts.map((e, i) => {
        let year = (Lt.preferences.forwardDirection) ? pts[i].year : pts[i].year + 1;
        if (year) {
          let first_or_last = (i == 1 || i == pts.length - 1) ? true : false;
          let options = (year % 50 == 0 || first_or_last) ? { permanent: true, direction: 'top' } : { direction: 'top' };
          let tooltip = String(year);
          Lt.visualAsset.lines[i].bindTooltip(tooltip, options);
        }
      });
    }

    function create_tooltips_subAnnual () {
      pts.map((e, i) => {
        let forward = Lt.preferences.forwardDirection;
        let backward = !Lt.preferences.forwardDirection;
        let year = pts[i].year;
        let ew = pts[i].earlywood;
        let latLng = L.latLng(pts[i].latLng);

        if (year) {
          let first_or_last = (i == 1 || i == pts.length - 2) ? true : false;
          let static = (year % 50 == 0 || first_or_last) ? true : false;
          let options = (static && pts[i].earlywood) ? { permanent: true, direction: 'top' } : { direction: 'top' };
          let tooltip = String(year);

          if (static && ew) { // permanent tooltips are attached to 1st increment of sub-annual measurements
            let inv_marker = getMarker(latLng, 'empty', Lt.basePath, false);
            inv_marker.bindTooltip(tooltip, options);
            inv_marker.addTo(Lt.viewer);
            inv_marker.openTooltip();
            options = { direction: 'top' };
          }
          // When measuring forwards, tooltip is attached to the line behind the marker.
          // When measuring backwards, tooltip is attached to the line infront of the marker.
          tooltip = (ew && backward) ? pts[i].year : pts[i].year + 1;
          ((ew && forward) || (!ew && backward)) ? tooltip += ', early' : tooltip += ', late';
          Lt.visualAsset.lines[i].bindTooltip(tooltip, options);
        }
      });
    }

    if (window.name.includes('popout') == false) {
      (Lt.measurementOptions.subAnnual) ? create_tooltips_subAnnual() : create_tooltips_annual();
    }

  }

  /**
   * A method used to create new markers and lines on the viewer
   * @function newLatLng
   * @param {Array} points -
   * @param {int} i - index of points
   * @param {Leaflet LatLng Object} latLng -
   */
  VisualAsset.prototype.newLatLng = function(pts, i, latLng, reload) {
    pts = pts.filter(Boolean);

    var leafLatLng = L.latLng(latLng);

    var draggable = false;
    if (window.name.includes('popout')) {
      draggable = true;
    }

    // When measuring backwards, marker color "lies". It will have the appearance...
    // ... as if it was measured forwards. For example, start points look like measurement...
    // ... points and measurement points look like start points (when appropriate.)
    var color;
    var forward = Lt.measurementOptions.forwardDirection;
    var backward = !forward;
    var annual = !Lt.measurementOptions.subAnnual;
    var subAnnual = !annual;
    // Start point icon:
    // !!! Start points after break points are visually shown as break points. !!!
    // !!! Refactor so break point is placed instead of start point !!!
    if (pts[i].start) {
      if (forward) {
        if (pts[i - 1] && pts[i - 1].break) {
          color = 'white_break';
        } else {
          color = 'white_start';
        }
      } else if (backward) {
        if (pts[i - 1] && pts[i - 1].break) {
          color = 'white_break';
        // Start points and measurement points swap when measuring backwards.
        } else if (pts[i - 1]) {
          if (pts[i - 1].year % 10 == 0) {
            color = (annual) ? 'light_red' :
                    (pts[i - 1].earlywood) ? 'pale_red' : 'light_red';
          } else {
            color = (annual) ? 'light_blue' :
                    (pts[i - 1].earlywood) ? 'light_blue' : 'dark_blue';
          }
        }
      }
    // Break point icon:
    } else if (pts[i].break) {
      if (forward) {
          color = 'white_break';
      } else if (backward) {
        color = 'white_break';
      }
    // Sub-annual icons:
    } else if (subAnnual) {
      if (pts[i].earlywood) {
        // Decades are colored red.
        color = (pts[i].year % 10 == 0) ? 'pale_red' : 'light_blue';
      } else { // Otherwise, point is latewood.
        color = (pts[i].year % 10 == 0) ? 'light_red' : 'dark_blue';
      }

      // Swap measurement path endings and start points.
      if (backward && pts[i + 1] && pts[i + 1].start) {
        color = 'white_start';
      }
    // Annual icons:
    } else {
      color = (pts[i].year % 10 == 0) ? 'light_red' : 'light_blue';

      // Swap measurement path endings and start points.
      if (backward && pts[i + 1] && pts[i + 1].start) {
        color = 'white_start';
      }
    };

    // Start and end points swapped when measuring backwards.
    if (backward && i === 0) {
      color = (pts[i + 1] && pts[i + 1].year % 10 == 0) ? 'light_red' :
              (annual) ? 'light_blue' : 'dark_blue';
    // Only apply this when active measuring disabled.
  } else if (backward && i === pts.length - 1 && reload) {
      color = 'white_start';
    }

    var marker = getMarker(leafLatLng, color, Lt.basePath, draggable);
    this.markers[i] = marker;   //add created marker to marker_list

    //tell marker what to do when being dragged
    this.markers[i].on('drag', (e) => {
      if (!pts[i].start) {
        this.lineLayer.removeLayer(this.lines[i]);
        this.lines[i] =
            L.polyline([this.lines[i]._latlngs[0], e.target._latlng],
            { color: this.lines[i].options.color,
              opacity: '.5', weight: '5'});
        this.lineLayer.addLayer(this.lines[i]);
      }
      if (this.lines[i + 1] !== undefined) {
        this.lineLayer.removeLayer(this.lines[i + 1]);
        this.lines[i + 1] =
            L.polyline([e.target._latlng, this.lines[i + 1]._latlngs[1]],
            { color: this.lines[i + 1].options.color,
              opacity: '.5',
              weight: '5'
            });
        this.lineLayer.addLayer(this.lines[i + 1]);
      } else if (this.lines[i + 2] !== undefined && !pts[i + 1].start) {
        this.lineLayer.removeLayer(this.lines[i + 2]);
        this.lines[i + 2] =
            L.polyline([e.target._latlng, this.lines[i + 2]._latlngs[1]],
            { color: this.lines[i + 2].options.color,
              opacity: '.5',
              weight: '5' });
        this.lineLayer.addLayer(this.lines[i + 2]);
      }
    });

    //tell marker what to do when the draggin is done
    this.markers[i].on('dragend', (e) => {
      Lt.undo.push();
      pts[i].latLng = e.target._latlng;
      Lt.annotationAsset.reloadAssociatedYears();
    });

    //tell marker what to do when clicked
    this.markers[i].on('click', (e) => {
      if (Lt.deletePoint.active) {
        Lt.deletePoint.action(i);
      };

      if (Lt.convertToStartPoint.active) {
        Lt.convertToStartPoint.action(i);
      };

      if (Lt.cut.active) {
        if (Lt.cut.point != -1) {
          Lt.cut.action(i);
        } else {
          Lt.cut.fromPoint(i);
        };
      };

      if (Lt.insertZeroGrowth.active) {
        var subAnnual = Lt.measurementOptions.subAnnual;
        var pointEW = pts[i].earlywood == true;
        var pointLW = pts[i].earlywood == false;
        var yearsIncrease = Lt.measurementOptions.forwardDirection == true;
        var yearsDecrease = Lt.measurementOptions.forwardDirection == false;

        if ((subAnnual && pointEW)
            || pts[i].start || pts[i].break) {
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

    // highlight year in plotting tool when point hovered over
    // add to conditional disable flashing when measuring
    // && !Lt.createPoint.active
    this.markers[i].on('mouseover', e => {
      if (Lt.popoutPlots.win && !Lt.createPoint.active) {
        // Do not highlight end point when measuring backward, but highlight start point.
        if (forward || (backward && i < pts.length - 1)) {
          var year = pts[i].year;
          if (backward && i === 0) {
            year = (annual && pts[i + 1]) ? pts[i + 1].year + 1 : pts[i + 1].year;
          }
          Lt.popoutPlots.highlightYear(year);
        }
      }
    })

    this.markers[i].on('mouseout', e => {
      if (Lt.popoutPlots.win && !Lt.createPoint.active) {
        Lt.popoutPlots.highlightYear(false)
      }
    })

    //drawing the line if the previous point exists
    if (pts[i - 1] != undefined && !pts[i].start) {
      var opacity = '.5';
      var weight = '5';
      if ((Lt.measurementOptions.forwardDirection && pts[i].earlywood) ||   // Line color condition swaps when....
         (!Lt.measurementOptions.forwardDirection && !pts[i].earlywood) || // ...measuring direction changes
          !Lt.measurementOptions.subAnnual ||
         (!Lt.measurementOptions.forwardDirection && pts[i - 1].earlywood && pts[i].break)) {
        var color = '#17b0d4'; // original = #00BCD4 : actual = #5dbcd
      } else {
        var color = '#026d75'; // original = #00838f : actual = #14848c
      };

      var comparisonPt = null;
      if (Lt.measurementOptions.forwardDirection) { // years counting up
        comparisonPt = pts[i].year
      } else { // years counting down
        comparisonPt = pts[i - 1].year;
      };

      //mark decades with red line
      if (comparisonPt % 10 == 0) {
        var opacity = '.6';
        var weight = '5';
        if (Lt.measurementOptions.subAnnual &&
           ((Lt.measurementOptions.forwardDirection && pts[i].earlywood) ||
            (!Lt.measurementOptions.forwardDirection && !pts[i].earlywood)) ||
            (!Lt.measurementOptions.forwardDirection && pts[i - 1].earlywood && pts[i].break)) {
              if (pts[i].break) {
                console.log('hey')
              }
          var color = '#e06f4c' // actual pale_red = #FC9272
        } else {
          var color = '#db2314' // actual light_red = #EF3B2C
        };
      };

      // Special case: start points much look to next point to determine line color.
      if (!Lt.measurementOptions.forwardDirection && pts[i - 1].start && pts[i].year % 10 == 0) {
        var color = '#db2314' // actual light_red = #EF3B2C
      }

      this.lines[i] = L.polyline([pts[i - 1].latLng, leafLatLng], {color: color, opacity: opacity, weight: weight});
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

  this.colorDivIcon = L.divIcon( {className: '#ff1c22'} ); // default red color

  this.createBtn = new Button (
    'comment',
    'Create annotations (Ctrl-a)',
    () => { Lt.disableTools(); this.enable(this.createBtn) },
    () => { this.disable(this.createBtn) }
  );
  this.createBtn.active = false;

  this.deleteBtn = new Button (
    'delete',
    'Delete an annotation',
    () => { Lt.disableTools(); this.enable(this.deleteBtn) },
    () => { this.disable(this.deleteBtn) }
  );
  this.deleteBtn.active = false;

  // crtl-a to activate createBtn
  L.DomEvent.on(window, 'keydown', (e) => {
    if (e.keyCode == 65 && e.getModifierState("Control") && window.name.includes('popout')) { // 65 refers to 'a'
      e.preventDefault();
      e.stopPropagation();
      Lt.disableTools();
      this.enable(this.createBtn);
    }
  }, this);

  // Only creating an annotation is tied to button enabling. Editing & deleting
  // are connected to saveAnnotation()
  AnnotationAsset.prototype.enable = function (btn) {
    btn.state('active');
    btn.active = true;
    Lt.viewer.getContainer().style.cursor = 'pointer';

    this.latLng = {};
    if (btn === this.createBtn) {
      Lt.viewer.doubleClickZoom.disable();
      $(Lt.viewer.getContainer()).click(e => {
        Lt.disableTools();
        Lt.collapseTools();
        this.createBtn.active = true; // disableTools() deactivates all buttons, need create annotation active

        this.latLng = Lt.viewer.mouseEventToLatLng(e);

        // display icon
        this.annotationIcon = L.marker([0, 0], {
          icon: this.colorDivIcon,
          draggable: true,
          riseOnHover: true,
        });

        this.annotationIcon.setLatLng(this.latLng);

        this.annotationIcon.addTo(Lt.viewer);

        this.createAnnotationDialog();

      });
    };;
  };

  AnnotationAsset.prototype.disable = function (btn) {
    if (!btn) { // for Lt.disableTools()
      this.disable(this.createBtn);
      this.disable(this.deleteBtn);
      return
    };

    $(Lt.viewer.getContainer()).off('click');
    btn.state('inactive');
    btn.active = false;
    Lt.viewer.getContainer().style.cursor = 'default';
  };

  AnnotationAsset.prototype.createAnnotationDialog = function (annotation, index) {
    this.index = index;

    if (annotation) { // set all meta data objects, catches for undefined elsewhere
      this.latLng = annotation.latLng;
      this.color = annotation.color;
      this.text = annotation.text;
      this.code = annotation.code;
      this.description = annotation.description;
      this.checkedUniqueNums = annotation.checkedUniqueNums;
      this.calculatedYear = annotation.calculatedYear;
      this.yearAdjustment = annotation.yearAdjustment;
      this.year = annotation.year;
    } else {
      // want this.color to stay constant between creating annotations
      this.text = '';
      this.code = [];
      this.description = [];
      this.checkedUniqueNums = [];
      this.calculatedYear = 0;
      this.yearAdjustment = 0;
      this.year = 0;
    };

    var decodedCookie = decodeURIComponent(document.cookie);
    var cookieArray = decodedCookie.split(';');
    for (var i = 0; i < cookieArray.length; i++) {;
      var cookieNameArray = cookieArray[i].split('=');
      var cookieNameIndex = cookieNameArray.indexOf('attributesObjectArray');
      var cookieAttributesObjectArray = cookieNameArray[cookieNameIndex + 1];
    };

    var defaultAttributes = [
      { 'title': 'Anatomical Anomaly',
        'options': [
                    {
                      'title': 'Fire Scar',
                      'code': 'FS',
                      'uniqueNum': '000000'
                    },
                    {
                      'title': 'Frost Ring',
                      'code': 'FR',
                      'uniqueNum': '000001'
                    },
                    {
                      'title': 'Intra-Annual Density Fluctuation',
                      'code': 'IADF',
                      'uniqueNum': '000002'
                    },
                    {
                      'title': 'Tramatic Resin Duct',
                      'code': 'TRD',
                      'uniqueNum': '000003'
                    },
                  ]
      },
      { 'title': 'Location',
        'options': [
                    {
                      'title': 'Earlywood',
                      'code': 'EW',
                      'uniqueNum': '000010'
                    },
                    {
                      'title': 'Latewood',
                      'code': 'LW',
                      'uniqueNum': '000020'
                    },
                    {
                      'title': 'Dormant',
                      'code': 'D',
                      'uniqueNum': '000030'
                    },
                  ]
      }
    ];

    if (!Lt.meta.attributesObjectArray || Lt.meta.attributesObjectArray.length == 0) {
      try {
        this.attributesObjectArray = JSON.parse(cookieAttributesObjectArray);
      }
      catch (error) {
        this.attributesObjectArray = defaultAttributes;
      }
    } else {
      if (Lt.meta.attributesObjectArray.length == 0) {
        this.attributesObjectArray = defaultAttributes;
      } else {
        this.attributesObjectArray = Lt.meta.attributesObjectArray;
      };
    };

    if (this.createBtn.active == false) {
      this.annotationIcon = this.markers[this.index];
    };

    let size = this.annotationDialogSize || [284, 265];
    let anchor = this.annotationDialogAnchor || [50, 5];

    // handlebars from template.html
    let content = document.getElementById("annotation-dialog-window-template").innerHTML;

    this.dialogAnnotationWindow = L.control.dialog({
      'minSize': [284, 265],
      'maxSize': [Number.MAX_SAFE_INTEGER, Number.MAX_SAFE_INTEGER],
      'size': size,
      'anchor': anchor,
      'initOpen': true
    }).setContent(content).addTo(Lt.viewer);

    // remember annotation size/location each times its resized/moved
    $(this.dialogAnnotationWindow._map).on('dialog:resizeend', () => { this.annotationDialogSize = this.dialogAnnotationWindow.options.size } );
    $(this.dialogAnnotationWindow._map).on('dialog:moveend', () => { this.annotationDialogAnchor = this.dialogAnnotationWindow.options.anchor } );

    // move between tabs & save edits
    var summaryBtn = document.getElementById('summary-btn');
    $(() => {
      $(summaryBtn).click(() => {
        if (this.dialogAttributesWindow) {
          this.dialogAttributesWindow.destroy();
          delete this.dialogAttributesWindow
        };
        this.summaryContent();
        this.openTab('summary-btn', 'summary-tab');
      });
    });

    var editBtn = document.getElementById('edit-summary-btn');
    if (window.name.includes('popout')) {
      $(editBtn).click(() => {
        this.editContent();
        this.openTab('edit-summary-btn', 'edit-summary-tab');
      });
    } else {
      editBtn.remove();
      summaryBtn.style.borderTopRightRadius = '10px';
      summaryBtn.style.borderBottomRightRadius = '10px';
    };

    // save & close dialog window when dialog closed w/ built in close button
    $(this.dialogAnnotationWindow._map).on('dialog:closed', (dialog) => {
      if (this.dialogAnnotationWindow && (dialog.originalEvent._content === this.dialogAnnotationWindow._content)) {
        if (this.createBtn.active) {
          this.saveAnnotation();
        } else {
          this.saveAnnotation(this.index);
          delete this.annotation;
          delete this.index;
        };

        if (this.dialogAttributesWindow) {
          this.dialogAttributesWindow.destroy();
          delete this.dialogAttributesWindow;
        };

        this.dialogAnnotationWindow.destroy();
        delete this.dialogAnnotationWindow;
      };
    });

    this.dialogAnnotationWindow.open();

    if (this.createBtn.active) { // if action is to create an annotation
      $(document).ready(() => {
        editBtn.click();
      });
    } else {
      $(document).ready(() => {
        summaryBtn.click();
      });
    };

  };

  AnnotationAsset.prototype.createAttributesDialog = function (attributeIndex) {
    this.attributeIndex = attributeIndex;

    let size = this.attributesDialogSize || [273, 215];
    let anchor = this.attributesDialogAnchor || [50, 294];

    // handlebars from template.html
    let content = document.getElementById("attributes-dialog-window-template").innerHTML;

    this.dialogAttributesWindow = L.control.dialog({
      'minSize': [273, 215],
      'maxSize': [Number.MAX_SAFE_INTEGER, Number.MAX_SAFE_INTEGER],
      'size': size,
      'anchor': anchor,
      'initOpen': true
    }).setContent(content).addTo(Lt.viewer);

    // remember annotation size/location each times its resized/moved
    $(this.dialogAttributesWindow._map).on('dialog:resizeend', () => { this.attributesDialogSize = this.dialogAttributesWindow.options.size; console.log(this.attributesDialogSize);} );
    $(this.dialogAttributesWindow._map).on('dialog:moveend', () => { this.attributesDialogAnchor = this.dialogAttributesWindow.options.anchor } );

    let divIndex = -1;

    var addAttributeOption = document.getElementById('create-option');
    $(addAttributeOption).click(() => {
      divIndex += 1;
      var newOptionDiv = document.createElement('div');
      newOptionDiv.id = divIndex;

      var optionTitle = document.createElement('label');
      optionTitle.className = 'attribute-label';
      optionTitle.innerHTML = 'Option: '
      newOptionDiv.appendChild(optionTitle);

      var optionDeleteBtn = document.createElement('button');
      optionDeleteBtn.className = 'annotation-btn';

      // handlebars from templates.html
      let content = document.getElementById("font-awesome-icon-template").innerHTML;
      let template = Handlebars.compile(content);
      let html = template({ icon_class: "fa fa-times" });

      optionDeleteBtn.innerHTML = html;
      $(optionDeleteBtn).click(() => {
        // remove div & option description/code from this.description & this.code
        let descriptionTextarea = newOptionDiv.getElementsByTagName('DIV')[0].getElementsByTagName('TEXTAREA')[0];
        let codeTextarea = newOptionDiv.getElementsByTagName('DIV')[0].getElementsByTagName('TEXTAREA')[1];

        if (this.description.includes(descriptionTextarea.value)) {
          var indexOfDescriptor = this.description.indexOf(descriptionTextarea.value);
          this.description.splice(indexOfDescriptor, 1);
        };

        if (this.code.includes(codeTextarea.value)) {
          var indexOfCodeEntry = this.code.indexOf(codeTextarea.value);
          this.code.splice(indexOfCodeEntry, 1);
        };

        $(newOptionDiv).remove();

        // remove option from existing attribute
        if (this.attributeIndex || this.attributeIndex == 0) {
          let existingAttributeObject = this.attributesObjectArray[this.attributeIndex];
          existingAttributeObject.options.splice(newOptionDiv.id, 1);
        };
      });
      newOptionDiv.appendChild(optionDeleteBtn);

      var optionTextDiv = document.createElement('div');

      var optionTextbox = document.createElement('textarea');
      optionTextbox.className += 'attribute-option attribute-textbox';
      optionTextbox.placeholder = 'Description.';
      optionTextDiv.appendChild(optionTextbox);

      var optionTextCode = document.createElement('textarea');
      optionTextCode.className += 'attribute-option attribute-textbox';
      optionTextCode.placeholder = 'Code.';
      optionTextDiv.appendChild(optionTextCode);

      newOptionDiv.appendChild(optionTextDiv)

      var fullOptionDiv = document.getElementById('attributes-options');
      fullOptionDiv.appendChild(newOptionDiv);
    });

    // destroy window without saving anything with ESC
    $(document).keyup((e) => {
      if (e.keyCode === 27 && this.dialogAttributesWindow) {
        this.dialogAttributesWindow.destroy();
        delete this.dialogAttributesWindow
      };
    });

    /* Model for saving attributes:
    var attributesObjectArray = [
      { 'title': 'title 1',
        'options': [
                    {
                      'title': 'option 1',
                      'code': 'code 1',
                      'uniqueNum': 'uniqueNum 1'
                    },
                    {
                      'title': 'option 2',
                      'code': 'code 2',
                      'uniqueNum': 'uniqueNum 2'
                    },
                  ]
      };
    ];
    */

    // save & close dialog window when dialog closed w/ built in close button
    this.alertCount = 0
    $(this.dialogAttributesWindow._map).on('dialog:closed', (dialog) => {
      if (this.dialogAttributesWindow && (dialog.originalEvent._content === this.dialogAttributesWindow._content)) {
        let allOptionsTitled = false;
        let newAttributeObject = new Object ();
        let optionsArray = [];

        var titleText = document.getElementById('title-input').value;
        var optionsElmList = document.getElementsByClassName('attribute-option');

        if (optionsElmList.length == 0) {
          this.dialogAttributesWindow.open();
          alert("Attribute must have at least one option.");
        };

        function uniqueNumber () {
          let randomNumString = ''
          for (var t = 0; t <= 5; t++) {
            randomNum = Math.floor(Math.random() * 10)
            randomNumString += String(randomNum);
          };
          return randomNumString;
        };

        for (var i = 0, j = 0; i < optionsElmList.length; i += 2, j += 1) { // i index for textarea elements, j index for optionObjects. 2i = j
          if (titleText == "" || optionsElmList[i].value == "") {
            this.dialogAttributesWindow.open();
            if (this.alertCount == 0) { // alert fires 3 times without catch for unknown reason
              this.alertCount += 1;
              alert("Attribute must have a title and all options must be named.");
            };
            allOptionsTitled = false;
            break;
          } else {
            // optionsElmList[i] is the option text, optionsElmList[i + 1] is the option code
            // based on the order they are created above
            let option = optionsElmList[i].value
            let code = optionsElmList[i + 1].value || '-'; // '-' is filler
            if (this.attributeIndex || this.attributeIndex == 0) {
              let existingAttributeObject = this.attributesObjectArray[this.attributeIndex];
              if (!existingAttributeObject.options[j]) { //  if option was deleted or added
                var optionObject = new Object ();
              } else {
                var optionObject = existingAttributeObject.options[j];
              };
              optionObject.title = option;
              optionObject.code = code;
              allOptionsTitled = true;
              if (!existingAttributeObject.options[j]) { //  if option was deleted or added
                optionObject.uniqueNum = uniqueNumber();
                existingAttributeObject.options.push(optionObject);
              };
            } else {
              let optionObject = new Object ();
              optionObject.title = option;
              optionObject.code = code;
              optionObject.uniqueNum = uniqueNumber();
              optionsArray.push(optionObject);
              allOptionsTitled = true;
            };
          };
        };

        if (allOptionsTitled === true && (this.attributeIndex != 0 && !this.attributeIndex)) { // new attribute being created
          newAttributeObject.title = document.getElementById('title-input').value;
          newAttributeObject.options = optionsArray;
          this.attributesObjectArray.push(newAttributeObject);

          this.dialogAttributesWindow.destroy();
          delete this.dialogAttributesWindow
          this.createCheckboxes(document.getElementById('attributes-options-div'));

        } else if (allOptionsTitled === true && (this.attributeIndex || this.attributeIndex == 0)) { // existing attribute was edited.
          let existingAttributeObject = this.attributesObjectArray[this.attributeIndex];
          existingAttributeObject.title = document.getElementById('title-input').value;

          this.dialogAttributesWindow.destroy();
          delete this.dialogAttributesWindow
          delete this.attributeIndex;
          this.createCheckboxes(document.getElementById('attributes-options-div'));
        };
      };
    });
  };

  AnnotationAsset.prototype.createCheckboxes = function (attributesOptionsDiv) {
    attributesOptionsDiv.innerHTML = '';

    for (let [attributeIndex, attributeObject] of this.attributesObjectArray.entries()) {
      let soloAttributeDiv = document.createElement('div');

      let title = document.createElement('p');
      title.className = 'option-title';
      title.innerHTML = attributeObject.title;
      soloAttributeDiv.appendChild(title);

      let deleteAttributeBtn = document.createElement('button');
      deleteAttributeBtn.className = 'annotation-btn attribute-btn';

      // handlebars from templates.html
      let content_A = document.getElementById("font-awesome-icon-template").innerHTML;
      let template_A = Handlebars.compile(content_A);
      let html_A = template_A({ icon_class: "fa fa-times" });

      deleteAttributeBtn.innerHTML = html_A;
      $(deleteAttributeBtn).click((e) => {
        var divToDelete = e.target.parentNode.parentNode; // user will click <i> image not button
        for (let checkboxDiv of divToDelete.getElementsByTagName('DIV')) {
          let descriptor = checkboxDiv.getElementsByTagName('INPUT')[0].id
          let code = checkboxDiv.getElementsByTagName('INPUT')[0].value

          // remove descriptor and code from this.description & this.code
          if (this.description.includes(descriptor)) {
            let indexOfDescriptor = this.description.indexOf(descriptor);
            this.description.splice(indexOfDescriptor, 1);
          };

          if (this.code.includes(code)) {
            var indexOfCodeEntry = this.code.indexOf(code);
            this.code.splice(indexOfCodeEntry, 1);
          };
        };

        delete this.attributesObjectArray.splice(attributeIndex, 1);
        $(divToDelete).remove();
      });
      soloAttributeDiv.appendChild(deleteAttributeBtn);

      let editAttributeBtn = document.createElement('button');
      editAttributeBtn.className = 'annotation-btn attribute-btn';

      // handlebars from templates.html
      let content_B = document.getElementById("font-awesome-icon-template").innerHTML;
      let template_B = Handlebars.compile(content_B);
      let html_B = template_B({ icon_class: "fa fa-pencil" });

      editAttributeBtn.innerHTML = html_B;
      $(editAttributeBtn).click((e) => {
        // edits saved with createAttributesDialog save button
        this.createAttributesDialog(attributeIndex);
        this.dialogAttributesWindow.open();

        let inputTitle = document.getElementById('title-input');
        inputTitle.value = attributeObject.title;

        // reset attribute code & description
        let optionNodes = soloAttributeDiv.childNodes;
        for (let node of optionNodes) {
          if (node.tagName == 'div' || node.tagName == 'DIV') { // each checkbox is held in its own div
            // firstchild = checkbox input
            let inputDescription = node.firstChild.id;
            let inputCode = node.firstChild.value;

            document.getElementById('create-option').click();

            // get second to last textarea created aka the most recent description textarea
            let textareaDescriptionInput = document.getElementsByClassName('attribute-textbox')[document.getElementsByClassName('attribute-textbox').length - 2];
            textareaDescriptionInput.value = inputDescription;
            let inputDescriptionIndex = this.description.indexOf(inputDescription);

            // get last textarea created aka the most recent code textarea
            let textareaCodeInput = document.getElementsByClassName('attribute-textbox')[document.getElementsByClassName('attribute-textbox').length - 1];
            if (inputCode != '-') { // '-' is used as filler
              textareaCodeInput.value = inputCode;
            };
            let inputCodeIndex = this.code.indexOf(inputCode);

            $(textareaDescriptionInput).change(() => {
              if (node.firstChild.checked && inputDescriptionIndex !== -1) {
                this.description[inputDescriptionIndex] = textareaDescriptionInput.value;
              };
            });

            $(textareaCodeInput).change(() => {
              if (node.firstChild.checked && inputCodeIndex !== -1) {
                if (!textareaCodeInput.value) {
                  this.code[inputCodeIndex] = '-'; // '-' is used as filler
                } else {
                  this.code[inputCodeIndex] = textareaCodeInput.value;
                };
              };
            });
          };
        };

      });
      soloAttributeDiv.appendChild(editAttributeBtn);

      let optionsArray = attributeObject.options || [];
      for (let option of optionsArray) {
        /* option =
              {
                'title': 'option 1',
                'code': 'code 1',
                'uniqueNum': 'number 1',
              },
        */
        let optionTitle = option.title;
        let optionCode = option.code;
        let optionUniqueNum = option.uniqueNum;

        let soloOptionDiv = document.createElement('div');
        soloOptionDiv.className = 'attribute-option-divs';

        let checkbox = document.createElement('input');
        checkbox.className = 'checkboxes';
        checkbox.type = 'checkbox';
        checkbox.id = optionTitle;
        checkbox.value = optionCode;
        checkbox.name = optionUniqueNum;

        if (this.checkedUniqueNums && this.checkedUniqueNums.includes(checkbox.name)) {
          checkbox.checked = true;
        };

        $(checkbox).change(() => { // any checkbox changes are saved;
          this.code = [];
          this.description = [];
          this.checkedUniqueNums = [];

          checkboxClass = document.getElementsByClassName('checkboxes')
          for (let checkboxIndex in checkboxClass) {
            if (checkboxClass[checkboxIndex].checked) {
              this.code.push(checkboxClass[checkboxIndex].value);
              this.description.push(checkboxClass[checkboxIndex].id);
              this.checkedUniqueNums.push(checkboxClass[checkboxIndex].name);
            };
          };
        });
        soloOptionDiv.appendChild(checkbox);

        let label = document.createElement('label');
        label.innerHTML = optionTitle;
        label.for = optionTitle;
        soloOptionDiv.appendChild(label);

        soloAttributeDiv.appendChild(soloOptionDiv);
      };

      attributesOptionsDiv.appendChild(soloAttributeDiv);
    };
  };

  AnnotationAsset.prototype.nearestYear = function (latLng) {
    if (Lt.data.points.length < 2) { // No points to find closest year from.
      return 0;
    }

    var closestI = Lt.helper.closestPointIndex(latLng);
    if ((Lt.measurementOptions.forwardDirection == false) || (closestI == Lt.data.points.length)) {
     // correct index when measuring backwards or if closest point is last point
     closestI--;
   };

    var closestPt = Lt.data.points[closestI];
    var closestYear;

    // find closest year to annotation
    if (!closestPt) {
      closestYear = 0;
    } else if (!closestPt.year && closestPt.year != 0) { // case 1: start or break point
      var previousPt = Lt.data.points[closestI - 1];
      var nextPt = Lt.data.points[closestI + 1];

      if (!previousPt && nextPt?.year) { // case 2: inital start point
        closestYear = nextPt.year
      } else if (!nextPt && previousPt?.year) { // case 3: last point is a start point
        closestYear = previousPt.year
      } else if (nextPt && !nextPt.year && Lt.data.points[closestI + 2]) { // case 4: break point & next point is a start point
        closestYear = Lt.data.points[closestI + 2].year;
      } else if (!previousPt?.year && Lt.data.points[closestI + 1]) { // case 5: start point & previous point is a break point
        closestYear = Lt.data.points[closestI + 1].year;
      } else { // case 6: start point in middle of point path
        var distanceToPreviousPt = Math.sqrt(Math.pow((closestPt.lng - previousPt.lng), 2) + Math.pow((closestPt.lat - previousPt.lat), 2));
        var distanceToNextPt = Math.sqrt(Math.pow((closestPt.lng - nextPt.lng), 2) + Math.pow((closestPt.lat - nextPt.lat), 2));

        if (distanceToNextPt > distanceToPreviousPt) {
          closestYear = previousPt.year;
        } else {
          closestYear = nextPt.year;
        };
      };
    } else {
      closestYear = closestPt.year;
    };

    return closestYear;
  };

  AnnotationAsset.prototype.createMouseEventListeners = function (index) {
    // how marker reacts when dragged
    this.markers[index].on('dragend', (e) => {
      Lt.aData.annotations[index].latLng = e.target._latlng;
      Lt.annotationAsset.reloadAssociatedYears();
    });

    // how marker reacts when clicked
    $(this.markers[index]).click(() => {
      if (this.deleteBtn.active) { // deleteing
        Lt.aData.deleteAnnotation(index);
        Lt.annotationAsset.reload();
      } else { // viewing or editing
        Lt.collapseTools();
        if (this.dialogAnnotationWindow) {
          this.dialogAnnotationWindow.destroy();
          delete this.dialogAnnotationWindow
        };
        this.createAnnotationDialog(Lt.aData.annotations[index], index);
      };
    });

    // how marker reacts when moussed over
    $(this.markers[index]).mouseover(() => {
      // handlebars from templates.html
      let content = document.getElementById("empty-div-template").innerHTML;
      let template = Handlebars.compile(content);
      let html = template( {div_name: "mouseover-popup-div"} )

      this.markers[index].bindPopup(html, { minWidth:160, closeButton:false }).openPopup();

      var popupDiv = document.getElementById('mouseover-popup-div');

      if (Lt.aData.annotations[index].text) { // only show text description if text exists
        var popupTextTitle = document.createElement('h5');
        popupTextTitle.className = 'annotation-title';
        popupTextTitle.innerHTML = 'Text: ';
        popupDiv.appendChild(popupTextTitle);

        var popupText = document.createElement('p');
        popupText.className = 'text-content';
        popupText.style.marginTop = 0;
        popupText.style.marginBottom = '4px';
        popupText.innerHTML = Lt.aData.annotations[index].text;
        popupDiv.appendChild(popupText);
      };

      if (Lt.aData.annotations[index].description && Lt.aData.annotations[index].description.length > 0) { // only show attributes if attributes exist/selected
        var popupDescriptionTitle = document.createElement('h5');
        popupDescriptionTitle.className = 'annotation-title';
        popupDescriptionTitle.style.margin = 0;
        popupDescriptionTitle.innerHTML = 'Attributes: '
        popupDiv.appendChild(popupDescriptionTitle);

        var popupDescriptionList = document.createElement('ul');
        popupDescriptionList.style.marginBottom = '3px';
        for (var descriptorIndex in Lt.aData.annotations[index].description) {
          var listElm = document.createElement('li');
          listElm.innerHTML = Lt.aData.annotations[index].description[descriptorIndex];
          popupDescriptionList.appendChild(listElm);
        };
        popupDiv.appendChild(popupDescriptionList);
      };

      var popupYearTitle = document.createElement('h5');
      popupYearTitle.style.margin = 0;
      popupYearTitle.className = 'annotation-title';
      popupYearTitle.innerHTML = 'Associated Year: ';
      popupDiv.appendChild(popupYearTitle);

      var popupYear = document.createElement('span');
      popupYear.className = 'text-content';
      popupYear.style.cssFloat = 'right';
      Lt.aData.annotations[index].calculatedYear = this.nearestYear(Lt.aData.annotations[index].latLng);
      popupYear.innerHTML = Lt.aData.annotations[index].calculatedYear + Lt.aData.annotations[index].yearAdjustment;
      popupDiv.appendChild(popupYear);
    });

    $(this.markers[index]).mouseout(() => {
      this.markers[index].closePopup();
    });
  };

  AnnotationAsset.prototype.openTab = function (btnName, tabName) {
    var i;
    var tabContent;
    var tabLinks;

    tabContent = document.getElementsByClassName("tabContent");
    for (i = 0; i < tabContent.length; i++) {
      tabContent[i].style.display = "none";
    };

    tabLinks = document.getElementsByClassName("tabLinks");
    for (i = 0; i < tabLinks.length; i++) {
      tabLinks[i].className = tabLinks[i].className.replace(" active", "");
    };

    if (tabName && btnName) {
      document.getElementById(tabName).style.display = "block";
      document.getElementById(btnName).className += " active";
    };
  };

  AnnotationAsset.prototype.summaryContent = function () {
    var summaryDiv = document.getElementById('summary-tab');
    summaryDiv.innerHTML = '';

    // Start: text
    var summaryTextDiv = document.createElement('div');
    summaryTextDiv.className = 'summaryTextDiv';

    var textTitle = document.createElement('h5');
    textTitle.id = 'text-title';
    textTitle.innerHTML = "Text:";
    summaryTextDiv.appendChild(textTitle);

    var textContent = document.createElement('p');
    textContent.className = 'text-content';
    if (this.text == "") {
      textContent.innerHTML = 'N/A';
    } else {
      textContent.innerHTML = this.text;
    };

    summaryTextDiv.appendChild(textContent);
    summaryDiv.appendChild(summaryTextDiv);
    // End: text

    // Start: attributes
    var summaryAttributesDiv = document.createElement('div');
    summaryAttributesDiv.className = 'summaryAttributesDiv';

    var attributesTitle = document.createElement('h5');
    attributesTitle.className = 'annotation-title'
    attributesTitle.innerHTML = "Attributes:";
    summaryAttributesDiv.appendChild(attributesTitle);

    var attributeCode = document.createElement('p');
    attributeCode.className = 'text-content';
    attributeCode.style.margin = 0;
    var code = '';
    if (this.code && this.code.length > 0) {
      for (var codeEntry of this.code) {
        code += codeEntry;
      }
    } else {
      code = 'N/A';
    };
    attributeCode.innerHTML = 'Attributes Code: ' + code;
    summaryAttributesDiv.appendChild(attributeCode);

    var attributesDescription = document.createElement('p');
    attributesDescription.className = 'text-content';
    attributesDescription.style.margin = 0;
    attributesDescription.innerHTML = 'Attributes:';
    summaryAttributesDiv.appendChild(attributesDescription);

    var attributesList = document.createElement('ul');
    summaryAttributesDiv.appendChild(attributesList);
    if (this.description && this.description.length > 0) {
      var descriptionList = this.description;
    } else {
      var descriptionList = [];
      var descriptorElm = document.createElement('li');
      descriptorElm.innerHTML = 'N/A';
      attributesList.appendChild(descriptorElm);
    };

    for (var descriptor in descriptionList) {
      var descriptorElm = document.createElement('li')
      descriptorElm.innerHTML = descriptionList[descriptor];
      attributesList.appendChild(descriptorElm);
    };

    summaryDiv.appendChild(summaryAttributesDiv);
    // End: attributes

    // START: associated year
    var summaryAssociatedYearDiv = document.createElement('div');
    summaryAssociatedYearDiv.className = 'summaryAssociatedYearDiv';

    var associatedYearTitle = document.createElement('h5');
    associatedYearTitle.innerHTML = 'Associated Year: ';
    associatedYearTitle.className = 'annotation-title';
    summaryAssociatedYearDiv.appendChild(associatedYearTitle);

    var associatedYearSpan = document.createElement('span');
    associatedYearSpan.className = 'text-content';
    this.calculatedYear = this.nearestYear(this.latLng);
    associatedYearSpan.innerHTML = this.calculatedYear + this.yearAdjustment;
    summaryAssociatedYearDiv.appendChild(associatedYearSpan);

    summaryDiv.appendChild(summaryAssociatedYearDiv);
    // END: associated year

    // START: link to annotation
    var summaryLinkDiv = document.createElement('div');
    summaryLinkDiv.className = 'summaryLinkDiv';

    var getURL = window.location.href;
    var parsedURL = new URL(getURL);

    var lat = this.latLng.lat;
    var lng = this.latLng.lng;
    // round to 5 decimal places
    lat = lat.toFixed(5);
    lng = lng.toFixed(5);

    var existingLatParam = parsedURL.searchParams.get("lat");
    var existingLngParam = parsedURL.searchParams.get("lng");
    if (!existingLatParam || !existingLngParam) { // url parameters don't exist
      parsedURL.searchParams.append("lat", lat);
      parsedURL.searchParams.append("lng", lng);
    } else { // url parameters already exist
      parsedURL.searchParams.set("lat", lat);
      parsedURL.searchParams.set("lng", lng);
    };

    var linkTitle = document.createElement('h5');
    // handlebars from template.html
    let content = document.getElementById("link-template").innerHTML;
    let template = Handlebars.compile(content);
    let html = template( {url: String(parsedURL), title: 'Annotation GeoLink'} );

    linkTitle.innerHTML = html;
    linkTitle.className = 'annotation-title';
    linkTitle.id = 'link-title';
    summaryLinkDiv.appendChild(linkTitle)

    var copyLinkBtn = document.createElement('button');
    copyLinkBtn.className = 'annotation-link-btn';

    // handlebars from templates.html
    let content_C = document.getElementById("font-awesome-icon-template").innerHTML;
    let template_C = Handlebars.compile(content_C);
    let html_C = template_C({ icon_class: "fa fa-clone" });

    copyLinkBtn.innerHTML = html_C;
    $(copyLinkBtn).click(() => {
      window.copyToClipboard(parsedURL);
    });
    summaryLinkDiv.appendChild(copyLinkBtn);

    summaryDiv.appendChild(summaryLinkDiv);
    // END : link to annotation
  };

  AnnotationAsset.prototype.editContent = function () {
    var editDiv = document.getElementById('edit-summary-tab');
    editDiv.innerHTML = ''; // reset div so elements do not duplicate

    // Start: text
    var editTextDiv = document.createElement('div');
    editTextDiv.className = 'editTextDiv';

    var textTitle = document.createElement('h5');
    textTitle.id = 'text-title';
    textTitle.innerHTML = "Text:";
    editTextDiv.appendChild(textTitle);

    var textBox = document.createElement('TEXTAREA');
    textBox.value = this.text;
    $(textBox).change(() => { //  any text changes are saved
      this.text = textBox.value;
    });

    editTextDiv.appendChild(textBox);
    editDiv.appendChild(editTextDiv);
    // End: text

    // Start: attributes
    var editAttributesDiv = document.createElement('div');
    editAttributesDiv.className = 'editAttributesDiv';

    var attributesTitle = document.createElement('h5');
    attributesTitle.className = 'annotation-title'
    attributesTitle.innerHTML = "Attributes:";
    editAttributesDiv.appendChild(attributesTitle);

    // add a new attribute options
    var openAttributeEditButton = document.createElement('button');
    openAttributeEditButton.className = 'annotation-btn';

    // handlebars from templates.html
    let content = document.getElementById("font-awesome-icon-template").innerHTML;
    let template = Handlebars.compile(content);
    let html = template({ icon_class: "fa fa-plus" });

    openAttributeEditButton.innerHTML = html;
    $(openAttributeEditButton).click(() => {
      if (this.dialogAttributesWindow) {
        this.dialogAttributesWindow.destroy();
        delete this.dialogAttributesWindow;
      };
      this.createAttributesDialog();
      this.dialogAttributesWindow.open();
      document.getElementById('create-option').click(); // add 2 options by default
      document.getElementById('create-option').click();
    });
    editAttributesDiv.appendChild(openAttributeEditButton);

    var attributesOptionsDiv = document.createElement('div');
    attributesOptionsDiv.id = 'attributes-options-div';
    this.createCheckboxes(attributesOptionsDiv);

    editAttributesDiv.appendChild(attributesOptionsDiv);
    editDiv.appendChild(editAttributesDiv);
    // END: attributes

    // START: associated year
    var editAssociatedYearDiv = document.createElement('div');
    editAssociatedYearDiv.className = 'editAssociatedYearDiv';

    var associatedYearTitle = document.createElement('h5');
    associatedYearTitle.innerHTML = 'Associated Year: ';
    associatedYearTitle.className = 'annotation-title';
    editAssociatedYearDiv.appendChild(associatedYearTitle);

    var associatedYearInput = document.createElement('input');
    associatedYearInput.type = 'number';
    this.calculatedYear = this.nearestYear(this.latLng);
    associatedYearInput.value = this.calculatedYear + this.yearAdjustment;
    $(associatedYearInput).change(() => {
      this.year = associatedYearInput.value;
      this.yearAdjustment = associatedYearInput.value - this.calculatedYear;
    });
    editAssociatedYearDiv.appendChild(associatedYearInput);

    editDiv.appendChild(editAssociatedYearDiv);
    // END: associated year

    // START: color selection
    var editColorDiv = document.createElement('div');
    editColorDiv.className = 'editColorDiv';

    var colorTitle = document.createElement('h5');
    colorTitle.className = 'annotation-title';
    colorTitle.innerHTML = 'Color: '
    colorTitle.style.display = 'block';
    editColorDiv.appendChild(colorTitle);

    var colorPalette = {
      'red': '#ff1c22',
      'green': '#17b341',
      'blue': '#1395d1',
      'purple': '#db029f',
    };

    for (color in colorPalette) { // create color buttons
      var colorBtn = document.createElement('button');
      var hexCode = colorPalette[color];
      colorBtn.className = 'color-btn';
      colorBtn.style.backgroundColor = hexCode;
      colorBtn.id = hexCode;
      $(colorBtn).click((e) => {
        this.colorDivIcon = L.divIcon( {className: e.currentTarget.id} );
        this.annotationIcon.setIcon(this.colorDivIcon);

        var colorBtnList = document.getElementsByClassName('color-btn');
        for (var i = 0; i < colorBtnList.length; i++) { // deselect other buttons
          colorBtnList[i].style.boxShadow = "0 0 0 0";
        };
        e.currentTarget.style.boxShadow = "0 0 0 4px #b8b8b8";
      });

      editColorDiv.appendChild(colorBtn);
    };

    for (var j = 0; j < editColorDiv.childNodes.length; j++) {
      var iconColor = this.colorDivIcon.options.className;
      var buttonColor = editColorDiv.childNodes[j].id;
      if (iconColor == buttonColor) {
        editColorDiv.childNodes[j].click();
      };
    };

    editDiv.appendChild(editColorDiv);
    // END: color selection
  };

  AnnotationAsset.prototype.saveAnnotation = function (index) {
    var content = {
      'latLng': this.latLng,
      'color': this.colorDivIcon.options.className,
      'text': this.text,
      'code': this.code,
      'description': this.description,
      'checkedUniqueNums': this.checkedUniqueNums,
      'calculatedYear': this.calculatedYear,
      'yearAdjustment': this.yearAdjustment,
      'year': this.year,
    };
    Lt.meta.attributesObjectArray = this.attributesObjectArray;
    document.cookie = 'attributesObjectArray=' + JSON.stringify(this.attributesObjectArray) + '; max-age=60*60*24*365';

    if (this.createBtn.active) {
      var newIndex = Lt.aData.index;
      Lt.aData.index++;

      Lt.aData.annotations[newIndex] = content;
      this.markers[newIndex] = this.annotationIcon;

      this.createMouseEventListeners(newIndex);

      this.markerLayer.addLayer(this.markers[newIndex]);

      this.disable(this.createBtn);
    } else {
      Lt.aData.annotations[index] = content;
    };
  };

  AnnotationAsset.prototype.reloadAssociatedYears = function () {
      Object.values(Lt.aData.annotations).map((e) => {
        e.calculatedYear = this.nearestYear(e.latLng);
        e.yearAdjustment = e.yearAdjustment || 0;
        e.year = e.calculatedYear + e.yearAdjustment;
      });
  };

  AnnotationAsset.prototype.reload = function () {
    this.markerLayer.clearLayers();
    this.markers = [];
    Lt.aData.index = 0;
    if (Lt.aData.annotations != undefined) {
      // remove null or undefined elements
      var reducedArray = Object.values(Lt.aData.annotations).filter(e => e != undefined);
      Lt.aData.annotations = {};
      reducedArray.map((e, i) => Lt.aData.annotations[i] = e);

      this.reloadAssociatedYears();

      Object.values(Lt.aData.annotations).map((e, i) => {
        var draggable = false;
        if (window.name.includes('popout')) {
          draggable = true;
        };

        e.color = e.color || '#ff1c22';

        this.annotationIcon = L.marker([0, 0], {
          icon: L.divIcon( {className: e.color} ),
          draggable: true,
          riseOnHover: true,
        });

        this.annotationIcon.setLatLng(e.latLng);
        this.annotationIcon.addTo(Lt.viewer);

        this.markers[i] = this.annotationIcon;
        this.createMouseEventListeners(i);

        this.markerLayer.addLayer(this.markers[i]);

        Lt.aData.index++;
      });
    }
  };

};

/**
 * Scale bar for orientation & screenshots
 * @constructor
 * @param {LTreering} - Lt
 */
function ScaleBarCanvas (Lt) {
  var scaleBarDiv = document.createElement('div');
  var nativeWindowWidth = Lt.viewer.getContainer().clientWidth;

  // handlebars from template.html
  let content = document.getElementById("scale-bar-template").innerHTML;
  let template = Handlebars.compile(content);
  let html = template( {width: nativeWindowWidth} );

  scaleBarDiv.innerHTML = html;
  document.getElementsByClassName('leaflet-bottom leaflet-left')[0].appendChild(scaleBarDiv);

  var canvas = document.getElementById("scale-bar-canvas");
  var ctx = canvas.getContext("2d");

  var map = Lt.viewer;

  ScaleBarCanvas.prototype.load = function () {
    var pixelWidth;
    map.eachLayer(function (layer) {
      if (layer.options.maxNativeZoom) {
        var leftMostPt = layer.options.bounds._southWest;
        var rightMostPt = layer.options.bounds._northEast;
        pixelWidth = map.project(rightMostPt, Lt.getMaxNativeZoom()).x;
      }
    });

    var windowZoom = true; // used to set initial zoom level
    function modifyScaleBar() {
      ctx.clearRect(0, 0, nativeWindowWidth, 100);

      if (windowZoom) {
        this.initialZoomLevel = map.getZoom();
        windowZoom = false;
      }

      var metricWidth = pixelWidth / Lt.meta.ppm;
      var currentZoomLevel = map.getZoom();
      var zoomExponentialChange = Math.pow(Math.E, -0.693 * (currentZoomLevel - this.initialZoomLevel)); // -0.693 found from plotting zoom level with respect to length in excel then fitting expoential eq.

      var tenth_metricLength = (metricWidth * zoomExponentialChange) / 10;

      this.value = 'Error';
      this.unit = ' nm';
      this.mmValue = 0;
      this.maxValue = Math.round(tenth_metricLength / 10000);

      this.unitTable =
        {
          row: [
            {
              begin: 10000,
              end: Number.MAX_SAFE_INTEGER,
              value: this.maxValue * 10,
              mmValue: this.maxValue * 1000,
              unit: ' m',
            },

            {
              begin: 5000,
              end: 10000,
              value: 10,
              mmValue: 10000,
              unit: ' m',
            },

            {
              begin: 1000,
              end: 5000,
              value: 5,
              mmValue: 5000,
              unit: ' m',
            },

            {
              begin: 500,
              end: 1000,
              value: 1,
              mmValue: 1000,
              unit: ' m',
            },

            {
              begin: 200,
              end: 500,
              value: 50,
              mmValue: 500,
              unit: ' cm',
            },

            {
              begin: 50,
              end: 200,
              value: 10,
              mmValue: 100,
              unit: ' cm',
            },

            {
              begin: 30,
              end: 50,
              value: 5,
              mmValue: 50,
              unit: ' cm',
            },

            {
              begin: 8,
              end: 30,
              value: 10,
              mmValue: 10,
              unit: ' mm',
            },

            {
              begin: 3,
              end: 8,
              value: 5,
              mmValue: 5,
              unit: ' mm',
            },

            {
              begin: 1,
              end: 3,
              value: 1,
              mmValue: 1,
              unit: ' mm',
            },

            {
              begin: 0.3,
              end: 1,
              value: 0.5,
              mmValue: 0.5,
              unit: ' mm',
            },

            {
              begin: 0.05,
              end: 0.3,
              value: 0.1,
              mmValue: 0.1,
              unit: ' mm',
            },

            {
              begin: 0.03,
              end: 0.05,
              value: 0.05,
              mmValue: 0.05,
              unit: ' mm',
            },

            {
              begin: 0.005,
              end: 0.03,
              value: 0.01,
              mmValue: 0.01,
              unit: ' mm',
            },

            {
              begin: 0.003,
              end: 0.005,
              value: 5,
              mmValue: 0.005,
              unit: ' um',
            },

            {
              begin: 0.0005,
              end: 0.003,
              value: 1,
              mmValue: 0.001,
              unit: ' um',
            },

            {
              begin: Number.MIN_SAFE_INTEGER,
              end: 0.0005,
              value: 0.5,
              mmValue: 0.0005,
              unit: ' um',
            },
          ]
        };

      var table = this.unitTable;
      var i;
      for (i = 0; i < table.row.length; i++) {
        if (table.row[i].end > tenth_metricLength && tenth_metricLength >= table.row[i].begin) {
          this.value = table.row[i].value;
          this.unit = table.row[i].unit;
          this.mmValue = table.row[i].mmValue;
        };
      };

      var stringValue_tenthMetric_ratio = this.mmValue / tenth_metricLength;
      var pixelLength = stringValue_tenthMetric_ratio * (nativeWindowWidth / 10);
      var rounded_metricLength = '~' + String(this.value) + this.unit;

      ctx.fillStyle = '#f7f7f7'
      ctx.globalAlpha = .7;
      ctx.fillRect(0, 70, pixelLength + 70, 30); // background

      ctx.fillStyle = '#000000';
      ctx.globalAlpha = 1;
      ctx.font = "12px Arial"
      ctx.fillText(rounded_metricLength, pixelLength + 15, 90); // scale bar length text

      ctx.fillRect(10, 90, pixelLength, 3); // bottom line
      ctx.fillRect(10, 80, 3, 10); // left major line
      ctx.fillRect(pixelLength + 7, 80, 3, 10); // right major line

      var i;
      for (i = 0; i < 4; i++) {
        var distanceBetweenTicks = pixelLength / 5
        var x = (distanceBetweenTicks) * i;
        ctx.fillRect(x + distanceBetweenTicks + 10, 85, 1, 5); // 10 = initial canvas x value
      };
    }

    map.on("resize", modifyScaleBar);
    map.on("zoom", modifyScaleBar);
  };
};

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

  // handlebars from templates.html
  let content_A = document.getElementById("button-icon-template").innerHTML;
  let template_A = Handlebars.compile(content_A);
  let html_A = template_A({ icon_string: icon });

  states.push({
    stateName: 'inactive',
    icon: html_A,
    title: toolTip,
    onClick: enable
  });
  if (disable !== null) {
    if (icon == 'expand') { // only used for mouse line toggle
      var icon = 'compress';
      var title = 'Disable h-bar path guide';
    } else {
      var icon = 'clear';
      var title = 'Cancel';
    }

    // handlebars from templates.html
    let content_B = document.getElementById("button-icon-template").innerHTML;
    let template_B = Handlebars.compile(content_B);
    let html_B = template_B({ icon_string: icon });

    states.push({
      stateName: 'active',
      icon: html_B,
      title: title,
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

  // handlebars from templates.html
  let content_A = document.getElementById("button-icon-template").innerHTML;
  let template_A = Handlebars.compile(content_A);
  let html_A = template_A({ icon_string: icon });

  // handlebars from templates.html
  let content_B = document.getElementById("button-icon-template").innerHTML;
  let template_B = Handlebars.compile(content_B);
  let html_B = template_B({ icon_string: "expand_less" });

  this.btn = L.easyButton({
    states: [
      {
        stateName: 'collapse',
        icon: html_A,
        title: toolTip,
        onClick: () => {
          Lt.disableTools();
          Lt.collapseTools();
          this.expand();
        }
      },
      {
        stateName: 'expand',
        icon: html_B,
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
  var height = (4/9) * screen.height;
  var width = screen.width;

  this.btn = new Button('straighten', 'Enter Popout Mode to access the full suite\nof measurement and annotation tools', () => {
    window.open(Lt.meta.popoutUrl, 'popout' + Math.round(Math.random()*10000),
                'location=yes,height=' + height + ',width=' + width + ',scrollbars=yes,status=yes, top=0');
  });
};

/** A popout with time series plots
 * @constructor
 * @param {Ltreering} Lt - Leaflet treering object
 */
 function PopoutPlots (Lt) {
   var height = (4/9) * screen.height;
   var top = (2/3) * screen.height;
   var width = screen.width;
   this.childSite = null
   this.win = null

   this.btn = new Button('insights',
                         'Open time series plots in a new window',
                         () => {
                           //this.childSite = 'http://localhost:8080/dendro-plots/'
                           this.childSite = 'https://umn-latis.github.io/dendro-plots/'
                           this.win = window.open(this.childSite, 'popout' + Math.round(Math.random()*10000),
                                       'location=yes,height=' + height + ',width=' + width + ',scrollbars=yes,status=yes, top=' + top);

                           let data = { points: Lt.helper.findDistances(), annotations: Lt.aData.annotations };
                           window.addEventListener('message', () => {
                             this.win.postMessage(data, this.childSite);
                           }, false)
                         });

    PopoutPlots.prototype.sendData = function() {
      let data = { points: Lt.helper.findDistances(), annotations: Lt.aData.annotations };
      this.win.postMessage(data, this.childSite);
    }

    PopoutPlots.prototype.highlightYear = function(year) {
      this.win.postMessage(year, this.childSite);
    }

};

/**
 * Undo actions
 * @constructor
 * @param {Ltreering} Lt - Leaflet treering object
 */
function Undo(Lt) {
  this.stack = new Array();
  this.btn = new Button('undo', 'Undo', () => {
    this.pop();
    Lt.metaDataText.updateText();
    if (Lt.popoutPlots.win) {
      Lt.popoutPlots.sendData();
    }
  });
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
  this.btn = new Button('redo', 'Redo', () => {
    this.pop();
    Lt.metaDataText.updateText();
    if (Lt.popoutPlots.win) {
      Lt.popoutPlots.sendData();
    }
  });
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

  // handlebars from templates.html
  let content = document.getElementById("calibration-template").innerHTML;

  this.popup = L.popup({closeButton: false}).setContent(content)
  this.btn = new Button(
    'space_bar',
    'Calibrate pixels per millimeter by measuring a known distance\n(This will override image resolution metadata from Elevator!)',
    () => { Lt.disableTools(); this.enable() },
    () => { this.disable() }
  );

  Calibration.prototype.calculatePPM = function(p1, p2, length) {
    var startPoint = Lt.viewer.project(p1, Lt.getMaxNativeZoom());
    var endPoint = Lt.viewer.project(p2, Lt.getMaxNativeZoom());
    var pixel_length = Math.sqrt(Math.pow(Math.abs(startPoint.x - endPoint.x), 2) +
        Math.pow(Math.abs(endPoint.y - startPoint.y), 2));
    var pixelsPerMillimeter = pixel_length / length;
    var retinaFactor = 1;
    // if (L.Browser.retina) {
    //   retinaFactor = 2; // this is potentially incorrect for 3x+ devices
    // }
    Lt.meta.ppm = pixelsPerMillimeter / retinaFactor;
    Lt.meta.ppmCalibration = true;
    console.log(Lt.meta.ppm);
  }

  Calibration.prototype.enable = function() {
    this.btn.state('active');
    Lt.mouseLine.enable();


    Lt.viewer.getContainer().style.cursor = 'pointer';

    $(document).keyup(e => {
      var key = e.which || e.keyCode;
      if (key === 27) {
        this.disable();
      }
    });

    var latLng_1 = null;
    var latLng_2 = null;
    $(Lt.viewer.getContainer()).click(e => {
      Lt.viewer.getContainer().style.cursor = 'pointer';


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
            var length = parseFloat(document.getElementById('length').value);
            this.calculatePPM(latLng_1, latLng_2, length);
            this.disable();
          }
        });
      } else {
        var length = parseFloat(document.getElementById('length').value);
        this.calculatePPM(latLng_1, latLng_2, length);
        this.disable();
      }
    });
  };

  Calibration.prototype.disable = function() {
    $(document).off('keyup');
    // turn off the mouse clicks from previous function
    $(Lt.viewer.getContainer()).off('click');
    this.btn.state('inactive');
    this.active = false;
    Lt.mouseLine.disable();
    Lt.viewer.getContainer().style.cursor = 'default';
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

  // enable with ctrl-d
  L.DomEvent.on(window, 'keydown', (e) => {
     if (e.keyCode == 68 && !(e.getModifierState("Shift")) && e.getModifierState("Control") && window.name.includes('popout')) { // 68 refers to 'd'
       e.preventDefault();
       e.stopPropagation();
       Lt.disableTools();
       this.enable();
     }
  }, this);

  /**
   * Open a text container for user to input date
   * @function action
   */
  Dating.prototype.action = function(i) {
    if (Lt.data.points[i] != undefined) {
      var year;

      // Start points are "measurement" points when measuring backwards.
      // Need to provide way for users to "re date" them.
      if (!Lt.measurementOptions.forwardDirection && !Lt.data.points[i].year) {
        year = (Lt.measurementOptions.subAnnual && Lt.data.points[i + 1]) ? Lt.data.points[i + 1].year : Lt.data.points[i + 1] + 1;
      } else if (Lt.data.points[i].year) {
        year = Lt.data.points[i].year;
      } else {
        return;
      }

      // handlebars from templates.html
      let content = document.getElementById("dating-template").innerHTML;
      let template = Handlebars.compile(content);
      let html = template({ date_year: year });

      var popup = L.popup({closeButton: false})
          .setContent(html)
          .setLatLng(Lt.data.points[i].latLng)
          .openOn(Lt.viewer);

      document.getElementById('year_input').select();

      $(Lt.viewer.getContainer()).click(e => {
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

            var shift = new_year - year;

            Object.values(Lt.data.points).map((e, i) => {
              if (Lt.data.points[i] && Lt.data.points[i].year != undefined) {
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
    Lt.viewer.getContainer().style.cursor = 'pointer';
  };

  /**
   * Disable dating
   * @function disable
   */
  Dating.prototype.disable = function() {
    Lt.metaDataText.updateText(); // updates once user hits enter
    Lt.annotationAsset.reloadAssociatedYears();
    if (Lt.popoutPlots.win) {
      Lt.popoutPlots.sendData();
    }

    this.btn.state('inactive');
    $(Lt.viewer.getContainer()).off('click');
    $(document).off('keypress');
    this.active = false;
    Lt.viewer.getContainer().style.cursor = 'default';
  };
}

/**
 * \eate measurement points
 * @constructor
 * @param {Ltreering} Lt - Leaflet treering object
 */
function CreatePoint(Lt) {
  this.active = false;
  this.startPoint = true;
  this.btn = new Button(
    'linear_scale',
    'Create measurement points (Ctrl-m)',
    () => { Lt.disableTools(); this.enable() },
    () => { this.disable() }
  );

  // create measurement w. ctrl-m
  L.DomEvent.on(window, 'keydown', (e) => {
     if (e.keyCode == 77 && e.getModifierState("Control")) {
       e.preventDefault();
       e.stopPropagation();
       Lt.disableTools();
       this.enable();
     }
  }, this);

  // resume measurement w. ctrl-k
  L.DomEvent.on(window, 'keydown', (e) => {
     if (e.keyCode == 75 && e.getModifierState("Control")) {
       e.preventDefault();
       e.stopPropagation();
       Lt.disableTools();
       this.startPoint = false;
       this.active = true;
       this.enable();
       Lt.mouseLine.from(Lt.data.points[Lt.data.index - 1].latLng);
     }
  }, this);

  /**
   * Enable creating new points on click events
   * @function enable
   */
  CreatePoint.prototype.enable = function() {
    this.btn.state('active');

    if (Lt.data.points.length == 0 && Lt.measurementOptions.userSelectedPref == false) {
      this.disable();
      Lt.measurementOptions.enable();
      return;
    };

    Lt.mouseLine.enable();

    Lt.viewer.getContainer().style.cursor = 'pointer';

    $(document).keyup(e => {
      var key = e.which || e.keyCode;
      if (key === 27) {
        this.disable();
      }
    });

    $(Lt.viewer.getContainer()).click(e => {
      Lt.viewer.getContainer().style.cursor = 'pointer';

      var latLng = Lt.viewer.mouseEventToLatLng(e);

      Lt.undo.push();

      if (this.startPoint) {
        if (Lt.data.points.length <= 1) { // only pop up for first start point

          // handlebars from templates.html
          let content = document.getElementById("start-point-popup-template").innerHTML;

          var popup = L.popup({closeButton: false}).setContent(content)
              .setLatLng(latLng)
              .openOn(Lt.viewer);

              document.getElementById('year_input').select();

              $(document).keypress(e => {
                var key = e.which || e.keyCode;
                if (key === 13) {
                  if (Lt.measurementOptions.forwardDirection == false && Lt.measurementOptions.subAnnual == false) {
                    // must subtract one so newest measurment is consistent with measuring forward value
                    // issue only applies to meauring backwwards annually
                    Lt.data.year = parseInt(document.getElementById('year_input').value) - 1;
                  } else  {
                    Lt.data.year = parseInt(document.getElementById('year_input').value);
                  }
                  popup.remove(Lt.viewer);
                }
              });
        }

        Lt.data.newPoint(this.startPoint, latLng);
        this.startPoint = false;
      } else {
        Lt.data.newPoint(this.startPoint, latLng);
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
    // If statement to reduce number of reloads.
    if (this.active) Lt.visualAsset.reload();

    $(document).off('keyup');
    // turn off the mouse clicks from previous function
    $(Lt.viewer.getContainer()).off('click');
    this.btn.state('inactive');
    this.active = false;
    Lt.mouseLine.disable();
    Lt.viewer.getContainer().style.cursor = 'default';
    this.startPoint = true;
  };
}

/**
 * Add a zero growth measurement
 * @constructor
 * @param {Ltreering} Lt - Leaflet treering object
 */
function CreateZeroGrowth(Lt) {
  this.btn = new Button('exposure_zero', 'Add a year with 0 mm width while measuring\n(Locally absent and missing rings count too!)', () => {
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

      var yearsIncrease = Lt.measurementOptions.forwardDirection == true;
      var yearsDecrease = Lt.measurementOptions.forwardDirection == false;
      var previousPointEW = Lt.data.points[Lt.data.index - 1].earlywood == true;
      var previousPointLW = Lt.data.points[Lt.data.index - 1].earlywood == false;
      var subAnnualIncrement = Lt.measurementOptions.subAnnual == true;
      var annualIncrement = Lt.measurementOptions.subAnnual == false;

      // ensure point only inserted at end of year
      if (annualIncrement || previousPointLW) {
        var firstEWCheck = true;
        var secondEWCheck = false;
        var yearAdjustment = Lt.data.year;
        if (yearsDecrease) yearAdjustment--;
      } else {
        alert('Must be inserted at end of year.');
        return;
      };

      Lt.data.points[Lt.data.index] = {'start': false, 'skip': false, 'break': false,
        'year': Lt.data.year, 'earlywood': firstEWCheck, 'latLng': latLng};
      Lt.visualAsset.newLatLng(Lt.data.points, Lt.data.index, latLng);
      Lt.data.index++;
      if (subAnnualIncrement) {
        Lt.data.points[Lt.data.index] = {'start': false, 'skip': false, 'break': false,
          'year': yearAdjustment, 'earlywood': secondEWCheck, 'latLng': latLng};
        Lt.visualAsset.newLatLng(Lt.data.points, Lt.data.index, latLng);
        Lt.data.index++;
      };

      if (yearsIncrease) {
        Lt.data.year++;
      } else if (yearsDecrease){
        Lt.data.year--;
      };

      Lt.metaDataText.updateText(); // updates after point is inserted
      Lt.annotationAsset.reloadAssociatedYears();
      if (Lt.popoutPlots.win) {
        Lt.popoutPlots.sendData();
      }

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
    'Create a within-year break in measurement path\n(Avoid measuring physical specimen gaps & cracks!)',
    () => {
      Lt.disableTools();
      this.enable();
      Lt.mouseLine.from(Lt.data.points[Lt.data.index - 1].latLng);
    },
    () => { this.disable }
  );

  L.DomEvent.on(window, 'keydown', (e) => {
     if (e.keyCode == 66 && e.getModifierState("Control") && window.name.includes('popout')) { // 66 refers to 'b'
       e.preventDefault();
       e.stopPropagation();
       Lt.disableTools();
       this.enable();
       Lt.mouseLine.from(Lt.data.points[Lt.data.index - 1].latLng);
     };
  });

  /**
   * Enable adding a break point from the last point
   * @function enable
   */
  CreateBreak.prototype.enable = function() {
    this.btn.state('active');

    Lt.mouseLine.enable();

    Lt.viewer.getContainer().style.cursor = 'pointer';

    $(Lt.viewer.getContainer()).click(e => {
      Lt.viewer.getContainer().style.cursor = 'pointer';

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
    $(Lt.viewer.getContainer()).off('click');
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
    'Delete a measurement point',
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

    Lt.data.deletePoint(i);

    Lt.visualAsset.reload();
  };

  /**
   * Enable deleting points on click
   * @function enable
   */
  DeletePoint.prototype.enable = function() {
    this.btn.state('active');
    this.active = true;
    Lt.viewer.getContainer().style.cursor = 'pointer';
  };

  /**
   * Disable deleting points on click
   * @function disable
   */
  DeletePoint.prototype.disable = function() {
    $(Lt.viewer.getContainer()).off('click');
    this.btn.state('inactive');
    this.active = false;
    Lt.viewer.getContainer().style.cursor = 'default';
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
    'Delete all points between two selected points',
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
    Lt.viewer.getContainer().style.cursor = 'pointer';
    this.point = -1;
  };

  /**
   * Disable cutting
   * @function disable
   */
  Cut.prototype.disable = function() {
    $(Lt.viewer.getContainer()).off('click');
    this.btn.state('inactive');
    this.active = false;
    Lt.viewer.getContainer().style.cursor = 'default';
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
    'Insert a point between two other points (Ctrl-i)',
    () => { Lt.disableTools(); this.enable() },
    () => { this.disable() }
  );

  // enable w. ctrl-i
  L.DomEvent.on(window, 'keydown', (e) => {
     if (e.keyCode == 73 && !(e.getModifierState("Shift")) && e.getModifierState("Control") && window.name.includes('popout')) { // 73 refers to 'i'
       e.preventDefault();
       e.stopPropagation();
       Lt.disableTools();
       this.enable();
     }
  }, this);

  /**
   * Insert a point on click event
   * @function action
   */
  InsertPoint.prototype.action = function() {
    Lt.viewer.getContainer().style.cursor = 'pointer';

    $(Lt.viewer.getContainer()).click(e => {
      var latLng = Lt.viewer.mouseEventToLatLng(e);

      Lt.undo.push();

      var k = Lt.data.insertPoint(latLng);
      if (k != null) {
        Lt.visualAsset.newLatLng(Lt.data.points, k, latLng);
        Lt.visualAsset.reload();
      }

      //Uncommenting line below will disable tool after one use
      //Currently it will stay enabled until user manually disables tool
      //this.disable();
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
    $(document).keyup(e => {
          var key = e.which || e.keyCode;
          if (key === 27) { // 27 = esc
            this.disable();
          }
        });

    $(Lt.viewer.getContainer()).off('click');
    this.btn.state('inactive');
    this.active = false;
    Lt.viewer.getContainer().style.cursor = 'default';
  };
};

/**
 * Insert a new start point where a measurement point exists
 * @constructor
 * @param {Ltreering} Lt - Leaflet treering object
 */
function ConvertToStartPoint(Lt) {
  this.active = false;
  this.btn = new Button(
    'change_circle',
    'Change a measurement point to a start point',
    () => { Lt.disableTools(); this.enable() },
    () => { this.disable() }
  );

  ConvertToStartPoint.prototype.action = function (i) {
    var points = Lt.data.points;
    var previousYear = points[i].year || 0;

    // cannot convert a point that is already a start point
    // start points are camouflage when measuring backwards.
    if (points[i].start || (!Lt.measurementOptions.forwardDirection && points[i + 1] && points[i + 1].start)) {
      return;
    }

    Lt.undo.push();

    // convert to start point by changing properties
    points[i].start = true;
    delete points[i].year;
    delete points[i].earlywood;

    if (i - 1 == 0) { // if previous point is first start point
      Lt.deletePoint.action(i - 1);
    };

    // re-assign years to following points
    var previousPoints = points.slice(0, i);
    var followingPoints = points.slice(i);

    if (Lt.measurementOptions.forwardDirection) { // if measuring forward in time
      var yearChange = 1;
    } else { // if measuring backward in time
      var yearChange = -1;
    };

    followingPoints.map((c) => {
      if (c && !c.start && !c.break) {
        if (Lt.measurementOptions.subAnnual) { // flip earlywood & latewood
          c.earlywood = !c.earlywood;
        };

        c.year = previousYear;
        if (!Lt.measurementOptions.subAnnual ||
           (Lt.measurementOptions.forwardDirection && !c.earlywood) ||
           (!Lt.measurementOptions.forwardDirection && c.earlywood)) { // only change year value if latewood or annual measurements
          previousYear += yearChange;
        };
      };
    });

    Lt.data.year = Lt.measurementOptions.forwardDirection ? points[points.length-1].year + 1: points[points.length-1].year - 1;
    Lt.visualAsset.reload();
    Lt.metaDataText.updateText();
    Lt.annotationAsset.reloadAssociatedYears();
    if (Lt.popoutPlots.win) {
      Lt.popoutPlots.sendData();
    }
  };

  ConvertToStartPoint.prototype.enable = function () {
    Lt.viewer.getContainer().style.cursor = 'pointer';
    this.btn.state('active');
    this.active = true;
  };

  ConvertToStartPoint.prototype.disable = function () {
    $(document).keyup(e => {
          var key = e.which || e.keyCode;
          if (key === 27) { // 27 = esc
            this.disable();
          }
        });

    $(Lt.viewer.getContainer()).off('click');
    this.btn.state('inactive');
    this.active = false;
    Lt.viewer.getContainer().style.cursor = 'default';
  };
};

/**
 * Insert a zero growth measurement in the middle of a chronology
 * @constructor
 * @param {Ltrering} Lt - Leaflet treering object
 */
function InsertZeroGrowth(Lt) {
  this.active = false;
  this.btn = new Button(
    'exposure_zero',
    'Insert a year with 0 mm width between two other points ',
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

    var k = Lt.data.insertZeroGrowth(i, latLng);
    if (k !== null) {
      if (Lt.measurementOptions.subAnnual) Lt.visualAsset.newLatLng(Lt.data.points, k-1, latLng);
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
    Lt.viewer.getContainer().style.cursor = 'pointer';
  };

  /**
   * Disable adding a zero growth year
   * @function disable
   */
  InsertZeroGrowth.prototype.disable = function() {
    $(Lt.viewer.getContainer()).off('click');
    this.btn.state('inactive');
    Lt.viewer.getContainer().style.cursor = 'default';
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

    $(Lt.viewer.getContainer()).click(e => {
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

        // TODO: use handlebars once fixed

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

            $(Lt.viewer.getContainer()).off('click');

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
    Lt.viewer.getContainer().style.cursor = 'pointer';
  };

  /**
   * Disable inserting a break point
   * @function disable
   */
  InsertBreak.prototype.disable = function() {
    $(Lt.viewer.getContainer()).off('click');
    this.btn.state('inactive');
    this.active = false;
    Lt.viewer.getContainer().style.cursor = 'default';
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
    'View & download measurement data',
    () => { Lt.disableTools(); this.enable() },
    () => { this.disable() }
  );

  // handlebars from templates.html
  let content = document.getElementById("view-data-default-template").innerHTML;

  this.dialog = L.control.dialog({'size': [200, 235], 'anchor': [50, 0], 'initOpen': false})
    .setContent(content)

    .addTo(Lt.viewer);

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

    if (Lt.measurementOptions.forwardDirection) { // years ascend in value
      var pts = Lt.data.points;
    } else { // otherwise years descend in value
      var pts = Lt.helper.reverseData();
    }

    if (Lt.data.points != undefined && Lt.data.points[1] != undefined) {

      var sum_points;
      var sum_string = '';
      var last_latLng;
      var break_length;
      var length_string;

      if (Lt.measurementOptions.subAnnual) {

        var sum_string = '';
        var ew_string = '';
        var lw_string = '';

        y = pts[1].year;
        var sum_points = pts.filter(e => {
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
              Math.round(Lt.helper.trueDistance(last_latLng, e.latLng) * 1000);
              break_point = true;
          } else {
            if (e.year % 10 == 0) {
              if(sum_string.length > 0) {
                sum_string = sum_string.concat('\n');
              }
              sum_string = sum_string.concat(
                  toEightCharString(Lt.meta.assetName) +
                  toFourCharString(e.year));
            }
            while (e.year > y) {
              sum_string = sum_string.concat('    -1');
              y++;
              if (y % 10 == 0) {
                sum_string = sum_string.concat('\n' +
                    toFourCharString(e.year));
              }
            }

            if (!last_latLng) {
              last_latLng = e.latLng;
            };

            var length = Math.round(Lt.helper.trueDistance(last_latLng, e.latLng) * 1000);
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

        // if we ended at the end of a decade, we need to add a new line
        if (y % 10 == 0) {
          sum_string = sum_string.concat('\n' +
          toEightCharString(Lt.meta.assetName) +
          toFourCharString(y));
        }
        sum_string = sum_string.concat(' -9999');

        y = pts[1].year;

        if (pts[1].year % 10 > 0) {
          ew_string = ew_string.concat(
              toEightCharString(Lt.meta.assetName) +
              toFourCharString(pts[1].year));
          lw_string = lw_string.concat(
              toEightCharString(Lt.meta.assetName) +
              toFourCharString(pts[1].year));
        }

        break_point = false;
        pts.map((e, i, a) => {
          if (e.start) {
            last_latLng = e.latLng;
          } else if (e.break) {
            break_length =
              Math.round(Lt.helper.trueDistance(last_latLng, e.latLng) * 1000);
            break_point = true;
          } else {
            if (e.year % 10 == 0) {
              if (e.earlywood) {
                if (ew_string.length >0) {
                  ew_string = ew_string.concat('\n');
                }
                ew_string = ew_string.concat(
                    toEightCharString(Lt.meta.assetName) +
                    toFourCharString(e.year));
              } else {
                if (lw_string.length >0) {
                  lw_string = lw_string.concat('\n');
                }
                lw_string = lw_string.concat(
                    toEightCharString(Lt.meta.assetName) +
                    toFourCharString(e.year));
              }
            }
            while (e.year > y) {
              ew_string = ew_string.concat('    -1');
              lw_string = lw_string.concat('    -1');
              y++;
              if (y % 10 == 0) {
                ew_string = ew_string.concat('\n' +
                    toEightCharString(Lt.meta.assetName) +
                    toFourCharString(e.year));
                lw_string = lw_string.concat('\n' +
                    toEightCharString(Lt.meta.assetName) +
                    toFourCharString(e.year));
              }
            }

            length = Math.round(Lt.helper.trueDistance(last_latLng, e.latLng) * 1000);
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

        if (y % 10 == 0) {
          ew_string = ew_string.concat('\n' +
            toEightCharString(Lt.meta.assetName) +
            toFourCharString(y));
          lw_string = lw_string.concat('\n' +
            toEightCharString(Lt.meta.assetName) +
            toFourCharString(y));
        }
        ew_string = ew_string.concat(' -9999');
        lw_string = lw_string.concat(' -9999');

        console.log(sum_string);
        console.log(ew_string);
        console.log(lw_string);

        var zip = new JSZip();
        zip.file((Lt.meta.assetName + '_TW_rwl.txt'), sum_string);
        zip.file((Lt.meta.assetName + '_LW_rwl.txt'), lw_string);
        zip.file((Lt.meta.assetName + '_EW_rwl.txt'), ew_string);

      } else {

        var y = pts[1].year;
        sum_points = pts;

        if (sum_points[1].year % 10 > 0) {
          sum_string = sum_string.concat(
              toEightCharString(Lt.meta.assetName) +
              toFourCharString(sum_points[1].year));
        }
        sum_points.map((e, i, a) => {
          if(e.start) {
              last_latLng = e.latLng;
            }
            else if (e.break) {
              break_length =
                Math.round(Lt.helper.trueDistance(last_latLng, e.latLng) * 1000);
              break_point = true;
            } else {
            if (e.year % 10 == 0) {
              if(sum_string.length > 0) {
                sum_string = sum_string.concat('\n');
              }
              sum_string = sum_string.concat(
                  toEightCharString(Lt.meta.assetName) +
                  toFourCharString(e.year));
            }
            while (e.year > y) {
              sum_string = sum_string.concat('    -1');
              y++;
              if (y % 10 == 0) {
                sum_string = sum_string.concat('\n' +
                    toFourCharString(e.year));
              }
            }

            length = Math.round(Lt.helper.trueDistance(last_latLng, e.latLng) * 1000);
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

        if (y % 10 == 0) {
          sum_string = sum_string.concat('\n' +
            toEightCharString(Lt.meta.assetName) +
            toFourCharString(y));
        }
        sum_string = sum_string.concat(' -9999');

        var zip = new JSZip();
        zip.file((Lt.meta.assetName + '_TW_rwl.txt'), sum_string);
      }

      zip.generateAsync({type: 'blob'})
          .then((blob) => {
            saveAs(blob, (Lt.meta.assetName + '_rwl.zip'));
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

    var stringSetup; // buttons & table headers
    var stringContent = ''; // years and lengths

    //closes data view if mouse clicks anywhere outside the data viewer box
    $(Lt.viewer.getContainer()).click(e => {
      this.disable();
    });

    if (Lt.measurementOptions.forwardDirection) { // years ascend in value
      var pts = Lt.data.points;
    } else { // otherwise years descend in value
      var pts = Lt.helper.reverseData();
    };

    if (pts[0] != undefined) {
      var y = pts[1].year;

      // handlebars from templates.html
      let content_A = document.getElementById("string-setup-data-template").innerHTML;

      stringSetup = content_A;

      var break_point = false;
      var last_latLng;
      var break_length;
      var break_point;
      var length;
      var copyDataString = Lt.measurementOptions.subAnnual? "Year\t   "+Lt.meta.assetName+"_ew\t"+Lt.meta.assetName+"_lw\t"+Lt.meta.assetName+"_tw\n": "Year\t"+Lt.meta.assetName+"_tw\n";
      var EWTabDataString = "Year\t" + Lt.meta.assetName + "_EW\n";
      var LWTabDataString ="Year\t" + Lt.meta.assetName + "_LW\n";
      var TWTabDataString = "Year\t" + Lt.meta.assetName + "_TW\n";
      var EWoodcsvDataString = "Year," + Lt.meta.assetName + "_EW\n";
      var LWoodcsvDataString ="Year," + Lt.meta.assetName + "_LW\n";
      var TWoodcsvDataString = 'Year,' + Lt.meta.assetName + "_TW\n";
      var lengthAsAString;
      var  totalWidthString = String(totalWidth);
      var totalWidth = 0;
      var wood;

      Lt.data.clean();
      pts.map((e, i, a) => {
        wood = Lt.measurementOptions.subAnnual? (e.earlywood? "E": "L") : ""
        if (e.start) {
          last_latLng = e.latLng;
        } else if (e.break) {
          break_length =
            Math.round(Lt.helper.trueDistance(last_latLng, e.latLng) * 1000) / 1000;
          break_point = true;
        } else {
          while (e.year > y) {
            // handlebars from templates.html
            let content_B = document.getElementById("concat-string-content-A-template").innerHTML;
            let template_B = Handlebars.compile(content_B);
            let html_B = template_B( {y: y} )

            stringContent = stringContent.concat(html_B);
            y++;
          }
          length = Math.round(Lt.helper.trueDistance(last_latLng, e.latLng) * 1000) / 1000;
          if (break_point) {
            length += break_length;
            length = Math.round(length * 1000) / 1000;
            break_point = false;
          }
          if (length == 9.999) {
            length = 9.998;
          }

          //Format length number into a string with trailing zeros
          lengthAsAString = String(length);
          lengthAsAString = lengthAsAString.padEnd(5,'0');

          if(lengthAsAString.includes('.999'))
          {
              lengthAsAString = lengthAsAString.substring(0,lengthAsAString.length-1);
              lengthAsAString+='8';

          }
          //assign color to data row
          var row_color_html = Lt.helper.assignRowColor(e,y,Lt,lengthAsAString)
          stringContent = stringContent.concat(row_color_html);
          y++;

          last_latLng = e.latLng;

          //Set up CSV files to download later
          //For subannual measurements
          if(Lt.measurementOptions.subAnnual)
          {
          if(wood=='E')
          {
            EWTabDataString += e.year + "\t" + lengthAsAString+ "\n";
            copyDataString += e.year + "\t   "+ lengthAsAString +"   \t";
            EWoodcsvDataString += e.year+","+lengthAsAString+"\n";
            totalWidth+=length;
          }
          else
          {
            LWoodcsvDataString += e.year+","+lengthAsAString+"\n";
            //adding two parts of the year together
            totalWidth+=length;
            totalWidth=Math.round(totalWidth * 1000) / 1000;
            totalWidthString = String(totalWidth);
            totalWidthString = totalWidthString.padEnd(5,'0');
            if(totalWidthString.includes('.999'))
          {
              totalWidthString = totalWidthString.substring(0,totalWidthString.length-1);
              totalWidthString+='8';
          }
            TWoodcsvDataString += e.year+","+totalWidthString+"\n";
            LWTabDataString += e.year + "\t" + lengthAsAString+ "\n";
            TWTabDataString += e.year + "\t" + totalWidthString+ "\n";
            copyDataString += lengthAsAString +"   \t"+totalWidthString +"\n";
            //set to zero only after latewood has been added and totalWidth is in csv
            totalWidth = 0;
          }
        }
        //For annual measurements
        else{
          TWoodcsvDataString+= e.year+","+lengthAsAString+"\n";
           //Copies data to a string that can be copied to the clipboard
           TWTabDataString += e.year + "\t" + lengthAsAString+ "\n";
          copyDataString += e.year + "\t"+ lengthAsAString +"\n";
        }
        }
      });
      this.dialog.setContent(stringSetup + stringContent + '</table><div>');
    } else {
      // handlebars from templates.html
      let content_D = document.getElementById("string-setup-no-data-template").innerHTML;

      stringSetup = content_D;
      this.dialog.setContent(stringSetup);
    }
    this.dialog.lock();
    this.dialog.open();

    $('#download-ltrr-button').click(() => this.download());
    $('#copy-data-button').click(()=> copyToClipboard(copyDataString));
    $('#download-csv-button').click(() => {
     if(Lt.measurementOptions.subAnnual)
     {
       downloadCSVFiles(Lt, TWoodcsvDataString,EWoodcsvDataString, LWoodcsvDataString);
     }
     else{
      downloadCSVFiles(Lt, TWoodcsvDataString);
     }
    }
    );
    $('#download-tab-button').click(() => {
          if(Lt.measurementOptions.subAnnual)
          {
            downloadTabFiles(Lt, TWTabDataString,EWTabDataString, LWTabDataString);
          }
          else{
           downloadTabFiles(Lt, TWTabDataString);
          }
         }
       );
    $('#delete-button').click(() => {
      // handlebars from templates.html
      let content_E = document.getElementById("data-view-delete-template").innerHTML;

      this.dialog.setContent(content_E);

      $('#confirm-delete').click(() => {
        Lt.undo.push();

        Lt.data.points = [];
        Lt.data.year = 0;
        Lt.data.earlywood = true;
        Lt.data.index = 0;

        Lt.visualAsset.reload();
        Lt.metaDataText.updateText();

        this.disable();
      });
      $('#cancel-delete').click(() => {
        this.disable();
        this.enable();
      });
    });
  },
  /**
   * copy text to clipboard
   * @function enable
   */
  copyToClipboard = function(allData){
    const el = document.createElement('textarea');
    el.value = allData;
    document.body.appendChild(el);
    el.select();
    document.execCommand('copy');
    document.body.removeChild(el);
  }

  /**
   * close the data viewer box
   * @function disable
   */
  ViewData.prototype.disable = function() {
    $(Lt.viewer.getContainer()).off('click');
    this.btn.state('inactive');
    $('#confirm-delete').off('click');
    $('#cancel-delete').off('click');
    $('#download-ltrr-button').off('click');
    $('#download-csv-button').off('click');
    $('#download-tab-button').off('click');
    $('#copy-data-button').off('click');
    $('#delete-button').off('click');
    $('#copy-data-button').off('click');
    this.dialog.close();
  };
};

/**
 * Change color properties of image
 * @constructor
 * @param {Ltreering} Lt - Leaflet treering object
 */
function ImageAdjustment(Lt) {
  this.btn = new Button(
    'brightness_6',
    'Adjust image appearance settings',
    () => { Lt.disableTools(); this.enable() },
    () => { this.disable() }
  );

  // handlebars from templates.html
  let content = document.getElementById("image-adjustment-template").innerHTML;

  this.dialog = L.control.dialog({
    'size': [340, 280],
    'anchor': [50, 5],
    'initOpen': false
  }).setContent(content).addTo(Lt.viewer);

  /**
   * Update the image filter to reflect slider values
   * @function updateFilters
   */
  ImageAdjustment.prototype.updateFilters = function() {
    var brightnessSlider = document.getElementById("brightness-slider");
    var contrastSlider = document.getElementById("contrast-slider");
    var saturationSlider = document.getElementById("saturation-slider");
    var hueSlider = document.getElementById("hue-slider");
    var invert = $("#invert-checkbox").prop('checked')?1:0;
    var sharpnessSlider = document.getElementById("sharpness-slider").value;
    var embossSlider = document.getElementById("emboss-slider").value;
    var edgeDetect = document.getElementById("edgeDetect-slider").value;
    var unsharpnessSlider = document.getElementById("unsharpness-slider").value;
    document.getElementsByClassName("leaflet-pane")[0].style.filter =
      "contrast(" + contrastSlider.value + "%) " +
      "brightness(" + brightnessSlider.value + "%) " +
      "saturate(" + saturationSlider.value + "%) " +
      "invert(" + invert + ")" +
      "hue-rotate(" + hueSlider.value + "deg)";
    Lt.baseLayer['GL Layer'].setKernelsAndStrength([
      {
			"name":"emboss",
			"strength": embossSlider
      },
      {
        "name":"edgeDetect3",
        "strength": edgeDetect
      },
      {
        "name":"sharpness",
        "strength": sharpnessSlider
      },
      {
        "name":"unsharpen",
        "strength": unsharpnessSlider
      }
    ]);
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
    var sharpnessSlider = document.getElementById("sharpness-slider");
    var embossSlider = document.getElementById("emboss-slider");
    var edgeDetect = document.getElementById("edgeDetect-slider");
    var unsharpnessSlider = document.getElementById("unsharpness-slider");
    //Close view if user clicks anywhere outside of slider window
    $(Lt.viewer.getContainer()).click(e => {
      this.disable();
    });

    this.btn.state('active');
    $(".imageSlider").change(() => {
      this.updateFilters();
    });
    $("#invert-checkbox").change(() => {
      this.updateFilters();
    });
    $("#reset-button").click(() => {
      $(brightnessSlider).val(100);
      $(contrastSlider).val(100);
      $(saturationSlider).val(100);
      $(hueSlider).val(0);
      $(sharpnessSlider).val(0);
      $(embossSlider).val(0);
      $(edgeDetect).val(0);
      $(unsharpnessSlider).val(0);
      this.updateFilters();
    });
    $("#invert-button").click(() => {
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
* Change measurement options (set subAnnual, previously hasLatewood, and direction)
* @constructor
* @param {Ltreeing} Lt - Leaflet treering object
*/
function MeasurementOptions(Lt) {
  this.userSelectedPref = false;
  this.btn = new Button(
    'settings',
    'Measurement preferences',
    () => { Lt.disableTools(); this.enable() },
    () => { this.disable() }
  );

  /**
  * Data from Lt.preferences
  * @function preferencesInfo
  */
  MeasurementOptions.prototype.preferencesInfo = function () {
    if (Lt.preferences.forwardDirection == false) { // direction object
      this.forwardDirection = false;
    } else {
      this.forwardDirection = true;
    }

    var pts = Lt.data.points;
    let ewFalse = pts.filter(pt => pt && pt.earlywood == false);
    if (ewFalse.length > 0) {
      this.hasLatewood = true;
    } else {
      this.hasLatewood = false;
    };

    if (Lt.preferences.subAnnual == undefined) {
      this.subAnnual = this.hasLatewood;
    } else {
      this.subAnnual = Lt.preferences.subAnnual;
    };
  };

  /**
  * Creates dialog box with preferences
  * @function displayDialog
  */
MeasurementOptions.prototype.displayDialog = function () {
  // handlebars from templates.html
  let content = document.getElementById("measurement-options-dialog-template").innerHTML;

  return L.control.dialog({
     'size': [510, 320],
     'anchor': [50, 5],
     'initOpen': false
   }).setContent(content).addTo(Lt.viewer);
  };

  /**
  * Based on initial data, selects buttons/dialog text
  * @function selectedBtns
  */
  MeasurementOptions.prototype.selectedBtns = function () {
    if (this.forwardDirection == true) {
      document.getElementById("forward_radio").checked = true;
    } else {
      document.getElementById("backward_radio").checked = true;
    };

    if (this.subAnnual == true) {
      document.getElementById("subannual_radio").checked = true;
    } else {
      document.getElementById("annual_radio").checked = true;
    };

  };

  /**
  * Changes direction & increment object to be saved
  * @function prefBtnListener
  */
  MeasurementOptions.prototype.prefBtnListener = function () {
    document.getElementById("forward_radio").addEventListener('change', (event) => {
      if (event.target.checked == true) {
        this.forwardDirection = true;
        Lt.data.earlywood = true;
        Lt.metaDataText.updateText(); // update text once selected
      };
    });

    document.getElementById("backward_radio").addEventListener('change', (event) => {
      if (event.target.checked == true) {
        this.forwardDirection = false;
        Lt.data.earlywood = true;
        Lt.metaDataText.updateText();
      };
    });

    document.getElementById("annual_radio").addEventListener('change', (event) => {
      if (event.target.checked == true) {
        this.subAnnual = false;
        Lt.metaDataText.updateText();
      };
    });

    document.getElementById("subannual_radio").addEventListener('change', (event) => {
      if (event.target.checked == true) {
        this.subAnnual = true;
        Lt.metaDataText.updateText();
      };
    });

  };

  /**
  * Open measurement options dialog
  * @function enable
  */
  MeasurementOptions.prototype.enable = function() {
    if (!this.dialog) {
      this.dialog = this.displayDialog();
    };

    this.selectedBtns();

    var forwardRadio = document.getElementById("forward_radio");
    var backwardRadio = document.getElementById("backward_radio");
    var annualRadio = document.getElementById("annual_radio");
    var subAnnualRadio = document.getElementById("subannual_radio");
    if ((Lt.data.points.length === 0 || !Lt.data.points[0]) && window.name.includes('popout')) {
      forwardRadio.disabled = false;
      backwardRadio.disabled = false;
      annualRadio.disabled = false;
      subAnnualRadio.disabled = false;
      this.prefBtnListener();
    } else { // lets users see preferences without being able to change them mid-measurement
      forwardRadio.disabled = true;
      backwardRadio.disabled = true;
      annualRadio.disabled = true;
      subAnnualRadio.disabled = true;
    };

    this.dialog.lock();
    this.dialog.open();
    this.btn.state('active');

    $("#confirm-button").click(() => {
      if (this.userSelectedPref == false) {
        this.userSelectedPref = true;
        Lt.createPoint.enable();
      };
      this.disable();
    });
  };

  /**
  * Close measurement options dialog
  * @function disable
  */
  MeasurementOptions.prototype.disable = function() {
    if (this.dialog) {
      this.dialog.unlock();
      this.dialog.close();
    };

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
    'Download .json file of current measurements, annotations, etc.',
    () => { this.action() }
  );

  /**
   * Save a local copy of the measurement data
   * @function action
   */
  SaveLocal.prototype.action = function() {
    Lt.data.clean();
    var dataJSON = {
      'SaveDate': Lt.data.saveDate,
      'year': Lt.data.year,
      'forwardDirection': Lt.measurementOptions.forwardDirection,
      'subAnnual': Lt.measurementOptions.subAnnual,
      'earlywood': Lt.data.earlywood,
      'index': Lt.data.index,
      'points': Lt.data.points,
      'attributesObjectArray': Lt.annotationAsset.attributesObjectArray,
      'annotations': Lt.aData.annotations,
      'ptWidths': Lt.helper.findDistances(),
    };

    // don't serialize our default value
    if(Lt.meta.ppm != 468 || Lt.meta.ppmCalibration) {
      dataJSON.ppm = Lt.meta.ppm;
    }

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
    'Save the current measurements, annotations, etc.\nto the cloud-hosted .json file (Ctrl-s)',
    () => { this.action() }
  );

  // save w. ctrl-s
  L.DomEvent.on(window, 'keydown', (e) => {
     if (e.keyCode == 83 && e.getModifierState("Control") && window.name.includes('popout')) { // 83 refers to 's'
       e.preventDefault();
       e.stopPropagation();
       this.action();
     };
  });

  this.date = new Date(),

  /**
   * Update the save date & meta data
   * @function updateDate
   */
  SaveCloud.prototype.updateDate = function() {
    this.date = new Date();
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

      this.saveText =
          ' &nbsp;|&nbsp; Saved to cloud ' + date.year + '/' + date.month + '/' + date.day + ' ' + date.hour + ':' + minute_string + am_pm;
    } else if (date.day != undefined) {
      this.saveText =
          ' &nbsp;|&nbsp; Saved to cloud ' + date.year + '/' + date.month + '/' + date.day;
    } else {
      this.saveText =
          ' &nbsp;|&nbsp; No data saved to cloud';
    };

    Lt.data.saveDate;
  };

  /**
   * Save the measurement data to the cloud
   * @function action
   */
  SaveCloud.prototype.action = function() {
    if (Lt.meta.savePermission && Lt.meta.saveURL != "") {
      Lt.data.clean();
      this.updateDate();
      var dataJSON = {
        'SaveDate': Lt.data.saveDate,
        'year': Lt.data.year,
        'forwardDirection': Lt.measurementOptions.forwardDirection,
        'subAnnual': Lt.measurementOptions.subAnnual,
        'earlywood': Lt.data.earlywood,
        'index': Lt.data.index,
        'points': Lt.data.points,
        'attributesObjectArray': Lt.annotationAsset.attributesObjectArray,
        'annotations': Lt.aData.annotations,
      };

      // don't serialize our default value
      if (Lt.meta.ppm != 468 || Lt.meta.ppmCalibration) {
        dataJSON.ppm = Lt.meta.ppm;
      }
      $.post(Lt.meta.saveURL, {sidecarContent: JSON.stringify(dataJSON)})
          .done((msg) => {
            this.displayDate();
            Lt.metaDataText.updateText();
          })
          .fail((xhr, status, error) => {
            alert('Error: failed to save changes');
          });
    } else {
      alert('Authentication Error: save to cloud permission not granted');
    };
  };
};

/**
 * Display assets meta data as text
 * @constructor
 * @param {Ltreering} Lt - Leaflet treering object
 */
function MetaDataText (Lt) {
  this.speciesID = Lt.meta.assetName; // empty string defaults to N/A
  Lt.viewer.on('zoomend', () => Lt.metaDataText.updateText());

  MetaDataText.prototype.initialize = function () {
    if (window.name.includes('popout')) {
      var metaDataTopDiv = document.createElement('div');

      // handlebars from templates.html
      let content_A = document.getElementById("meta-data-top-div-template").innerHTML;

      metaDataTopDiv.innerHTML = content_A;
      document.getElementsByClassName('leaflet-bottom leaflet-left')[0].appendChild(metaDataTopDiv);
    };

    // handlebars from templates.html
    let content_B = document.getElementById("meta-data-bottom-div-template").innerHTML;

    var metaDataBottomDiv = document.createElement('div');
    metaDataBottomDiv.innerHTML = content_B;
    document.getElementsByClassName('leaflet-bottom leaflet-left')[0].appendChild(metaDataBottomDiv);
  };

  MetaDataText.prototype.updateText = function () {
      var points = Lt.data.points;

      var i;
      for (i = 0; i < points.length; i++) { // find 1st point w/ year value
        if (points[i] && (points[i].year || points[i].year == 0)) {
          var firstYear = points[i].year;
          break;
        };
      };

      for (i = points.length - 1; i >= 0; i--) { //  find last point w/ year value
        if (points[i] && (points[i].year || points[i].year == 0)) {
          var lastYear = points[i].year;
          break;
        };
      };

      var startYear;
      var endYear;
      if (firstYear <= lastYear) { // for measuring forward in time, smallest year value first in points array
        startYear = firstYear;
        endYear = lastYear;
      } else if (firstYear > lastYear) { // for measuring backward in time, largest year value first in points array
        startYear = lastYear + 1; // last point considered a start point when measuring backwards
        if (Lt.measurementOptions.subAnnual == false) {
          // add 1 to keep points consistent with measuring forwards
          // only applies to measuring bakcwards annually
          endYear = firstYear + 1;
        } else {
          endYear = firstYear;
        }
      };

      var years = '';
      if ((startYear || startYear == 0) && (endYear || endYear == 0)) {
        years = ' &nbsp;|&nbsp; ' + String(startYear) + ' — ' + String(endYear);
      };

      var branding = ' &nbsp;|&nbsp; DendroElevator developed at the <a href="http://z.umn.edu/treerings" target="_blank"> University of Minnesota </a>';

      var saveText = '';
      if (Lt.meta.savePermission) {
        saveText = Lt.saveCloud.saveText;
      };

      if (window.name.includes('popout')) {
        if (Lt.measurementOptions.subAnnual) { // if 2 increments per year
          var increment = 'sub-annual increments';
        } else { // otherwise 1 increment per year
          var increment  = 'annual increments';
        };
``
        if (Lt.measurementOptions.forwardDirection) { // if years counting up
          var direction = 'Measuring forward, ';
        } else { // otherwise years counting down
          var direction = 'Measuring backward, '
        };

        var zoomPercentage = 100 * ((Lt.viewer.getZoom() - Lt.viewer.getMinZoom()) / (Lt.viewer.getMaxZoom() - Lt.viewer.getMinZoom()));
        var zoom = ' &nbsp;|&nbsp; Zoom ' + (zoomPercentage).toFixed(1) + '%';

        document.getElementById("meta-data-top-text").innerHTML = direction + increment + zoom + saveText;
      };

      document.getElementById("meta-data-bottom-text").innerHTML = this.speciesID + years + branding;
  };
};

/**
 * Load a local copy of the measurement data
 * @constructor
 * @param {Ltreering} Lt - Leaflet treering object
 */
function LoadLocal(Lt) {
  this.btn = new Button(
    'file_upload',
    'Upload .json file with measurements, annotations, etc.',
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

      Lt.preferences = {
        'forwardDirection': newDataJSON.forwardDirection,
        'subAnnual': newDataJSON.subAnnual,
      };

      Lt.data = new MeasurementData(newDataJSON, Lt);
      Lt.aData = new AnnotationData(newDataJSON.annotations);

      // if the JSON has PPM data, use that instead of loaded data.
      if(newDataJSON.ppm) {
        Lt.meta.ppm = newDataJSON.ppm;
      }

      Lt.loadData();
    };

    fr.readAsText(files.item(0));
  };

}

function Panhandler(La) {
  var map = La.viewer;
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
/**
   * copy text to clipboard
   * @function enable
   */
  function copyToClipboard(allData){
    console.log('copying...', allData);
    const el = document.createElement('textarea');
    el.value = allData;
    document.body.appendChild(el);
    el.select();
    document.execCommand('copy');
    document.body.removeChild(el);
  }

  /**
   * Download CSV ZIP file
   * @function
   */
  function downloadCSVFiles(Lt,TWoodcsvDataString,EWoodcsvDataString,LWoodcsvDataString)
  {
    var zip = new JSZip();
    if(Lt.measurementOptions.subAnnual)
    {
    zip.file((Lt.meta.assetName + '_LW_csv.csv'), LWoodcsvDataString);
    zip.file((Lt.meta.assetName + '_EW_csv.csv'), EWoodcsvDataString);
    }
    zip.file((Lt.meta.assetName + '_TW_csv.csv'), TWoodcsvDataString)
    zip.generateAsync({type: 'blob'})
          .then((blob) => {
            saveAs(blob, (Lt.meta.assetName + '_csv.zip'));
          });
    }
    function downloadTabFiles(Lt,TWTabDataString,EWTabDataString,LWTabDataString)
  {
    var zip = new JSZip();
    if(Lt.measurementOptions.subAnnual)
    {
    zip.file((Lt.meta.assetName + '_LW_tab.txt'), LWTabDataString);
    zip.file((Lt.meta.assetName + '_EW_tab.txt'), EWTabDataString);
    }
    zip.file((Lt.meta.assetName + '_TW_tab.txt'), TWTabDataString)
    zip.generateAsync({type: 'blob'})
          .then((blob) => {
            saveAs(blob, (Lt.meta.assetName + '_tab.zip'));
          });
        }

/**
 * Opens dialog box with all keyboard shortcuts
 * @function
 */
function KeyboardShortCutDialog (Lt) {
  this.btn = new Button (
    'keyboard',
    'Display keyboard shortcuts',
    () => { this.action() },
  );

  KeyboardShortCutDialog.prototype.action = function () {
    if (this.dialog) {
      this.dialog.close();
    };

    let anchor = this.anchor || [1, 442];

    this.dialog = L.control.dialog ({
      'size': [310, 300],
      'anchor': anchor,
      'initOpen': true
    }).addTo(Lt.viewer);

    // remember annotation location each times its moved
    $(this.dialog._map).on('dialog:moveend', () => { this.anchor = this.dialog.options.anchor } );

    const shortcutGuide = [
      {
       'key': 'Ctrl-l',
       'use': 'Toggle magnification loupe on/off',
      },
      {
       'key': 'Ctrl-m',
       'use': 'Create new measurement path',
      },
      {
       'key': 'Ctrl-k',
       'use': 'Resume last measurement path',
      },
      {
       'key': 'Ctrl-i',
       'use': 'Insert measurement point',
      },
      {
        'key': 'Ctrl-d',
        'use': 'Edit measurement point dating',
      },
      {
       'key': 'Ctrl-a',
       'use': 'Create new annotation',
      },
      {
       'key': 'Ctrl-b',
       'use': 'Create within-year break',
      },
      {
       'key': 'Ctrl-s',
       'use': 'Save changes to cloud (if permitted)',
      },
      {
       'key': 'Shift',
       'use': 'Disable cursor panning near edge',
      },
      {
       'key': 'Arrows',
       'use': 'Pan up/down/left/right',
      },
      {
       'key': 'Shift-arrows',
       'use': 'Pan slowly up/down/left/right',
      },
      {
       'key': 'Right click or esc',
       'use': 'Disable current tool',
      },
    ];

    // reset dialog box
    if (document.getElementById('keyboardShortcutDiv') != null) {
      document.getElementById('keyboardShortcutDiv').remove();
      this.dialog.setContent('');
    };

    // handlebars from template.html
    let content = document.getElementById("empty-div-template").innerHTML;
    let template = Handlebars.compile(content);
    let html = template( {div_name: "keyboardShortcutDiv"} );

    this.dialog.setContent(html);

    let mainDiv = document.getElementById('keyboardShortcutDiv');

    var title = document.createElement('h4');
    title.innerHTML = 'Keyboard Shortcuts';
    mainDiv.appendChild(title);

    for (shortcut of shortcutGuide) {
      let subDiv = document.createElement('div');

      let key = document.createElement('p');
      key.innerHTML = shortcut.key;
      subDiv.appendChild(key);

      let description = document.createElement('span');
      description.innerHTML = shortcut.use;
      subDiv.appendChild(description);

      mainDiv.appendChild(subDiv);
    };

    this.dialog.hideResize();
    this.dialog.open();

  };
};


/**
 * Hosts all global helper functions
 * @function
 */
function Helper(Lt) {

  /**
   * Reverses points data structure so points ascend in time.
   * @function trueDistance
   * @param {first point.latLng} p1
   * @param {second point.latLng} p2
   */
  Helper.prototype.trueDistance = function(p1, p2) {
    var lastPoint = Lt.viewer.project(p1, Lt.getMaxNativeZoom());
    var newPoint = Lt.viewer.project(p2, Lt.getMaxNativeZoom());
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
    return length * retinaFactor;
  };

  /**
   * Reverses points data structure so points ascend in time.
   * @function
   */
 Helper.prototype.reverseData = function(inputPts) {
   var pref = Lt.measurementOptions; // preferences
   var pts = (inputPts) ? inputPts : JSON.parse(JSON.stringify(Lt.data.points));

   var i;
   var lastIndex = pts.length - 1;
   var before_lastIndex = pts.length - 2;

   // reformatting done in seperate for-statements for code clarity/simplicity

   for (i = 0; i < pts.length; i++) { // swap start & break point cycle
     if (pts[i + 1] && pts[i]) {
       if (pts[i].break && pts[i + 1].start) {
         pts[i].start = true;
         pts[i].break = false;
         pts[i + 1].start = false;
         pts[i + 1].break = true;
       };
     };
   };

   for (i = 0; i < pts.length; i++) { // swap start & end point cycle
     if (pts[i + 2] && pts[i + 1] && pts[i]) {
       if (pts[i].year && pts[i + 1].start && !pts[i + 2].break) {
         pts[i + 1].start = false;
         pts[i + 1].year = pts[i].year;
         pts[i + 1].earlywood = pts[i].earlywood;
         pts[i].start = true;
         delete pts[i].year;
         delete pts[i].earlywood;
       };
     };
   };

   // reverse array order so years ascending
   pts.reverse();

   // change last point from start to end point
   if (pts[lastIndex] && pts[before_lastIndex]) {
     pts[lastIndex].start = false;
     pts[lastIndex].year =  (pref.subAnnual) ? pts[before_lastIndex].year : pts[before_lastIndex].year + 1;
     pts[lastIndex].earlywood = false;
   };

   // change first point to start point
   if (pts.length > 0) {
     let i = pts.findIndex(Boolean)
     pts[i].start = true;
     delete pts[i].year;
     delete pts[i].earlywood;
   };

   return pts.filter(Boolean) // remove any null points;
  };

  /**
   * Finds distances between points for plotting
   * @function
   */
   Helper.prototype.findDistances = function () {
     var disObj = new Object()
     var pts = (Lt.measurementOptions.forwardDirection) ? Lt.data.points : this.reverseData();

     var yearArray = [];
     var ewWidthArray = [];
     var lwWidthArray = [];
     var twWidthArray = [];

     var breakDis = 0;
     var prevPt = null;
     pts.map((e, i) => {
       if (!e) {
         return
       }
       if (e.start) {
         if (!pts[i - 1] || !pts[i - 1].break) {
           prevPt = e;
         } else if (pts[i - 1].break) {
           breakDis = Lt.helper.trueDistance(prevPt.latLng, e.latLng);
         }
       } else if (e.year || e.year == 0) {
         if (!yearArray.includes(e.year)) {
            yearArray.push(parseInt(e.year));
         }
         var width = Lt.helper.trueDistance(prevPt.latLng, e.latLng) - breakDis;
         width = parseFloat(width.toFixed(5));
         if (e.earlywood && Lt.measurementOptions.subAnnual) {
           ewWidthArray.push(width);
         } else if (!e.earlywood && Lt.measurementOptions.subAnnual) {
           lwWidthArray.push(width)
         } else {
           twWidthArray.push(width)
         }
         breakDis = 0;
         prevPt = e;
       }
     });

     if (Lt.measurementOptions.subAnnual) {
       var forward = Lt.measurementOptions.forwardDirection;
       // Year array should not have excess.
       // For measuring backwards, excess is at beginning.
       // For measuring forwards, excess is at end.
       var lwYears = yearArray.slice();
       if (lwWidthArray.length < yearArray.length) {
         (forward) ? lwYears.pop()  : lwYears.shift();
       }

       var ewYears = yearArray.slice();
       if (ewWidthArray.length < yearArray.length) {
         (forward) ? ewYears.pop() : ewYears.shift();
       }

       disObj.lw = { x: lwYears, y: lwWidthArray, name: Lt.meta.assetName + '_lw' };
       disObj.ew = { x: ewYears, y: ewWidthArray, name: Lt.meta.assetName + '_ew' };

       // When measuring with two increments, one increment may be ahead of the other.
       // If the increments are of uneven length, total width can only be as long as the shorter one.
       // Account for this by create tw array from beginning when measuring forwards...
       // ... and from end when measuring backwards.
       var length = Math.min(ewYears.length, lwYears.length);
       var j, k, width;
       for (var i = 0; i < length; i++) {
         j = (forward) ? i : ewYears.length - 1 - i;
         k = (forward) ? i : lwYears.length - 1 - i;
         width = parseFloat((ewWidthArray[j] + lwWidthArray[k]).toFixed(5));
         (forward) ? twWidthArray.push(width) : twWidthArray.unshift(width);
       }

       yearArray = (ewYears.length < lwYears.length) ? ewYears : lwYears;
     }

     disObj.tw = { x: yearArray, y: twWidthArray, name: Lt.meta.assetName + '_tw' }
     console.log(disObj);

     return disObj
   }

  /**
   * Finds closest points for connection
   * @function
   * @param {leaflet object} - Lt
   */
  Helper.prototype.closestPointIndex = function (latLng) {
    var ptsData = Lt.data
    var disList = [];

    /**
    * calculate the distance between 2 points
    * @function distanceCalc
    * @param {first point.latLng} pointA
    * @param {second point.latLng} pointB
    */
    function distanceCalc (pointA, pointB) {
      return Math.sqrt(Math.pow((pointB.lng - pointA.lng), 2) +
                       Math.pow((pointB.lat - pointA.lat), 2));
    };

    // finds point with smallest abs. distance
    for (i = 0; i <= ptsData.points.length; i++) {
      var distance = Number.MAX_SAFE_INTEGER;
      if (ptsData.points[i] && ptsData.points[i].latLng) {
         var currentPoint = ptsData.points[i].latLng;
         distance = distanceCalc(currentPoint, latLng);
      disList.push(distance);
      }
    };

    var minDistance = Math.min(...disList);
    i = disList.indexOf(minDistance)

    if (ptsData.points[i] == null) {
      return;
    };

    // catch if points are stacked on top of each other
    var stackedPointsCount = -1; // while loop will always repeat once
    while (!dis_i_to_plus || dis_i_to_plus == 0) {
      // define 4 points: points[i], points[i - 1], points[i + 1], & inserted point
      var pt_i = ptsData.points[i].latLng;

      if (ptsData.points[i - 1]) {
        var pt_i_minus = ptsData.points[i - 1].latLng;
      } else {
        var pt_i_minus = L.latLng(-2 * (pt_i.lat), -2 * (pt_i.lng));
      };

      if (ptsData.points[i + 1]) {
        var pt_i_plus = ptsData.points[i + 1].latLng;
      } else {
        var pt_i_plus = L.latLng(2 * (pt_i.lat), 2 * (pt_i.lng));
      };

      var pt_insert = latLng;

      // distance: point[i] to point[i + 1]
      var dis_i_to_plus = distanceCalc(pt_i, pt_i_plus);
      // distance: point[i} to point[i - 1]
      var dis_i_to_minus = distanceCalc(pt_i, pt_i_minus);
      // distance: point[i] to inserted point
      var dis_i_to_insert= distanceCalc(pt_i, pt_insert);
      // distance: point[i + 1] to inserted point
      var dis_plus_to_insert = distanceCalc(pt_i_plus, pt_insert);
      // distance: point[i - 1] to inserted point
      var dis_minus_to_insert = distanceCalc(pt_i_minus, pt_insert);

      stackedPointsCount++;
      i++;
    };

    i--; // need to subtract due to while loop

    // if denominator = 0, set denominator = ~0
    if (dis_i_to_minus == 0) {
      dis_i_to_minus = Number.MIN_VALUE;
    };
    if (dis_i_to_plus == 0) {
      dis_i_to_plus = Number.MIN_VALUE;
    };
    if (dis_i_to_insert == 0) {
      dis_i_to_insert = Number.MIN_VALUE;
    };

    /* Law of cosines:
       * c = distance between inserted point and points[i + 1] or points[i - 1]
       * b = distance between points[i] and points[i + 1] or points[i - 1]
       * a = distance between inserted points and points[i]
       Purpose is to find angle C for triangles formed:
       * Triangle [i + 1] = points[i], points[i + 1], inserted point
       * Triangle [i - 1] = points[i], points[i - 1], inserted point
       Based off diagram from: https://www2.clarku.edu/faculty/djoyce/trig/formulas.html#:~:text=The%20law%20of%20cosines%20generalizes,cosine%20of%20the%20opposite%20angle.
    */
    // numerator and denominator for calculating angle C using Law of cosines (rearranged original equation)
    var numeratorPlus = (dis_plus_to_insert ** 2) - ((dis_i_to_insert ** 2) + (dis_i_to_plus ** 2));
    var denominatorPlus = -2 * dis_i_to_insert * dis_i_to_plus;
    var numeratorMinus = (dis_minus_to_insert ** 2) - ((dis_i_to_insert ** 2) + (dis_i_to_minus ** 2));
    var denominatorMinus = -2 * dis_i_to_insert * dis_i_to_minus;
    var anglePlus = Math.acos(numeratorPlus/denominatorPlus);
    var angleMinus = Math.acos(numeratorMinus/denominatorMinus);

    // smaller angle determines connecting lines
    if (stackedPointsCount > 0) { // special case for stacked points
      if (anglePlus > angleMinus) {
        i -= stackedPointsCount + 1; // go to first stacked point
      };
    } else if (anglePlus < angleMinus) {
      i++;
    };

    return i;
  }
  /**
   * returns the correct colors for points in a measurement path
   * @function
   * @param {leaflet object} - Lt
   */
   Helper.prototype.assignRowColor = function (e,y,Lt, lengthAsAString)
   {
     var stringContent;
     if (Lt.measurementOptions.subAnnual) {
       var wood;
       var row_color;
       if (e.earlywood) {
         wood = 'E';
         row_color = '#02bfd1';
       } else {
         wood = 'L';
         row_color = '#00838f';
         y++;
       };
       if(e.year%10===0)
       {
         if(wood === 'E')
         {
           row_color='#d17154';
         }
         else{
           row_color= '#db2314';
         }
       }

       // handlebars from template.html
       let content_A = document.getElementById("assign-row-color-subannual-template").innerHTML;
       let template_A = Handlebars.compile(content_A);
       let html_A = template_A( {row_color: row_color, row_year: e.year, row_wood: wood, length: lengthAsAString} );

       stringContent = html_A;
     } else {
       y++;

       row_color = e.year % 10===0 ? 'red':'#00d2e6';

       // handlebars from template.html
       let content_B = document.getElementById("assign-row-color-annual-template").innerHTML;
       let template_B = Handlebars.compile(content_B);
       let html_B = template_B( {row_color: row_color, row_year: e.year, length: lengthAsAString} );

       stringContent = html_B;
     }
     return stringContent;
   }
};
