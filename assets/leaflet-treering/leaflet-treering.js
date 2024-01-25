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
    setTimeout(function() {find
      viewer.setView([latData, lngData], 16); //  max zoom level is 18
    }, 500);
  }

  //options
  this.defaultResolution = 468;
  this.options = options;
  this.meta = {
    'ppm': options.initialData.ppm || options.ppm || this.defaultResolution,
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

  /* Current helper tools:
   * closestPointIndex -> will find the absolute closest point and its index or its point[i] value
  */
  this.helper = new Helper(this);

  //error alerts in 'measuring' mode aka popout window
  //will not alert in 'browsing' mode aka DE browser window
  if (window.name.includes('popout') && options.ppm === 0 && !options.initialData.ppm) {
    alert('Calibration needed: set p/mm in asset metadata or use calibration tool');
  }

  this.autoscroll = new Autoscroll(this, this.viewer);
  this.mouseLine = new MouseLine(this);
  this.visualAsset = new VisualAsset(this);
  this.annotationAsset = new AnnotationAsset(this);
  this.panhandler = new Panhandler(this);

  this.scaleBarCanvas = new ScaleBarCanvas(this);
  this.metaDataText = new MetaDataText(this);

  this.popout = new Popout(this);
  this.undo = new Undo(this);
  this.redo = new Redo(this);

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

  this.keyboardShortCutDialog = new KeyboardShortCutDialog(this);

  this.universalDelete = new UniversalDelete(this);

  this.undoRedoBar = new L.easyBar([this.undo.btn, this.redo.btn]);
  this.createTools = new ButtonBar(this, [this.createPoint.btn, this.mouseLine.btn, this.zeroGrowth.btn, this.createBreak.btn], 'straighten', 'Create new measurements');
  this.editTools = new ButtonBar(this, [this.dating.btn, this.insertPoint.btn, this.insertBreak.btn, this.convertToStartPoint.btn, this.insertZeroGrowth.btn, this.cut.btn], 'edit', 'Edit existing measurements');
  this.settings = new ButtonBar(this, [this.measurementOptions.btn, this.calibration.btn, this.keyboardShortCutDialog.btn], 'settings', 'Measurement preferences & distance calibration');

  this.tools = [this.calibration, this.dating, this.createPoint, this.createBreak, this.universalDelete, this.cut, this.insertPoint, this.convertToStartPoint, this.insertZeroGrowth, this.insertBreak, this.annotationAsset, this.imageAdjustment, this.measurementOptions];
  // --- //
  // Code hosted in Leaflet.AreaCapture.js
  this.areaCaptureInterface = new AreaCaptureInterface(this);
  this.areaTools = new ButtonBar(this, this.areaCaptureInterface.btns, 'hdr_strong', 'Manage ellipses');

  // Alert for Beta purposes: 
  this.betaToggle = true;
  $(this.areaTools.btn.button).on("click", () => {
    if (this.betaToggle) {
      //alert("Area measurement tools for beta testing & provisional data development. Please direct any issues or feedback to: thorn573@umn.edu.");
      this.betaToggle = false;
    }
  })

  this.areaCaptureInterface.tools.map(tool => {
    this.tools.push(tool);
  });
  // --- //

  // Code hosted in Leaflet.DataAccess.js
  this.dataAccessInterface = new DataAccessInterface(this);

  this.baseLayer = {
    'Tree Ring': base_layer,
    'GL Layer': gl_layer
  };

  this.overlay = {
    'Points': this.visualAsset.markerLayer,
    'H-bar': this.mouseLine.layer,
    'Lines': this.visualAsset.lineLayer,
    'Annotations': this.annotationAsset.markerLayer,
    'Ellipses': this.areaCaptureInterface.ellipseVisualAssets.ellipseLayer,
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

    // if popout is opened display measuring tools
    if (window.name.includes('popout')) {
      this.imageAdjustment.btn.addTo(this.viewer);
      this.dataAccessInterface.viewData.btn.addTo(this.viewer);
      this.dataAccessInterface.popoutPlots.btn.addTo(this.viewer);
      //this.PixelAdjustment.btn.addTo(this.viewer);
      this.createTools.bar.addTo(this.viewer);
      this.editTools.bar.addTo(this.viewer);
      this.annotationAsset.createBtn.addTo(this.viewer);
      this.universalDelete.btn.addTo(this.viewer);
      this.settings.bar.addTo(this.viewer);
      this.areaTools.bar.addTo(this.viewer);
      this.undoRedoBar.addTo(this.viewer);
    } else {
      this.imageAdjustment.btn.addTo(this.viewer);
      this.dataAccessInterface.viewData.btn.addTo(this.viewer);
      this.dataAccessInterface.popoutPlots.btn.addTo(this.viewer);
      this.popout.btn.addTo(this.viewer);
      //this.PixelAdjustment.btn.addTo(this.viewer);
      //defaults overlay 'points' option to disabled
      map.removeLayer(this.visualAsset.markerLayer);
    }

    // Right click disables whatever tool is active.
    this.viewer.on('contextmenu', () => {
      this.disableTools();
    });

    // Close dialog box with enter/return, but do not automatically disable tool.
    L.DomEvent.on(window, 'keydown', (e) => {
       if (e.keyCode == 13) {
         // Refactor so simple loop may be used.
         console.log("Enter pressed!")
         if (this.dating.active) {
           this.dating.keypressAction(e);
           return;
         }
         if (this.helper.dialog) this.helper.dialog.close();
         if (this.measurementOptions.dialog) {
           $("#confirm-button").click();
         }
         if (this.annotationAsset.dialogAnnotationWindow) this.annotationAsset.dialogAnnotationWindow.close();
         if (this.annotationAsset.dialogAttributesWindow) this.annotationAsset.dialogAttributesWindow.close();
       }
    }, this);

    // Disable all tools w/ esc.
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
      this.dataAccessInterface.cloudUpload.displayDate();
    };
    if (this.dataAccessInterface.popoutPlots.win) {
      this.dataAccessInterface.popoutPlots.sendData();
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
      if (this.annotationAsset.annotationIcon) this.annotationAsset.annotationIcon.removeFrom(this.viewer);
    } else if (this.annotationAsset.dialogAnnotationWindow) {
      this.annotationAsset.dialogAnnotationWindow.destroy();
    };

    if (this.annotationAsset.dialogAttributesWindow) {
      this.annotationAsset.dialogAttributesWindow.destroy();
      delete this.annotationAsset.dialogAttributesWindow;
    };

    this.tools.forEach(e => { 
      e.disable() 
    });

    if (!this.dataAccessInterface.viewDataDialog.dialog.options.size[0]) this.dataAccessInterface.viewDataDialog.close();
  };

  LTreering.prototype.collapseTools = function() {
    this.createTools.collapse();
    this.editTools.collapse();
    this.settings.collapse();
    this.areaTools.collapse();
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

  // --- //
  // Code hosted in Leaflet.AreaCapture.js
  if (options.initialData.ellipses) this.areaCaptureInterface.ellipseData.loadJSON(options.initialData.ellipses);
  // --- //
}

/*******************************************************************************/

/**
 * Universal delete button
 * @constructor 
 * 
 * @param {object} Lt - Leaflet Treering object.  
 */
