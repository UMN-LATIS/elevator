/**
 * @file Leaflet Area Capture 
 * @author Jessica Thorne <thorn573@umn.edu>
 * @version 1.0.0
 */

/**
 * Interface for area capture tools. Instantiates & connects all area or supporting tools. 
 * @constructor
 * 
 * @param {object} Lt - LTreering object from leaflet-treering.js. 
 */
function AreaCaptureInterface(Lt) {
    this.treering = Lt;
    this.calculator = new Calculator(this);

    this.ellipseData = new EllipseData(this);
    this.ellipseCSVDownload = new EllipseCSVDownload(this);
    this.ellipseVisualAssets = new EllipseVisualAssets(this);

    this.newEllipse = new NewEllipse(this); 
    this.newEllipseDialog = new NewEllipseDialog(this);

    this.lassoEllipses = new LassoEllipses(this);

    this.deleteEllipses = new DeleteEllipses(this);
    this.deleteEllipsesDialog = new DeleteEllipsesDialog(this);

    this.dateEllipses = new DateEllipses(this);
    this.dateEllipsesDialog = new DateEllipsesDialog(this);

    this.assistBoundaryLines = new AssistBoundaryLines(this);
    
    // Order in btns array dictates order in button dropdown in browser. 
    this.btns = [
        this.newEllipse.btn, 
        this.assistBoundaryLines.btn,
        this.lassoEllipses.btn, 
        this.dateEllipses.btn, 
        this.deleteEllipses.btn,
        this.ellipseCSVDownload.btn
    ];
    this.tools = [
        this.newEllipse, 
        this.assistBoundaryLines,
        this.lassoEllipses, 
        this.dateEllipses, 
        this.deleteEllipses
    ];
}

/**
 * Storage of ellipse points & related meta data.  
 * @constructor
 * 
 * @param {object} Inte - AreaCaptureInterface object. Allows access to all other tools.  
 */
function EllipseData(Inte) {
    this.year = 0;
    this.data = [];
    this.selectedData = [];

    /**
     * Saves data entry for a new ellipse. 
     * @function
     * 
     * @param {object} centerLatLng - Location of center of ellipse. 
     * @param {object} majorLatLng - Location of edge along major axis. 
     * @param {object} minorLatLng - Location of edge along minor axis.
     * @param {float} degrees - Rotation of ellipse from west or -x axis. 
     */
    EllipseData.prototype.saveEllipseData = function(centerLatLng, majorLatLng, minorLatLng, degrees) {
        let majorRadius = Inte.treering.helper.trueDistance(centerLatLng, majorLatLng);
        let minorRadius = Inte.treering.helper.trueDistance(centerLatLng, minorLatLng);
        let area = Math.PI * majorRadius * minorRadius;
        
        let color = (this.year % 10 == 0) ? Inte.ellipseVisualAssets.decadeColor : Inte.ellipseVisualAssets.ellipseBaseColor;

        let newDataElement = {
            "latLng": centerLatLng, 
            "majorLatLng": majorLatLng,
            "minorLatLng": minorLatLng, 
            "majorRadius": majorRadius,
            "minorRadius": minorRadius, 
            "degrees": degrees,
            "area": area,
            "year": this.year,
            "color": color,
            "selected": false,
        }

        this.data.push(newDataElement);
    }

    /**
     * Increase current year while maintaining decimal value.
     * @function
     */
    EllipseData.prototype.increaseYear = function() {
        let floatYear = parseFloat(this.year);
        let intYear = Math.floor(this.year);

        // Special case when transitioning from negative to positive years (crossing 0).
        if (intYear < floatYear) intYear++; 

        let trailingNums = parseFloat((floatYear - intYear).toFixed(2));

        // Special case cont.
        if (intYear == 0) trailingNums = Math.abs(trailingNums);

        let newYear = intYear + 1;
        this.year = newYear + trailingNums;
    }

    /**
     * Decrease current year while maintaining decimal value. 
     * @function
     */
    EllipseData.prototype.decreaseYear = function() {
        let floatYear = parseFloat(this.year);
        let intYear = Math.floor(this.year);

        // Special case when transitioning from positive to negative years (crossing 0).
        if (intYear > floatYear) intYear--; 

        let trailingNums = parseFloat((floatYear - intYear).toFixed(2));

        // Special case cont.
        if (intYear == 0) trailingNums = -1 * trailingNums;

        let newYear = intYear - 1;
        this.year = newYear + trailingNums;
    }

    /**
     * Get JSON data. 
     * 
     * @returns {list} List of ellipse data objects. 
     */
    EllipseData.prototype.getJSON = function() {
        return this.data;
    }

    /**
     * Load JSON data. 
     * @function
     * 
     * @param {string} JSONdata - All ellipse data to load. 
     */
    EllipseData.prototype.loadJSON = function(JSONdata) {
        this.data = JSON.parse(JSON.stringify(JSONdata));

        // JSON strips LatLng object of properties, need to recreate. 
        this.data.map(dat => {
            dat.latLng = L.latLng(dat.latLng.lat, dat.latLng.lng);
            dat.selected = false;
        });

        Inte.ellipseVisualAssets.reload();
    }

    /**
     * Reload JSON data. 
     * @function
     */
    EllipseData.prototype.reloadJSON = function() { 
        this.data.map(dat => {
            let majorRadius = Inte.treering.helper.trueDistance(dat.latLng, dat.majorLatLng);
            let minorRadius = Inte.treering.helper.trueDistance(dat.latLng, dat.minorLatLng);
            let area = Math.PI * majorRadius * minorRadius;

            dat.majorRadius = majorRadius;
            dat.minorRadius = minorRadius;
            dat.area = area;
            dat.selected = false;
        });

        Inte.ellipseVisualAssets.reload();
    }

    /**
     * Clear JSON data. 
     * @function
     */
    EllipseData.prototype.clearJSON = function() {
        this.data = [];
        this.reloadJSON();
    }

    /**
     * Undo recent changes.
     * @function
     * 
     * @param {string} JSONdata - All ellipse data to load. 
     */
    EllipseData.prototype.undo = function(JSONdata) {
        this.loadJSON(JSONdata);
    }

    /**
     * Redo recent changes.
     * @function
     * 
     * @param {string} JSONdata - All ellipse data to load. 
     */
    EllipseData.prototype.redo = function(JSONdata) {
        this.loadJSON(JSONdata);
    }
}

/**
 * Download ellipse points as CSV.
 * @constructor
 * 
 * @param {object} Inte - AreaCaptureInterface object. Allows access to all other tools. 
 */
