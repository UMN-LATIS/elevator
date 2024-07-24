/**
 * @file Leaflet Dating
 * @author Jessica Thorne <thorn572@umn.edu>
 * @version 1.0.0
 */

/**
 * Interface for dating tools. 
 * @constructor
 * 
 * @param {object} Lt - LTreering object from leaflet-treering.js. 
 */
function DatingInterface(Lt) {
    this.treering = Lt;

    this.dating = new Dating(this);
    this.datingPopup = new DatingPopup(this);

    this.btns = [this.dating.btn];
    this.tools = [this.dating];
}

/**
 * Set date of chronology.
 * @constructor
 * 
 * @param {object} Inte - DatingInterface objects. Allows access to DataAccess tools.
 */
function Dating(Inte) {
    this.active = false;
    this.btn = new Button(
      'access_time',
      'Edit measurement point dating (Shift-d)',
      () => { Inte.treering.disableTools(); Inte.treering.collapseTools(); this.enable() },
      () => { this.disable() }
    );
  
    // Enable with shift-d
    L.DomEvent.on(window, 'keydown', (e) => {
       if (e.keyCode == 68 && e.getModifierState("Shift") && !e.getModifierState("Control") && // 68 refers to 'd'
       window.name.includes('popout') && !Inte.treering.annotationAsset.dialogAnnotationWindow) { // Dialog windows w/ text cannot be active 
         e.preventDefault();
         e.stopPropagation();
         Inte.treering.disableTools();
         this.enable();
       }
    }, this);

    /**
     * Enable dating.
     * @function enable
     */
    Dating.prototype.enable = function() {
        this.btn.state('active');
        this.active = true;
        Inte.treering.viewer.getContainer().style.cursor = 'pointer';
      };
    
      /**
       * Disable dating.
       * @function disable
       */
      Dating.prototype.disable = function() {
        this.btn.state('inactive');
        $(Inte.treering.viewer.getContainer()).off('click');
        $(document).off('keypress');
        this.active = false;
        Inte.treering.viewer.getContainer().style.cursor = 'default';
      };
  
    /**
     * Open a text container for user to input date
     * @function action
     */
    Dating.prototype.action = function(i) {
        // Check if selected point valid for dating. 
        if (Inte.treering.data.points[i] != undefined) {
        // Start points are "measurement" points when measuring backwards.
        // Need to provide way for users to "re-date" them.
        let pt_forLocation = Inte.treering.data.points[i];
        if (i == 0 || !Inte.treering.data.points[i - 1]) {
            alert("Cannot date first point. Select a different point to adjust dating");
            return;
        } else if (Inte.treering.data.points[i].break || (Inte.treering.data.points[i].start && Inte.treering.data.points[i - 1].break)) {
            alert("Cannot date break points. Select a different point to adjust dating");
            return;
        } else if (Inte.treering.data.points[i].start) {
            i--;
            if (!Inte.treering.measurementOptions.forwardDirection) pt_forLocation = Inte.treering.data.points[i + 1];
        }

            this.index = i;
            Inte.datingPopup.openPopup(Inte.treering.data.points[i].year, pt_forLocation.latLng);
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

        let year = Inte.treering.data.points[this.index].year;
        let newYear = Inte.datingPopup.yearInput;
        let shift = newYear - year;
        Inte.datingPopup.closePopup();
  
        if (!newYear && newYear != 0) {
            alert("Entered year must be a number");
            return;
        }
  
        Inte.treering.undo.push();

        // Helpful constants: 
        let directionConstant = (Inte.treering.measurementOptions.forwardDirection) ? 1 : -1;
        let sliceStart, sliceEnd;

        // There are 3 shift options: 
        // 1 = Shift all points
        // 2 = Shift chronologically earlier points
        // 3 = Shift chronologically later points
        // 4 = Shift only selected point
        let points = [];
        switch(Inte.datingPopup.shiftOption) {
            case 1: 
                points = Inte.treering.data.points;
                break;
            case 2:
                // Slicing index depends on if core was measured forward or backward chronologically. 
                // If measured forward, earlier points are at smaller indices. 
                // If measured backward, earlier points are at larger indices.
                sliceStart = (directionConstant > 0) ? 0 : this.index;
                sliceEnd = (directionConstant > 0) ? this.index + 1 : Inte.treering.data.points.length;
                points = Inte.treering.data.points.slice(sliceStart, sliceEnd);
                break;
            case 3:
                // Same slicing caveat as above, but logic reversed. 
                sliceStart = (directionConstant > 0) ? this.index : 0;
                sliceEnd = (directionConstant > 0) ? Inte.treering.data.points.length : this.index + 1; 
                points = Inte.treering.data.points.slice(sliceStart, sliceEnd);
                break;
            case 4:
                points = [Inte.treering.data.points[this.index]];
        }
        this.shiftYears(shift, points);
        // Must shift additonal point in 2 special cases: 
        // 1) Subannual measurements, shift earlier, but select earlywood value. Must also adjust associated latewood. 
        // 2) Subannual measurements, shift later, but select latewood value. Must also adjust associated earlywood.
        // Direction of measuring changes which index these cases reference. 
        if (Inte.treering.measurementOptions.subAnnual) {
            let point = Inte.treering.data.points[this.index];
            let pointBefore = Inte.treering.data.points.slice(0, this.index).findLast((point) => (point?.year || point?.year == 0));
            let pointAfter = Inte.treering.data.points.slice(this.index + 1).find((point) => (point?.year || point?.year == 0));

            if (Inte.datingPopup.shiftOption == 2 && point.earlywood) {
                (directionConstant > 0) ? pointAfter.year += shift : pointBefore.year += shift;
            } else if (Inte.datingPopup.shiftOption == 3 && !point.earlywood) {
                (directionConstant > 0) ? pointBefore.year += shift : pointAfter.year += shift;
            }
        }
        
        Inte.treering.visualAsset.reload();
        // Updates once user hits enter.
        Inte.treering.helper.updateFunctionContainer(true);
        this.disable();
      }
    }

    /**
     * Checks whether point needs to be incremented when redating. 
     * @function
     * 
     * @param {object} pt - Measurement point created from leaflet.treering.js.  
     */
    Dating.prototype.checkIncrementYear = function(pt) {
        let annual = !Inte.treering.measurementOptions.subAnnual; // Measured annually. 
        let subAnnual = Inte.treering.measurementOptions.subAnnual; // Measured subannually (distinguish between early- and late-wood).
        let forward = Inte.treering.measurementOptions.forwardDirection; // Measured forward in time (1900 -> 1901 -> 1902).
        let backward = !Inte.treering.measurementOptions.forwardDirection// Measured backward in time (1902 -> 1901 -> 1900).

        // Increment year if annual, latewood when measuring forward in time, or earlywood when measuring backward in time.
        return (annual || (forward && !pt.earlywood) || (backward && pt.earlywood));
    }

    /**
     * Shifts a collection of points by a specified amount. 
     * @function
     * 
     * @param {integer} shift - Number of years to shift point.  
     * @param {array} points - Array of points that need adjustment. 
     */
    Dating.prototype.shiftYears = function(shift, points) {
        points.map((point) => {
            if (point && (point?.year || point?.year == 0)) point.year += shift;
        });
        Inte.treering.data.year += shift;
    }
}



