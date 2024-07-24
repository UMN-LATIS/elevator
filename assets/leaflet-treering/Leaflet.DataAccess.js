/**
 * @file Leaflet Data Access
 * @author Daniel Binsfeld <binsf024@umn.edu> & Jessica Thorne <thorn572@umn.edu>
 * @version 1.0.0
 */

/**
 * Interface for data access tools. 
 * @constructor
 * 
 * @param {object} Lt - LTreering object from leaflet-treering.js. 
 */
function DataAccessInterface(Lt) {
    this.treering = Lt;

    this.viewData = new ViewData(this);
    this.viewDataDialog = new ViewDataDialog(this);

    this.popoutPlots = new PopoutPlots(this);
    this.jsonFileUpload = new JSONFileUpload(this);
    this.cloudUpload = new CloudUpload(this);
    
    this.deleteData = new DeleteData(this);
    this.deleteDataDialog = new DeleteDataDialog(this);

    this.download = new Download(this);
}

/**
 * Tool for viewing data. 
 * @constructor
 * 
 * @param {object} Inte - DataAccessInterface objects. Allows access to DataAccess tools.
 */
function ViewData(Inte) {
    this.btn = new Button (
        'find_in_page',
        'View & download measurement data',
        () => { this.enable() },
        () => { this.disable() });

    this.active = false;
    
    ViewData.prototype.enable = function() {
        this.btn.state('active');
        this.active = true;
        Inte.viewDataDialog.open();
    }

    ViewData.prototype.disable = function() {
        this.btn.state('inactive');
        this.active = false;
        Inte.viewDataDialog.close();
    }
}

/** 
 * Generates dialog window to view data. 
 * @constructor
 * 
 * @param {object} Inte - DataAccessInterface objects. Allows access to DataAccess tools.
*/
function ViewDataDialog(Inte) {
    this.dialogOpen = false;

    Handlebars.registerHelper('ifDecadeCheck', function(year, block) {
        var selected = year % 10 == 0;
        if(selected) {
          return block.fn(this)
        }
    });

    Handlebars.registerHelper('fourDigitlookup', function(obj, key) {
      decimal = null
      if(obj !== undefined && obj !== null &&  obj[key] !== undefined && obj[key] !== null) {
        decimal = obj[key];
      }
      if (decimal || decimal == 0) {
            let rounded = "0.000"
            if (decimal > 0) {
                decimal = decimal.toString() + "000"; // Add zeroes for already truncated values (i.e. 0.3 -> 0.300).
                let dec_idx = decimal.indexOf('.');
                rounded = decimal.slice(0, dec_idx + 4);
            }
            
            return rounded;
        }
    });

    let html = document.getElementById("DataAccess-dialog-template").innerHTML;
    this.template = Handlebars.compile(html);
    
    this.dialogHeight = 290;
    this.tableHeight = 250;
      
    this.dialog = L.control.dialog({
        "size": [0, 0],
        "anchor": [50, 0],
        "initOpen": false,
        'position': 'topleft',
        "maxSize": [Number.MAX_SAFE_INTEGER, Number.MAX_SAFE_INTEGER],
        "minSize": [0, 0],
    }).addTo(Inte.treering.viewer);
    this.dialog.hideClose();

    this.scrollPositionFromTop = 0;

    /**
     * Opens dialog window.
     * @function
     */
    ViewDataDialog.prototype.open = function() { 
        Inte.treering.collapseTools();

        let dat = Inte.treering.helper.findDistances();
        let content = this.template({
            data: dat,
            sub: dat.ew !== undefined && dat.lw !== undefined,
            savePermissions: Inte.treering.meta.savePermission,
        });

        let size = dat?.ew ? [260, this.dialogHeight] : [176, this.dialogHeight];
        
        this.dialog.setContent(content);

        this.dialog.setSize(size);
        this.dialog.options.maxSize = [size[0], Number.MAX_SAFE_INTEGER];
        this.dialog.options.minSize = [size[0], 260];

        document.getElementById('DataAccess-table-body').style.height = this.tableHeight + "px";
        document.getElementById('DataAccess-table-id').style.height = this.tableHeight + "px"; 

        this.dialog.open();
        this.dialogOpen = true;

        document.getElementById("DataAccess-table-body").scrollTop = this.scrollPositionFromTop;
        this.createEventListeners();
    }

    /**
     * Closes dialog window.
     * @function
     */
    ViewDataDialog.prototype.close = function() {
        if (this.dialogOpen) this.dialog.close();
        if (Inte.deleteData.dialogOpen) Inte.deleteData.dialog.close();
        this.dialogOpen = false;
    }
    
    /**
     * Reloads dialog window.
     * @function
     */
    ViewDataDialog.prototype.reload = function() {
        let dat = Inte.treering.helper.findDistances();
        let content = this.template({
            data: dat,
            sub: dat.ew !== undefined && dat.lw !== undefined,
            savePermissions: Inte.treering.meta.savePermission,
        });
        
        this.dialog.setContent(content);

        document.getElementById('DataAccess-table-body').style.height = this.tableHeight + "px";
        document.getElementById('DataAccess-table-id').style.height = this.tableHeight + "px";

        this.dialog.open();

        document.getElementById("DataAccess-table-body").scrollTop = this.scrollPositionFromTop;
        this.createEventListeners();
    }
    
    /**
     * Creates all event listeners for HTML elements in dialog window. 
     * @function
     */
    ViewDataDialog.prototype.createEventListeners = function () {
        $("#DataAccess-table-body").on("scroll", () => {
            this.scrollPositionFromTop = document.getElementById("DataAccess-table-body").scrollTop;
        });

        $(this.dialog._map).on('dialog:resizeend', () => { 
            this.dialogHeight = this.dialog.options.size[1];
            this.tableHeight = this.dialogHeight - 45; // Adjust by 45 to ensure data is not cut off. 
            document.getElementById('DataAccess-table-body').style.height = this.tableHeight + "px";
            document.getElementById('DataAccess-table-id').style.height = this.tableHeight + "px";
        });

        // $("#insert_chart").on("click", () => { Inte.popoutPlots.action() });
        $("#upload_file").on("click", () => { Inte.jsonFileUpload.input() });
        $("#cloud_upload").on("click", () => { Inte.cloudUpload.action() });
        $("#delete").on("click", () => { if (Inte.treering.data.points.length) Inte.deleteDataDialog.open() });

        $("#copy").on("click",() => { Inte.download.copy() });
        $("#csv").on("click", () => { Inte.download.csv() });
        $("#tsv").on("click", () => { Inte.download.tsv() });
        $("#rwl").on("click", () => { Inte.download.rwl() });
        $("#json").on("click", () => { Inte.download.json() });
    }
}