function UniversalDelete(Lt) {
  this.btn = new Button(
    'delete',
    'Delete points or annotations (not ellipses)',
    () => { Lt.disableTools(); this.enable() },
    () => { this.disable() }
  );

  // Enable with DEL
  L.DomEvent.on(window, 'keydown', (e) => {
    if (e.keyCode == 46 && window.name.includes('popout')) { 
      e.preventDefault();
      e.stopPropagation();
      Lt.disableTools();

      Lt.viewer.getContainer().style.cursor = 'pointer';
      this.enable();
    }
 }, this);

  /**
   * Enables button
   * @function
   */
  UniversalDelete.prototype.enable = function() {
    this.btn.state('active');
    this.btn.active = true;
    Lt.deletePoint.selectedAdjustment = false;

    Lt.viewer.getContainer().style.cursor = 'pointer';
  }

  /**
   * Disbales button
   * @function
   */
  UniversalDelete.prototype.disable = function() {
    this.btn.state('inactive');
    this.btn.active = false;
    Lt.deletePoint.selectedAdjustment = false;

    $(Lt.viewer.getContainer()).off('click');
    Lt.viewer.getContainer().style.cursor = 'default';
  }
}

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
      if ((measurementOptions.subAnnual && ((direction == forwardInTime && !this.earlywood) ||
                                            (direction == backwardInTime && this.earlywood))) ||
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
    Lt.helper.updateFunctionContainer(true);
  };

  /**
   * delete a point from the measurement data
   * @function deletePoint
   */
  MeasurementData.prototype.deletePoint = function(i) {
    let direction = directionCheck();

    let second_points;
    if (this.points[i].start) {
      // Case 1: Start point of break pair. Remove break section.
      if (this.points[i - 1] != undefined && this.points[i - 1].break) {
        delete this.points[i];
        delete this.points[i - 1];
        this.points = this.points.filter(Boolean);
        this.index = this.points.length;
      // Case 2: Typical start point. Connect to previous measurement section.
      // If very first start point, remove & create new start point.
      } else {
        second_points = this.points.slice().splice(i + 1, this.index - 1);
        second_points.map(e => {
          if (i === 0) {
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
    // Case 3: Break point of break pair. Remove break section.
    } else if (this.points[i].break) {
      delete this.points[i];
      if (this.points[i + 1] && this.points[i + 1]?.start) delete this.points[i + 1];
      this.points = this.points.filter(Boolean);
      this.index = this.points.length;
    // Case 4: Point selected has a year value.
    } else {
      let tempDirection = (Lt.deletePoint.adjustOuter) ? backwardInTime : forwardInTime;
      let new_points = JSON.parse(JSON.stringify(this.points));
      second_points = (direction == tempDirection) ? JSON.parse(JSON.stringify(this.points)).slice(0, i) : JSON.parse(JSON.stringify(this.points)).slice(i + 1);
      delete new_points[i];

      let year_adjustment = (Lt.deletePoint.adjustOuter) ? -1 : 1;
      let index_adjustment = (direction == tempDirection) ? 0 : i + 1;
      second_points.map((e, k) => {
        if (!e) return;
        if (!e.start && !e.break) {
          if (measurementOptions.subAnnual) e.earlywood = !e.earlywood;
          if (
              (Lt.deletePoint.adjustOuter && !e.earlywood) || // When adjusting outer portion, change year at latewood instance.
              (!Lt.deletePoint.adjustOuter && e.earlywood) || // When adjusting inner portion, change year at earlywood instance.
              !measurementOptions.subAnnual
             ) {
            e.year = e.year + year_adjustment;
          }
        };
        new_points[index_adjustment + k] = e;
      });

      this.points = new_points.filter(Boolean);
      this.index = this.points.length;
      let lastIndex = this.points.length - 1;
      // If only a start point exists, reset data.
      if (!this.points[lastIndex].year) {
        this.year = 0;
        this.earlywood = true;
      } else {
        // Determine next measurement point by last existing point.
        if (measurementOptions.subAnnual) {
          this.earlywood = !(this.points[lastIndex].earlywood)
          if (direction == forwardInTime) {
            this.year = (this.points[lastIndex].earlywood) ? this.points[lastIndex].year : this.points[lastIndex].year + 1;
          } else if (direction == backwardInTime) {
            this.year = (this.points[lastIndex].earlywood) ? this.points[lastIndex].year - 1 : this.points[lastIndex].year;
          }
        } else {
          this.year = (direction == forwardInTime) ? this.points[lastIndex].year + 1 : this.points[lastIndex].year - 1;
        }
      }
    }

    // updates after a point is deleted
    Lt.helper.updateFunctionContainer(true);
  };

  /**
   * Remove a range of points from the measurement data.
   * @function cut
   */
  MeasurementData.prototype.cut = function(i, j) {
    let direction = directionCheck();
    let lower = Math.min(i, j)
    let upper = Math.max(i, j);

    // If cut includes a break, include all break segement points in cut.
    if (this.points[lower].start && this.points[lower - 1] && this.points[lower - 1].break) lower--;
    else if (this.points[upper].break && this.points[upper + 1] && this.points[upper + 1].start) upper++;

    let tempDirection = (Lt.cut.adjustOuter) ? backwardInTime : forwardInTime;
    let new_points = JSON.parse(JSON.stringify(this.points));
    second_points = (direction == tempDirection) ? JSON.parse(JSON.stringify(this.points)).slice(0, lower) : JSON.parse(JSON.stringify(this.points)).slice(upper + 1);

    let num_non_pts = this.points.slice(lower, upper + 1).filter(e => (!e.year && e.year != 0)).length;
    // When measuring subincrements, years recorded are half of number of points plotted.
    let pt_delta = (measurementOptions.subAnnual) ? Math.floor((upper - lower) / 2) : upper - lower;
    let non_pt_delta = (measurementOptions.subAnnual) ? Math.round(num_non_pts / 2) : num_non_pts;
    let year_delta = pt_delta - non_pt_delta + 1;

    // Only need to swap earlywood latewood if selected points the same thus a full year is not removed.
    let need_swap = (measurementOptions.subAnnual && this.points[lower].earlywood == this.points[upper].earlywood);
    let year_adjustment = (Lt.cut.adjustOuter) ? -1 * year_delta : year_delta;
    let index_adjustment = (direction == tempDirection) ? 0 : upper + 1;

    second_points.map((e, k) => {
      if (!e) return;
      if (!e.start && !e.break) {
        if (measurementOptions.subAnnual && need_swap) e.earlywood = !e.earlywood;
        e.year = e.year + year_adjustment;
        // When partial cuts taken (need_swap = true), need to "shimmey" years along. EX:
        // ORG: S -> 1E -> 1L -> 2E -> 2L -> 3E -> 3L
        // Cuts:     /\          /\
        // NEW: S ->                   1E -> 1L -> 2E
        // Year Delta:                 1     2     1
        // Earlywood/latewood points need to be bumped up for outer/inner, respectively.
        if (need_swap) {
          if (Lt.cut.adjustOuter && e.earlywood) e.year++;
          else if (!Lt.cut.adjustOuter && !e.earlywood) e.year--;
        }
      };
      new_points[index_adjustment + k] = e;
    });

    new_points.splice(lower, upper - lower + 1);
    new_points = new_points.filter(Boolean);

    this.points = new_points.filter(Boolean);
    this.index = this.points.length;
    let lastIndex = this.points.length - 1;

    // If all points or all except first start point removed, "reset" points.
    if (!this.points[1]) {
      this.earlywood = true;
      this.year = 0;

      // Updates after points are cut
      Lt.helper.updateFunctionContainer(true);
      return
    }

    // If only a start point exists, reset data.
    if (!this.points[lastIndex].year) {
      this.year = 0;
      this.earlywood = true;
    } else {
      // Determine next measurement point by last existing point.
      if (measurementOptions.subAnnual) {
        this.earlywood = !(this.points[lastIndex].earlywood)
        if (direction == forwardInTime) {
          this.year = (this.points[lastIndex].earlywood) ? this.points[lastIndex].year : this.points[lastIndex].year + 1;
        } else if (direction == backwardInTime) {
          this.year = (this.points[lastIndex].earlywood) ? this.points[lastIndex].year - 1 : this.points[lastIndex].year;
        }
      } else {
        this.year = (direction == forwardInTime) ? this.points[lastIndex].year + 1 : this.points[lastIndex].year - 1;
      }
    }

    // If first start point removed, create new one.
    if (!this.points[0].start) this.points[0] =  {'start': true, 'skip': false, 'break': false, 'latLng': this.points[0].latLng};

    // Updates after points are cut
    Lt.helper.updateFunctionContainer(true);
  };

  /**
   * insert a point in the middle of the measurement data
   * @function insertPoint
   */
  MeasurementData.prototype.insertPoint = function(latLng) {
    let direction = directionCheck();

    var i = Lt.helper.closestPointIndex(latLng);
    if (!i && i != 0) {
      alert('New point must be within existing points. Use the create toolbar to add new points to the series');
      return;
    };

    // Adjustment area (inner v. outer) determines which section of points is adjusted.
    // If the user was measuring forward in time & wanted to shift earlier in time (inner) points, ...
    // ... need to change first half of points array.
    // If the user was measuring forward in time & wanted to shift later in time (outer) points, ...
    // ... need to change second half of points array.
    // Logic swaps for measuring backwards.
    let tempDirection = (Lt.insertPoint.adjustOuter) ? backwardInTime : forwardInTime;
    let new_points = JSON.parse(JSON.stringify(this.points));
    let second_points = (direction == tempDirection) ? JSON.parse(JSON.stringify(this.points)).slice(0, i) : JSON.parse(JSON.stringify(this.points)).slice(i);
    let year_adjusted;
    let earlywood_adjusted = true;

    if (0 < i && i < this.points.length) {
      let nearest_prevPt = this.points.slice(0, i).reverse().find(e => !e.start && !e.break && (e.year || e.year === 0));
      let nearest_nextPt = this.points.slice(i).find(e => !e.start && !e.break && (e.year || e.year === 0));

      year_adjusted = (direction == tempDirection) ? nearest_prevPt?.year : nearest_nextPt?.year;

      if (measurementOptions.subAnnual) {
        earlywood_adjusted = (direction == tempDirection) ? nearest_prevPt?.earlywood : nearest_nextPt?.earlywood;
        if (earlywood_adjusted === undefined) {
          earlywood_adjusted = !nearest_nextPt.earlywood;
        }
        // If inserted point is a latewood point, must have same year as next inner earlywood point.
        if (earlywood_adjusted === false) {
          year_adjusted = (direction == forwardInTime) ? nearest_prevPt?.year : nearest_nextPt?.year;
        }
      }

      // If nearest previous point is the first start point, must infer year from next point.
      if (!nearest_prevPt) {
        let yearAdj = (direction == forwardInTime) ? -1 : 1;
        let lwCheck = (direction == forwardInTime) ? !nearest_nextPt?.earlywood : nearest_nextPt?.earlywood;
        if (!measurementOptions.subAnnual) {
          year_adjusted = (direction != tempDirection) ? nearest_nextPt.year : nearest_nextPt.year + yearAdj;
        } else {
          year_adjusted = (measurementOptions.subAnnual && lwCheck) ? nearest_nextPt.year : nearest_nextPt.year + yearAdj;
        }
      }
    } else {
      alert('Please insert new point closer to connecting line')
      return
    };

    // Snap inserted point to nearest polyline if line exists.
    if (!Lt.visualAsset.lines[i]) return;
    coord = Lt.viewer.latLngToLayerPoint(latLng)
    new_coord = Lt.visualAsset.lines[i].closestLayerPoint(coord)
    new_latLng = Lt.viewer.layerPointToLatLng(new_coord)
    new_pt = {'start': false, 'skip': false, 'break': false,
              'year': year_adjusted, 'earlywood': earlywood_adjusted,
              'latLng': new_latLng};
    new_points.splice(i, 0, new_pt);

    let year_adjustment = (Lt.insertPoint.adjustOuter) ? 1 : -1;
    let index_adjustment = (direction == tempDirection) ? 0 : i + 1;
    second_points.map((e, k) => {
      if (!e) return;
      if (!e.start && !e.break) {
        if (measurementOptions.subAnnual) e.earlywood = !e.earlywood;
        if (
            (Lt.insertPoint.adjustOuter && e.earlywood) || // When adjusting outer portion, change year at earlywood instance.
            (!Lt.insertPoint.adjustOuter && !e.earlywood) || // When adjusting inner portion, change year at latewood instance.
            !measurementOptions.subAnnual
           ) {
          e.year = e.year + year_adjustment;
        }
      };
      new_points[index_adjustment + k] = e;
    });

    this.points = new_points.filter(Boolean);
    this.index = this.points.length;
    let lastIndex = this.points.length - 1;
    if (measurementOptions.subAnnual) {
      this.earlywood = !(this.points[lastIndex].earlywood)
      if (direction == forwardInTime) {
        this.year = (this.points[lastIndex].earlywood) ? this.points[lastIndex].year : this.points[lastIndex].year + 1;
      } else if (direction == backwardInTime) {
        this.year = (this.points[lastIndex].earlywood) ? this.points[lastIndex].year - 1 : this.points[lastIndex].year;
      }
    } else {
      this.year = (direction == forwardInTime) ? this.points[lastIndex].year + 1 : this.points[lastIndex].year - 1;
    }

    // Update other features after point inserted.
    Lt.helper.updateFunctionContainer(true);

    return i;
  };

  /**
   * Convert a point to a start point
   * @function convertToStartPoint
   */
  MeasurementData.prototype.convertToStartPoint = function(i) {
    let direction = directionCheck();
    // Points are lagged when measuring backwards, need to account for.
    if (direction == backwardInTime) i++;
    let tempDirection = (Lt.convertToStartPoint.adjustOuter) ? backwardInTime : forwardInTime;

    var points = Lt.data.points;
    var previousYear = points[i].year || 0;

    // Convert to start point by changing properties.
    points[i].start = true;
    let tempYear = points[i].year;
    delete points[i].year;
    delete points[i].earlywood;

    // Remove orphanned start points.
    if (points[i - 1]?.start) {
      delete points[i - 1];
    }
    if (points[i + 1]?.start) {
      delete points[i];
    }

    let new_points = JSON.parse(JSON.stringify(this.points));
    let second_points = (direction == tempDirection) ? JSON.parse(JSON.stringify(this.points)).slice(0, i) : JSON.parse(JSON.stringify(this.points)).slice(i + 1);

    let year_adjustment = (Lt.convertToStartPoint.adjustOuter) ? -1 : 1;
    let index_adjustment = (direction == tempDirection) ? 0 : i + 1;
    second_points.map((e, k) => {
      if (!e) return;
      if (!e.start && !e.break) {
        if (measurementOptions.subAnnual) e.earlywood = !e.earlywood;
        if (
            (Lt.convertToStartPoint.adjustOuter && !e.earlywood) || // When adjusting outer portion, change year at latewood instance.
            (!Lt.convertToStartPoint.adjustOuter && e.earlywood) || // When adjusting inner portion, change year at earlywood instance.
            !measurementOptions.subAnnual
           ) {
          e.year = e.year + year_adjustment;
        }
      };
      new_points[index_adjustment + k] = e;
    });

    new_points = new_points.filter(Boolean);
    this.points = new_points;
    // Removes orphanned end point.
    if (this.points[new_points.length - 1]?.start) this.points.pop();
    this.index = (new_points.length - 1 > 0) ? new_points.length : 0;

    let lastIndex = new_points.length - 1;
    // If only a start point exists, reset data.
    if (!this.points[lastIndex].year) {
      this.year = 0;
      this.earlywood = true;
    } else {
      // Determine next measurement point by last existing point.
      if (measurementOptions.subAnnual) {
        this.earlywood = !(this.points[lastIndex].earlywood)
        if (direction == forwardInTime) {
          this.year = (this.points[lastIndex].earlywood) ? this.points[lastIndex].year : this.points[lastIndex].year + 1;
        } else if (direction == backwardInTime) {
          this.year = (this.points[lastIndex].earlywood) ? this.points[lastIndex].year - 1 : this.points[lastIndex].year;
        }
      } else {
        this.year = (direction == forwardInTime) ? this.points[lastIndex].year + 1 : this.points[lastIndex].year - 1;
      }
    }

    Lt.helper.updateFunctionContainer(true);
  }

  /**
   * Insert a zero growth year in the middle of the measurement data
   * @function insertZeroGrowth
   */
  MeasurementData.prototype.insertZeroGrowth = function(i, latLng) {
    // Example of action with sub-anual increments, annual increments need not...
    // ... account for inserting 2 points (much simpiler):

    // Old points: --- | --- | --- | --- | --- | --- | --- | ---
    // Increments:     e     l     e     l     e     l     e
    // Years:          1     1     2     2     3     3     4
    // Indices:       i-3   i-2   i-1    i    i+1   i+2   i+3

    // Shift outer:
    // New points:  --- | --- | --- | --- | || --- | --- | --- | ---
    // Increments:      e     l     e     l el     e     l     e
    // Years:           1     1     2     2 33     4     4     5
    // Indices:        i-3   i-2   i-1    i i+1   i+3   i+4   i+5
    //                                      i+2

    // Shift inner:
    // New points:  --- | --- | --- | --- || | --- | --- | --- | ---
    // Increments:      e     l     e     le l     e     l     e
    // Years:           0     1     1     12 2     3     3     4
    // Indices:        i-5   i-4   i-3   i-1 i    i+1   i+3   i+4
    //                                   i-2

    let direction = directionCheck();
    let tempDirection = (Lt.insertZeroGrowth.adjustOuter) ? backwardInTime : forwardInTime;
    let new_points = JSON.parse(JSON.stringify(this.points));

    // Index to splice new points in array depends on shifting direction.
    let k = (direction == tempDirection) ? i : i + 1;
    let earlywoodAdjusted = true;
    let comparisonYear = (this.points[i].year) ? this.points[i].year : this.points.slice(i).find(e => !e.start && !e.break && (e.year || e.year === 0)).year;

    if (Lt.measurementOptions.subAnnual) {
      let yearA, yearB, indexA, indexB, ewA, ewB;
      // Special case for inserting a zero growth year on ends points when measuring backwards (hidden start points.)
      // No need to adjust when adjusting inner portion and need to shift point to be within next
      // measurement segement.
      if (this.points[i].start) {
        yearA = (Lt.insertZeroGrowth.adjustOuter) ? comparisonYear : comparisonYear - 1;
        yearB = (Lt.insertZeroGrowth.adjustOuter) ? comparisonYear + 1 : comparisonYear;
        if (Lt.insertZeroGrowth.adjustOuter) k++;
        indexA = k;
        indexB = k;
        ewA = false;
        ewB = true;
      } else {
        // See above for how years & indices decided.
        yearA = (Lt.insertZeroGrowth.adjustOuter) ? comparisonYear + 1 : comparisonYear;
        yearB = (Lt.insertZeroGrowth.adjustOuter) ? comparisonYear + 1 : comparisonYear - 1;

        if (direction == forwardInTime) {
          indexA = (Lt.insertZeroGrowth.adjustOuter) ? i + 1 : i;
          indexB = (Lt.insertZeroGrowth.adjustOuter) ? i + 2 : i;
        } else {
          indexA = (Lt.insertZeroGrowth.adjustOuter) ? i : i + 1;
          indexB = (Lt.insertZeroGrowth.adjustOuter) ? i : i + 2;
        }

        ewA = true;
        ewB = false;
      }

      let pt_A = {
        'start': false, 'skip': false, 'break': false,
        'year': yearA, 'earlywood': ewA, 'latLng': latLng
      };
      new_points.splice(indexA, 0, pt_A);

      let pt_B = {
        'start': false, 'skip': false, 'break': false,
        'year': yearB, 'earlywood': ewB, 'latLng': latLng
      };
      new_points.splice(indexB, 0, pt_B);
      (direction == tempDirection) ? k-- : k++;
    } else {
      let yearAdjusted = (Lt.insertZeroGrowth.adjustOuter) ? comparisonYear + 1 : comparisonYear - 1;

      // Special case when measuring backwards b/c start points are treated like measurment points;
      // no need to adjust when adjusting inner portion and need to shift point to be within next
      // measurement segement.
      if (this.points[i].start && !Lt.insertZeroGrowth.adjustOuter) yearAdjusted = comparisonYear;
      if (this.points[i].start && Lt.insertZeroGrowth.adjustOuter) k++;

      let new_pt = {
        'start': false, 'skip': false, 'break': false,
        'year': yearAdjusted, 'earlywood': true, 'latLng': latLng
      };
      new_points.splice(k, 0, new_pt);
    };

    let year_adjustment = (Lt.insertZeroGrowth.adjustOuter) ? 1 : -1;
    let index_adjustment = (direction == tempDirection) ? 0 : k;
    // When inserting a sub-annual zero growth point, must slice after the second inserted point.
    let second_points = (direction == tempDirection) ? JSON.parse(JSON.stringify(this.points)).slice(0, i) :
                                                       JSON.parse(JSON.stringify(this.points)).slice(i);

    second_points.map((e, j) => {
      if (!e) return;
      if (!e.start && !e.break) e.year = e.year + year_adjustment;
      if (this.points[i].start) { // Special case when measuring backwards b/c start points are treated like measurment points.
        if (!e.start) new_points[index_adjustment + j] = e;
      } else {
        new_points[index_adjustment + j] = e;
      }
    });

    this.points = new_points.filter(Boolean);
    this.index = this.points.length;
    let lastIndex = this.points.length - 1;
    if (measurementOptions.subAnnual) {
      this.earlywood = !(this.points[lastIndex].earlywood)
      if (direction == forwardInTime) {
        this.year = (this.points[lastIndex].earlywood) ? this.points[lastIndex].year : this.points[lastIndex].year + 1;
      } else if (direction == backwardInTime) {
        this.year = (this.points[lastIndex].earlywood) ? this.points[lastIndex].year - 1 : this.points[lastIndex].year;
      }
    } else {
      this.year = (direction == forwardInTime) ? this.points[lastIndex].year + 1 : this.points[lastIndex].year - 1;
    }

    // Update other features after point inserted.
    Lt.helper.updateFunctionContainer(true);

    return k;
  };

  /**
   * remove any entries in the data
   * @function clean
   */
  MeasurementData.prototype.clean = function() {
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
 * @param {object} Lt - Initializing object
 * @param {Leaflet Map Object} viewer - a refrence to the leaflet map object
 */
function Autoscroll (Lt, viewer) {
  //Creates areas on right, left, top, bottom part of screen
  let rightDiv = document.getElementById("AutoScroll-Right-Div");
  let leftDiv = document.getElementById("AutoScroll-Left-Div");
  let upDiv = document.getElementById("AutoScroll-Up-Div");
  let downDiv = document.getElementById("AutoScroll-Down-Div");
  let mapContainer = document.getElementById("imageMap");
  mapContainer.append(rightDiv);
  mapContainer.append(leftDiv);
  mapContainer.append(upDiv);
  mapContainer.append(downDiv);

  /**
   * Turn on autoscroll based on viewer dimmensions
   * @function on
   */
  Autoscroll.prototype.on = function() {   
    //Turns off scrolling when shift is released
    $(window).on("keyup", (e) => {
      if (e.key == "Shift") $(viewer).off("moveend");
    });

    //Smooths out panning
    let panByOptions = {animate: true, easeLinearity: 1};

    $("#AutoScroll-Right-Div").on("mouseenter", () => {
      if(!event.getModifierState("Shift")) return

      let mapSize = viewer.getSize();   // Map size used for map scrolling.
      let panAmount = 0.15 * mapSize.x;

      let panLimit = viewer.getZoom() * (mapSize.x / panAmount);
      let panCount = 0;

      //Repeatedly pans until the border of picture is reached
      viewer.panBy([panAmount, 0], panByOptions); 
      $(viewer).on("moveend", () => {
        panCount++;
        if (panCount < panLimit) viewer.panBy([panAmount, 0], panByOptions);
      });
    })

    $("#AutoScroll-Left-Div").on("mouseenter", () => {
      if(!event.getModifierState("Shift")) return

      let mapSize = viewer.getSize();  
      let panAmount = 0.15 * mapSize.x; 

      let panLimit = viewer.getZoom() * (mapSize.x / panAmount);
      let panCount = 0;

      viewer.panBy([-panAmount, 0], panByOptions); 
      $(viewer).on("moveend", () => {
        panCount++;
        if (panCount < panLimit) viewer.panBy([-panAmount, 0], panByOptions);
      })
    })

    $("#AutoScroll-Up-Div").on("mouseenter", () => {
      if(!event.getModifierState("Shift")) return

      let mapSize = viewer.getSize();  
      let panAmount = 0.125 * mapSize.y; 

      let panLimit = viewer.getZoom() * (mapSize.y / panAmount);
      let panCount = 0;

      viewer.panBy([0, -panAmount], panByOptions); 
      $(viewer).on("moveend", () => {
        panCount++;
        if (panCount < panLimit) viewer.panBy([0, -panAmount], panByOptions);
      })
    })

    $("#AutoScroll-Down-Div").on("mouseenter", () => {
      if(!event.getModifierState("Shift")) return

      let mapSize = viewer.getSize();  
      let panAmount = 0.125 * mapSize.y; 

      let panLimit = viewer.getZoom() * (mapSize.y / panAmount);
      let panCount = 0;

      viewer.panBy([0, panAmount], panByOptions); 
      $(viewer).on("moveend", () => {
        panCount++;
        if (panCount < panLimit) viewer.panBy([0, panAmount], panByOptions);
      })
    })

    //Stops panning when mouse leaves area
    $("#AutoScroll-Right-Div").on("mouseleave", () => {
      $(viewer).off("moveend");
    }); 

    $("#AutoScroll-Left-Div").on("mouseleave", () => {
      $(viewer).off("moveend");
    }); 

    $("#AutoScroll-Up-Div").on("mouseleave", () => {
      $(viewer).off("moveend");
    }); 

    $("#AutoScroll-Down-Div").on("mouseleave", () => {
      $(viewer).off("moveend");
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
    'light_blue' : { 'path': imagePath + 'images/EW_Point.png',
                     'size': [32, 32] },
    'dark_blue'  : { 'path': imagePath + 'images/LW_Point.png',
                     'size': [32, 32] },
    'light_red'  : { 'path': imagePath + 'images/EW_Decade.png',
                     'size': [32, 32] },
    'dark_red'   : { 'path': imagePath + 'images/LW_Decade.png',
                     'size': [32, 32] },
    'start'      : { 'path': imagePath + 'images/Start.png',
                     'size': [32, 32] },
    'break'      : { 'path': imagePath + 'images/Break.png',
                     'size': [32, 32] },
    'zero'       : { 'path': imagePath + 'images/Zero_Growth.png',
                     'size': [64, 64] },
    'empty'      : { 'path': imagePath + 'images/Empty.png',
                     'size': [0, 0] },
  };

  if (!colors[color]?.path) {
    console.log("Color path error: ", color);
  }

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

        // Specs for h-bar. Color is Light blue by default.
        let opacity = "0.75";
        let color  = "#49c4d9";
        let weight = "5";
        // Switches to dark blue if measuring with two increments and the next point is latewood.
        // Latewood point when measuring backwards is "hidden" as an earlywood point.
        let lwForward = (Lt.measurementOptions.forwardDirection && !Lt.data.earlywood);
        let lwBackward = (!Lt.measurementOptions.forwardDirection && Lt.data.earlywood);
        if (Lt.measurementOptions.subAnnual && (lwForward || lwBackward)) {
          color = "#18848c";
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
              {interactive: false, color: color, opacity: opacity,
                weight: weight}));

          // path guide for mouse
          this.layer.addLayer(L.polyline([mouseLatLng, latLngTwo],
              {interactive: false, color: color, opacity: opacity,
                weight: weight}));
        };

        this.layer.addLayer(L.polyline([latLng, mouseLatLng],
            {interactive: false, color: color, opacity: opacity,
              weight: weight}));

        this.layer.addLayer(L.polyline([topLeftPoint, bottomLeftPoint],
            {interactive: false, color: color, opacity: opacity,
              weight: weight}));

        this.layer.addLayer(L.polyline([topRightPoint, bottomRightPoint],
            {interactive: false, color: color, opacity: opacity,
              weight: weight}));
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
  this.initialLoad = false;
  this.markers = new Array();
  this.lines = new Array();
  this.markerLayer = L.layerGroup().addTo(Lt.viewer);
  this.lineLayer = L.layerGroup().addTo(Lt.viewer);

  /**
   * Reload all visual assets on the viewer
   * @function reload
   */
  VisualAsset.prototype.reload = function() {
    // Erase all markers.
    this.markerLayer.clearLayers();
    this.markers = new Array();
    // Erase all lines.
    this.lineLayer.clearLayers();
    this.lines = new Array();

    var swap = (!this.initialLoad &&
                !Lt.measurementOptions.forwardDirection &&
                 Lt.measurementOptions.subAnnual &&
                (Lt.data.points[1] && !Lt.data.points[1].earlywood));

    // Plot data back onto map.
    if (Lt.data.points !== undefined) {
      // Remove empty indices.
      Lt.data.points = Lt.data.points.filter(Boolean);
      Lt.data.points.map((e, i) => {
        // Old design of measuring backwards had the first point as latewood.
        // Need to swap earlywood values of legacy cores.
        if (swap) {
          e.earlywood = !e.earlywood
        }

        this.newLatLng(Lt.data.points, i, e.latLng, true);

        // Marker tool tips:
        // If measuring forward, point tooltips are "honest". For a start/break pair: start point says Start, break says Break.
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
          // Break point pair.
          if ((Lt.data.points[i].start && Lt.data.points[i - 1] && Lt.data.points[i - 1].break) ||
              (Lt.data.points[i + 1] && Lt.data.points[i + 1].start && Lt.data.points[i].break)) {
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
          if (i === 0) {
            let firstMeasurementPt = Lt.data.points.find(e => !e.start && !e.break && (e.year || e.year === 0));
            if (firstMeasurementPt) {
              var desc = (!Lt.measurementOptions.subAnnual) ? '' :
                         (Lt.data.points[i + 1].earlywood) ? ', late' : ', early';
              var c = (!Lt.measurementOptions.subAnnual || !firstMeasurementPt.earlywood) ? 1 : 0;
              tooltip = String(firstMeasurementPt.year + c) + desc;
            } else {
              tooltip = 'Start';
            }
          // Last point is treated as a start point, not a measurement point.
          } else if (i === Lt.data.points.length - 1) {
            tooltip = 'Start';
          }
        }

        this.markers[i].bindTooltip(tooltip, { direction: 'top' })
      });
      this.initialLoad = true;
    }

    // Bind popups to lines if not popped out.
    const pts = JSON.parse(JSON.stringify(Lt.data.points)).filter(Boolean);

    function create_tooltips_annual () {
      pts.map((e, i) => {
        let year = (Lt.measurementOptions.forwardDirection) ? pts[i].year : pts[i].year + 1;
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
        let forward = Lt.measurementOptions.forwardDirection;
        let backward = !Lt.measurementOptions.forwardDirection;
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
          tooltip = ((ew && backward) || forward) ? pts[i].year : pts[i].year + 1;
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
  VisualAsset.prototype.newLatLng = function(pts, i, latLng, reload, zero) {
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

    // Zero growth point icon:
    if (zero ||
       (forward && pts[i - 1] && pts[i].latLng.lat == pts[i - 1].latLng.lat && pts[i].latLng.lng == pts[i - 1].latLng.lng) ||
       (backward && pts[i + 1] && pts[i].latLng.lat == pts[i + 1].latLng.lat && pts[i].latLng.lng == pts[i + 1].latLng.lng)) {
      color = 'zero';
    // Start point icon:
    // !!! Start points after break points are visually shown as break points. !!!
    // !!! Refactor so break point is placed instead of start point !!!
    } else if (pts[i].start) {
      if (forward) {
        if (pts[i - 1] && pts[i - 1].break) {
          color = 'break';
        } else {
          color = 'start';
        }
      } else if (backward) {
        if (pts[i - 1] && pts[i - 1].break) {
          color = 'break';
        // Start points and measurement points swap when measuring backwards.
        } else if (pts[i - 1]) {
          if (pts[i - 1].year % 10 == 0) {
            color = (annual) ? 'dark_red' :
                    (pts[i - 1].earlywood) ? 'light_red' : 'dark_red';
          } else {
            color = (annual) ? 'light_blue' :
                    (pts[i - 1].earlywood) ? 'light_blue' : 'dark_blue';
          }
        }
      }
    // Break point icon:
    } else if (pts[i].break) {
      color = 'break';
    } else if (subAnnual) {
      if (pts[i].earlywood) {
        // Decades are colored red.
        color = (pts[i].year % 10 == 0) ? 'light_red' : 'light_blue';
      } else { // Otherwise, point is latewood.
        color = (pts[i].year % 10 == 0) ? 'dark_red' : 'dark_blue';
      }

      // Swap measurement path endings and start points.
      if (backward && pts[i + 1]?.start) {
        color = 'start';
      }
    // Annual icons:
    } else {
      color = (pts[i].year % 10 == 0) ? 'dark_red' : 'light_blue';

      // Swap measurement path endings and start points.
      if (backward && pts[i + 1] && pts[i + 1].start) {
        color = 'start';
      }
    };

    // Start and end points swapped when measuring backwards.
    // Only apply this when active measuring disabled.
    if (backward && i === 0) {
      let nextMeasurePt = pts.find(e => !e.start && !e.break && (e.year || e.year === 0));
      if (nextMeasurePt && pts[i + 1]) {
        if (subAnnual) {
          if (nextMeasurePt.earlywood) {
            // Decades are colored red.
            color = (nextMeasurePt.year % 10 == 0) ? 'dark_red' : 'dark_blue';
          } else { // Otherwise, point is latewood.
            color = (nextMeasurePt.year % 10 == 0) ? 'light_red' : 'light_blue';
          }
        } else {
          color = ((pts[i + 1].year + 1) % 10 == 0) ? 'dark_red' : 'light_blue';
        }

        if ((forward && pts[i - 1] && pts[i].latLng.lat == pts[i - 1].latLng.lat && pts[i].latLng.lng == pts[i - 1].latLng.lng) ||
            (backward && pts[i + 1] && pts[i].latLng.lat == pts[i + 1].latLng.lat && pts[i].latLng.lng == pts[i + 1].latLng.lng)) {
          color = 'zero';
        }
      } else {
        color = (annual) ? 'light_blue' : 'dark_blue';
      }
    // Only apply this when active measuring disabled.
    } else if (backward && i === pts.length - 1 && reload) {
        color = 'start';
    }

  if (!color) color = 'empty';
  var marker = getMarker(leafLatLng, color, Lt.basePath, draggable);
  this.markers[i] = marker;
  // Denote if marker uses zero icon, important for drag end events.
  if (color == "zero") this.markers[i].zero = true;

  // Tell marker what to do when being dragged.
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

    // Tell marker what to do when dragging is done.
    this.markers[i].on('dragend', (e) => {
      Lt.undo.push();
      pts[i].latLng = e.target._latlng;

      // Check if moving icon disturbed a zero growth icon.
      if (this.markers[i]?.zero || this.markers[i - 1]?.zero || this.markers[i + 1]?.zero) {
        let k = i;
        if (!this.markers[i].zero && this.markers[i - 1]?.zero) k = i - 1;
        else if (!this.markers[i].zero && this.markers[i + 1]?.zero) k = i + 1;

        if (subAnnual) {
          if (pts[k].earlywood) color = (pts[k].year % 10 == 0) ? 'light_red' : 'light_blue';
          else color = (pts[k].year % 10 == 0) ? 'dark_red' : 'dark_blue';
        } else if (annual) {
          color = (pts[k].year % 10 == 0) ? 'dark_red' : 'light_blue';
        }

        this.markers[k].setIcon(new MarkerIcon(color, "../"));
        this.markers[k].zero = false;
      }

      Lt.annotationAsset.reloadAssociatedYears();
      if (Lt.dataAccessInterface.popoutPlots.win) {
        Lt.dataAccessInterface.popoutPlots.sendData();
      }

      Lt.helper.updateFunctionContainer(true);
    });

    // Tell marker what to do when clicked.
    this.markers[i].on('click', (e) => {
      if (Lt.universalDelete.btn.active) {
        Lt.deletePoint.openDialog(e, i);
      };

      if (Lt.convertToStartPoint.active) {
        Lt.convertToStartPoint.openDialog(e, i);
      };

      if (Lt.cut.active) {
        if (Lt.cut.point != -1) {
          Lt.cut.openDialog(e, i);
        } else {
          Lt.cut.fromPoint(i);
        };
      };

      if (Lt.insertZeroGrowth.active) {
        // Cannot add a zero growth ring to earlywood, start points (shifted when measuring backwards), or break sets.
        if ((Lt.measurementOptions.subAnnual && pts[i].earlywood && !pts[i].start) ||
            (Lt.measurementOptions.forwardDirection && pts[i].start) ||
            (!Lt.measurementOptions.forwardDirection && pts[i + 1]?.start) ||
             pts[i].break || (pts[i].start && pts[i - 1]?.break)) {
          alert('Zero width years must be added at the annual ring boundary, (i.e. latewood points)');
        } else {
          Lt.insertZeroGrowth.openDialog(e, i);
        }
      }

      if (Lt.dating.active) {
        Lt.dating.action(i);
      }
    });

    // Highlight year in plotting tool when point hovered over.
    // Line below disables flashing when measuring.
    // && !Lt.createPoint.active
    this.markers[i].on('mouseover', e => {
      if (Lt.dataAccessInterface.popoutPlots.win && !Lt.createPoint.active) {
        // Do not highlight end point when measuring backward, but highlight start point.
        if (forward || (backward && i < pts.length - 1)) {
          var year = pts[i].year;
          if (backward && i === 0) {
            year = (annual && pts[i + 1]) ? pts[i + 1].year + 1 : pts[i + 1].year;
          }
          Lt.dataAccessInterface.popoutPlots.highlightYear(year);
        }
      }
    })

    this.markers[i].on('mouseout', e => {
      if (Lt.dataAccessInterface.popoutPlots.win && !Lt.createPoint.active) {
        Lt.dataAccessInterface.popoutPlots.highlightYear(false)
      }
    })

    // Draw connecting line if the previous point exists
    // Line color depends on measurment direction, early-/latewood value & year value.
    //   - Light blue: #17b0d4
    //   - Dark blue: #026d75
    //   - Light red: #e06f4c
    //   - Dark red: #db2314
    // For measuring forward in time:
    //   Line is light shade (blue if non decade, red if decade):
    //     1) Annual measurements (assigned dark shade if decade)
    //     2) Point is earlywood
    //     3) Point is a break and previous point is latewood
    //  Line is dark shade:
    //     1) Point is latewood
    //     2) Point is a break and previous point is earlywood
    // For measuring backward in time:
    //    Adjust year & use same decade logic as above. Light v. dark logic flipped.
    if (pts[i - 1] && !pts[i].start) {
      let forward = Lt.measurementOptions.forwardDirection;
      let annual = !Lt.measurementOptions.subAnnual;

      let opacity = "0.5";
      let weight = "5";
      let color = "FFF"; // White is debug color.

      // Blue by default.
      let light = "#17b0d4";
      let dark = "#026d75";

      // Shift evaluated year if measuring backward in time.
      let year = (forward) ? pts[i].year : ((!pts[i].earlywood || !Lt.measurementOptions.subAnnual) ? pts[i].year + 1 : pts[i].year);

      // Check if break in middle of decade measurment.
      if (pts[i].break) {
        let closest_prevPt = pts.slice(0, i).reverse().find(e => !e.start && !e.break && (e.year || e.year === 0));
        // Special case for first start point.
        if (!closest_prevPt) {
          let closest_nextPt = pts.slice(i).find(e => !e.start && !e.break && (e.year || e.year === 0));
          closest_prevPt = {
            "year": (forward) ? closest_nextPt?.year - 1 : ((annual) ? closest_nextPt?.year + 1 : closest_nextPt?.year),
            "earlywood": (annual) ? closest_nextPt?.earlywood : !closest_nextPt?.earlywood,
          }
        }

        if (annual) {
          year = (forward) ? closest_prevPt?.year + 1 : closest_prevPt?.year;
        } else {
          if (forward) {
            year = (closest_prevPt?.earlywood) ? closest_prevPt?.year : closest_prevPt?.year + 1;
          } else {
            year = closest_prevPt?.year;
          }
        }
      }
      // Red if point is at decade.
      if (year % 10 == 0) {
        light = (annual) ? "#db2314" : "#e06f4c";
        dark = "#db2314";
      }

      if (annual) {
        color = light;
      } else {
        // Light & dark conditions swap when measuring forwards v. backward in time.
        if (forward) {
          if (pts[i].earlywood || (pts[i].break && !pts[i - 1]?.earlywood)) {
            color = light;
          } else if (!pts[i].earlywood || (pts[i].break && pts[i - 1]?.earlywood)) {
            color = dark;
          }
        } else {
          if (!pts[i].earlywood || (pts[i].break && pts[i - 1]?.earlywood)) {
            color = light;
          } else if (pts[i].earlywood || (pts[i].break && !pts[i - 1]?.earlywood)) {
            color = dark;
          }
        }
      }

      this.lines[i] = L.polyline([pts[i - 1].latLng, leafLatLng], {color: color, opacity: opacity, weight: weight});
      this.lineLayer.addLayer(this.lines[i]);
    }

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
    'Create annotations (Shift-a)',
    () => { Lt.disableTools(); this.enable(this.createBtn) },
    () => { this.disable(this.createBtn) }
  );
  this.createBtn.active = false;

  // crtl-a to activate createBtn
  L.DomEvent.on(window, 'keydown', (e) => {
    if (e.keyCode == 65 && e.getModifierState("Shift") && !e.getModifierState("Control") && // 65 refers to 'a'
    window.name.includes('popout') && !this.dialogAnnotationWindow) { // Dialog windows w/ text cannot be active 
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
      // Reset annotation values: 
      this.text = '';
      this.code = [];
      this.description = [];
      this.checkedUniqueNums = [];
      this.calculatedYear = 0;
      this.yearAdjustment = 0;
      this.year = 0;
      this.annotationIcon = null;

      Lt.viewer.doubleClickZoom.disable();
      $(Lt.viewer.getContainer()).click(e => {
        Lt.disableTools();
        Lt.collapseTools();
        this.createBtn.active = true; // disableTools() deactivates all buttons, need create annotation active

        // Prevent jQuery event error.
        if (!e.originalEvent) return;
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
    };
  };

  AnnotationAsset.prototype.disable = function (btn) {
    if (!btn) { // for Lt.disableTools()
      this.disable(this.createBtn);
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
      'initOpen': true,
      'position': 'topleft'
    }).setContent(content).addTo(Lt.viewer);

    // Set dialog close button to a check mark instead of X.
    let control_dialog_inner_parent = document.getElementById("tab").parentElement.parentElement;
    let control_dialog_close = control_dialog_inner_parent.getElementsByClassName("leaflet-control-dialog-close")[0];
    control_dialog_close.innerHTML = '<i class="fa fa-check" aria-hidden="true"></i>';

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
      'initOpen': true,
      'position': 'topleft'
    }).setContent(content).addTo(Lt.viewer);

    // Set dialog close button to a check mark instead of X.
    let control_dialog_inner_parent = document.getElementById("attributes-options").parentElement.parentElement;
    let control_dialog_close = control_dialog_inner_parent.getElementsByClassName("leaflet-control-dialog-close")[0];
    control_dialog_close.innerHTML = '<i class="fa fa-check" aria-hidden="true"></i>';

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
          alert("Attribute must have at least one option");
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
              alert("Attribute must have a title and all options must be named");
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

        $(checkbox).on("change", () => { // any checkbox changes are saved;
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
    $(this.markers[index]).on("click", () => {
      if (Lt.universalDelete.btn.active) { // deleteing
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
    $(textBox).on("input", () => { //  any text changes are saved
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

/**
 * Undo actions
 * @constructor
 * @param {Ltreering} Lt - Leaflet treering object
 */
function Undo(Lt) {
  this.stack = new Array();
  this.btn = new Button('undo', 'Undo', () => {
    this.pop();
    Lt.helper.updateFunctionContainer(false);
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
    let ellipse_points = JSON.parse(JSON.stringify(Lt.areaCaptureInterface.ellipseData.data));

    this.stack.push({'year': Lt.data.year, 'earlywood': Lt.data.earlywood,
      'index': Lt.data.index, 'points': restore_points, 'ellipses':  ellipse_points});
  };

  /**
   * Pop the last state from the stack, update the data, and push to the redo stack
   * @function pop
   */
  Undo.prototype.pop = function() {
    if (this.stack.length > 0) {
      if (Lt.data.points.length) {
        if (Lt.data.points[Lt.data.index - 1].start) {
          Lt.createPoint.disable();
        } else {
          Lt.mouseLine.from(Lt.data.points[Lt.data.index - 2].latLng);
        }
      }

      Lt.redo.btn.enable();

      var restore_points = JSON.parse(JSON.stringify(Lt.data.points));
      let ellipse_points = JSON.parse(JSON.stringify(Lt.areaCaptureInterface.ellipseData.data));

      Lt.redo.stack.push({'year': Lt.data.year, 'earlywood': Lt.data.earlywood,
        'index': Lt.data.index, 'points': restore_points, 'ellipses':  ellipse_points});
      var dataJSON = this.stack.pop();

      Lt.data.points = JSON.parse(JSON.stringify(dataJSON.points));

      Lt.data.index = dataJSON.index;
      Lt.data.year = dataJSON.year;
      Lt.data.earlywood = dataJSON.earlywood;

      Lt.visualAsset.reload();
      Lt.areaCaptureInterface.ellipseData.undo(dataJSON.ellipses);

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
    Lt.helper.updateFunctionContainer(false);
  });
  this.btn.disable();

  /**
   * Pop the last state in the stack and update data
   * @function pop
   */
  Redo.prototype.pop = function() {
    Lt.undo.btn.enable();
    var restore_points = JSON.parse(JSON.stringify(Lt.data.points));
    let ellipse_points = JSON.parse(JSON.stringify(Lt.areaCaptureInterface.ellipseData.data));

    Lt.undo.stack.push({'year': Lt.data.year, 'earlywood': Lt.data.earlywood,
      'index': Lt.data.index, 'points': restore_points, 'ellipses':  ellipse_points});
    var dataJSON = this.stack.pop();

    Lt.data.points = JSON.parse(JSON.stringify(dataJSON.points));

    Lt.data.index = dataJSON.index;
    Lt.data.year = dataJSON.year;
    Lt.data.earlywood = dataJSON.earlywood;

    Lt.visualAsset.reload();
    Lt.areaCaptureInterface.ellipseData.redo(dataJSON.ellipses);

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

    // Recalculate ellipse areas. 
    Lt.areaCaptureInterface.ellipseData.reloadJSON();
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
            Lt.metaDataText.updateText();
            this.disable();
          }
        });
      } else {
        var length = parseFloat(document.getElementById('length').value);
        this.calculatePPM(latLng_1, latLng_2, length);
        Lt.metaDataText.updateText();
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
    'Edit measurement point dating (Shift-d)',
    () => { Lt.disableTools(); Lt.collapseTools(); this.enable() },
    () => { this.disable() }
  );

  // enable with shift-d
  L.DomEvent.on(window, 'keydown', (e) => {
     if (e.keyCode == 68 && e.getModifierState("Shift") && !e.getModifierState("Control") && // 68 refers to 'd'
     window.name.includes('popout') && !Lt.annotationAsset.dialogAnnotationWindow) { // Dialog windows w/ text cannot be active 
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
      // Start points are "measurement" points when measuring backwards.
      // Need to provide way for users to "re-date" them.
      let pt_forLocation = Lt.data.points[i];
      if (i == 0 || !Lt.data.points[i - 1]) {
        alert("Cannot date first point. Select a different point to adjust dating");
        return;
      } else if (Lt.data.points[i].break || (Lt.data.points[i].start && Lt.data.points[i - 1].break)) {
        alert("Cannot date break points. Select a different point to adjust dating");
        return;
      } else if (Lt.data.points[i].start) {
        i--;
        if (!Lt.measurementOptions.forwardDirection) pt_forLocation = Lt.data.points[i + 1];
      }

      // Handlebars from templates.html
      this.index = i;
      let year = Lt.data.points[i].year;
      let content = document.getElementById("dating-template").innerHTML;
      let template = Handlebars.compile(content);
      let html = template({ date_year: year });

      this.popup = L.popup({closeButton: false})
          .setContent(html)
          .setLatLng(pt_forLocation.latLng)
          .openOn(Lt.viewer);

      document.getElementById('year_input').select();

      $(Lt.viewer.getContainer()).click(e => {
        this.popup.remove(Lt.viewer);
        this.disable();
      });
    }
  };

  /**
   * Dating action after new year entered
   * @function keypressAction
   */
  Dating.prototype.keypressAction = function(e) {
    let key = e.which || e.keyCode;
    if (key === 13) {
      this.active = false;
      let input = document.getElementById('year_input');
      let i = this.index;
      let year = Lt.data.points[i].year;

      var new_year = parseInt(input.value);
      this.popup.remove(Lt.viewer);

      if (!new_year && new_year != 0) {
        alert("Entered year must be a number");
        return
      }

      Lt.undo.push();

      function incrementYear(pt) {
        // Increment year if annual,                 latewood when measuring forward in time,                 or earlywood when measuring backward in time
        return (!Lt.measurementOptions.subAnnual || (Lt.measurementOptions.forwardDirection && !pt.earlywood) || (!Lt.measurementOptions.forwardDirection && pt.earlywood));
      }

      let shift = new_year - year;
      let pts_before = Lt.data.points.slice(0, i + 1);
      let year_diff = pts_before.filter(pb => pb.year && incrementYear(pb)).length;
      let pts_after = Lt.data.points.slice(i + 1);
      let dir_constant = (Lt.measurementOptions.forwardDirection) ? 1 : -1;

      // Delta is the starting count value. Need "jump start" value if...
      // ... expected to increment on next value. Special case if any ...
      // values before point are 0, then do not "jump start".
      let numOfZeroYears = pts_before.filter(e => e.year === 0).length;
      let delta = 0;
      if (numOfZeroYears && year != 0 && !incrementYear(Lt.data.points[i])) delta = -1;
      else if (!numOfZeroYears && incrementYear(Lt.data.points[i])) delta = 1;
      pts_before.map((pb, j) => {
        if (pb.year || pb.year == 0) {
          pb.year = new_year - dir_constant * (year_diff - delta);
          if (incrementYear(pb)) {
            delta++;
          }
        }
      })

      // Special case does no apply to after points.
      delta = 0;
      if (incrementYear(Lt.data.points[i])) delta = 1;
      pts_after.map((pa, k) => {
        if (pa.year || pa.year == 0) {
          pa.year = new_year + dir_constant * (delta);
          if (incrementYear(pa)) {
            delta++;
          }
        }
      })

      Lt.data.year += shift;
      Lt.visualAsset.reload();

      // Updates once user hits enter
      Lt.helper.updateFunctionContainer(true);

      this.disable();
    }
  }

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
    'Create measurement points (Shift-m)',
    () => { Lt.disableTools(); this.enable() },
    () => { this.disable() }
  );

  // create measurement w. shift-m
  L.DomEvent.on(window, 'keydown', (e) => {
     if (e.keyCode == 77 && e.getModifierState("Shift") && !e.getModifierState("Control") && 
     window.name.includes('popout') && !Lt.annotationAsset.dialogAnnotationWindow) { // Dialog windows w/ text cannot be active
       e.preventDefault();
       e.stopPropagation();
       Lt.disableTools();
       this.enable();
     }
  }, this);

  // resume measurement w. shift-k
  L.DomEvent.on(window, 'keydown', (e) => {
     if (e.keyCode == 75 && e.getModifierState("Shift") && !e.getModifierState("Control") && 
     Lt.data.points.length && window.name.includes('popout') && !Lt.annotationAsset.dialogAnnotationWindow) { // Dialog windows w/ text cannot be active
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

      // Prevent jQuery event error.
      if (!e.originalEvent) return;
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
        alert('Must be inserted at end of year');
        return;
      };

      Lt.data.points[Lt.data.index] = {'start': false, 'skip': false, 'break': false,
        'year': Lt.data.year, 'earlywood': firstEWCheck, 'latLng': latLng};
      Lt.visualAsset.newLatLng(Lt.data.points, Lt.data.index, latLng, false, true);
      Lt.data.index++;
      if (subAnnualIncrement) {
        Lt.data.points[Lt.data.index] = {'start': false, 'skip': false, 'break': false,
          'year': yearAdjustment, 'earlywood': secondEWCheck, 'latLng': latLng};
        Lt.visualAsset.newLatLng(Lt.data.points, Lt.data.index, latLng, false, true);
        Lt.data.index++;
      };

      if (yearsIncrease) {
        Lt.data.year++;
      } else if (yearsDecrease){
        Lt.data.year--;
      };

      // updates after point is inserted
      Lt.helper.updateFunctionContainer(true);

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
    () => { this.disable() }
  );

  L.DomEvent.on(window, 'keydown', (e) => {
     if (e.keyCode == 66 && e.getModifierState("Shift") && !e.getModifierState("Control") && // 66 refers to 'b'
     window.name.includes('popout') && !Lt.annotationAsset.dialogAnnotationWindow) { // Dialog windows w/ text cannot be active
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
  this.title = "Delete points: ";
  this.desc= "To delete existing points, you must adjust the dating of earlier or later points";
  this.optA = "shift dating of later points back in time";
  this.optB = "shift dating of earlier points forward in time";
  this.size = [280, 240];
  this.adjustOuter = false;
  this.selectedAdjustment = false;
  this.maintainAdjustment = false;

  /**
   * Open dialog for user to choose shift direction
   * @function openDialog
   */
  DeletePoint.prototype.openDialog = function(e, i) {
    if (this.maintainAdjustment || (i > 0 && Lt.data.points[i].start && Lt.data.points[i - 1].break) || Lt.data.points[i].break) {
      this.action(i);
    } else {
      Lt.helper.createEditToolDialog(e.containerPoint.x, e.containerPoint.y, i, "deletePoint");
    }
  };

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
}

/**
 * Delete several points on either end of a chronology
 * @constructor
 * @param {Ltreering} Lt - Leaflet treering object
 */
function Cut(Lt) {
  this.title = "Delete a series of points: ";
  this.desc = "To delete all points between two selected points, you must adjust the dating of earlier or later points.";
  this.optA = "shift dating of later points back in time";
  this.optB = "shift dating of earlier points forward in time";
  this.size = [280, 240];
  this.adjustOuter = false;
  this.selectedAdjustment = false;
  this.maintainAdjustment = false;

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
   * Open dialog for user to choose shift direction
   * @function openDialog
   */
  Cut.prototype.openDialog = function(e, i) {
    if (i == this.point) {
      alert('You cannot select the same point');
      this.disable()
      return;
    }

    if (this.maintainAdjustment || (Lt.data.points[i].start && Lt.data.points[i - 1].break) || Lt.data.points[i].break) {
      this.action(i);
    } else {
      Lt.helper.createEditToolDialog(e.containerPoint.x, e.containerPoint.y, i, "cut");
    }
  };

  /**
   * Enable cutting
   * @function enable
   */
  Cut.prototype.enable = function() {
    this.btn.state('active');
    this.active = true;
    this.selectedAdjustment = false;
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
    this.selectedAdjustment = false;
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
  this.title = "Insert points: ";
  this.desc = "To insert points along a path between two existing points, you must adjust the dating of earlier or later points.";
  this.optA = "shift dating of later points forward in time";
  this.optB = "shift dating of earlier points back in time";
  this.size = [280, 240];
  this.adjustOuter = false;
  this.selectedAdjustment = false;
  this.maintainAdjustment = false;

  this.active = false;
  this.btn = new Button(
    'add_circle_outline',
    'Insert a point between two other points (Shift-i)',
    () => { Lt.disableTools(); this.enable() },
    () => { this.disable() }
  );

  // enable w. shift-i
  L.DomEvent.on(window, 'keydown', (e) => {
     if (e.keyCode == 73 && e.getModifierState("Shift") && !e.getModifierState("Control") && // 73 refers to 'i'
     window.name.includes('popout') && !Lt.annotationAsset.dialogAnnotationWindow) { // Dialog windows w/ text cannot be active
       e.preventDefault();
       e.stopPropagation();
       Lt.disableTools();
       this.enable();
     }
  }, this);

  /**
   * Open dialog for user to choose shift direction
   * @function openDialog
   */
  InsertPoint.prototype.openDialog = function() {
    Lt.viewer.getContainer().style.cursor = 'pointer';

    $(Lt.viewer.getContainer()).click(e => {
      // Prevent jQuery event error.
      if (!e.originalEvent) return;
      let latLng = Lt.viewer.mouseEventToLatLng(e);
      if (this.maintainAdjustment) {
        this.action(latLng);
      } else {
        Lt.helper.createEditToolDialog(e.clientX, e.clientY, latLng, "insertPoint");
      }
    });
  };

  /**
   * Insert a point on click event
   * @function action
   */
  InsertPoint.prototype.action = function(latLng) {
    Lt.undo.push();
    let k = Lt.data.insertPoint(latLng);
    if (k != null) {
      Lt.visualAsset.newLatLng(Lt.data.points, k, latLng);
      Lt.visualAsset.reload();
    }

    // Uncommenting line below will disable tool after one use.
    // Currently it will stay enabled until user manually disables tool.
    //this.disable();
  }

  /**
   * Enable inserting points
   * @function enable
   */
  InsertPoint.prototype.enable = function() {
    this.btn.state('active');
    this.selectedAdjustment = false;
    this.openDialog();
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
    this.selectedAdjustment = false;
    if (Lt.helper.dialog) {
      Lt.helper.dialog.destroy();
      delete Lt.helper.dialog
    }
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
  this.title = "Convert to start point: ";
  this.desc = "To convert existing measurement points to a start point, you must adjust the dating of earlier or later points.";
  this.optA = "shift dating of later points back in time";
  this.optB = "shift dating of earlier points forward in time";
  this.size = [280, 240];
  this.adjustOuter = false;
  this.selectedAdjustment = false;
  this.maintainAdjustment = false;

  this.active = false;
  this.btn = new Button(
    'change_circle',
    'Change a measurement point to a start point',
    () => { Lt.disableTools(); this.enable() },
    () => { this.disable() }
  );

  /**
   * Open dialog for user to choose shift direction
   * @function openDialog
   */
  ConvertToStartPoint.prototype.openDialog = function(e, i) {
    // Cannot convert a point that is already a start point.
    // Start points are camouflage when measuring backwards.
    if (Lt.data.points[i].start || Lt.data.points[i].break ||
        (!Lt.measurementOptions.forwardDirection && Lt.data.points[i + 1] && Lt.data.points[i + 1].start)) {
      alert("Can only convert measurement points")
      return;
    }

    if (this.maintainAdjustment) {
      this.action(i);
    } else {
      Lt.helper.createEditToolDialog(e.containerPoint.x, e.containerPoint.y, i, "convertToStartPoint");
    }
  };

  ConvertToStartPoint.prototype.action = function (i) {
    Lt.undo.push();
    Lt.data.convertToStartPoint(i);
    Lt.visualAsset.reload();
  };

  ConvertToStartPoint.prototype.enable = function () {
    Lt.viewer.getContainer().style.cursor = 'pointer';
    this.btn.state('active');
    this.selectedAdjustment = false;
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
    this.selectedAdjustment = false;
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
  this.title = "Insert zero width year: ";
  this.desc = "To insert a zero width year, you must adjust the dating of earlier or later points.";
  this.optA = "shift dating of later points forward in time";
  this.optB = "shift dating of earlier points back in time";
  this.size = [280, 240];
  this.adjustOuter = false;
  this.selectedAdjustment = false;
  this.maintainAdjustment = false;

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
      Lt.visualAsset.reload();
    }

    this.disable();
  };

  /**
   * Open dialog for user to choose shift direction
   * @function openDialog
   */
  InsertZeroGrowth.prototype.openDialog = function(e, i) {
    if (this.maintainAdjustment) {
      this.action(i);
    } else {
      Lt.helper.createEditToolDialog(e.containerPoint.x, e.containerPoint.y, i, "insertZeroGrowth");
    }
  };

  /**
   * Enable adding a zero growth year
   * @function enable
   */
  InsertZeroGrowth.prototype.enable = function() {
    this.btn.state('active');
    this.active = true;
    this.selectedAdjustment = false;
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
    this.selectedAdjustment = false;
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
  this.firstLatLng = null;
  this.secondLatLng = null;
  this.closestFirstIndex = null;
  this.closestSecondIndex = null;

  this.active = false;
  this.btn = new Button(
    'broken_image',
    'Insert a within-year break and exclude that measurement increment',
    () => { Lt.disableTools(); this.enable() },
    () => { this.disable() }
  );

  /**
   * Insert a break after point i
   * @function action
   * @param i int - add the break point after index i
   */
  InsertBreak.prototype.action = function() {
    Lt.viewer.getContainer().style.cursor = 'pointer';

    $(Lt.viewer.getContainer()).click(e => {
      // Prevent jQuery event error.
      if (!e.originalEvent) return;

      // Check if click is for second break placement decision first.
      // Then assign first click placement value, etc..
      if (this.firstLatLng && !this.secondLatLng) {
        this.secondLatLng = Lt.viewer.mouseEventToLatLng(e);

        this.closestSecondIndex = Lt.helper.closestPointIndex(this.secondLatLng);
        if (!this.closestSecondIndex && this.closestSecondIndex != 0) {
          alert('New break points must be within existing points. Use the create toolbar to add new points to the series');
          return;
        };

        // Check if both break points would be placed in same point interval.
        // If not, throw alert and have user choose second point again.
        // Add/subtract 1 to closestFirstIndex to account for its own index since it was spliced in previously.
        // Adding & subtracting dependent on measurement direction.
        let adjustedFirstIndex = this.closestFirstIndex + 1;
        if (adjustedFirstIndex !== this.closestSecondIndex) {
          alert('Insert a within-year break and exclude that measurement increment: ' +
                'The newly inserted break points must be added between two already ' +
                'existing and temporally consecutive points. Also, the new points ' +
                'must be added in sequence consistent with that of the series measurement direction (forward or backward)');
          this.secondLatLng = null;
          this.closestSecondIndex = null;
          return;
        }

        // Snap inserted point to nearest polyline.
        if (!Lt.visualAsset.lines[i]) return;
        let secondLayerCoord = Lt.viewer.latLngToLayerPoint(this.secondLatLng)
        let secondSnapLayerCoord = Lt.visualAsset.lines[this.closestSecondIndex].closestLayerPoint(secondLayerCoord)
        let secondSnapLatLng = Lt.viewer.layerPointToLatLng(secondSnapLayerCoord)
        let secondBreakPt = {'start': true, 'skip': false, 'break': false, 'latLng': secondSnapLatLng};
        Lt.data.points.splice(this.closestSecondIndex, 0, secondBreakPt);
        Lt.data.index = Lt.data.points.length;
        Lt.visualAsset.reload();
        if (Lt.dataAccessInterface.popoutPlots.win) {
          Lt.dataAccessInterface.popoutPlots.sendData();
        }

        this.disable();
      } else if (!this.firstLatLng) {
        this.firstLatLng = Lt.viewer.mouseEventToLatLng(e);

        this.closestFirstIndex = Lt.helper.closestPointIndex(this.firstLatLng);
        if (!this.closestFirstIndex && this.closestFirstIndex != 0) {
          alert('New break points must be within existing points. Use the create toolbar to add new points to the series');
          this.firstLatLng = null;
          this.closestFirstIndex = null;
          return;
        };

        Lt.undo.push();

        // Snap inserted point to nearest polyline.
        if (!Lt.visualAsset.lines[i]) return;
        let firstLayerCoord = Lt.viewer.latLngToLayerPoint(this.firstLatLng)
        let firstSnapLayerCoord = Lt.visualAsset.lines[this.closestFirstIndex].closestLayerPoint(firstLayerCoord)
        let firstSnapLatLng = Lt.viewer.layerPointToLatLng(firstSnapLayerCoord)
        let firstBreakPt = {'start': false, 'skip': false, 'break': true, 'latLng': firstSnapLatLng};
        Lt.data.points.splice(this.closestFirstIndex, 0, firstBreakPt);
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
    this.firstLatLng = null;
    this.secondLatLng = null;
    this.closestFirstIndex = null;
    this.closestSecondIndex = null;

    this.action();
  };

  /**
   * Disable inserting a break point
   * @function disable
   */
  InsertBreak.prototype.disable = function() {
    $(Lt.viewer.getContainer()).off('click');
    this.btn.state('inactive');

    // Remove incomplete break point sets.
    if ((!this.secondLatLng || !this.closestSecondIndex) && this.closestFirstIndex) {
      Lt.data.points.splice(this.closestFirstIndex, 1);
      Lt.undo.stack.pop();
      Lt.visualAsset.reload();
    }

    this.active = false;
    this.firstLatLng = null;
    this.secondLatLng = null;
    this.closestFirstIndex = null;
    this.closestSecondIndex = null;

    Lt.viewer.getContainer().style.cursor = 'default';
    Lt.viewer.dragging.enable();
    Lt.mouseLine.disable();
  };
}

/**
 * Change color properties of image
 * @constructor
 * @param {Ltreering} Lt - Leaflet treering object
 */
function ImageAdjustment(Lt) {
  this.open = false;

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
    'initOpen': false,
    'position': 'topleft',
    'minSize': [0, 0]
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
    this.open = true;

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
    if (this.open) {
      this.dialog.unlock();
      this.dialog.close();
    }
    
    this.btn.state('inactive');
    this.open = false;
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
  this.dialog = L.control.dialog({
     'size': [510, 320],
     'anchor': [50, 5],
     'initOpen': false,
     'position': 'topleft',
     'minSize': [0, 0]
   }).setContent(content).addTo(Lt.viewer);

  return this.dialog;
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
 * Display assets meta data as text
 * @constructor
 * @param {Ltreering} Lt - Leaflet treering object
 */
function MetaDataText (Lt) {
  this.speciesID = Lt.meta.assetName; // empty string defaults to N/A
  Lt.viewer.on('zoomend', () => Lt.metaDataText.updateText());

  MetaDataText.prototype.initialize = function () {
    // Handlebars from templates.html.
    let metaDataTopDiv = document.createElement('div');

    // Specimin ID | ppm level | zoom level
    let content_A = document.getElementById("meta-data-top-div-template").innerHTML;
    metaDataTopDiv.innerHTML = content_A;
    document.getElementsByClassName('leaflet-bottom leaflet-left')[0].appendChild(metaDataTopDiv);

    // Start/end years | measurement options
    let content_B = document.getElementById("meta-data-middle-div-template").innerHTML;
    var metaDataMiddleDiv = document.createElement('div');
    metaDataMiddleDiv.innerHTML = content_B;
    document.getElementsByClassName('leaflet-bottom leaflet-left')[0].appendChild(metaDataMiddleDiv);

    // Save option | development source.
    let content_C = document.getElementById("meta-data-bottom-div-template").innerHTML;
    var metaDataBottomDiv = document.createElement('div');
    metaDataBottomDiv.innerHTML = content_C;
    document.getElementsByClassName('leaflet-bottom leaflet-left')[0].appendChild(metaDataBottomDiv);
  };

  MetaDataText.prototype.updateText = function () {
    let pts = JSON.parse(JSON.stringify(Lt.data.points));
    let firstPt = pts.find(e => (e && (e?.year || e?.year === 0)));
    let lastPt = pts.reverse().find(e => (e && (e?.year || e?.year === 0)));

    let startPt, endPt;
    if (firstPt?.year <= lastPt?.year) {
      startPt = firstPt;
      endPt = lastPt;
    } else if (firstPt?.year > lastPt?.year) {
      startPt = lastPt;
      endPt = firstPt;
      // Add 1 to keep points consistent with measuring forwards.
      startPt.year++;
      if (!Lt.measurementOptions.subAnnual) {
        endPt.year++;
      }
    }

    let years = '';
    let startAddition = '';
    let endAddition = '';
    if ((startPt?.year || startPt?.year == 0) && (endPt?.year || endPt?.year == 0)) {
      if (Lt.measurementOptions.subAnnual) {
        // Earlywood & latewood reversed when measuring backwards.
        let ew = (Lt.measurementOptions.forwardDirection) ? "E" : "L";
        let lw = (Lt.measurementOptions.forwardDirection) ? "L" : "E";
        startAddition = (startPt.earlywood) ? ew + " " : lw + " ";
        endAddition = (endPt.earlywood) ? " " + ew : " " + lw;
      }
      years = startAddition + String(startPt.year) + " — " + String(endPt.year) + endAddition + " &nbsp;|&nbsp; ";
    };

    let speciesID = this.speciesID + " &nbsp;|&nbsp; ";
    let branding = 'DendroElevator developed at <a href="http://z.umn.edu/treerings" target="_blank"> UMN </a>';
    let saveText = (Lt.meta.savePermission) ? Lt.dataAccessInterface.cloudUpload.saveText + " &nbsp;|&nbsp; " : '';
    let increment = (Lt.measurementOptions.subAnnual) ? 'sub-annual increments' : 'annual increments';
    let direction = (Lt.measurementOptions.forwardDirection) ? 'Measuring forward, ' : 'Measuring backward, ';

    let dpi = Lt.meta.ppm * 25.4;
    let ppmText = Math.round(Lt.meta.ppm).toLocaleString() + " p/mm (" + Math.round(dpi).toLocaleString() + " dpi) &nbsp;|&nbsp; "
    if (!Lt.meta.ppmCalibration && !Lt.options.ppm && !Lt.options.initialData.ppm && Lt.meta.ppm == Lt.defaultResolution) ppmText = "Resolution unknown &nbsp;|&nbsp; "

    let zoomPercentage = 100 * ((Lt.viewer.getZoom() - Lt.viewer.getMinZoom()) / (Lt.viewer.getMaxZoom() - Lt.viewer.getMinZoom()));
    let zoom = Math.round(zoomPercentage) + '% zoom';

    document.getElementById("meta-data-top-text").innerHTML = speciesID + ppmText + zoom;
    document.getElementById("meta-data-middle-text").innerHTML = years + direction + increment;
    document.getElementById("meta-data-bottom-text").innerHTML = saveText + branding;
  };
};

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

      console.log(panArray)
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
function downloadCSVFiles(Lt,TWoodcsvDataString,EWoodcsvDataString,LWoodcsvDataString) {
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
      'size': [310, 380],
      'anchor': anchor,
      'initOpen': true,
      'position': 'topleft',
      'minSize': [0, 0]
    }).addTo(Lt.viewer);

    // remember annotation location each times its moved
    $(this.dialog._map).on('dialog:moveend', () => { this.anchor = this.dialog.options.anchor } );

    const shortcutGuide = [
      {
       'key': 'Shift-l',
       'use': 'Toggle magnification loupe on/off',
      },
      {
       'key': 'Shift-m',
       'use': 'Create new measurement path',
      },
      {
       'key': 'Shift-k',
       'use': 'Resume last measurement path',
      },
      {
       'key': 'Shift-i',
       'use': 'Insert measurement point',
      },
      {
        'key': 'Shift-d',
        'use': 'Edit measurement point dating',
      },
      {
        'key': 'Shift-e',
        'use': 'Create new ellipse'
      },
      {
       'key': 'Shift-a',
       'use': 'Create new annotation',
      },
      {
       'key': 'Shift-b',
       'use': 'Create within-year break',
      },
      {
       'key': 'Shift-s',
       'use': 'Save changes to cloud (if permitted)',
      },
      {
       'key': 'Shift',
       'use': 'Enable cursor panning near edge',
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
      {
        'key': 'Enter or return',
        'use': 'Accept menu selection',
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
 * @param {leaflet object} - Lt
 */
function Helper(Lt) {

  /**
   * Creates dialog box for edit tools. Allows user to decide which direction points are shifted.
   * @function createEditToolDialog
   * @param {x position to open dialog} x
   * @param {y position to open dialog} y
   * @param {input into tool function} input
   * @param {string to access edit tool} tool
   **/
  Helper.prototype.createEditToolDialog = function(x, y, input, tool) {
    if (this.dialog) {
      $(this.dialog._map).off("dialog:closed");
      this.dialog.destroy();
      delete this.dialog
    }

    // Only create dialog window for user to choose which direction to shift points...
    // ... if choice not previously made since tool enabled and if user has not...
    // ... disabled adjustment choice.
    if (!this.dialog && !Lt[tool].selectedAdjustment) {
      // Handlebars from templates.html.
      let content = document.getElementById("edit-tools-shifting-dialog-template").innerHTML;
      let template = Handlebars.compile(content);
      let html = template({
        title: Lt[tool].title,
        desc: Lt[tool].desc,
        optionA: Lt[tool].optA,
        optionB: Lt[tool].optB,
      });
      let anchor = [y, x];
      this.dialog = L.control.dialog({
        'size': Lt[tool].size,
        'maxSize': [Number.MAX_SAFE_INTEGER, Number.MAX_SAFE_INTEGER],
        'anchor': anchor,
        'initOpen': true,
        'position': 'topleft',
        'minSize': [0, 0]
      }).setContent(html).addTo(Lt.viewer);
      // Set dialog close button to a check mark instead of X.
      let control_dialog_inner_parent = document.getElementById("shift-container").parentElement.parentElement;
      let control_dialog_close = control_dialog_inner_parent.getElementsByClassName("leaflet-control-dialog-close")[0];
      control_dialog_close.innerHTML = '<i class="fa fa-check" aria-hidden="true"></i>';

      // Default selection consistent with previous choice.
      document.getElementById("shift-radioA").checked = Lt[tool].adjustOuter;
      document.getElementById("shift-radioB").checked = !Lt[tool].adjustOuter;

      $(this.dialog._map).on("dialog:resizeend", () => { console.log(this.dialog) });
      $(this.dialog._closeNode).on("click", (e) => {
        if (this.dialog) {
          // Only have user select adjustment once per activation.
          Lt[tool].selectedAdjustment = true;
          Lt[tool].adjustOuter = document.getElementById("shift-radioA").checked;
          // If set to true, user maintains adjustment until page reloaded.
          Lt[tool].maintainAdjustment = document.getElementById("shift-checkbox").checked;

          // Need to remove event listener to update latlng used.
          // Otherwise, points become stuck at one location.
          $(this.dialog._map).off("dialog:closed");
          this.dialog.destroy();
          delete this.dialog

          Lt[tool].action(input);
        }
      });
    // Additional points only placed if dialog window not visible.
    } else if (!this.dialog) {
      Lt[tool].action(input);
    }
  }

  /**
   * Finds true distance between two points.
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

   // reverse array order so years ascending, but get ending year first
   let endPt_withYear = pts.find(e => !e.start && !e.break && (e.year || e.year === 0));
   pts.reverse();

   // change last point from start to end point
   if (pts[lastIndex] && pts[before_lastIndex]) {
     pts[lastIndex].start = false;
     pts[lastIndex].year =  (pref.subAnnual) ? endPt_withYear.year : endPt_withYear.year + 1;
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

     var disToBreak = 0;
     var prevPt = null;
     pts.map((e, i) => {
       if (!e) {
         return
       }
       if (e.start) {
         prevPt = e;
       } else if (e.break) {
         disToBreak = Lt.helper.trueDistance(prevPt.latLng, e.latLng);
       } else if (e.year || e.year == 0) {
         if (!yearArray.includes(e.year)) {
            yearArray.push(parseInt(e.year));
         }
         var width = Lt.helper.trueDistance(prevPt.latLng, e.latLng) + disToBreak;
         width = parseFloat(width.toFixed(5));
         if (e.earlywood && Lt.measurementOptions.subAnnual) {
           ewWidthArray.push(width);
         } else if (!e.earlywood && Lt.measurementOptions.subAnnual) {
           lwWidthArray.push(width)
         } else {
           twWidthArray.push(width)
         }
         disToBreak = 0;
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
   * @param
   */
   Helper.prototype.assignRowColor = function (e, y, Lt, lengthAsAString)
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

   Helper.prototype.updateFunctionContainer = function(reloadYears) {
    if (reloadYears == true) Lt.annotationAsset.reloadAssociatedYears();
    Lt.metaDataText.updateText();
    if (Lt.dataAccessInterface.popoutPlots.win) Lt.dataAccessInterface.popoutPlots.sendData();
    if (Lt.dataAccessInterface.viewData.active && Lt.dataAccessInterface.viewDataDialog?.dialog) Lt.dataAccessInterface.viewDataDialog.reload();
   }
};