/**
 * Generates popup related to cdating chronologies. 
 * @constructor
 * 
 * @param {object} Inte - DatingInterface objects. Allows access to DataAccess tools.
 */
function DatingPopup(Inte) {
    this.popup = null;
    this.yearInput = 0;
    // There are 3 shift options: 
    // 1 = Shift all points
    // 2 = Shift chronologically earlier points
    // 3 = Shift chronologically later points
    // 4 = Shift only selected point
    this.shiftOption = 1;

    /**
     * Opens popup with dating dialog.
     * @function
     * 
     * @param {integer} year - Current year of point.  
     * @param {object} location - Leaflet latlng location of point. 
     */
    DatingPopup.prototype.openPopup = function(year, location) {
        // Handlebars from templates.html
        let content = document.getElementById("Dating-template").innerHTML;
        let template = Handlebars.compile(content);
        let html = template({ date_year: year });
  
        this.popup = L.popup({closeButton: false})
            .setContent(html)
            .setLatLng(location)
            .openOn(Inte.treering.viewer);
        
        this.yearInput = year;
        this.setDefaults();
        this.createEventListeners();
    }

    /**
     * Closes popup.
     * @function
     */
    DatingPopup.prototype.closePopup = function() {
        this.popup.remove(Inte.treering.viewer);
    }

    /**
     * Creates all event listeners for HTML content within popup. 
     * @function
     */
    DatingPopup.prototype.createEventListeners = function() {
        $("#Dating-year-input").on("input", () => {
            this.yearInput = parseInt($("#Dating-year-input").val());
        });

        $("#Dating-shiftAll-radio").on("change", () => {
            this.shiftOption = 1;
        });

        $("#Dating-shiftEarlier-radio").on("change", () => {
            this.shiftOption = 2;
        });

        $("#Dating-shiftLater-radio").on("change", () => {
            this.shiftOption = 3;
        });

        $("#Dating-shiftSingle-radio").on("change", () => {
            this.shiftOption = 4;
        })
    }

    /**
     * Set default values for HTML content in popup. Maintains users previous choice.
     * @function
     */
    DatingPopup.prototype.setDefaults = function() {
        // Select previous option: 
        switch(this.shiftOption) {
            case 1: 
                $("#Dating-shiftAll-radio").trigger("click");
                break;
            case 2: 
                $("#Dating-shiftEarlier-radio").trigger("click");
                break;
            case 3: 
                $("#Dating-shiftLater-radio").trigger("click");
                break;
            case 4: 
                $("#Dating-shiftSingle-radio").trigger("click");
                break;
        }

        // Let year input be already selected. 
        document.getElementById("Dating-year-input").select();
    }
}