function EllipseCSVDownload(Inte) {
    this.btn = new Button (
        'download',
        'Download elliptical data as CSV',
        () => { this.action() }
    );
    
    /**
     * Action for download.
     * @function
     */
    EllipseCSVDownload.prototype.action = function() {
        // Sort ellipses by year before downloading.
        Inte.ellipseData.data.sort((a, b) => {
            if (a.year < b.year) {
              return -1;
            }

            if (a.year > b.year) {
              return 1;
            }
          
            return 0;
          });

        let csvString = "year,area_mm2\n";
        for (let obj of Inte.ellipseData.data) {
            csvString += obj.year + "," + obj.area.toFixed(3) + "\n";
        }

        let zip = new JSZip();
        zip.file((Inte.treering.meta.assetName + '_ellipses.csv'), csvString)
        zip.generateAsync({type: 'blob'})
            .then((blob) => {
                saveAs(blob, (Inte.treering.meta.assetName + '_ellipses_csv.zip'));
            });
    }
}

/**
 * Manage visual assets related to ellipses.  
 * @constructor
 * 
 * @param {object} Inte - AreaCaptureInterface object. Allows access to all other tools.  
 */
function EllipseVisualAssets(Inte) {
    this.elements = [];
    this.selectedElements = [];
    
    this.ellipseLayer = L.layerGroup().addTo(Inte.treering.viewer);

    this.guideMarkerLayer = L.layerGroup().addTo(Inte.treering.viewer);
    this.guideLineLayer = L.layerGroup().addTo(Inte.treering.viewer);

    this.boundaryMarkerLayer = L.layerGroup().addTo(Inte.treering.viewer);
    this.boundaryLineLayer = L.layerGroup().addTo(Inte.treering.viewer);
    this.boundaryGuideLineLayer = L.layerGroup().addTo(Inte.treering.viewer);

    /* Full color scheme for when EW/LW difference enabled. 
    this.colorScheme = [
        "#b2df8a", // Light green
        "#33a02c", // Dark green
        "#fdbf6f", // Light orange
        "#ff7f00", // Dark orange
        "#cab2d6", // Light purple
        "#6a3d9a", // Dark purple
        "#e06f4c", // Light red
        "#db2314"  // Dark red
    ]
    */

    this.colorScheme = ["#33a02c", "#ff7f00", "#6a3d9a"] // Green, orange, purple. 
    this.colorIndex = 0;
    this.ellipseBaseColor = this.colorScheme[this.colorIndex];
    this.selectedEllipseColor = "#ffd92f";
    this.decadeColor = "#db2314";

    /**
     * Create a new ellipse element on Leaflet viewer. 
     * @function
     * 
     * @param {object} centerLatLng - Location of ellipse center. 
     * @param {object} majorLatLng - Location of major axis edge. 
     * @param {object} minorLatLng - Location of minor axis edge. 
     * @param {float} degrees - Rotation of ellipse in degrees. 
     * @param {integer} [year = Inte.ellipseData.year] - Year of ellipse to show when hovered over. 
     * @param {string} [color = this.ellipseBaseColor] - Color of ellipse ring and inner shade. 
     */
    EllipseVisualAssets.prototype.createEllipse = function(centerLatLng, majorLatLng, minorLatLng, degrees, year = Inte.ellipseData.year, color = this.ellipseBaseColor) {
        const latLngToMetersConstant = 111139;
        const majorRadius = Inte.calculator.distance(centerLatLng, majorLatLng) * latLngToMetersConstant;
        const minorRadius = Inte.calculator.distance(centerLatLng, minorLatLng) * latLngToMetersConstant;
        
        const majorRadiusScaled = Inte.treering.helper.trueDistance(centerLatLng, majorLatLng);
        const minorRadiusScaled = Inte.treering.helper.trueDistance(centerLatLng, minorLatLng);
        const area = (Math.PI * majorRadiusScaled * minorRadiusScaled).toFixed(3);

        if (year % 10 == 0) color = this.decadeColor;
        let ellipse = L.ellipse(centerLatLng, [majorRadius, minorRadius], degrees, {color: color, weight: 5}); 
        let center = L.marker(centerLatLng, { icon: L.divIcon({className: "fa fa-plus guide"}) }); 
        
        center.bindPopup(
            `T: ${year} <br>
            A: ${area} mm<sup>2</sup>`, 
            { closeButton: false }
            );
        ellipse.on('mouseover', function (e) {
            center.openPopup();
        });
        ellipse.on('mouseout', function (e) {
            center.closePopup();
        });

        this.ellipseLayer.addLayer(ellipse);
        this.ellipseLayer.addLayer(center);
        this.elements.push({
            "ellipse": ellipse, 
            "center": center,
        });
    }

    /**
     * Shift color cycle up/forward in color scheme array.
     * @function 
     */
    EllipseVisualAssets.prototype.cycleColorsUp = function() {
        this.colorIndex++;
        if (this.colorIndex > this.colorScheme.length - 1) {
            this.colorIndex = 0;
        }
        this.ellipseBaseColor = this.colorScheme[this.colorIndex];
    }

    /**
     * Shift color cycle down/backward in color scheme array.
     * @function 
     */
    EllipseVisualAssets.prototype.cycleColorsDown = function() {
        this.colorIndex--;
        if (this.colorIndex < 0) {
            this.colorIndex = this.colorScheme.length - 1;
        }
        this.ellipseBaseColor = this.colorScheme[this.colorIndex];
    }

    /**
     * Shift color cycle by a given year difference. 
     * @function 
     * 
     * @param {integer} year - New year value.
     */
    EllipseVisualAssets.prototype.cycleColorsMulti = function(year) {
        let n = this.colorScheme.length;
        this.colorIndex = Math.abs(Math.floor(year)) % n;
        this.ellipseBaseColor = this.colorScheme[this.colorIndex];
    }

    /**
     * Get color from color scheme cycle given a year. 
     * @function 
     * 
     * @param {integer} year - New year value.
     */
    EllipseVisualAssets.prototype.getColorFromCycle = function(year) {
        let n = this.colorScheme.length;
        let index = Math.abs(Math.floor(year)) % n;
        let color = this.colorScheme[index];

        return color;
    }

    /**
     * Creates mousemove event to create a guide line given an angle & line from the major axis. 
     * @function
     * 
     * @param {object} fromLatLng - Originating location of line to mouse. 
     * @param {float} [radiansFromMajorAxis = -1] - Optional, forces line to have a constant angle from the major axis. 
     * @param {object} [majorAxisLine = null] - Optional, informs direction of guideline (above or below major axis) with respect to mouse position. 
     */
    EllipseVisualAssets.prototype.createGuideLine = function(fromLatLng, radiansFromMajorAxis = -1, majorAxisLine = null) {
        $(Inte.treering.viewer.getContainer()).off("mousemove");
        $(Inte.treering.viewer.getContainer()).on("mousemove", e => {
            this.guideLineLayer.clearLayers();

            let eventLatLng = Inte.treering.viewer.mouseEventToLatLng(e);
            let toLatLngA = eventLatLng;
            let toLatLngB = eventLatLng;

            if (radiansFromMajorAxis > 0) {
                /* For a single guide line, direction of guide line is determined by if mouse is above or below the major axis. 
                let direction = (eventLatLng.lat > (majorAxisLine.slope * eventLatLng.lng + majorAxisLine.intercept)) ? 1 : -1;
                */

                let length = Inte.calculator.distance(fromLatLng, eventLatLng);

                toLatLngA = {
                    "lat": fromLatLng.lat + (1 * length * Math.sin(radiansFromMajorAxis)),
                    "lng": fromLatLng.lng + (1 * length * Math.cos(radiansFromMajorAxis)),
                };

                toLatLngB = {
                    "lat": fromLatLng.lat + (-1 * length * Math.sin(radiansFromMajorAxis)),
                    "lng": fromLatLng.lng + (-1 * length * Math.cos(radiansFromMajorAxis)),
                    };
            }
            
            let lineA = L.polyline([fromLatLng, toLatLngA], {color: 'red'});
            this.guideLineLayer.addLayer(lineA);

            let lineB = L.polyline([fromLatLng, toLatLngB], {color: 'red'});
            this.guideLineLayer.addLayer(lineB);

            let tipA = L.marker(toLatLngA, { icon: L.divIcon({className: "fa fa-plus guide"}) });
            this.guideLineLayer.addLayer(tipA);

            let tipB = L.marker(toLatLngB, { icon: L.divIcon({className: "fa fa-plus guide"}) });
            this.guideLineLayer.addLayer(tipB);
        })
    }

    /**
     * Creates a guide marker. 
     * @function
     * 
     * @param {object} latLng - Location to create marker. 
     * @returns {object} Created marker object. 
     */
    EllipseVisualAssets.prototype.createGuideMarker = function(latLng) {
        let marker = L.marker(latLng, { icon: L.divIcon({className: "fa fa-plus guide"}) });
        this.guideMarkerLayer.addLayer(marker);

        return marker;
    }

    /**
     * Draws line between two markers.  
     * @function
     * 
     * @param {object} fromLatLng - Starting location of line. 
     * @param {object} toLatLng - Ending location of line. 
     */
    EllipseVisualAssets.prototype.connectMarkerLatLngs = function(fromLatLng, toLatLng) {
        let line = L.polyline([fromLatLng, toLatLng], {color: 'red'});
        this.guideMarkerLayer.addLayer(line);
    }

    /**
     * Draw all ellipses to Leaflet map.  
     * @function
     */
    EllipseVisualAssets.prototype.reload = function() {
        this.clearEllipses();
        Inte.ellipseData.data.map(e => {
            this.createEllipse(e.latLng, e.majorLatLng, e.minorLatLng, e.degrees, e.year, e.color);
        })
    }

    /**
     * Clears all ellipses from Leaflet map. 
     * @function
     */
    EllipseVisualAssets.prototype.clearEllipses = function() {
        this.elements = [];
        this.ellipseLayer.clearLayers();
    }

    /**
     * Clears all guide markers (and related objects) from Leaflet map. 
     * @function
     */
    EllipseVisualAssets.prototype.clearGuideMarkers = function() {
        this.guideMarkerLayer.clearLayers();
    }

    /**
     * Clears all guide lines (and related objects) from Leaflet map.. 
     * @function
     */
    EllipseVisualAssets.prototype.clearGuideLines = function() {
        this.guideLineLayer.clearLayers();
    }
}