/** 
 * A popout window with time series plots.
 * @constructor
 * 
 * @param {object} Inte - DataAccessInterface objects. Allows access to DataAccess tools.
 */
function PopoutPlots(Inte) {
    this.btn = new Button (
        'query_stats',
        'Open time series plots in new window',
        () => { this.action() });

    let height = (4/9) * screen.height;
    let top = (2/3) * screen.height;
    let width = screen.width;
    this.childSite = null
    this.win = null
    
    /**
     * Opens popout plot window.  
     * @function
     */
    PopoutPlots.prototype.action = function() {
        // this.childSite = 'http://localhost:8080/dendro-plots/'
        this.childSite = 'https://umn-latis.github.io/dendro-plots/'
        this.win = window.open(this.childSite, 'popout' + Math.round(Math.random()*10000),
                    'location=yes,height=' + height + ',width=' + width + ',scrollbars=yes,status=yes, top=' + top);

        let data = { points: Inte.treering.helper.findDistances(), annotations: Inte.treering.aData.annotations };
        window.addEventListener('message', () => {
          this.win.postMessage(data, this.childSite);
        }, false)
    }
    
    /**
     * Sends data to plotting child site. 
     * @function
     */
    PopoutPlots.prototype.sendData = function() {
        let data = { points: Inte.treering.helper.findDistances(), annotations: Inte.treering.aData.annotations };
        this.win.postMessage(data, this.childSite);
    }

    /**
     * Highlights year on child site. 
     * @function
     * 
     * @param {number} year - Value to highlight on plot.  
     */
    PopoutPlots.prototype.highlightYear = function(year) {
        this.win.postMessage(year, this.childSite);
    }
}

/** 
 * Allows user to upload local JSON files. 
 * @constructor
 * 
 * @param {object} Inte - DataAccessInterface objects. Allows access to DataAccess tools.
 */
