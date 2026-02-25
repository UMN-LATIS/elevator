/**
 * Automatic Boundary Detection Sequence:
 * 1. User determines area of core to be analyzed for boundary detection
 *  - User can adjust start/end points, resolution level, height, and image adjustments
 * 2. Data Collection (function lives in TileLayer.GL.js)
 * 3. Boundary Detection Algorithm
 *  - a. Median blur is applied to specified color channel of image data
 *  - b. Threshold is applied to processed data for binary classification of pixels
 *  - c. Image data is swept to find transitions in binary values
 *  - d. Sets of transitions that can be traced from the top to the bottom of the detection area are stored as boundaries
 *  - e. Points within boundaries that lie at center of detection area are saved as boundary points (one per boundary)
 * 4. Boundary points saved, essentially the same as manual poits with different markers
 */


/**
 * Interface for auto ring detection and related tools
 * @constructor
 * 
 * @param {object} Lt - LTreering object from leaflet-treering.js. 
 */
function AutoRingDetectionInterface(Lt) {
    this.treering = Lt;
    this.autoRingDetection = new AutoRingDetection(this);
}

/**
 * Use GL Layer filters and ring detection algorithms to automatically
 * place points.
 * @constructor
 * 
 * @param {LTreering} Lt - Leaflet treering object
 */