/**
 * Tool for capturing area with ellipses. 
 * @constructor
 * 
 * @param {object} Inte - AreaCaptureInterface object. Allows access to all other tools.
 */
function NewEllipse(Inte) {
    this.btn = new Button (
        'scatter_plot',
        'Create elliptical area measurements',
        () => { Inte.treering.disableTools(); this.enable() },
        () => { this.disable() },
    );

    // Shift-E to create a new ellipse. 
    L.DomEvent.on(window, 'keydown', (e) => {
        if (e.keyCode == 69 && e.getModifierState("Shift") && !e.getModifierState("Control") && 
        window.name.includes('popout') && !Inte.treering.annotationAsset.dialogAnnotationWindow) { // Dialog windows w/ text cannot be active
        e.preventDefault();
        e.stopPropagation();
        Inte.treering.disableTools();
        this.enable();
        }
    }, this);
    
    /**
     * Enable tool by activating button & starting event chain. 
     * @function
     */
    NewEllipse.prototype.enable = function() {
        this.btn.state('active');
        Inte.treering.viewer.getContainer().style.cursor = 'pointer';

        // Push change to undo stack: 
        Inte.treering.undo.push();

        Inte.newEllipseDialog.open();
        this.action();
    }

    /**
     * Disable tool by removing all events & setting button to inactive. 
     * @function
     */
    NewEllipse.prototype.disable = function() {
        this.btn.state('inactive');
        Inte.treering.viewer.getContainer().style.cursor = 'default';

        $(Inte.treering.viewer.getContainer()).off('click');
        Inte.ellipseVisualAssets.clearGuideMarkers();

        $(Inte.treering.viewer.getContainer()).off('mousemove');
        Inte.ellipseVisualAssets.clearGuideLines();

        Inte.newEllipseDialog.close();
    }

    /**
     * Drives events which create new ellipses. 
     * @function
     */
    NewEllipse.prototype.action = function() {
        let count = 0;
        let centerLatLng, majorLatLngA, majorLatLngB, minorLatLng;
        let radians, directionCorrection;

        $(Inte.treering.viewer.getContainer()).on("click", e => {
            // Prevent jQuery event error.
            if (!e.originalEvent) return;

            count++;
            switch (count) {
                case 1:
                    majorLatLngA = Inte.treering.viewer.mouseEventToLatLng(e);
                    Inte.ellipseVisualAssets.createGuideMarker(majorLatLngA);
                    Inte.ellipseVisualAssets.createGuideLine(majorLatLngA);
                    break;
                case 2:
                    majorLatLngB = Inte.treering.viewer.mouseEventToLatLng(e);
                    Inte.ellipseVisualAssets.createGuideMarker(majorLatLngB);
                    Inte.ellipseVisualAssets.connectMarkerLatLngs(majorLatLngA, majorLatLngB);

                    // Find center of major axis via midpoint: 
                    centerLatLng = L.latLng(
                        (majorLatLngA.lat + majorLatLngB.lat) / 2,
                        (majorLatLngA.lng + majorLatLngB.lng) / 2
                    );
                    Inte.ellipseVisualAssets.createGuideMarker(centerLatLng);

                    // Next guide line informs the minor axis. Minor axis must be 90 degrees from major axis. 
                    // Use CAH geometry rule to determine angle adjustment in radians: 
                    adjacentLatLng = L.latLng(centerLatLng.lat, majorLatLngB.lng);
                    radians = Math.acos(Inte.calculator.distance(centerLatLng, adjacentLatLng) / Inte.calculator.distance(centerLatLng, majorLatLngB));
                    // Returned radians value is always positive. If majorLatLngB is in the 2cd or 4th quadrent (in relation to centerLatLng),
                    // the radians must be multiplied by -1 to correct the rotation orientation. 
                    directionCorrection = (Inte.calculator.inSecondQuadrent(centerLatLng, majorLatLngB) || Inte.calculator.inFourthQuadrent(centerLatLng, majorLatLngB)) ? -1 : 1;
                    let rotatedRightRadians = (Math.PI / 2) + (directionCorrection * radians);

                    // Determine major axis line to calculate direction of minor axis guide line. 
                    let slope = (majorLatLngA.lat - majorLatLngB.lat) / (majorLatLngA.lng - majorLatLngB.lng);
                    let intercept = majorLatLngA.lat - (slope * majorLatLngA.lng);
                    let majorAxisLine = {
                        "slope": slope,
                        "intercept": intercept,
                    }

                    Inte.ellipseVisualAssets.createGuideLine(centerLatLng, rotatedRightRadians, majorAxisLine);
                    break;
                case 3:
                    // Push change to undo stack:
                    Inte.treering.undo.push();

                    minorLatLng = Inte.treering.viewer.mouseEventToLatLng(e);
                    Inte.ellipseVisualAssets.createGuideMarker(minorLatLng);

                    // Ellipse rotates from the -x axis, not the +x axis. Thus, the directionCorrection found above
                    // must be multipled by -1. 
                    let degrees = -directionCorrection * radians * (180 / Math.PI);

                    // Create ellipse & save meta data: 
                    Inte.ellipseVisualAssets.createEllipse(centerLatLng, majorLatLngB, minorLatLng, degrees);
                    Inte.ellipseData.saveEllipseData(centerLatLng, majorLatLngB, minorLatLng, degrees);

                    // Reset event series: 
                    count = 0;
                    $(Inte.treering.viewer.getContainer()).off('mousemove');
                    Inte.ellipseVisualAssets.clearGuideMarkers();
                    Inte.ellipseVisualAssets.clearGuideLines();
            }
        });
    }
}