function JSONFileUpload(Inte) {
    /**
     * Create an input div on the UI and click it.
     * @function
     */
    JSONFileUpload.prototype.input = function() {
        var input = document.createElement('input');
        input.type = 'file';
        input.id = 'file';
        input.style = 'display: none';
        input.addEventListener('change', () => {this.action(input)});
        input.click();
    };

    /**
     * Load the file selected in the input.
     * @function 
     */
    JSONFileUpload.prototype.action = function(inputElement) {
        var files = inputElement.files;
        console.log(files);
        if (files.length <= 0) {
            return false;
        }

        var fr = new FileReader();

        fr.onload = function(e) {
            let newDataJSON = JSON.parse(e.target.result);

            Inte.treering.preferences = {
                'forwardDirection': newDataJSON.forwardDirection,
                'subAnnual': newDataJSON.subAnnual,
            };

            Inte.treering.data = new MeasurementData(newDataJSON, Inte.treering);
            Inte.treering.aData = new AnnotationData(newDataJSON.annotations);

            if (newDataJSON?.ellipses) Inte.treering.areaCaptureInterface.ellipseData.loadJSON(newDataJSON.ellipses); 
            else Inte.treering.areaCaptureInterface.ellipseData.clearJSON();
            if (newDataJSON?.currentView) Inte.treering.imageAdjustmentInterface.imageAdjustment.loadCurrentViewJSON(newDataJSON.currentView);

            if (newDataJSON?.pithEstimate && (newDataJSON.pithEstimate.innerYear || newDataJSON.pithEstimate.innerYear == 0)) {
                Inte.treering.pithEstimateInterface.estimateData.updateShownValues(newDataJSON.pithEstimate.innerYear, newDataJSON.pithEstimate.growthRate);
                Inte.treering.pithEstimateInterface.estimateVisualAssets.reloadArcVisuals(
                    typeof newDataJSON.pithEstimate.growthRate == "string",
                    newDataJSON.pithEstimate.pithLatLng, 
                    newDataJSON.pithEstimate.radius_unCorrected,
                    newDataJSON.pithEstimate.latLngObject, 
                    newDataJSON.pithEstimate.innerYear,
                );
            }

            // If the JSON has PPM data, use that instead of loaded data.
            if (newDataJSON.ppm) {
                Inte.treering.meta.ppm = newDataJSON.ppm;
                Inte.treering.options.ppm = newDataJSON.ppm;
            }

            Inte.treering.loadData();
            Inte.treering.metaDataText.updateText();

            // Temporary solution to refresh data. Size does not update unless opened twice. Race condition?
            Inte.viewDataDialog.close();
            Inte.viewDataDialog.open();
            Inte.viewData.disable();
            Inte.viewData.enable();
        };

        fr.readAsText(files.item(0));
    };
}

/**
 * Save JSON to cloud.
 * @constructor
 * 
 * @param {object} Inte - DataAccessInterface objects. Allows access to DataAccess tools.
 */
function CloudUpload(Inte) {
    // Trigger save action with Shift-S
    L.DomEvent.on(window, 'keydown', (e) => {
        if (e.keyCode == 83 && e.getModifierState("Shift") && !e.getModifierState("Control") && // 83 refers to 's'
            window.name.includes('popout') && !Inte.treering.annotationAsset.dialogAnnotationWindow) { // Dialog windows w/ text cannot be active
        e.preventDefault();
        e.stopPropagation();
        this.action();
        };
    });

    this.date = new Date();

    /**
     * Update the save date & meta data.
     * @function
     */
    CloudUpload.prototype.updateDate = function() {
        this.date = new Date();
        var day = this.date.getDate();
        var month = this.date.getMonth() + 1;
        var year = this.date.getFullYear();
        var minute = this.date.getMinutes();
        var hour = this.date.getHours();
        Inte.treering.data.saveDate = {'day': day, 'month': month, 'year': year, 'hour': hour, 'minute': minute};
    };

    /**
     * Display the save date in the bottom left corner.
     * @function 
     */
    CloudUpload.prototype.displayDate = function() {
        var date = Inte.treering.data.saveDate;
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
            "Saved to cloud " + date.year + '/' + date.month + '/' + date.day + ' ' + date.hour + ':' + minute_string + am_pm;
        } else if (date.day != undefined) {
        this.saveText =
            "Saved to cloud " + date.year + '/' + date.month + '/' + date.day;
        } else {
        this.saveText =
            'No data saved to cloud';
        };

        Inte.treering.data.saveDate;
    };

    /**
     * Save the measurement data to the cloud.
     * @function 
     */
    CloudUpload.prototype.action = function() {
        if (Inte.treering.meta.savePermission && Inte.treering.meta.saveURL != "") {
        Inte.treering.data.clean();
        this.updateDate();
        var dataJSON = Inte.download.formatJSON();

        // Do not serialize our default value.
        if (Inte.treering.meta.ppm != Inte.treering.defaultResolution || Inte.treering.meta.ppmCalibration) {
            dataJSON.ppm = Inte.treering.meta.ppm;
        }

        $.post(Inte.treering.meta.saveURL, {sidecarContent: JSON.stringify(dataJSON)})
            .done((msg) => {
                this.displayDate();
                Inte.treering.metaDataText.updateText();
            })
            .fail((xhr, status, error) => {
                alert('Error: Failed to save changes.');
            });
        } else {
            alert('Authentication Error: Save to cloud permission not granted.');
        };
    };
}