function AutoRingDetection(Inte) {
    this.active = false;

    this.startLatLng = null;
    this.endLatLng = null;
  
    this.leftLatLng = null;
    this.rightLatLng = null;

    this.startMarker = L.marker(); //blank marker for event listeners
    this.endMarker = L.marker();
    
    this.detectionHeight = 0;
    this.detectionAreaOutline = [L.polyline([])];
    this.markers = [];  //Temporary markers for adjusting automatic detection settings
    this.imgData = [[]];
    this.u = {};
    this.boundaryPlacements = [];

    this.listenersOn = false;

    this.userImageSettings = {};
    this.detectionImageSettings = {
        brightness: 100,
        contrast: 150,
        sharpness: 0.05,
        emboss: 0.1,
        saturate: 100,
        edgeDetect: 0,
    };

    //Settings used in boundary detection that save between uses
    this.userDetectionSettings = {
      zoom: 0,
      zoomOnChange: false,
      boxHeight: 30,
      colorChannel: "intensity",
      blurRadius: 1,
      threshold: 80,
      markerColor: "#dfe615",
      edgeColor: "#02bfd1"
    }

    L.DomEvent.on(window, 'keydown', (e) => {
      if (e.keyCode == 70 && e.getModifierState("Shift") && !e.getModifierState("Control") && // 70 refers to 'f'
        window.name.includes('popout') && !Inte.treering.annotationAsset.dialogAnnotationWindow) { // Dialog windows w/ text cannot be active
           e.preventDefault();
           e.stopPropagation();
           Inte.treering.disableTools(); 
           this.enable();
        }
    });
  
    this.btn = new Button(
      'search',
      'Auto ring detection',
      () => { Inte.treering.disableTools(); this.enable() },
      () => { this.disable() }
    );

    let content = document.getElementById("AutoRingDetection-dialog-template").innerHTML;
    this.dialog = L.control.dialog({
      'size': [525, 200],
      'anchor': [50, 5],
      'initOpen': false,
      'position': 'topleft',
      'minSize': [475, 75],
      'maxSize': [Number.MAX_SAFE_INTEGER, 225]
    }).setContent(content).addTo(Inte.treering.viewer);
  
    /**
     * Turn on tool, either set preferences or outline area to detect from
     * @function
     */
    AutoRingDetection.prototype.enable = function () {
      this.active = true;

      //Save user's settings before changing to detection settings
      this.userImageSettings = Inte.treering.imageAdjustmentInterface.imageAdjustment.getCurrentViewJSON();
      this.btn.state('active');

      $(document).on('keyup', e => {
        var key = e.which || e.key;
        if (key === 'Escape') {
          this.disable();
        }
      });

      this.leftLatLng = null;
      this.rightLatLng = null;

      this.startLatLng = null;
      this.endLatLng = null;

      //Grab user's zoom level if checkbox checked
      if ($("#auto-ring-detection-zoom-change-check").is(":checked") || this.userDetectionSettings.zoom == 0) {
        let zoom = Math.floor(Inte.treering.viewer.getZoom());
        if (zoom > Inte.treering.getMaxNativeZoom()) {
          this.userDetectionSettings.zoom = Inte.treering.getMaxNativeZoom();
        } else {
          this.userDetectionSettings.zoom = zoom;
        }
      }

      //Set bounds for zoom input
      $("#auto-ring-detection-zoom-input").prop('max', Inte.treering.getMaxNativeZoom())
      $("#auto-ring-detection-zoom-input").prop('min', Inte.treering.viewer.getMinZoom())
      $("#auto-ring-detection-zoom-input").prop('value', this.userDetectionSettings.zoom);
      $("#auto-ring-detection-zoom-number-display").html(this.userDetectionSettings.zoom);

      $("#auto-ring-detection-box-placement").one('finishEnable', () => {
        this.dialog.open();
        this.setStartUpStyle();

        //For some reason, map container event is removed when 
        this.handleEndpointPlacement(); //Handles click events to place start and end points

        if (!this.listenersOn) { //Only need to enable event listens once
          this.enableEventListeners();
        }
      })
      
      if (!Inte.treering.measurementOptions.userSelectedPref && Inte.treering.data.year === 0) {
        Inte.treering.measurementOptions.enable();
      } else {
        $("#auto-ring-detection-box-placement").trigger('finishEnable');
      }
    }

    /**
     * Turn off tool, remove/revert visuals
     * @function
     */
    AutoRingDetection.prototype.disable = function () {
      if (this.active) {
        this.dialog.close();
        // this.dialog.remove();
  
        this.active = false;
        this.btn.state('inactive');
        Inte.treering.viewer.getContainer().style.cursor = 'default';
        if (Inte.treering.imageAdjustmentInterface.imageAdjustment.open) {
          Inte.treering.imageAdjustmentInterface.imageAdjustment.disable()
        }

        //Remove visuals
        for (let line of this.detectionAreaOutline) { line.remove() }
        this.startMarker.remove();
        this.endMarker.remove();
        for (let marker of this.markers) { marker.remove()};

        //Remove visuals/event listeners from detectionAreaPlacement
        if (Inte.treering.mouseLine.active) { Inte.treering.mouseLine.disable(); }

        Inte.treering.imageAdjustmentInterface.imageAdjustment.loadImageSettings(this.userImageSettings);

        $(Inte.treering.viewer.getContainer()).off('click')
      }
    }

    /**
     * Enables event listeners, including click events, dialog inputs, keydowns, etc.
     * @function
     */
    AutoRingDetection.prototype.enableEventListeners = function() {
      this.listenersOn = true;
      /**
       * Event listeners for box placement and dialog startup
      */

      //Listen for dialog closure
      $(this.dialog._closeNode).on("click", () => {
        this.disable();
      })
    
      //Instructions dialog open/close
      $("#auto-ring-detection-directions-toggle").on("click", () => {
        if ($("#auto-ring-detection-directions-text").is(":hidden")) {
          $("#auto-ring-detection-directions-toggle").css("color", "#0000ffff")
          $("#auto-ring-detection-directions-toggle").css("border-color", "#0000ffff")
          $("#auto-ring-detection-directions-text").show()
        } else {
          $("#auto-ring-detection-directions-toggle").css("color", "#000000ff")
          $("#auto-ring-detection-directions-toggle").css("border-color", "darkgrey")
          $("#auto-ring-detection-directions-text").hide()
        }
      });

      //Quick open image adjustments
      $("#auto-ring-detection-img-adjust-toggle").on("click", () => {
        let leftPosition = parseInt(this.dialog._container.style.left.slice(0,-2));

        if (Inte.treering.imageAdjustmentInterface.imageAdjustment.open) {
          Inte.treering.imageAdjustmentInterface.imageAdjustment.disable();
          if (leftPosition <= 325) {
            this.dialog._container.style.left = "5px"
          }
        } else {
          Inte.treering.imageAdjustmentInterface.imageAdjustment.enable();
          if (leftPosition <= 325) {
            this.dialog._container.style.left = "300px"
          }
        }
      });

      //Reset button
      $("#auto-ring-detection-reset").on("click", () => {
        if ($("#auto-ring-detection-point-placement").hasClass("ard-disabled-div")) {
          this.disable();
          this.enable();
        } else {
          $("#auto-ring-detection-point-placement").addClass("ard-disabled-div");
          $("#auto-ring-detection-box-placement").removeClass("ard-disabled-div");

          if ($("#auto-ring-detection-zoom-change-check").is(":checked")) {
            let newZoom = Math.floor(Inte.treering.viewer.getZoom());
            newZoom = newZoom > Inte.treering.getMaxNativeZoom() ? Inte.treering.getMaxNativeZoom() : newZoom;
            $("#auto-ring-detection-zoom-input").val(newZoom);
            $("#auto-ring-detection-zoom-number-display").html(newZoom);
            this.userDetectionSettings.zoom = newZoom;

            for (let line of this.detectionAreaOutline) { line.remove() }; //Remove outline

            //Create new outline if necessary points exist
            if (this.leftLatLng && this.rightLatLng) {
              corners = this.getDetectionGeometry().corners;
              this.detectionAreaOutline = this.createOutline(corners);          
            }
          }

          this.startMarker.addTo(Inte.treering.viewer);
          this.endMarker.addTo(Inte.treering.viewer);

          for (let marker of this.markers) {marker.remove()};
          this.markers = [];
        }
      });

      $("#auto-ring-detection-height-input").on("change", () => {
        for (let line of this.detectionAreaOutline) { line.remove() };

        let input = document.getElementById("auto-ring-detection-height-input"); //Grab input, min, max
        let val = parseInt(input.value);
        let boxHeight = this.calcBoxHeight(val);

        //Save height
        this.userDetectionSettings.boxHeight = boxHeight;

        //Update display
        $("#auto-ring-detection-box-height-number-display").html(this.userDetectionSettings.boxHeight)

        //Create new outline if necessary points exist
        if (this.leftLatLng && this.rightLatLng) {
          corners = this.getDetectionGeometry().corners;
          this.detectionAreaOutline = this.createOutline(corners);          
        }
      });
      
      $("#auto-ring-detection-zoom-input").on('change', () => {
        this.userDetectionSettings.zoom = Math.floor($("#auto-ring-detection-zoom-input").val());
        $("#auto-ring-detection-zoom-number-display").html(this.userDetectionSettings.zoom);
        if ($("#auto-ring-detection-zoom-change-check").is(':checked')) { Inte.treering.viewer.setZoom(this.userDetectionSettings.zoom) } //Zoom if user has setting toggled

        for (let line of this.detectionAreaOutline) { line.remove() }; //Remove outline

        //Create new outline if necessary points exist
        if (this.leftLatLng && this.rightLatLng) {
          corners = this.getDetectionGeometry().corners;
          this.detectionAreaOutline = this.createOutline(corners);          
        }
      });

      //If user zooms while zoom checkbox is checked, set slider to match zoom
      Inte.treering.viewer.on("zoomend", () => {
        if (this.active && $("#auto-ring-detection-zoom-change-check").is(":checked") && $("#auto-ring-detection-point-placement").hasClass("ard-disabled-div")) {
          let newZoom = Math.floor(Inte.treering.viewer.getZoom());
          newZoom = newZoom > Inte.treering.getMaxNativeZoom() ? Inte.treering.getMaxNativeZoom() : newZoom;
          $("#auto-ring-detection-zoom-input").val(newZoom);
          $("#auto-ring-detection-zoom-number-display").html(newZoom);
          this.userDetectionSettings.zoom = newZoom;
          // console.log(Inte.treering.getMaxNativeZoom())

          for (let line of this.detectionAreaOutline) { line.remove() }; //Remove outline

          //Create new outline if necessary points exist
          if (this.leftLatLng && this.rightLatLng) {
            corners = this.getDetectionGeometry().corners;
            this.detectionAreaOutline = this.createOutline(corners);          
          }
        }
      })

      //Make sure zoom and resolution match when checked
      $("#auto-ring-detection-zoom-change-check").on("change", e => {
        let newZoom = Math.floor(Inte.treering.viewer.getZoom())
        $("#auto-ring-detection-zoom-input").val(newZoom);
        $("#auto-ring-detection-zoom-number-display").html(newZoom);
        this.userDetectionSettings.zoom = newZoom;
      });
      
      //Save area button
      $("#auto-ring-detection-area-save-button").on("click", () => {this.saveDetectionBox()});

      /**
       * Event listeners for thresholding and point placement
       */
      let blurredData;
      let boundarySets = [];
      $("#auto-ring-detection-point-placement").on("select", () => {
        this.u = this.getUnitVector();
        blurredData = this.medianBlur(this.imgData, this.userDetectionSettings.blurRadius, this.userDetectionSettings.colorChannel);
        boundarySets = this.findBoundaryEdges(blurredData);
        this.boundaryPlacements = this.findBoundaryPoints(blurredData, boundarySets);
        this.showAutomaticPlacements(this.u, boundarySets, this.boundaryPlacements);

        if (this.boundaryPlacements && this.boundaryPlacements.length > 0) {
          $("#auto-ring-detection-placements-save-button").prop("disabled", false)
          $("#auto-ring-detection-placements-save-button").addClass("ard-button-ready");
        } else {
          $("#auto-ring-detection-placements-save-button").prop("disabled", true);
          $("#auto-ring-detection-placements-save-button").removeClass("ard-button-ready");
        }
      })

      $("#auto-ring-detection-blur-input").on("change", () => {
        this.userDetectionSettings.blurRadius = $("#auto-ring-detection-blur-input").val();
        $("#auto-ring-detection-blur-text").html(this.userDetectionSettings.blurRadius);
        
        blurredData = this.medianBlur(this.imgData, this.userDetectionSettings.blurRadius, this.userDetectionSettings.colorChannel);

        boundarySets = this.findBoundaryEdges(blurredData);
        this.boundaryPlacements = this.findBoundaryPoints(blurredData, boundarySets);
        this.showAutomaticPlacements(this.u, boundarySets, this.boundaryPlacements);

        if (this.boundaryPlacements && this.boundaryPlacements.length > 0) {
          $("#auto-ring-detection-placements-save-button").prop("disabled", false)
          $("#auto-ring-detection-placements-save-button").addClass("ard-button-ready");
        } else {
          $("#auto-ring-detection-placements-save-button").prop("disabled", true);
          $("#auto-ring-detection-placements-save-button").removeClass("ard-button-ready");
        }
      })

      $("#auto-ring-detection-global-threshold").on("change", () => {
        this.userDetectionSettings.threshold = $("#auto-ring-detection-global-threshold").val();
        $("#auto-ring-detection-global-threshold-text").html(this.userDetectionSettings.threshold);

        boundarySets = this.findBoundaryEdges(blurredData);
        this.boundaryPlacements = this.findBoundaryPoints(blurredData, boundarySets);
        this.showAutomaticPlacements(this.u, boundarySets, this.boundaryPlacements);

        if (this.boundaryPlacements && this.boundaryPlacements.length > 0) {
          $("#auto-ring-detection-placements-save-button").prop("disabled", false)
          $("#auto-ring-detection-placements-save-button").addClass("ard-button-ready");
        } else {
          $("#auto-ring-detection-placements-save-button").prop("disabled", true);
          $("#auto-ring-detection-placements-save-button").removeClass("ard-button-ready");
        }
      })

      $(".auto-ring-detection-channel-radio").on("change", (e) => {
        let colorChannel = e.currentTarget.value;
        this.userDetectionSettings.colorChannel = colorChannel;

        blurredData = this.medianBlur(this.imgData, this.userDetectionSettings.blurRadius, colorChannel);

        boundarySets = this.findBoundaryEdges(blurredData);
        this.boundaryPlacements = this.findBoundaryPoints(blurredData, boundarySets);
        this.showAutomaticPlacements(this.u, boundarySets, this.boundaryPlacements);

        if (this.boundaryPlacements && this.boundaryPlacements.length > 0) {
          $("#auto-ring-detection-placements-save-button").prop("disabled", false)
          $("#auto-ring-detection-placements-save-button").addClass("ard-button-ready");
        } else {
          $("#auto-ring-detection-placements-save-button").prop("disabled", true);
          $("#auto-ring-detection-placements-save-button").removeClass("ard-button-ready");
        }
      })

      $("#auto-ring-detection-point-color").on("change", () => {
        this.userDetectionSettings.markerColor = $("#auto-ring-detection-point-color").val()

        this.showAutomaticPlacements(this.u, boundarySets, this.boundaryPlacements);
        if (this.boundaryPlacements.length > 0) {
          $("#auto-ring-detection-placements-save-button").prop("disabled", false)
          $("#auto-ring-detection-placements-save-button").addClass("ard-button-ready");
        } else {
          $("#auto-ring-detection-placements-save-button").prop("disabled", true);
          $("#auto-ring-detection-placements-save-button").removeClass("ard-button-ready");
        }
      })

      $("#auto-ring-detection-edge-color").on("change", () => {
        this.userDetectionSettings.edgeColor = $("#auto-ring-detection-edge-color").val()

        this.showAutomaticPlacements(this.u, boundarySets, this.boundaryPlacements);
        if (this.boundaryPlacements.length > 0) {
          $("#auto-ring-detection-placements-save-button").prop("disabled", false)
          $("#auto-ring-detection-placements-save-button").addClass("ard-button-ready");
        } else {
          $("#auto-ring-detection-placements-save-button").prop("disabled", true);
          $("#auto-ring-detection-placements-save-button").removeClass("ard-button-ready");
        }
      })

      //Save placements button listener
      $("#auto-ring-detection-placements-save-button").on("click", () => {
        this.placePoints();
      });

      //Save Boundary Points
      L.DomEvent.on(window, "keydown", e => {
        if (this.active && e.key === "Enter" && $("#year_input")[0] === undefined) {
          if ($("#auto-ring-detection-point-placement").hasClass("ard-disabled-div")) {
            this.saveDetectionBox();
          } else if (this.boundaryPlacements > 0) {
            this.placePoints();
          }
        }
      });
    }

    /**
     * Makes necessary style changes and saves relevant data upon enabling
     * @function
     */
    AutoRingDetection.prototype.setStartUpStyle = function () {
      $("#auto-ring-detection-box-placement").removeClass("ard-disabled-div")
      $("#auto-ring-detection-point-placement").addClass("ard-disabled-div");
      Inte.treering.viewer.getContainer().style.cursor = 'pointer'; //Indicate first point can be placed
      Inte.treering.imageAdjustmentInterface.imageAdjustment.setDetectionSettings(this.detectionImageSettings);
      $("#auto-ring-detection-height-input").prop("disabled", true)
    }

    /**
     * Handles logic of placing endpoints of detection area
     * @function
     */
    AutoRingDetection.prototype.handleEndpointPlacement = function () {
      let forward = Inte.treering.measurementOptions.forwardDirection;
      let firstPoint = forward ? this.startLatLng : this.endLatLng;
      let secondPoint = forward ? this.endLatLng : this.startLatLng;
      let firstMarker = L.marker();
      let secondMarker = L.marker();

      $(Inte.treering.viewer.getContainer()).on("click", (e) => {
        if (!firstPoint) {
          firstPoint = Inte.treering.viewer.mouseEventToLatLng(e);
          let color = forward ? 'start' : 'dark_blue';

          firstMarker = L.marker(firstPoint, {
            icon: new MarkerIcon(color, Inte.treering.basePath),
            draggable: true
          }).addTo(Inte.treering.viewer);
          this.startMarker = firstMarker; //Store (possibly incorrectly) first point to remove later if needed

          firstMarker.on("dragend", () => {
            for (let line of this.detectionAreaOutline) { line.remove() } //Remove outline
            firstPoint = firstMarker._latlng;
            this.assignPoints(forward, firstPoint, secondPoint, firstMarker, secondMarker);

            corners = this.getDetectionGeometry().corners; //Get new outline
            this.detectionAreaOutline = this.createOutline(corners);
          });

          //Turn on mouseLine (line from point to cursor)
          Inte.treering.mouseLine.enable();
          Inte.treering.mouseLine.from(firstPoint);

        } else if (!secondPoint) {
          secondPoint = Inte.treering.viewer.mouseEventToLatLng(e);
          let color = forward ? 'dark_blue' : 'start';

          secondMarker = L.marker(secondPoint, {
            icon: new MarkerIcon(color, Inte.treering.basePath),
            draggable: true
          }).addTo(Inte.treering.viewer);

          secondMarker.on("dragend", () => {
            for (let line of this.detectionAreaOutline) { line.remove() } //Remove outline
            secondPoint = secondMarker._latlng;
            this.assignPoints(forward, firstPoint, secondPoint, firstMarker, secondMarker);

            corners = this.getDetectionGeometry().corners; //Get new outline
            this.detectionAreaOutline = this.createOutline(corners);
          });

          this.assignPoints(forward, firstPoint, secondPoint, firstMarker, secondMarker);
          
          //If year not yet chosen by user, use popup to get it
          if (Inte.treering.data.year === 0) { this.getYear(forward); }

          $("#auto-ring-detection-height-input").prop("disabled", false); //Allow height input to be changed

          //Allow box settings to be saved (requires first and second point to be placed)
          $("#auto-ring-detection-area-save-button").prop("disabled", false);
          $("#auto-ring-detection-area-save-button").addClass("ard-button-ready");
          
          Inte.treering.viewer.getContainer().style.cursor = 'default'; //Change cursor back to default
          Inte.treering.mouseLine.disable();

          //Create new outline if necessary points exist
          if (this.leftLatLng && this.rightLatLng) {
            corners = this.getDetectionGeometry().corners;
            this.detectionAreaOutline = this.createOutline(corners);          
          }
        }
      })
    }

    /**
     * Assigns user-placed latlng points to variables used in later functions
     * @param {boolean} forward Whether or not user is measuring forward or backward
     * @param {object} firstPoint First point (latlng) user placed, which isn't always the start point of measurements
     * @param {object} secondPoint Second point user placed
     * @param {object} firstMarker Marker associated with first point
     * @param {object} secondMarker Marker associated with second point
     */
    AutoRingDetection.prototype.assignPoints = function(forward, firstPoint, secondPoint, firstMarker, secondMarker) {
      this.startLatLng = forward ? firstPoint : secondPoint;
      this.endLatLng = forward ? secondPoint : firstPoint;
      this.startMarker = forward ? firstMarker: secondMarker;
      this.endMarker = forward ? secondMarker : firstMarker;

      //Assign left & right end points
      if (firstPoint.lng < secondPoint.lng) {
        this.leftLatLng = firstPoint;
        this.rightLatLng = secondPoint;
      } else {
        this.leftLatLng = secondPoint;
        this.rightLatLng = firstPoint;
      }
    }

    /**
     * Triggers subsequent steps upon user saving detection box (data collection, boundary placement algorithm)
     * @function
     */
    AutoRingDetection.prototype.saveDetectionBox = async function () {
      if (this.leftLatLng && this.rightLatLng) {
        let detectionGeometry = this.getDetectionGeometry();

        //Hide image adjust dialog
        Inte.treering.imageAdjustmentInterface.imageAdjustment.disable();
        // this.dialog._container.style.left = "5px"

        //Disable buttons
        $("#auto-ring-detection-box-placement").addClass("ard-disabled-div");

        //Get CSS image filters for data collection
        let cssFilters = Inte.treering.imageAdjustmentInterface.imageAdjustment.getCSSAdjustments();

        //Save the user's view
        let viewCenter = Inte.treering.baseLayer["GL Layer"]._map.getCenter();
        let viewZoom = Inte.treering.baseLayer["GL Layer"]._map.getZoom();

        //Save the user's image adjustments, specific to auto detection
        this.detectionImageSettings = Inte.treering.imageAdjustmentInterface.imageAdjustment.getCurrentViewJSON();

        //Save the user's zoom on change settings
        this.userDetectionSettings.zoomOnChange = $("#auto-ring-detection-zoom-change-check").is(':checked');

        //Collect data
        let data = await Inte.treering.baseLayer["GL Layer"].getImageData(detectionGeometry.corners, detectionGeometry.angle, this.userDetectionSettings.zoom, cssFilters);

        if (typeof data === "string") {
          $("#auto-ring-detection-box-placement").removeClass("ard-disabled-div");
        }
        else {
          this.imgData = data;

          //Remove visuals and image adjust dialog
          this.startMarker.remove();
          this.endMarker.remove();

          Inte.treering.viewer.getContainer().style.cursor = 'default';
          Inte.treering.baseLayer["GL Layer"]._map.flyTo(viewCenter, viewZoom, {animate: false}) //Return to view settings

          //Move on to adjust point placements
          $("#auto-ring-detection-point-placement").removeClass("ard-disabled-div");
          $("#auto-ring-detection-point-placement").trigger("select");
        }
      }
    }

    /**
     * Used to get start year if using auto ring detection on a core with no data
     * @param {boolean} forward Whether or not user is measuring forwards or backwards in time
     */
    AutoRingDetection.prototype.getYear = function(forward) {
      let placedPoint = forward ? this.startLatLng : this.endLatLng
      let content = document.getElementById("start-point-popup-template").innerHTML;
      var popup = L.popup({closeButton: false}).setContent(content)
        .setLatLng(placedPoint)
        .openOn(Inte.treering.viewer);

        document.getElementById('year_input').select();

        let yearSaved = false;
        $(document).on("keypress", e => {
          if (!e.originalEvent) return;

          var key = e.which || e.key;
          if (key === 13 && !yearSaved) {
            yearSaved = true;
            if (Inte.treering.measurementOptions.forwardDirection == false && Inte.treering.measurementOptions.subAnnual == false) {
              // must subtract one so newest measurment is consistent with measuring forward value
              // issue only applies to meauring backwards annually
              Inte.treering.data.year = parseInt(document.getElementById('year_input').value) - 1;
            } else  {
              Inte.treering.data.year = parseInt(document.getElementById('year_input').value);
            }
            popup.remove(Inte.treering.viewer);
          }
      });
    }

    /**
     * Creates rectangle showing detection area, with its color based on brightness
     * @function
     * 
     * @param {Array} corners - Array with 4 latLng objects
     * @returns - Leaflet polygon, for future removal
     */
    AutoRingDetection.prototype.createOutline = function(corners) {
      // let rectColor = $("#brightness-slider").val() > 150 ? "#121212ff" : "#a0a0a0ff"
      return [L.polygon(corners, {color: "#a0a0a0ff", weight: 3}).addTo(Inte.treering.viewer)]
    }

    /**
     * Finds corners of detection area and angle between start/end points
     * 
     * @param {Integer} detectionHeight - Height of detection area
     * @param {Integer} zoom - Map zoom level
     * @returns - Object with corners, angle of detection area
     */
    AutoRingDetection.prototype.getDetectionGeometry = function () {
      let detectionHeight = this.userDetectionSettings.boxHeight;
      let zoom = this.userDetectionSettings.zoom;

      let firstPixelCoords = Inte.treering.baseLayer["GL Layer"]._map.project(this.leftLatLng, zoom).floor();
      let secondPixelCoords = Inte.treering.baseLayer["GL Layer"]._map.project(this.rightLatLng, zoom).floor();

      let deltaX = secondPixelCoords.x - firstPixelCoords.x;
      let deltaY = secondPixelCoords.y - firstPixelCoords.y;
      let latLngPerPixel, angle;
      if (deltaX === 0) {
        latLngPerPixel = (this.rightLatLng.lat - this.leftLatLng.lat) / deltaY;
        angle = (deltaY > 0) ? Math.PI/2 : -Math.PI/2
      } else {
        latLngPerPixel = (this.rightLatLng.lng - this.leftLatLng.lng) / deltaX;
        angle = Math.atan(deltaY/deltaX);
      }
      
      let corners = [];
      let cornerLat = this.leftLatLng.lat - (detectionHeight / 2) * Math.cos(angle) * latLngPerPixel;
      let cornerLng = this.leftLatLng.lng - (detectionHeight / 2) * Math.sin(angle) * latLngPerPixel;
      corners.push(L.latLng(cornerLat, cornerLng))
      
      cornerLat = this.leftLatLng.lat + (detectionHeight / 2) * Math.cos(angle) * latLngPerPixel;
      cornerLng = this.leftLatLng.lng + (detectionHeight / 2) * Math.sin(angle) * latLngPerPixel;
      corners.push(L.latLng(cornerLat, cornerLng))
      
      cornerLat = this.rightLatLng.lat + (detectionHeight / 2) * Math.cos(angle) * latLngPerPixel;
      cornerLng = this.rightLatLng.lng + (detectionHeight / 2) * Math.sin(angle) * latLngPerPixel;
      corners.push(L.latLng(cornerLat, cornerLng))
      
      cornerLat = this.rightLatLng.lat - (detectionHeight / 2) * Math.cos(angle) * latLngPerPixel;
      cornerLng = this.rightLatLng.lng - (detectionHeight / 2) * Math.sin(angle) * latLngPerPixel;
      corners.push(L.latLng(cornerLat, cornerLng))

      return {corners: corners, angle: -angle}
    }

    /**
     * Performs horizontal and vertical sweeps of image data to find transitions in threshold value, then traces boundaries
     * 
     * @param {Array} imageData - 2D array of processed image data, where each point [i,j] represents the r, g, b, or intensity of the corresponding pixel
     * @returns boundarySets - Array of boundaries, which are arrays of points that make up the boundaries
     */
    AutoRingDetection.prototype.findBoundaryEdges = function(imageData) {
      let globalThreshold = this.userDetectionSettings.threshold;

      let l = imageData[0].length;
      let h = imageData.length;

      let transitions = [];
      let trs = {};
      let prevClass, currentClass;


      //Horizontal pass
      for (let i = 0; i < h; i++) {
        prevClass = imageData[i][0] <= globalThreshold ? "dark" : "bright"
        for (let j = 1; j < l; j++) {
          let intensity = imageData[i][j];
          currentClass = intensity <= globalThreshold ? "dark" : "bright";
          if (currentClass !== prevClass) {
            transitions.push([i, j]);
            trs[[i,j]] = currentClass;
          }
          prevClass = currentClass;
        }
      }

      //Vertical Pass
      for (let j = 0; j < l; j++) {
        prevClass = imageData[0][j] <= globalThreshold ? "dark" : "bright";
        for (let i = 1; i < h; i++) {
          let intensity = imageData[i][j];
          currentClass = intensity <= globalThreshold ? "dark" : "bright";
          if (currentClass !== prevClass && !transitions.includes([i,j])) {
            transitions.push([i, j]);
            trs[[i,j]] = currentClass;
          }
          prevClass = currentClass;
        }
      }

      let boundarySets = [];
      let edges;
      let trsInEdges = {}
      for (let point of transitions) {
        if (point[0] <= 1 || point[0] >= h - 1) {
          edges = this.traceEdge(trs, point, h)
          if (edges.length > 0) {
            for (let edge of edges) {
              for (let point of edge) {
                trsInEdges[point] = trs[point];
                delete trs[point];
              }
            }
            boundarySets = boundarySets.concat(edges);
          }
        }
      }

      return boundarySets
    }

    /**
     * Uses boundaries to identify specific points for each boundary that lie in center of data collection box
     * @param {Array} imageData 
     * @param {Array} boundarySets 
     * @returns boundaryPlacements - Array of boundary points
     */
    AutoRingDetection.prototype.findBoundaryPoints = function(imageData, boundarySets) {
      let l = imageData[0].length;
      let h = imageData.length;

      let boundaryPlacements = [];
      let y = Math.floor(h/2);

      for (let edge of boundarySets) {
        for (let i = 0; i < edge.length; i++) {
          let point = edge[i];
          if (point[0] == y) {
            boundaryPlacements.push(point)
            i = edge.length;
          }
        }
      }

      //If first/last edges are close to manually placed
      if (boundaryPlacements.length > 0) {
        if (boundaryPlacements[0][1] > 14) {
          boundaryPlacements = [[Math.floor(h/2), 0]].concat(boundaryPlacements)
        }

        if (boundaryPlacements.slice(-1)[0][1] < l - 14) {
          boundaryPlacements.push([Math.floor(h/2), l])
        }
      }

      return boundaryPlacements;
    }
    
    /**
     * Places temporary markers representing boundaries and boundary points
     * @function
     * 
     * @param {Object} u - unit vector of form {x: x, y: y} that represents direction from start to end point
     * @param {Array} boundarySets - Set of identified boundaries
     * @param {Array} boundaryPlacements - Boundary placements from detection algorithms
     */
    AutoRingDetection.prototype.showAutomaticPlacements = function(u, boundarySets, boundaryPlacements) {
      for (let marker of this.markers) {marker.remove()};
      this.markers = [];

      let leftLatLng = this.leftLatLng;

      for (let edge of boundarySets) {
        let edgeLatLngs = [];
        for (let point of edge) {
          let lat = leftLatLng.lat + point[1] * u.y + (-point[0] + this.userDetectionSettings.boxHeight/2) * u.x;
          let lng = leftLatLng.lng + point[1] * u.x - (-point[0] + this.userDetectionSettings.boxHeight/2) * u.y;
          let latLng = L.latLng(lat, lng);

          edgeLatLngs.push(latLng)

        }
        this.markers.push(L.polyline(edgeLatLngs, {color: this.userDetectionSettings.edgeColor}).addTo(Inte.treering.viewer))
      }

      for (let point of boundaryPlacements) {
        let lat = leftLatLng.lat + point[1] * u.y + (-point[0] + this.userDetectionSettings.boxHeight/2) * u.x;
        let lng = leftLatLng.lng + point[1] * u.x - (-point[0] + this.userDetectionSettings.boxHeight/2) * u.y;
        let latlng = L.latLng(lat, lng);

        this.markers.push(L.circleMarker(latlng, {color: this.userDetectionSettings.markerColor, fill: true, fillOpacity: 1, radius: 2}).addTo(Inte.treering.viewer))
      }
    }

    /**
     * Saves boundary placements (similar to manual placements) and accompanying data
     * @function
     */
    AutoRingDetection.prototype.placePoints = function() {
      for (let line of this.detectionAreaOutline) {line.remove();}
      for (let marker of this.markers) { marker.remove(); }

      let u = this.u;
      let boundaryPlacements = this.boundaryPlacements;

      let h = this.userDetectionSettings.boxHeight;
      let l = this.imgData[0].length;
      Inte.treering.undo.push();

      if (Inte.treering.measurementOptions.forwardDirection != (this.startLatLng == this.leftLatLng)) {
        boundaryPlacements = boundaryPlacements.reverse()
      }

      let leftLatLng = this.leftLatLng;

      let i = 0;
      for (let point of boundaryPlacements) {
        let lat = leftLatLng.lat + point[1] * u.y + (-point[0] + h/2) * u.x;
        let lng = leftLatLng.lng + point[1] * u.x - (-point[0] + h/2) * u.y;
        let latLng = L.latLng(lat, lng);

        let start = (i === 0) ? true : false;
        //If start/end points placed by user, they should have manual markers

        let auto = (point[1] === 0 || point[1] === l) ? false: true;
        Inte.treering.data.newPoint(start, latLng, auto);
        Inte.treering.visualAsset.newLatLng(Inte.treering.data.points, Inte.treering.data.index-1, latLng);
        i++;
      }
      Inte.treering.visualAsset.reload();

      this.disable()
    }
  
    /**
     * Calculates the unit vector with direction from first to second point
     * @function
     * 
     * @returns object in the form {x: int, y: int} representing the unit vector with the direction from the first to the second placed point
     */
    AutoRingDetection.prototype.getUnitVector = function() {
      let zoom = this.userDetectionSettings.zoom;
      let firstPixelCoords = Inte.treering.baseLayer["GL Layer"]._map.project(this.leftLatLng, zoom).floor();
      let secondPixelCoords = Inte.treering.baseLayer["GL Layer"]._map.project(this.rightLatLng, zoom).floor();
  
      let deltaX = secondPixelCoords.x - firstPixelCoords.x;
      let deltaY = secondPixelCoords.y - firstPixelCoords.y;
      let numPixels = (deltaX**2 + deltaY**2)**(1/2);
      let latLngPerPixel = (this.rightLatLng.lng - this.leftLatLng.lng) / deltaX
  
      let dx = -deltaX/numPixels * latLngPerPixel;
      let dy = deltaY/numPixels * latLngPerPixel;
  
      return {x: -dx, y: -dy}
    }

    /**
     * Uses a median blur on the image data to reduce noise when searching for boundaries
     * @function
     * 
     * @param {Array} data - Matrix of pixels in image, with each pixel represented by (r, g, b)
     * @param {Integer} r - Blur radius for kernel size, r=1 uses a 3x3 kernel, r=2 uses 5x5 etc.
     * @param {string} channel - Indicates which color channnel to use for blurring and user in later functions 
     * @returns 2D matrix of pixels, represented by avg rgb after blurring
     */
    AutoRingDetection.prototype.medianBlur = function (data, r, channel) {
      let h = data.length;
      let w = data[0].length;

      let channelWeights = {
        "intensity": [1/3, 1/3, 1/3],
        "r": [1, 0, 0],
        "g": [0, 1, 0],
        "b": [0, 0, 1]
      }
      let selectedChannel = channelWeights[channel]

      let intensityData = [];
      for (let i = 0; i < h; i++) {
        let row = [];
        for (let j = 0; j < w; j++) {
          let pixel = data[i][j];
          let value = selectedChannel[0] * pixel [0] + selectedChannel[1] * pixel[1] + selectedChannel[2] * pixel[2];
          row.push((value))
        }
        intensityData.push(row)
      }

      let blurData = []
      for (let i = 0; i < h; i++) {
        let row = [];
        for (let j = 0; j < w; j++) {
          let window = [];
          for (let ry = -r; ry <= r; ry++) {
            for (let rx = -r; rx <= r; rx++) {
              let ny = i + ry;
              if (ny < 0) {
                ny = 0;
              } else if (ny >= h) {
                ny = h - 1
              }

              let nx = j + rx;
              if (nx < 0) {
                nx = 0
              } else if (nx >= w) {
                nx = w - 1
              }

              window.push(intensityData[ny][nx])
            }
          }

          window.sort((a,b) => a - b)
          let median = window[window.length/2 - 0.5];
          row.push(median)
        }
        blurData.push(row)
      }

      return blurData
    }

    /**
     * Finds all points that connect to the start point, then finds the shortest path to the bottom of detection box
     * see https://www.geeksforgeeks.org/dsa/shortest-path-unweighted-graph/
     * 
     * @param {object} transitions - Object with transition points [i,j] as keys (values not used tbh, just easier than using an array)
     * @param {Array} start - Starting point [i,j] of search
     * @param {integer} h - height of detection box
     * @returns [optPath] - Array of points [i,j] that make up most efficient path from top to bottom
     */
    AutoRingDetection.prototype.traceEdge = function(transitions, start, h) {
      let edge = {};
      edge[start] = 0;
      let o2 = {0: start}
      let graph = [];
      let paths = [];
      let point = start;
      let edgeEnd = false;
      let sources = [];
      let destinations = [];
      let currentIndex = 0;
      let nextIndex = 1;

      while (!edgeEnd) {
        for (let x = -1; x <= 1; x++) {
          for (let y = -1; y <= 1; y++) {
            let check = [point[0] + y, point[1] + x]
            //If the check is a transition:
            // add the connection to the array (ie show that 0 is connected to 1), but don't connect to itself
            //Add the coordinates to the object to store the index of the point, if it hasn't already been added
            if (transitions[check]) {
              if (typeof(edge[check]) !== "number") {
                edge[check] = nextIndex;
                o2[nextIndex] = check
                paths.push(check)
                if (check[0] < 2) {sources.push(nextIndex)}
                if (check[0] > h - 2) {destinations.push(nextIndex)}
                nextIndex++
              }

              if (graph[currentIndex] && edge[check] != currentIndex) {
                graph[currentIndex].push(edge[check])
              } else if (edge[check] != currentIndex) {
                graph[currentIndex] = [edge[check]]
              }
            }

 
          }
        }
        if (paths.length == 0) {
          edgeEnd = true;
        } else {
          point = paths.pop();
          currentIndex = edge[point]
        }
      }


      if (sources.length > 0 && destinations.length > 0) {
        let optDist = Infinity;
        let optPath = [[]];
        for (let s of sources) {
          for (let d of destinations) {
            optDist = Infinity;
            optPath = [[]];
            if (s && d) {
              let vertexCount = nextIndex;
        
              let par = Array(vertexCount).fill(-1);
              let distance = Array(vertexCount).fill(Infinity)

              let q = [];
              distance[s] = 0;
              q.push(s);

              while (q.length > 0) {
                let node = q.shift();

                for (let neighbor of graph[node]) {
                  if (distance[neighbor] === Infinity) {
                    par[neighbor] = node;
                    distance[neighbor] = distance[node] + 1;
                    q.push(neighbor);
                  }
                }
              }

              let path = [d];
              let currentNode = d;
              while (par[currentNode] !== -1) {
                path.push(par[currentNode]);
                currentNode = par[currentNode]
              }
              path.push(s)

              let localOptPath = []
              for (let point of path) {
                localOptPath.push(o2[point])
              }
              // return [optimalPath]
              if (localOptPath.length < optDist) {
                optDist = localOptPath.length;
                optPath = localOptPath;
              }
            }
          }
        }
        return [optPath];
      } else {
        return [[]];
      }
    }

    /**
     * see https://www.geeksforgeeks.org/dsa/shortest-path-unweighted-graph/
     * @param {*} graph 
     * @param {*} S 
     * @param {*} par 
     * @param {*} dist 
     */
    AutoRingDetection.prototype.shortestPath = function(graph, S, par, dist) {
      let q = [];
      dist[S] = 0;
      q.push(S);

      while (q.length > 0) {
        let node = q.shift();

        for (let neighbor of graph[node]) {
          if (dist[neighbor] === Infinity) {
            par[neighbor] = node;
            dist[neighbor] = dist[node] + 1;
            q.push(neighbor)
          }
        }
      }
    }

    /**
     * Uses piecewise linear function to transform slider input to box height, resulting in a slider with
     * small step sizes near bottom of slider and large step sizer near top
     * @param {integer} x - Input of box height slider
     * @returns boxHeight - Height of box to be used later
     */
    AutoRingDetection.prototype.calcBoxHeight = function(x) {
      let boxHeight = 10;
      if (x <= 9) {
        boxHeight = 10 + 5 * x;
      } else if (x <= 14) {
        boxHeight = 50 + 10 * (x - 9);
      } else if (x <= 18) {
        boxHeight = 100 + 25 * (x - 14);
      } else if (x <= 22) {
        boxHeight = 200 + 50 * (x - 18);
      } else {
        boxHeight = 400 + 100 * (x - 22);
      }
      return boxHeight;
    }
  }