/**
 * Generates dialog boxes related to creating new ellipses. 
 * @constructor
 * 
 * @param {object} Inte - AreaCaptureInterface object. Allows access to all other tools.
 */
function NewEllipseDialog(Inte) {
    let minWidth = 130;
    let minHeight = 130;
    this.size = [minWidth, minHeight];
    this.anchor = [50, 0];

    let html = document.getElementById("AreaCapture-incrementDialog-template").innerHTML;
    this.template = Handlebars.compile(html);
    let content = this.template({
        "year": Inte.ellipseData.year,
    });
    
    this.dialog = L.control.dialog({
        "size": this.size,
        "anchor": this.anchor,
        "initOpen": false,
        "position": 'topleft',
        "maxSize": [Number.MAX_SAFE_INTEGER, Number.MAX_SAFE_INTEGER],
        "minSize": [minWidth, minHeight],
    }).setContent(content).addTo(Inte.treering.viewer);
    this.dialog.hideClose();

    this.dialogOpen = false;
    this.eventsCreated = false;

    /**
     * Opens dialog window. 
     * @function
     */
    NewEllipseDialog.prototype.open = function() {
        this.dialog.open();
        this.dialogOpen = true;

        if (!this.eventsCreated) {
            this.createDialogEventListeners();
            this.createShortcutEventListeners();

            this.eventsCreated = true;
        }
    }

    /**
     * Closes dialog window.
     * @function
     */
    NewEllipseDialog.prototype.close = function() {
        this.dialog.close();
        this.dialogOpen = false;
    }

    /**
     * Updates dialog window HTML content. 
     * @function
     */
    NewEllipseDialog.prototype.update = function() {
        let content = this.template({
            "year": Inte.ellipseData.year,
        });

        // Once content updated, need to recreate element event listeners.
        this.dialog.setContent(content);
        this.createDialogEventListeners();
    }

    /**
     * Creates all event listeners for HTML elements in dialog window. 
     * @function
     */
    NewEllipseDialog.prototype.createDialogEventListeners = function () {
        // Year editing buttons: 
        $("#AreaCapture-editYear-btn").on("click", () => {
            let html = document.getElementById("AreaCapture-newYearDialog-template").innerHTML;
            let template = Handlebars.compile(html);
            let content = template({
                "year": Inte.ellipseData.year,
            });
            this.dialog.setContent(content);
            document.getElementById("AreaCapture-newYear-input").select();

            $("#AreaCapture-confirmYear-btn").on("click", () => {
                let year = $("#AreaCapture-newYear-input").val();
                if (year || year == 0) {
                    Inte.ellipseVisualAssets.cycleColorsMulti(year);
                    Inte.ellipseData.year = year;
                }
                this.update();
            })
        });

        $("#AreaCapture-subtractYear-btn").on("click", () => {
            Inte.ellipseData.decreaseYear();
            Inte.ellipseVisualAssets.cycleColorsDown();
            this.update();
        });

        $("#AreaCapture-addYear-btn").on("click", () => {
            Inte.ellipseData.increaseYear();
            Inte.ellipseVisualAssets.cycleColorsUp();
            this.update();
        });
    }

    /**
     * Creates all DOM event listeners - keyboard shortcuts.  
     * @function
     */
    NewEllipseDialog.prototype.createShortcutEventListeners = function () {
        // Keyboard short cut for subtracting year: Shift - 1
        L.DomEvent.on(window, 'keydown', (e) => {
            if (e.keyCode == 49 && e.shiftKey && !e.ctrlKey && this.dialogOpen) {
                Inte.ellipseData.decreaseYear();
                Inte.ellipseVisualAssets.cycleColorsDown();
                this.update();
            }
         }, this);

         // Keyboard short cut for confirming a new year: Shift - 2
        L.DomEvent.on(window, 'keydown', (e) => {
            if (e.keyCode == 50 && e.shiftKey && !e.ctrlKey && this.dialogOpen) {
                let year = $("#AreaCapture-newYear-input").val();
                if (year || year == 0) {
                    Inte.ellipseVisualAssets.cycleColorsMulti(year);
                    Inte.ellipseData.year = year;
                }
                this.update();
            }
         }, this); 

        // Keyboard short cut for adding year: Shift - 3
        L.DomEvent.on(window, 'keydown', (e) => {
            if (e.keyCode == 51 && e.shiftKey && !e.ctrlKey && this.dialogOpen) {
                Inte.ellipseData.increaseYear();
                Inte.ellipseVisualAssets.cycleColorsUp();
                this.update();
            }
         }, this);         
    }
}