/**
 * Deletes all measurement data.
 * @constructor
 * 
 * @param {object} Inte - DataAccessInterface objects. Allows access to DataAccess tools.
 */
function DeleteData(Inte) {
    /**
     * Deletes data.
     * @function
     */
    DeleteData.prototype.action = function() {
        Inte.treering.undo.push();

        Inte.treering.data.points = [];
        Inte.treering.data.year = 0;
        Inte.treering.data.earlywood = true;
        Inte.treering.data.index = 0;

        Inte.treering.visualAsset.reload();
        Inte.treering.metaDataText.updateText();

        Inte.viewDataDialog.reload();
    }
}

/**
 * Creates dialog for deleting data.
 * @constructor
 * 
 * @param {object} Inte - DataAccessInterface objects. Allows access to DataAccess tools.
 */
function DeleteDataDialog(Inte) {
    let content = document.getElementById("DataAccess-deleteAllDialog-template").innerHTML;

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

    /**
     * Opens dialog window. 
     * @function
     */
    DeleteDataDialog.prototype.open = function() {
        // Recenter dialog window. Otherwise anchor location remebered. 
        this.dialog.setLocation([this.fromTop, this.fromLeft]);
        this.dialog.open();
        this.createEventListeners();
    }

    /**
     * Closes dialog window. 
     * @function
     */
    DeleteDataDialog.prototype.close = function() {
        this.dialog.close();
    }

    /**
     * Creates all event listeners for HTML elements in dialog window. 
     * @function
     */
    DeleteDataDialog.prototype.createEventListeners = function() {
        $("#DataAccess-confirmDelete-btn").on("click", () => {
            Inte.deleteData.action();
            this.close();
        });

        $("#DataAccess-cancelDelete-btn").on("click", () => {
            this.close();
        });
    }
}

/**
 * Hosts all download types. 
 * @constructor
 * 
 * @param {object} Inte - DataAccessInterface objects. Allows access to DataAccess tools.
 */
function Download(Inte) {
    /**
     * Format data into full JSON data package. 
     * @function
     */
    Download.prototype.formatJSON = function() {
      Inte.treering.data.clean();
      let data = {
          'SaveDate': Inte.treering.data.saveDate,
          'year': Inte.treering.data.year,
          'forwardDirection': Inte.treering.measurementOptions.forwardDirection,
          'subAnnual': Inte.treering.measurementOptions.subAnnual,
          'earlywood': Inte.treering.data.earlywood,
          'index': Inte.treering.data.index,
          'points': Inte.treering.data.points,
          'attributesObjectArray': Inte.treering.annotationAsset.attributesObjectArray,
          'annotations': Inte.treering.aData.annotations,
          'ppm': Inte.treering.meta.ppm,
          'ptWidths': Inte.treering.helper.findDistances(),
          'ellipses': Inte.treering.areaCaptureInterface.ellipseData.getJSON(),
          'currentView': Inte.treering.imageAdjustmentInterface.imageAdjustment.getCurrentViewJSON(),
          'pithEstimate': Inte.treering.pithEstimateInterface.estimateData.getJSON(),
      };

      return data;
    }

    /**
     * Format data into seperated text and return as a single string.
     * @function
     * 
     * @param {string} sep - Seperator value (i.e. comma). 
     */
    Download.prototype.seperateDataCombined = function(sep) {
        let dat = Inte.treering.helper.findDistances();

        let outStr = "Year" + sep + Inte.treering.meta.assetName + "_TW";
        if (dat?.ew) outStr += sep + Inte.treering.meta.assetName + "_EW" + sep + Inte.treering.meta.assetName + "_LW";
        outStr += "\n"

        for (var i = 0; i < dat.tw.x.length; i++) {
            outStr += dat.tw.x[i] + sep + dat.tw.y[i].toFixed(3);
            if (dat?.ew) outStr += sep + dat.ew.y[i].toFixed(3) + sep + dat.lw.y[i].toFixed(3);
            
            if (i < dat.tw.x.length - 1) outStr += "\n"
        }

        return outStr;
    }

    /**
     * Format data into seperated text and return multiple strings.
     * @function
     * 
     * @param {string} sep - Seperator value (i.e. comma). 
     */
    Download.prototype.seperateDataDifferent = function(sep) {
        let dat = Inte.treering.helper.findDistances();
        
        let outTWStr = "Year" + sep + Inte.treering.meta.assetName + "_TW\n";
        let outEWStr = "Year" + sep + Inte.treering.meta.assetName + "_EW\n";
        let outLWStr = "Year" + sep + Inte.treering.meta.assetName + "_LW\n";

        for (var i = 0; i < dat.tw.x.length; i++) {
            outTWStr += dat.tw.x[i] + sep + dat.tw.y[i].toFixed(3);
            if (dat?.ew) {
                outEWStr += dat.ew.x[i] + sep + dat.ew.y[i].toFixed(3);
                outLWStr += dat.lw.x[i] + sep + dat.lw.y[i].toFixed(3);
            }

            if (i < dat.tw.x.length - 1) {
                outTWStr += "\n";
                outEWStr += "\n";
                outLWStr += "\n";
            }
        }

        let outLst = (dat?.ew) ? [outTWStr, outEWStr, outLWStr] : [outTWStr];

        return outLst;
    }

    /**
     * Create zip folder.
     * @function
     * 
     * @param {string} fileType - File type (i.e. csv, tsv).
     * @param {string} fileExt - Extension at end of file. 
     * @param {string} twDatString - String of total width data.
     * @param {string} [o] allDatString - Optional string of all data.
     * @param {string} [o] ewDatString - Optional string of earlywood width data. 
     * @param {string} [o] lwDatString - Optional string of latewood width data.
     */
    Download.prototype.zipFiles = function(fileType, fileExt, twDatString, allDatString, ewDatString, lwDatString) {
        let zip = new JSZip();

        if (ewDatString && lwDatString) {
          zip.file((Inte.treering.meta.assetName + "_EW_" + fileType + "." + fileExt), ewDatString);
          zip.file((Inte.treering.meta.assetName + "_LW_" + fileType + "." + fileExt), lwDatString);
        }

        if (allDatString) {
          zip.file((Inte.treering.meta.assetName + "_all_" + fileType + "." + fileExt), allDatString);
        }

        zip.file((Inte.treering.meta.assetName + "_TW_" + fileType + "." + fileExt), twDatString);
        zip.generateAsync({type: 'blob'})
            .then((blob) => {
                saveAs(blob, (Inte.treering.meta.assetName + "_" + fileType + ".zip"));
            });
    }

    /**
     * Copy data to clipboard.
     * @function
     */
    Download.prototype.copy = function() {
        navigator.clipboard.writeText(this.seperateDataCombined("\t"));
    }

    /**
     * Download .csv file.
     * @function
     */
    Download.prototype.csv = function() {
        let allDatString = this.seperateDataCombined(",");
        let datStringLst = this.seperateDataDifferent(",");
        if (datStringLst.length > 1) this.zipFiles("csv", "csv", datStringLst[0], allDatString, datStringLst[1], datStringLst[2]);
        else this.zipFiles("csv", "csv", datStringLst[0]);
    }

    /**
     * Download .tsv file.
     * @function
     */
    Download.prototype.tsv = function() {
        let allDatString = this.seperateDataCombined("\t");
        let datStringLst = this.seperateDataDifferent("\t");
        if (datStringLst.length > 1) this.zipFiles("tsv", "tsv", datStringLst[0], allDatString, datStringLst[1], datStringLst[2]);
        else this.zipFiles("tsv", "tsv", datStringLst[0]);
    }

    /**
     * Download .rwl file. 
     * @function
     */
    Download.prototype.rwl = function() {
        let dat = Inte.treering.helper.findDistances();

        let name = this.constructNameRWL(Inte.treering.meta.assetName);
        let year = dat.tw.x[0];
        let yearStr = this.formatYearRWL(year);
        let stopMarker = " -9999";
        
        let outTWStr = name + yearStr + this.formatDataPointRWL(dat.tw.y[0]);
        // If data begins on a year ending in '9', begin newline immediately.
        if ((year + 1) % 10 == 0) outTWStr += "\n" + name + this.formatYearRWL(year + 1);;

        let outEWStr = "";
        let outLWStr = "";

        if (dat?.ew) {
            let yearEW = dat.ew.x[0];
            let yearLW = dat.lw.x[0];

            let yearEWStr = this.formatYearRWL(yearEW);
            let yearLWStr = this.formatYearRWL(yearLW);

            outEWStr = name + yearEWStr + this.formatDataPointRWL(dat.ew.y[0]);
            outLWStr = name + yearLWStr + this.formatDataPointRWL(dat.lw.y[0]);

            if ((yearEW + 1) % 10 == 0) outEWStr += "\n" + name + this.formatYearRWL(yearEW + 1);;
            if ((yearLW + 1) % 10 == 0) outLWStr += "\n" + name + this.formatYearRWL(yearLW + 1);;
        }

        for (let i = 1; i < dat.tw.x.length; i++) {
            year = dat.tw.x[i];
            outTWStr += this.formatDataPointRWL(dat.tw.y[i]);

            if (dat?.ew) {
                outEWStr += this.formatDataPointRWL(dat.ew.y[i]);
                outLWStr += this.formatDataPointRWL(dat.lw.y[i]);
            }
    
            if ((year + 1) % 10 == 0) {
                yearStr = this.formatYearRWL(year + 1);
                outTWStr += "\n" + name + yearStr;

                if (dat?.ew) {
                    outEWStr += "\n" + name + yearStr;
                    outLWStr += "\n" + name + yearStr;
                }
            }
        }
        outTWStr += stopMarker;
        if (dat?.ew) {
            outEWStr += stopMarker;
            outLWStr += stopMarker;
        }

        if (outEWStr.length > 1) this.zipFiles("rwl", "txt", outTWStr, null, outEWStr, outLWStr);
        else this.zipFiles("rwl", "txt", outTWStr);
    }

    /**
     * Converts year value into RWL format. 
     * @function
     *  
     * @param {integer} year - Year value.  
     */
    Download.prototype.formatYearRWL = function(year) {
        year = String(year);
        year = " ".repeat(4 - year.length) + year;

        return year;
    }

    /**
     * Converts measurement points into RWL format. 
     * @function
     *  
     * @param {float} dataPoint - Measurement value. 
     */
    Download.prototype.formatDataPointRWL = function(dataPoint) {
        dataPoint *= 1000;
        dataPoint = Math.round(dataPoint);

        // Change 0.999mm measurements to 0.998mm for software compatibility. 
        if (dataPoint == 999) dataPoint = 998;

        dataPoint = String(dataPoint);
        dataPoint = " ".repeat(6 - dataPoint.length) + dataPoint;

        return dataPoint;
    }
    
    /**
     * Converts asset name into RWL format. 
     * @function
     * 
     * @param {strong} str - Name of asset. 
     */
    Download.prototype.constructNameRWL = function(str) {
        let nameSpaces = 8 - str.length;
        let name = str + " ".repeat(nameSpaces);

        return name;
    }

    /**
     * Download .json file.
     * @function
     */
    Download.prototype.json = function() {
        let dataJSON = this.formatJSON();

        // Do not serialize our default value
        if (Inte.treering.meta.ppm != Inte.treering.defaultResolution || Inte.treering.meta.ppmCalibration) {
            dataJSON.ppm = Inte.treering.meta.ppm;
        }

        var file = new File([JSON.stringify(dataJSON)], (Inte.treering.meta.assetName + '.json'), {type: 'text/plain;charset=utf-8'});
        saveAs(file);
    }
}