/**
 * Tool for selecting (lassoing) one or more existing ellipses.  
 * @constructor
 * 
 * @param {object} Inte - AreaCaptureInterface object. Allows access to all other tools.
 */
function LassoEllipses(Inte) {
    this.active = false;
    this.eventListenersEnabled = false;
    this.btn = new Button (
        "lasso_select",
        "Lasso existing ellipses",
        () => {
            Inte.treering.disableTools(); 
            Inte.treering.collapseTools();
            this.enable() 
        },
        () => { this.disable() },
    );
    
    this.lasso = L.lasso(Inte.treering.viewer, {
        intersect: true,
        polygon: {
            color: "#FF0000", // Red coloring. 
            fillRule: "nonzero",
        }
    });

    /**
     * Enable tool & assign shotcuts upon first enable. 
     * @function
     */
    LassoEllipses.prototype.enable = function() {
        if (!Inte.ellipseData.data.length) {
            alert("Must create ellipses before lasso can be used.");
            return
        }

        this.btn.state('active');
        this.active = true;
        Inte.treering.viewer.getContainer().style.cursor = 'crosshair';

        if (!this.eventListenersEnabled) {
            // Only do once when instantiated.
            // Otherwise multiple listeners will be assigned. 
            this.createEventListeners();
            
            this.eventListenersEnabled = true;
        }

        this.action();
    }

    /**
     * Disable tool.  
     * @function
     */
    LassoEllipses.prototype.disable = function() {
        this.btn.state('inactive');
        this.active = false;
        Inte.treering.viewer.getContainer().style.cursor = 'default';

        this.lasso.disable();
    }

    /**
     * Creates keyboard shortcut event listeners. Based on file selection shortcuts.   
     * @function
     */
    LassoEllipses.prototype.createEventListeners = function() {
        Inte.treering.viewer.on('lasso.finished', lassoed => {
            this.selectEllipses(lassoed.layers);
            this.highlightSelected();

            // Timeout required to avoid disable() call from lasso-handler.ts. 
            setTimeout(() => this.lasso.enable(), 1);
        });
    }

    /**
     * Enables lasso plugin to select ellipeses then highlights each one.   
     * @function
     */
    LassoEllipses.prototype.action = function() {
        this.lasso.enable();
    }

    /**
     * Selects all data and elements based on which layers lassoed by user. 
     * @function
     * 
     * @param {array} layers - Array of Leaflet layers/HTML elements. 
     */
    LassoEllipses.prototype.selectEllipses = function(layers) {
        layers.map(layer => {
            // Ensures measurement markers not included in selection. 
            if (!(layer instanceof L.Marker)) {
                return
            }

            // Finds saved JSON data of ellipse based on latLng. 
            let data = Inte.ellipseData.data.find(dat => dat.latLng.equals(layer.getLatLng()));
            if (data && !data.selected) {
                data.selected = true;
                Inte.ellipseData.selectedData.push(data);

                // Finds saved Leaflet element of ellipse based on latLng. 
                let element = Inte.ellipseVisualAssets.elements.find(ele => ele.ellipse.getLatLng().equals(layer.getLatLng()));
                Inte.ellipseVisualAssets.selectedElements.push(element);
            } else {
                this.deselectEllipses(layer);
            }
        });
    }

    /**
     * Deselects all data and elements based on which layers lassoed by user. 
     * @function
     * 
     * @param {array} [layer = null] - Optional Leaflet layer. Include if only a single layer is to be deselected. 
     */
    LassoEllipses.prototype.deselectEllipses = function(layer = null) {
        // Deselect specific layer if specified. 
        if (layer) {
            let index = Inte.ellipseData.selectedData.findIndex(dat => dat.latLng.equals(layer.getLatLng()));
            if (index > -1) {
                Inte.ellipseData.selectedData[index].selected = false;
                Inte.ellipseData.selectedData.splice(index, 1);
                Inte.ellipseVisualAssets.selectedElements.splice(index, 1);
            }
            return
        }

        Inte.ellipseData.selectedData.map(dat => dat.selected = false);

        Inte.ellipseData.selectedData = [];
        Inte.ellipseVisualAssets.selectedElements = [];
    }

    /**
     * Highlights (changes element style) selected ellipses. 
     * @function
     */
    LassoEllipses.prototype.highlightSelected = function() {
        // Remove highlight from all before applying new color. 
        Inte.ellipseVisualAssets.elements.map((ele, i) => {
            ele.ellipse.setStyle({
                color: Inte.ellipseData.data[i].color,
            });
        });

        Inte.ellipseVisualAssets.selectedElements.map((ele, i) => {
            ele.ellipse.setStyle({
                color: Inte.ellipseVisualAssets.selectedEllipseColor,
            });
        });
    }

    /**
     * Dehighlights (reverts element style) selected ellipses. 
     * @function
     */
    LassoEllipses.prototype.dehighlightSelected = function() {
        Inte.ellipseVisualAssets.selectedElements.map((ele, i) => {
            ele.ellipse.setStyle({
                color: Inte.ellipseData.selectedData[i].color,
            });
        });
    }

    /**
     * Alerts user about editting abilities. 
     * @function
     */
    LassoEllipses.prototype.warning = function() {
        alert("Must have at least one ellipse selected to edit.");
    }
}

/**
 * Tool for deleting existing selected ellipses. 
 * @constructor
 * 
 * @param {object} Inte - AreaCaptureInterface object. Allows access to all other tools.
 */
function DeleteEllipses(Inte) {
    this.btn = new Button (
        'delete',
        'Delete selected ellipses',
        () => { Inte.treering.disableTools(); this.enable() },
        () => { this.disable() },
    );
    
    /**
     * Enables deletion, opens warning dialog. 
     * @function
     */
    DeleteEllipses.prototype.enable = function() {
        if (!Inte.ellipseData.selectedData.length) {
            Inte.lassoEllipses.warning();
            return
        }

        Inte.deleteEllipsesDialog.open();
    }

    /**
     * Disables deletion, closes warning dialog. 
     * @function
     */
    DeleteEllipses.prototype.disable = function() {
        Inte.deleteEllipsesDialog.close();
    }

    /**
     * Removes all selected data and elements from Leaflet map and storage arrays.  
     * @function
     */
    DeleteEllipses.prototype.action = function() {
        // Push change to undo stack: 
        Inte.treering.undo.push();

        Inte.ellipseData.data = Inte.ellipseData.data.filter(dat => !dat.selected);
        Inte.ellipseData.selectedData = [];

        Inte.ellipseVisualAssets.selectedElements.map(ele => {
            let index = Inte.ellipseVisualAssets.elements.findIndex(temp => temp.ellipse.getLatLng().equals(ele.ellipse.getLatLng()));
            Inte.ellipseVisualAssets.elements.splice(index, 1);
            
            Inte.ellipseVisualAssets.ellipseLayer.removeLayer(ele.ellipse);
            Inte.ellipseVisualAssets.ellipseLayer.removeLayer(ele.center);
        });
        Inte.ellipseVisualAssets.selectedElements = [];
    }

}

/**
 * Generates dialog boxes related to deleting existing ellipses. 
 * @constructor
 * 
 * @param {object} Inte - AreaCaptureInterface object. Allows access to all other tools.
 */
function DeleteEllipsesDialog(Inte) {
    let content = document.getElementById("AreaCapture-deleteSelectedEllipsesDialog-template").innerHTML;

    let size = [300, 260];
    this.fromLeft = ($(window).width() - size[0]) / 2;
    this.fromTop = ($(window).height() - size[1]) / 2;
    
    this.dialog = L.control.dialog({
        "size": size,
        "anchor": [this.fromTop, this.fromLeft],
        "initOpen": false,
        'position': 'topleft',
        "maxSize": [Number.MAX_SAFE_INTEGER, Number.MAX_SAFE_INTEGER],
    }).setContent(content).addTo(Inte.treering.viewer);
    this.dialog.hideClose();
    this.dialog.hideResize();

    this.eventsCreated = false;

    /**
     * Opens dialog window. 
     * @function
     */
    DeleteEllipsesDialog.prototype.open = function() {
        // Recenter dialog window. Otherwise anchor location remebered. 
        this.dialog.setLocation([this.fromTop, this.fromLeft]);
        this.dialog.open();

        if (!this.eventsCreated) {
            this.createDialogEventListeners();

            this.eventsCreated = true;
        }
        
    }

    /**
     * Closes dialog window. 
     * @function
     */
    DeleteEllipsesDialog.prototype.close = function() {
        this.dialog.close();
    }

    /**
     * Creates all event listeners for HTML elements in dialog window. 
     * @function
     */
    DeleteEllipsesDialog.prototype.createDialogEventListeners = function() {
        $("#AreaCapture-confirmDelete-btn").on("click", () => {
            Inte.deleteEllipses.action();
            this.close();
        });

        $("#AreaCapture-cancelDelete-btn").on("click", () => {
            this.close();
        });
    }
}

/**
 * Tool for dating existing selected ellipses. 
 * @constructor
 * 
 * @param {object} Inte - AreaCaptureInterface object. Allows access to all other tools.
 */
function DateEllipses(Inte) {
    this.btn = new Button (
        'access_time',
        'Date selected ellipses',
        () => { Inte.treering.disableTools(); this.enable() },
        () => { this.disable() },
    );

    // These strings are arbutary, just used to pair inputs & events. 
    this.forwardValue = "i";
    this.backwardValue = "j";
    this.individualValue = "k";

    /**
     * Enables dating, opens selection dialog. 
     * @function
     */
    DateEllipses.prototype.enable = function() {
        if (!Inte.ellipseData.selectedData.length) {
            Inte.lassoEllipses.warning();
            return
        }

        Inte.dateEllipsesDialog.open();
    }

    /**
     * Disables dating, opens selection dialog. 
     * @function
     */
    DateEllipses.prototype.disable = function() {
        Inte.dateEllipsesDialog.close();
    }

    /**
     * Dates all selected ellipses per the mode chosen by user.   
     * @function
     * 
     * @param {integer} year - New year value. 
     */
    DateEllipses.prototype.action = function(year) {
        let floatYear = parseFloat(year);
        let intYear = (year >= 0) ? Math.floor(year) : Math.ceil(year);
        let trailingNums = parseFloat((floatYear - intYear).toFixed(2));

        // Push change to undo stack: 
        Inte.treering.undo.push();

        let selectedValue = $('input[name="AreaCapture-dateRadioBtn-value"]:checked').val();
        
        let selectedYear = Inte.ellipseData.selectedData[0].year;
        let intSelectedYear = Math.floor(selectedYear);

        let deltaYear = intYear - intSelectedYear;

        let otherEllipses = [];
        switch(selectedValue) {
            case(this.forwardValue): {
                otherEllipses = Inte.ellipseData.data.filter(ele => ele.year > selectedYear);
                break;
            }
            case(this.backwardValue): {
                otherEllipses = Inte.ellipseData.data.filter(ele => ele.year < selectedYear);
                break;
            }
        }

        otherEllipses.map(ele => {
            let yearIntShift = Math.floor(ele.year) + deltaYear
            if (yearIntShift < 0) trailingNums = -1 * Math.abs(trailingNums); // Sign must be the same as year (+/-).
            ele.year = yearIntShift + trailingNums;

            ele.color = Inte.ellipseVisualAssets.getColorFromCycle(ele.year);
        });

        let color = Inte.ellipseVisualAssets.getColorFromCycle(year);
            Inte.ellipseData.selectedData.map(ele => {
                ele.year = year;
                ele.color = color;
            });

        Inte.lassoEllipses.deselectEllipses();
        Inte.ellipseVisualAssets.reload();
    }
}

/**
 * Generates dialog boxes related to dating existing ellipses. 
 * @constructor
 * 
 * @param {object} Inte - AreaCaptureInterface object. Allows access to all other tools.
 */
function DateEllipsesDialog(Inte) {
    let html = document.getElementById("AreaCapture-dateSelectedEllipsesDialog-template").innerHTML;
    this.template = Handlebars.compile(html);
    let content = this.template({
        "forwardValue": Inte.dateEllipses.forwardValue,
        "backwardValue": Inte.dateEllipses.backwardValue,
        "individualValue": Inte.dateEllipses.individualValue,
        "year": 0, 
        "shiftDisabled": true, 
    });

    let size = [360, 296];
    this.fromLeft = ($(window).width() - size[0]) / 2;
    this.fromTop = ($(window).height() - size[1]) / 2;
    
    this.dialog = L.control.dialog({
        "size": size,
        "anchor": [this.fromTop, this.fromLeft],
        "initOpen": false,
        'position': 'topleft',
        "maxSize": [Number.MAX_SAFE_INTEGER, Number.MAX_SAFE_INTEGER],
    }).setContent(content).addTo(Inte.treering.viewer);
    this.dialog.hideClose();
    this.dialog.hideResize();

    this.eventsCreated = false;
    this.dialogOpen = false;

    /**
     * Opens dialog window. 
     * @function
     */
    DateEllipsesDialog.prototype.open = function() {
        // Update dialog content with most recent items. 
        this.update();

        // Recenter dialog window. Otherwise anchor location remebered. 
        this.dialog.setLocation([this.fromTop, this.fromLeft]);
        this.dialog.open();
        this.dialogOpen = true;

        if (!this.eventsCreated) {
            // Only create shortcut listeners since update() creates event listenters.
            this.createShortcutEventListeners();

            this.eventsCreated = true;
        }
        
    }

    /**
     * Closes dialog window. 
     * @function
     */
    DateEllipsesDialog.prototype.close = function() {
        this.dialog.close();
        this.dialogOpen = false;
    }

    /**
     * Updates dialog window HTML content. 
     * @function
     */
    DateEllipsesDialog.prototype.update = function() {
        let year = Inte.ellipseData.selectedData[0].year;
        let uniqueYears = [...new Set(Inte.ellipseData.selectedData.map(ele => ele.year))];
        let multipleYearsSelected = uniqueYears.length > 1;

        let content = this.template({
            "forwardValue": Inte.dateEllipses.forwardValue,
            "backwardValue": Inte.dateEllipses.backwardValue,
            "individualValue": Inte.dateEllipses.individualValue,
            "year": year,
            "shiftDisabled": multipleYearsSelected,
        });

        // Once content updated, need to recreate element event listeners. 
        this.dialog.setContent(content);
        this.createDialogEventListeners();
    }

    /**
     * Creates all event listeners for HTML elements in dialog window. 
     * @function
     */
    DateEllipsesDialog.prototype.createDialogEventListeners = function() {
        $("#AreaCapture-confirmDate-btn").on("click", () => {
            let year = parseFloat($("#AreaCapture-newDate-input").val());
            if (year || year == 0) {
                Inte.dateEllipses.action(year);
            }
            this.close();
        });

        $("#AreaCapture-cancelDate-btn").on("click", () => {
            this.close();
        });
    }

    /**
     * Creates all DOM event listeners - keyboard shortcuts.  
     * @function
     */
    DateEllipsesDialog.prototype.createShortcutEventListeners = function () {
        // Keyboard short cut for confirming new date: Ctrl - 2
        L.DomEvent.on(window, 'keydown', (e) => {
            if (e.keyCode == 50 && !e.shiftKey && e.ctrlKey && this.dialogOpen) {
                Inte.dateEllipses.action();
                this.close();
            }
         }, this);        
    }
}

/**
 * Draws two parallel lines to bound where ellipses are drawn.  
 * @constructor
 * 
 * @param {object} Inte - AreaCaptureInterface object. Allows access to all other tools.
 */
function AssistBoundaryLines(Inte) {
    this.btn = new Button (
        'text_select_move_forward_word',
        'Draw boundary lines (currently cannot be removed)',
        () => { this.enable() },
        () => { this.disable() },
    );
    
    /**
     * Enables boundary drawing. 
     * @function
     */
    AssistBoundaryLines.prototype.enable = function() {
        this.btn.state('active');
        Inte.treering.viewer.getContainer().style.cursor = 'pointer';

        this.placeGuideMarker();
    }

    /**
     * Disables boundary drawing. 
     * @function
     */
    AssistBoundaryLines.prototype.disable = function() {
        this.btn.state('inactive');
        Inte.treering.viewer.getContainer().style.cursor = 'default';
        $(Inte.treering.viewer.getContainer()).off('click');

        Inte.ellipseVisualAssets.boundaryMarkerLayer.clearLayers();
        Inte.ellipseVisualAssets.boundaryGuideLineLayer.clearLayers();
    }

    /**
     * Places marker and allows user to decide width of lines. 
     * @function
     */
    AssistBoundaryLines.prototype.placeGuideMarker = function() {
        $(Inte.treering.viewer.getContainer()).on('click', (e) => {
            $(Inte.treering.viewer.getContainer()).off('click');

            let latlng = Inte.treering.viewer.mouseEventToLatLng(e);
            this.marker = L.marker(latlng, { icon: L.divIcon({className: "fa fa-plus guide"}) }); 
            Inte.ellipseVisualAssets.boundaryMarkerLayer.addLayer(this.marker);

            let content = document.getElementById("AreaCapture-guideMarkerWidth-template").innerHTML;
            this.marker.bindPopup(content, {closeButton: false, closeOnEscapeKey: false, closeOnClick: false}).openPopup();
            document.getElementById("AreaCapture-guideWidth-input").select();

            // Close dialog box with enter/return and draw guide lines. 
            L.DomEvent.on(window, 'keydown', this.drawGuideLines, this);
        });
    }

    /**
     * Draws temporary guidelines for user to rotate around previously decided anchor. 
     * @function
     * 
     * @param {object} event - Keydown event
     */
    AssistBoundaryLines.prototype.drawGuideLines = function(event) {
        // Keycode 13 refers to Enter/Return buttons. 
        if (event.keyCode == 13) {
            let viewer = Inte.treering.viewer;
            let halfWidthMillimeter = parseFloat(document.getElementById("AreaCapture-guideWidth-input").value) / 2; 
            let halfWidthPixel = Inte.treering.meta.ppm * halfWidthMillimeter;

            this.marker.closePopup();
            let markerPoint = viewer.project(this.marker.getLatLng(), Inte.treering.getMaxNativeZoom());

            let topLine, bottomLine;
            let opacity = "0.75";
            let color  = "#49c4d9";
            let weight = "5";
            
            // Mouse movement events: 
            $(viewer.getContainer()).on("mousemove", e => {
                Inte.ellipseVisualAssets.boundaryGuideLineLayer.clearLayers();

                // Get four locations of points for guidelines. Order of points determines rotation. 
                // Based on: https://math.stackexchange.com/questions/2043054/find-a-point-on-a-perpendicular-line-a-given-distance-from-another-point
                let mousePoint = viewer.project(viewer.mouseEventToLatLng(e), Inte.treering.getMaxNativeZoom());

                let slope = (mousePoint.y - markerPoint.y) / (mousePoint.x - markerPoint.x);
                if (slope == 0) slope = 0.00000001; // Set slope to be near 0 to avoid dividing by 0 errors. 
                let addative = Math.sqrt((halfWidthPixel**2) / (1 + (1/(slope**2))));

                let topLeftX = markerPoint.x + addative;
                let topRightX = mousePoint.x + addative;
                let bottomLeftX = markerPoint.x - addative;
                let bottomRightX = mousePoint.x - addative;

                let topLeftY = ((-1/slope) * (topLeftX - markerPoint.x)) + markerPoint.y;
                let topRightY = ((-1/slope) * (topRightX - mousePoint.x)) + mousePoint.y;
                let bottomLeftY = ((-1/slope) * (bottomLeftX - markerPoint.x)) + markerPoint.y;
                let bottomRightY = ((-1/slope) * (bottomRightX - mousePoint.x)) + mousePoint.y;

                let topLeftLatLng = viewer.unproject([topLeftX, topLeftY], Inte.treering.getMaxNativeZoom());
                let topRightLatLng = viewer.unproject([topRightX, topRightY], Inte.treering.getMaxNativeZoom());
                let bottomLeftLatLng = viewer.unproject([bottomLeftX, bottomLeftY], Inte.treering.getMaxNativeZoom());
                let bottomRightLatLng = viewer.unproject([bottomRightX, bottomRightY], Inte.treering.getMaxNativeZoom());

                topLine = L.polyline([topLeftLatLng, topRightLatLng], {
                    interactive: false, 
                    color: color, 
                    opacity: opacity,
                    weight: weight
                });
                Inte.ellipseVisualAssets.boundaryGuideLineLayer.addLayer(topLine);

                let mainLine = L.polyline([this.marker.getLatLng(), viewer.mouseEventToLatLng(e)], {
                    interactive: false, 
                    color: color, 
                    opacity: opacity,
                    weight: weight
                });
                Inte.ellipseVisualAssets.boundaryGuideLineLayer.addLayer(mainLine);

                bottomLine = L.polyline([bottomLeftLatLng, bottomRightLatLng], {
                    interactive: false, 
                    color: color, 
                    opacity: opacity,
                    weight: weight
                });
                Inte.ellipseVisualAssets.boundaryGuideLineLayer.addLayer(bottomLine);
            });

            // Click events: 
            $(Inte.treering.viewer.getContainer()).on('click', (e) => {
                if (topLine && bottomLine) this.placeBoundaryLines(topLine, bottomLine);
            });
        }
    }

    /**
     * Draws boundary lines on semi-permanent layer. 
     * @function
     * 
     * @param {object} top - Leaflet polyline for top boundary.
     * @param {*} bot - Leaflet polyline for bottom boundary. 
     */
    AssistBoundaryLines.prototype.placeBoundaryLines = function(top, bot) {
        // Remove placement events. 
        $(Inte.treering.viewer.getContainer()).off('mousemove');
        $(Inte.treering.viewer.getContainer()).off('click');

        // Clear marker & guide lines. 
        Inte.ellipseVisualAssets.boundaryMarkerLayer.clearLayers();
        Inte.ellipseVisualAssets.boundaryGuideLineLayer.clearLayers();

        // Add boundary lines. 
        Inte.ellipseVisualAssets.boundaryLineLayer.addLayer(top);
        Inte.ellipseVisualAssets.boundaryLineLayer.addLayer(bot);

        // Start call chain again.
        this.placeGuideMarker();
    }
}

/**
 * Various calulation related helper functions.  
 * @constructor
 * 
 * @param {object} Inte - AreaCaptureInterface object. Allows access to all other tools.
 */
function Calculator(Inte) {
    /**
     * Calculates distance between two locations. 
     * @function
     * 
     * @param {object} fromLatLng - Starting location.
     * @param {object} toLatLng - Ending location. 
     * @returns {float} Distance between the given points. 
     */
    Calculator.prototype.distance = function(fromLatLng, toLatLng) {
        return Math.sqrt(Math.pow(fromLatLng.lat - toLatLng.lat, 2) + Math.pow(fromLatLng.lng - toLatLng.lng, 2));
    }

    /**
     * Determines if a location is in the first quadrent relative to a central location. 
     * @function
     * 
     * @param {object} centralLatLng - Central location. 
     * @param {object} otherLatLng - Location to test. 
     * @returns {boolean} Whether or not the test location is in the first quadrent. 
     */
    Calculator.prototype.inFirstQuadrent = function(centralLatLng, otherLatLng) {
        let standardizedLat = otherLatLng.lat - centralLatLng.lat;
        let standardizedLng = otherLatLng.lng - centralLatLng.lng;

        return standardizedLat > 0 && standardizedLng > 0;
    }

    /**
     * Determines if a location is in the second quadrent relative to a central location. 
     * @function
     * 
     * @param {object} centralLatLng - Central location. 
     * @param {object} otherLatLng - Location to test. 
     * @returns {boolean} Whether or not the test location is in the second quadrent. 
     */
    Calculator.prototype.inSecondQuadrent = function(centralLatLng, otherLatLng) {
        let standardizedLat = otherLatLng.lat - centralLatLng.lat;
        let standardizedLng = otherLatLng.lng - centralLatLng.lng;
        
        return standardizedLat > 0 && standardizedLng < 0;
    }

    /**
     * Determines if a location is in the third quadrent relative to a central location. 
     * @function
     * 
     * @param {object} centralLatLng - Central location. 
     * @param {object} otherLatLng - Location to test. 
     * @returns {boolean} Whether or not the test location is in the third quadrent. 
     */
    Calculator.prototype.inThirdQuadrent = function(centralLatLng, otherLatLng) {
        let standardizedLat = otherLatLng.lat - centralLatLng.lat;
        let standardizedLng = otherLatLng.lng - centralLatLng.lng;
        
        return standardizedLat < 0 && standardizedLng < 0;
    }

    /**
     * Determines if a location is in the fourth quadrent relative to a central location. 
     * @function
     * 
     * @param {object} centralLatLng - Central location. 
     * @param {object} otherLatLng - Location to test. 
     * @returns {boolean} Whether or not the test location is in the fourth quadrent. 
     */
    Calculator.prototype.inFourthQuadrent = function(centralLatLng, otherLatLng) {
        let standardizedLat = otherLatLng.lat - centralLatLng.lat;
        let standardizedLng = otherLatLng.lng - centralLatLng.lng;
        
        return standardizedLat < 0 && standardizedLng > 0;
    }
}