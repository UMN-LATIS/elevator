
var leafletTreering = function(map, basePath, saveURL, initialData, assetName, innerYear){
    this.map = map;
    this.basePath = basePath;
    this.saveURL = saveURL;
    this.initialData = initialData;
    this.assetName = assetName;
    this.innerYear = innerYear;

    //after a leafletTreering is defined, loadInterface will be used to load all buttons and any initial data
    this.loadInterface = function(basePath){

        //set up the cursor
        map.on('movestart', function(e){
        document.getElementById('map').style.cursor = 'move';
        })
        map.on('moveend', function(e){
            if(create.dataPoint.active || annotation.lineMarker.active){
                document.getElementById('map').style.cursor = 'pointer';
            }
            else{
                document.getElementById('map').style.cursor = 'default';
            }
        })

        document.getElementById('map').style.cursor = 'default';

        //add all UI elements to map
        miniMap.addTo(map);

        undoRedoBar.addTo(map);
        timeBar.addTo(map);
        createBar.addTo(map);
        editBar.addTo(map);
        annotationBar.addTo(map);
        dataBar.addTo(map);

        L.control.layers(baseLayer, overlay).addTo(map);

        //add event listner for keyboard
        document.addEventListener('keyup', doc_keyUp, false);

        //click function for an html save button with the id #save-local
        $("#save-local").click(function(event){
            //create anoter JSON and store the current counters for year, earlywood, and index, along with points data
            dataJSON = {'year': year, 'earlywood': earlywood, 'index': index, 'points': points, 'annotations': annotations};
            this.href = 'data:plain/text,' + JSON.stringify(dataJSON);
        });

        loadData(initialData);
    };

    //add an html button with id #save-cloud for saving data to a target url
    this.addSaveButton = function(){
        document.getElementById('admin-save').innerHTML = '<a href="#" id="save-cloud"><i class="material-icons md-18">backup</i></a>';

        $("#save-cloud").click(function(event) {
            dataJSON = {'year': year, 'earlywood': earlywood, 'index': index, 'points': points, 'annotations': annotations};
            $.post(saveURL, {sidecarContent: JSON.stringify(dataJSON)}, function(data, textStatus, xhr) {
                alert("Saved Successfully");
            });
        });
    };

    //load data into asset through a file with html id #file
    this.loadFile = function(){
        var files = document.getElementById('file').files;
        console.log(files);
        if (files.length <= 0) {
            return false;
        }
      
        var fr = new FileReader();
      
        fr.onload = function(e) { 
            console.log(e);
            newDataJSON = JSON.parse(e.target.result);

            loadData(newDataJSON);
        }

        fr.readAsText(files.item(0));
    };

    //parses and loads data
    var loadData = function(newData){
        if(newData.points != undefined){
            index = newData.index;
            year = newData.year;
            earlywood = newData.earlywood;
            points = newData.points;
            visualAsset.reload();
        }
        if(newData.annotations != undefined){
            annotations = newData.annotations;
            annotation.reload();
        }
        time.collapse();
        annotation.collapse();
        edit.collapse();
        create.collapse(); 
    };

    var points = {};            //object with all the point data
    var annotations = {};       //object with all annotations data
    var year = this.innerYear;  //year
    var earlywood = true;       //earlywood or latewood
    var index = 0;              //points index

    //creating colored icons for points
    var markerIcon = {
        light_blue: L.icon({
            iconUrl: '/assets/leaflet-treering/images/light_blue_icon.png',
            iconSize: [32, 32] // size of the icon
        }),
        dark_blue: L.icon({
            iconUrl: '/assets/leaflet-treering/images/dark_blue_icon.png',
            iconSize: [32, 32] // size of the icon
        }),
        white: L.icon({
            iconUrl: '/assets/leaflet-treering/images/white_icon.png',
            iconSize: [32, 32] // size of the icon
        }),
        grey: L.icon({
            iconUrl: '/assets/leaflet-treering/images/grey_icon.png',
            iconSize: [32, 32] // size of the icon
        })
    };

    //when user adds new markers lines and hbars will be created from the mouse
    var interactiveMouse = {
        layer:
            L.layerGroup().addTo(map),
        lineFrom:
            function(latLng){
                var self = this;
                $(map._container).mousemove(function lineToMouse(e){
                    //only create lines when collecting data
                    if(annotation.lineMarker.active){
                        self.layer.clearLayers(); //continously delete previous lines
                        var mouseLatLng = map.mouseEventToLatLng(e);
                        var point = map.latLngToLayerPoint(latLng);

                        //create lines and add them to mouseLine layer
                        self.layer.addLayer(L.polyline([latLng, mouseLatLng], {color: '#000', weight: '6'}));
                    }
                });
            },
        hbarFrom:
            function(latLng){
                var self = this;
                $(map._container).mousemove(function lineToMouse(e){
                    if(create.dataPoint.active){
                        self.layer.clearLayers();
                        var mousePoint = map.mouseEventToLayerPoint(e);
                        var mouseLatLng = map.mouseEventToLatLng(e);
                        var point = map.latLngToLayerPoint(latLng);

                        //getting the four points for the h bars, this is doing 90 degree rotations on mouse point
                        var newX = mousePoint.x + (point.x - mousePoint.x)*Math.cos(Math.PI/2) - (point.y - mousePoint.y)*Math.sin(Math.PI/2);
                        var newY = mousePoint.y + (point.x - mousePoint.x)*Math.sin(Math.PI/2) + (point.y - mousePoint.y)*Math.cos(Math.PI/2);
                        var topRightPoint = map.layerPointToLatLng([newX, newY]);

                        var newX = mousePoint.x + (point.x - mousePoint.x)*Math.cos(Math.PI/2*3) - (point.y - mousePoint.y)*Math.sin(Math.PI/2*3);
                        var newY = mousePoint.y + (point.x - mousePoint.x)*Math.sin(Math.PI/2*3) + (point.y - mousePoint.y)*Math.cos(Math.PI/2*3);
                        var bottomRightPoint = map.layerPointToLatLng([newX, newY]);

                        //doing rotations 90 degree rotations on latlng
                        var newX = point.x + (mousePoint.x - point.x)*Math.cos(Math.PI/2) - (mousePoint.y - point.y)*Math.sin(Math.PI/2);
                        var newY = point.y + (mousePoint.x - point.x)*Math.sin(Math.PI/2) + (mousePoint.y - point.y)*Math.cos(Math.PI/2);
                        var topLeftPoint = map.layerPointToLatLng([newX, newY]);

                        var newX = point.x + (mousePoint.x - point.x)*Math.cos(Math.PI/2*3) - (mousePoint.y - point.y)*Math.sin(Math.PI/2*3);
                        var newY = point.y + (mousePoint.x - point.x)*Math.sin(Math.PI/2*3) + (mousePoint.y - point.y)*Math.cos(Math.PI/2*3);
                        var bottomLeftPoint = map.layerPointToLatLng([newX, newY]);

                        self.layer.addLayer(L.polyline([latLng, mouseLatLng], {color: '#00BCD4', weight: '5'}));
                        self.layer.addLayer(L.polyline([topLeftPoint, bottomLeftPoint], {color: '#00BCD4', weight: '5'}));
                        self.layer.addLayer(L.polyline([topRightPoint, bottomRightPoint], {color: '#00BCD4', weight: '5'}));
                    }
                });
            }
    }

    //all visual assets on the map such as markers and lines
    var visualAsset = {
        markers:
            new Array(),
        lines:
            new Array(),
        markerLayer:
            L.layerGroup().addTo(map),
        lineLayer:
            L.layerGroup().addTo(map),
        reload:
            function(){
                //erase the markers
                this.markerLayer.clearLayers();
                this.markers = new Array();
                 //erase the lines
                this.lineLayer.clearLayers();
                this.lines = new Array();

                //plot the data back onto the map
                if(points != undefined){
                    Object.values(points).map(function(e, i){
                        if(e.latLng != undefined){
                            visualAsset.newLatLng(points, i, e.latLng);
                        }
                    });
                }
            },
        previousLatLng:
            undefined,
        newLatLng:
            function(p, i, latLng){
                leafLatLng = L.latLng(latLng);   //leaflet is stupid and only uses latlngs that are created through L.latlng

                //check if index is the start point
                if(p[i].start){
                    var marker = L.marker(leafLatLng, {icon: markerIcon.white, draggable: true, title: "Start Point"});
                }
                //check if point is a break
                else if(p[i].break){
                    var marker = L.marker(leafLatLng, {icon: markerIcon.white, draggable: true, title: "Break Point"})
                }
                //check if point is earlywood
                else if(p[i].earlywood){
                    var marker = L.marker(leafLatLng, {icon: markerIcon.light_blue, draggable: true, title: "Year " + p[i].year + ", earlywood"});         
                }
                //otherwise it's latewood
                else{
                    var marker = L.marker(leafLatLng, {icon: markerIcon.dark_blue, draggable: true, title: "Year " + p[i].year + ", latewood"});
                }

                //deal with previous skip point if one exists
                if(p[i-1] != undefined && p[i-1].skip){
                    if(i-1){
                        average = L.latLng([(leafLatLng.lat + this.previousLatLng.lat)/2, (leafLatLng.lng + this.previousLatLng.lng)/2]);
                    }
                    else{
                        average = L.latLng([leafLatLng.lat, (leafLatLng.lng - .001)]);
                    }
                    skip_marker = L.marker(average, {icon: markerIcon.grey, draggable: true, title: "Year " + p[i-1].year + ", None"});
                    skip_marker.on('click', function(e){
                        if(edit.deletePoint.active){
                            edit.deletePoint.action(i-1);
                        }
                    });
                    this.markers[i-1] = skip_marker;
                    this.markerLayer.addLayer(this.markers[i-1]);
                }

                this.markers[i] = marker;     //add created marker to marker_list
                var self = this;

                //tell marker what to do when being draged
                this.markers[i].on('dragend', function(e){

                    undo.push();
                    p[i].latLng = e.target._latlng;     //get the new latlng of the mouse pointer

                    //adjusting the line from the previous and preceeding point if they exist
                    if(p[i-1] != undefined && p[i-1].latLng != undefined && !p[i].start){
                        self.lineLayer.removeLayer(self.lines[i]);
                        self.lines[i] = L.polyline([p[i-1].latLng, e.target._latlng], {color: '#00BCD4', weight: '5'});
                        self.lineLayer.addLayer(self.lines[i]);
                    }
                    if(p[i+1] != undefined && p[i+1].latLng != undefined && self.lines[i+1] != undefined){
                        self.lineLayer.removeLayer(self.lines[i+1]);
                        self.lines[i+1] = L.polyline([e.target._latlng, p[i+1].latLng], {color: '#00BCD4', weight: '5'});
                        self.lineLayer.addLayer(self.lines[i+1]);
                    }
                });

                //tell marker what to do when clicked
                this.markers[i].on('click', function(e){
                    if(edit.deletePoint.active){
                        edit.deletePoint.action(i);
                    }
                    if(edit.cut.active){
                        if(edit.cut.point != -1){
                            edit.cut.action(edit.cut.point, i);
                        }
                        else{
                            edit.cut.point = i;
                        }
                    }
                    if(edit.addData.active){
                        if(p[i].earlywood){
                            alert("must select latewood or start point")
                        }
                        else{
                            edit.addData.action(i);
                        }
                    }
                    if(edit.addZeroGrowth.active){
                        edit.addZeroGrowth.action(i);
                    }
                    if(edit.addBreak.active){
                        edit.addBreak.action(i);
                    }
                    if(time.setYearFromStart.active){
                        time.setYearFromStart.action(i);
                    }
                    if(time.setYearFromEnd.active){
                        time.setYearFromEnd.action(i);
                    }
                })

                //drawing the line if the previous point exists
                if(p[i-1] != undefined && !p[i-1].skip && !p[i].start){
                    this.lines[i] = L.polyline([p[i-1].latLng, leafLatLng], {color: '#00BCD4', weight: '5'});
                    this.lineLayer.addLayer(this.lines[i]);
                }

                this.previousLatLng = leafLatLng;
                this.markerLayer.addLayer(this.markers[i]);    //add the marker to the marker layer
            },
    }

    //undo changes to points using a stack
    var undo = {
        stack:
            new Array(),
        push:
            function(){
                this.btn.enable();
                redo.btn.disable();
                redo.stack.length = 0;
                var restore_points = JSON.parse(JSON.stringify(points));
                this.stack.push({'year': year, 'earlywood': earlywood, 'index': index, 'points': restore_points });
            },
        pop:
            function(){
                if(this.stack.length > 0){
                    redo.btn.enable();
                    var restore_points = JSON.parse(JSON.stringify(points));
                    redo.stack.push({'year': year, 'earlywood': earlywood, 'index': index, 'points': restore_points});
                    dataJSON = this.stack.pop();

                    points = JSON.parse(JSON.stringify(dataJSON.points));

                    index = dataJSON.index;
                    year = dataJSON.year;
                    earlywood = dataJSON.earlywood;

                    visualAsset.reload();

                    if(this.stack.length == 0){
                        this.btn.disable();
                    }
                }
            },
        btn:
            L.easyButton ({
                states: [
                {
                    stateName:  'undo',
                    icon:       '<i class="material-icons md-18">undo</i>',
                    title:      'Undo',
                    onClick:    function(btn, map){
                        undo.pop();
                    }
                }]
            }), 
    }

    //redo changes to points from undoing using a second stack
    var redo = {
        stack:
            new Array(),
        pop:
            function redo(){
                undo.btn.enable();
                var restore_points = JSON.parse(JSON.stringify(points));
                undo.stack.push({'year': year, 'earlywood': earlywood, 'index': index, 'points': restore_points});
                dataJSON = this.stack.pop();

                points = JSON.parse(JSON.stringify(dataJSON.points));

                index = dataJSON.index;
                year = dataJSON.year;
                earlywood = dataJSON.earlywood;

                visualAsset.reload();

                if(this.stack.length == 0){
                    this.btn.disable();
                }
            },
        btn:
            L.easyButton ({
                states: [
                {
                    stateName:  'redo',
                    icon:       '<i class="material-icons md-18">redo</i>',
                    title:      'Redo',
                    onClick:    function(btn, map){
                        redo.pop();
                    }
                }]
            }),
    }

    //all buttons and assets related to changing the time of the series
    var time = {
        collapse:
            function(){
                this.btn.state('collapse');
                this.setYearFromStart.btn.disable();
                this.setYearFromEnd.btn.disable();
                this.shift.forwardBtn.disable();
                this.shift.backwardBtn.disable();

                this.setYearFromStart.disable();
                this.setYearFromEnd.disable();
            },
        setYearFromStart: {
            active:
                false,
            dialog:
                L.control.dialog({'size': [270, 65], 'anchor': [80, 50], 'initOpen': false})
                    .setContent('Year: <input type="number" size="4" maxlength="4" id="year_input"/>' +
                                '<button id="year_submit">enter</button>')
                    .addTo(map),
            action:
                function(i){    
                    if(points[i].start){
                        this.dialog.open();  
                        var self = this;         

                        document.getElementById('year_submit').addEventListener('click', function(){
                            new_year = document.getElementById('year_input').value;
                            self.dialog.close();

                            if(new_year.toString().length > 4){
                                alert("Year cannot exceed 4 digits!");
                            }
                            else{
                                undo.push();

                                i++
                                
                                while(points[i] != undefined){
                                    if(points[i].start || points[i].break){
                                    }
                                    else if(points[i].earlywood){
                                        points[i].year = new_year;
                                    }
                                    else{
                                        points[i].year = new_year++;
                                    }
                                    i++;
                                }
                                visualAsset.reload();
                            }
                            self.disable();
                        }, false);
                    }   
                },
            enable:
                function(){
                    this.btn.state('active');
                    this.active = true;
                },
            disable:
                function(){
                    this.btn.state('inactive');
                    this.active = false;
                    this.dialog.close();
                },
            btn:
                L.easyButton ({
                    states: [
                    {
                        stateName:  'inactive',
                        icon:       '<i class="material-icons md-18">arrow_forward</i>',
                        title:      'Set the start year at any start point',
                        onClick:    function(btn, map){
                            time.setYearFromEnd.disable();
                            time.setYearFromStart.enable();
                        }
                    },
                    {
                        stateName:  'active',
                        icon:       '<i class="material-icons md-18">clear</i>',
                        title:      'Cancel',
                        onClick:    function(btn, map){
                            time.setYearFromStart.disable();
                        }
                    }]
                })
        },

        setYearFromEnd: {
            active:
                false,
            dialog:
                L.control.dialog({'size': [270, 65], 'anchor': [80, 50], 'initOpen': false})
                    .setContent('Year: <input type="number" size="4" maxlength="4" id="end_year_input"/>' +
                                '<button id="end_year_submit">enter</button>')
                    .addTo(map),
            action:    
                function(i){    
                    if(!(points[i+1] != undefined) || points[i+1].break || points[i+1].start){
                        this.dialog.open();
                        var self = this;        

                        document.getElementById('end_year_submit').addEventListener('click', function(){
                            new_year = parseInt(document.getElementById('end_year_input').value);
                            self.dialog.close();

                            if(new_year.toString().length > 4){
                                alert("Year cannot exceed 4 digits!");
                            }
                            else{
                                undo.push();
                                
                                if(i == index){
                                    year = new_year;
                                }

                                while(points[i] != undefined){
                                    if(points[i].start || points[i].break){
                                    }
                                    else if(points[i].earlywood){
                                        points[i].year = new_year--;
                                    }
                                    else{
                                        points[i].year = new_year;
                                    }
                                    i--;
                                }
                                visualAsset.reload();
                            }
                            self.disable();
                        }, false);
                    }   
                },
            enable:
                function(){
                    this.btn.state('active');
                    this.active = true;
                },
            disable:
                function(){
                    this.btn.state('inactive');
                    this.active = false;
                    this.dialog.close();
                },
            btn:
                L.easyButton ({
                    states: [
                    {
                        stateName:  'inactive',
                        icon:       '<i class="material-icons md-18">arrow_back</i>',
                        title:      'Set the end year at any start point',
                        onClick:    function(btn, map){
                            time.setYearFromStart.disable();
                            time.setYearFromEnd.enable();
                        }
                    },
                    {
                        stateName:  'active',
                        icon:       '<i class="material-icons md-18">clear</i>',
                        title:      'Cancel',
                        onClick:    function(btn, map){
                            time.setYearFromEnd.disable();
                        }
                    }]
                })
        },
        shift: {
            action:
                function(x){
                    undo.push();
                    for(i = 0; i < index; i++){
                        if(!points[i].start){
                            points[i].year = parseInt(points[i].year) + x;
                        }
                    }
                    visualAsset.reload();
                },
            forwardBtn:
                L.easyButton ({
                    states: [
                    {
                        stateName:  'year-forward',
                        icon:       '<i class="material-icons md-18">exposure_plus_1</i>',
                        title:      'Shift series forward',
                        onClick:    function(btn, map){
                            time.setYearFromStart.disable();
                            time.shift.action(1);
                        }
                    }]
                }),
            backwardBtn:
                L.easyButton ({
                    states: [
                    {
                        stateName:  'year-backward',
                        icon:       '<i class="material-icons md-18">exposure_neg_1</i>',
                        title:      'Shift series backward',
                        onClick:    function(btn, map){
                            time.setYearFromStart.disable();
                            time.shift.action(-1);
                        }
                    }]
                })
        },
        btn:
            L.easyButton ({
                states: [
                {
                    stateName:  'collapse',
                    icon:       '<i class="material-icons md-18">access_time</i>',
                    title:      'Set and adjust timeline',
                    onClick:    function(btn, map){
                        annotation.collapse();
                        edit.collapse();
                        create.collapse();

                        time.btn.state('expand');
                        time.setYearFromStart.btn.enable();
                        time.setYearFromEnd.btn.enable();
                        time.shift.forwardBtn.enable();
                        time.shift.backwardBtn.enable();
                        data.collapse();
                    }
                },
                {
                    stateName:  'expand',
                    icon:       '<i class="material-icons md-18">expand_less</i>',
                    title:      'Collapse',
                    onClick:    function(btn, map){
                        time.collapse();
                    }
                }]
            }),
    }

    //the main object for creating new data
    var create = {
        collapse:
            function(){
                this.btn.state('collapse');
                this.dataPoint.btn.disable();
                this.zeroGrowth.btn.disable();
                this.breakPoint.btn.disable();

                this.dataPoint.disable();
            },
        dataPoint: {
            active:
                false,
            startPoint:
                true,
            enable:
                function(){
                    this.btn.state('active');

                    document.getElementById('map').style.cursor = "pointer";

                    var self = this;
                    $(map._container).click(function startLine(e){
                        document.getElementById('map').style.cursor = "pointer";

                        var latLng = map.mouseEventToLatLng(e);

                        undo.push();

                        if(self.startPoint){
                            points[index] = {'start': true, 'skip': false, 'break': false, 'latLng':latLng};
                            self.startPoint = false;
                        }
                        else{
                            points[index] = {'start': false, 'skip': false, 'break': false, 'year':year, 'earlywood': earlywood, 'latLng':latLng};
                        }

                        visualAsset.newLatLng(points, index, latLng); //call newLatLng with current index and new latlng 

                        interactiveMouse.hbarFrom(latLng); //create the next mouseline from the new latlng

                        //avoid incrementing earlywood for start point
                        if(!points[index].start){
                            if(earlywood){
                                earlywood = false;
                            }
                            else{
                                earlywood = true;
                                year++;
                            }
                        }

                        index++;
                        self.active = true;     //activate dataPoint after one point is made
                    });
                },
            disable:
                function(){
                    $(map._container).off('click');  //turn off the mouse clicks from previous function
                    this.btn.state('inactive');
                    this.active = false;
                    interactiveMouse.layer.clearLayers(); //clear the mouseline
                    document.getElementById('map').style.cursor = 'default';

                    this.startPoint = true;
                },
            btn:
                L.easyButton ({
                    states: [
                    {
                        stateName:  'inactive',
                        icon:       '<i class="material-icons md-18">add_circle_outline</i>',
                        title:      'Begin data collection (Alt+C)',
                        onClick:    function(btn, map){
                            create.dataPoint.enable();
                        }
                    },
                    {
                        stateName:  'active',
                        icon:       '<i class="material-icons md-18">add_circle</i>',
                        title:      'End data collection (Alt+C)',
                        onClick:    function(btn, map){
                            create.dataPoint.disable();
                        }
                    }]
                })
        },
        zeroGrowth: {
            action:
                function(){
                    undo.push();

                    points[index] = {'start': false, 'skip': true, 'break': false, 'year':year}; //no point or latlng
                    year++;
                    index++;
                },
            btn:
                L.easyButton ({
                    states: [
                    {
                        stateName:  'skip-year',
                        icon:       '<i class="material-icons md-18">update</i>',
                        title:      'Add a zero growth year (Alt+S)',
                        onClick:    function(btn, map){
                            create.zeroGrowth.action();
                        }
                    }]
                })
        },
        breakPoint: {
            enable:      
                function(){
                    this.btn.state('active');

                    create.dataPoint.active = true;
                        
                    var self = this;
                    $(map._container).click(function(e){
                        var latLng = map.mouseEventToLatLng(e);

                        interactiveMouse.hbarFrom(latLng)

                        undo.push();

                        map.dragging.disable();
                        points[index] = {'start': false, 'skip': false, 'break': true, 'latLng':latLng};
                        visualAsset.newLatLng(points, index, latLng);
                        index++;
                        self.disable();
                        create.dataPoint.enable();
                    });
                },
            disable:
                function(){
                    $(map._container).off('click');
                    this.btn.state('inactive');
                    map.dragging.enable();
                },
            btn:
                L.easyButton ({
                    states: [
                    {
                        stateName:  'inactive',
                        icon:       '<i class="material-icons md-18">broken_image</i>',
                        title:      'Create a break',
                        onClick:    function(btn, map){
                            create.dataPoint.disable();
                            create.breakPoint.enable();
                        }
                    },
                    {
                        stateName:  'active',
                        icon:       '<i class="material-icons md-18">clear</i>',
                        title:      'Cancel',
                        onClick:    function(btn, map){
                            create.breakPoint.disable();
                        }
                    }]
                })
        },
        btn: 
            L.easyButton ({
                states: [
                {
                    stateName:  'collapse',
                    icon:       '<i class="material-icons md-18">timeline</i>',
                    title:      'Create new data points',
                    onClick:    function(btn, map){
                        create.btn.state('expand');
                        create.dataPoint.btn.enable();
                        create.zeroGrowth.btn.enable();
                        create.breakPoint.btn.enable();

                        data.collapse();
                        edit.collapse();
                        annotation.collapse();
                        time.collapse();
                    }
                },
                {
                    stateName:  'expand',
                    icon:       '<i class="material-icons md-18">expand_less</i>',
                    title:      'Collapse',
                    onClick:    function(btn, map){
                        create.collapse();
                    }
                }]
            })
    }

    //all editing tools are a part of the edit object
    var edit = {
        collapse:
            function(){
                this.btn.state('collapse');
                this.deletePoint.btn.disable();
                this.cut.btn.disable();
                this.addData.btn.disable();
                this.addZeroGrowth.btn.disable();
                this.addBreak.btn.disable();

                this.deletePoint.disable();
                this.cut.disable();
                this.addData.disable();
                this.addZeroGrowth.disable();
                this.addBreak.disable();
            },
        deletePoint: {
            active:
                false,
            action:
                function(i){
                    undo.push();

                    if(points[i].start){ 
                        if(points[i-1] != undefined && points[i-1].break){
                            alert("You cannot delete this point!");
                        }
                        else{
                            if(points[0] != undefined){
                                var latLng = points[1].latLng;
                                points[1] = {'start': true, 'skip': false, 'break': false, 'latLng': latLng}; 
                            }
                            second_points = Object.values(points).splice(i+1, index-1);
                            second_points.map(function(e){
                                points[i] = e;
                                i++;
                            });
                            index = index - 2;
                            delete points[index];
                            delete points[index+1];
                        }
                    }
                    else if(points[i].break){
                        second_points = Object.values(points).splice(i+1, index-1);
                        second_points.map(function(e){
                            points[i] = e;
                            i++;
                        });
                        index = index - 1;
                        delete points[index];
                    }
                    else if(points[i].skip){
                        second_points = Object.values(points).splice(i+1, index-1);
                        second_points.map(function(e){
                            e.year--;
                            points[i] = e;
                            i++
                        });
                        index = index - 1;
                        delete points[index];
                    }
                    else{
                        if(points[i].earlywood && points[i+1].earlywood != undefined){
                            j = i+1;
                        }
                        else if(points[i-1].earlywood != undefined){
                            j = i;
                            i--;
                        }
                        //get the second half of the data
                        second_points = Object.values(points).splice(j+1, index-1);
                        second_points.map(function(e){
                            e.year--;
                            points[i] = e;
                            i++;
                        })
                        index = i-1;
                        delete points[i];
                        delete points[i+1];
                    }

                    visualAsset.reload();
                    this.disable();
                },
            enable:
                function(){
                    this.btn.state('active');
                    this.active = true;
                },
            disable:
                function(){
                    $(map._container).off('click');
                    this.btn.state('inactive');
                    this.active = false;
                },
            btn:
                L.easyButton ({
                    states: [
                    {
                        stateName:  'inactive',
                        icon:       '<i class="material-icons md-18">delete</i>',
                        title:      'Enable Delete (Alt+D)',
                        onClick: function(btn, map){
                            edit.cut.disable();
                            edit.addData.disable();
                            edit.addZeroGrowth.disable();
                            edit.addBreak.disable();
                            edit.deletePoint.enable();
                        }
                    },
                    {
                        stateName:  'active',
                        icon:       '<i class="material-icons md-18">clear</i>',
                        title:      'Disable Delete (Alt+D)',
                        onClick:    function(btn, map){
                            edit.deletePoint.disable()
                        }
                    }]
                })
        },
        cut: {
            active:
                false,
            point:
                -1,
            action:
                function(i, j){
                    undo.push();

                    if(i > j){
                        trimmed_points = Object.values(points).splice(i, index-1);
                        var k = 0;
                        points = {};
                        trimmed_points.map(function(e){
                            if(!k){
                                points[k] = {"start": true,"latLng": e.latLng, "measurable": false};
                            }   
                            else{    
                                points[k] = e;
                            }
                            k++;
                        })
                        index = k;
                    }
                    else if(i < j){
                        points = Object.values(points).splice(0, i);
                        index = i;
                    }
                    else{
                        alert("You cannot select the same point");
                    }

                    visualAsset.reload();
                    edit.cut.disable();
                },
            enable:
                function(){
                    this.btn.state('active');
                    this.active = true;
                    this.point = -1;
                },
            disable:
                function(){
                    $(map._container).off('click');
                    this.btn.state('inactive');
                    this.active = false;
                    this.point = -1;
                },
            btn:
                L.easyButton ({
                    states: [
                    {
                        stateName:  'inactive',
                        icon:       '<i class="material-icons md-18">content_cut</i>',
                        title:      'Cut a portion of the series',
                        onClick:    function(btn, map){
                            edit.deletePoint.disable();
                            edit.addData.disable();
                            edit.addZeroGrowth.disable();
                            edit.addBreak.disable();
                            edit.cut.enable();
                        }
                    },
                    {
                        stateName:  'active',
                        icon:       '<i class="material-icons md-18">clear</i>',
                        title:      'Cancel cutting',
                        onClick:    function(btn, map){
                            edit.cut.disable()
                        }
                    }]
                })
        },
        addData: {
            active:
                false,
            action:
                function(i){
                    var new_points = points;
                    var second_points = Object.values(points).splice(i+1, index-1);
                    var first_point = true;
                    var k = i+1;
                    var year_adjusted = points[i+1].year;

                    document.getElementById('map').style.cursor = "pointer";
                    create.dataPoint.active = true;
                    interactiveMouse.hbarFrom(points[i].latLng);

                    var self = this;
                    $(map._container).click(function(e){
                        var latLng = map.mouseEventToLatLng(e);
                        map.dragging.disable();

                        interactiveMouse.hbarFrom(latLng);

                        if(first_point){
                            new_points[k] = {'start': false, 'skip': false, 'break': false, 'year': year_adjusted, 'earlywood': true, 'latLng':latLng};
                            visualAsset.newLatLng(new_points, k, latLng);
                            k++;
                            first_point = false;
                        }
                        else{
                            new_points[k] = {'start': false, 'skip': false, 'break': false, 'year': year_adjusted, 'earlywood': false, 'latLng':latLng};
                            year_adjusted++;
                            visualAsset.newLatLng(new_points, k, latLng);
                            k++;
                            second_points.map(function(e){
                                e.year++;
                                new_points[k] = e;
                                k++;
                            })
                            $(map._container).off('click');

                            undo.push();

                            points = new_points;
                            index = k;
                            year++;

                            visualAsset.reload();
                            self.disable();
                        }
                    });
                },
            enable:
                function(){
                    this.btn.state('active');
                    this.active = true;
                },
            disable:
                function(){
                    $(map._container).off('click');
                    this.btn.state('inactive');
                    this.active = false;
                    //map.dragging.enable();
                    document.getElementById('map').style.cursor = "default";
                    interactiveMouse.layer.clearLayers();
                    create.dataPoint .active = false;
                },
            btn:
                L.easyButton ({
                    states: [
                    {
                        stateName:  'inactive',
                        icon:       '<i class="material-icons md-18">add_circle_outline</i>',
                        title:      'Add a point in the middle of the series',
                        onClick:    function(btn, map){
                            edit.deletePoint.disable();
                            edit.cut.disable();
                            edit.addZeroGrowth.disable();
                            edit.addBreak.disable();
                            edit.addData.enable();
                        }
                    },
                    {
                        stateName:  'active',
                        icon:       '<i class="material-icons md-18">clear</i>',
                        title:      'Cancel',
                        onClick:    function(btn, map){
                            edit.addData.disable();
                        }
                    }]
                })
        },
        addZeroGrowth: {
            active:
                false,
            action:
                function(i){
                    undo.push();

                    var second_points = Object.values(points).splice(i+1, index-1);
                    points[i+1] = {'start': false, 'skip': true, 'break': false, 'year': points[i].year+1};
                    k=i+2;
                    second_points.map(function(e){
                        e.year++
                        points[k] = e;
                        k++;
                    })
                    $(map._container).off('click');
                    index = k;
                    year++;

                    visualAsset.reload();
                    this.disable();
                },
            enable:
                function(){
                    this.btn.state('active');
                    this.active = true;
                },
            disable:
                function(){
                    $(map._container).off('click');
                    this.btn.state('inactive');
                    this.active = false;
                    map.dragging.enable();
                    interactiveMouse.layer.clearLayers();
                    create.dataPoint.active = false;
                },
            btn:
                L.easyButton ({
                    states: [
                    {
                        stateName:  'inactive',
                        icon:       '<i class="material-icons md-18">update</i>',
                        title:      'Add a zero growth year in the middle of the series',
                        onClick:    function(btn, map){
                            edit.deletePoint.disable();
                            edit.cut.disable();
                            edit.addData.disable();
                            edit.addBreak.disable();
                            edit.addZeroGrowth.enable();
                        }
                    },
                    {
                        stateName:  'active',
                        icon:       '<i class="material-icons md-18">clear</i>',
                        title:      'Cancel',
                        onClick:    function(btn, map){
                            edit.addZeroGrowth.disable();
                        }
                    }]
                })
        },
        addBreak: {
            active:
                false,
            action:
                function(i){
                    var new_points = points;
                    var second_points = Object.values(points).splice(i+1, index-1);
                    var first_point = true;
                    var k = i+1;

                    document.getElementById('map').style.cursor = "pointer";
                    create.dataPoint.active = true;
                    interactiveMouse.hbarFrom(points[i].latLng);

                    var self = this;
                    $(map._container).click(function(e){
                        var latLng = map.mouseEventToLatLng(e);
                        map.dragging.disable();

                        interactiveMouse.hbarFrom(latLng);

                        if(first_point){
                            new_points[k] = {'start': false, 'skip': false, 'break': true, 'latLng':latLng};
                            visualAsset.newLatLng(new_points, k, latLng);
                            k++;
                            first_point = false;
                        }
                        else{
                            new_points[k] = {'start': true, 'skip': false, 'break': false, 'latLng':latLng};
                            visualAsset.newLatLng(new_points, k, latLng);
                            k++;
                            second_points.map(function(e){
                                new_points[k] = e;
                                k++;
                            })
                            $(map._container).off('click');

                            undo.push();

                            points = new_points;
                            index = k;

                            visualAsset.reload();
                            self.disable();
                        }
                    });
                },
            enable:
                function(){
                    this.btn.state('active');
                    this.active = true;
                },
            disable:
                function(){
                    $(map._container).off('click');
                    this.btn.state('inactive');
                    this.active = false;
                    map.dragging.enable();
                    interactiveMouse.layer.clearLayers();
                    create.dataPoint.active = false;
                },
            btn:
                L.easyButton ({
                    states: [
                    {
                        stateName:  'inactive',
                        icon:       '<i class="material-icons md-18">broken_image</i>',
                        title:      'Add a break in the series',
                        onClick:    function(btn, map){
                            edit.deletePoint.disable();
                            edit.cut.disable();
                            edit.addData.disable();
                            edit.addZeroGrowth.disable();
                            edit.addBreak.enable();
                        }
                    },
                    {
                        stateName:  'active',
                        icon:       '<i class="material-icons md-18">clear</i>',
                        onClick:    function(btn, map){
                            edit.addBreak.disable();
                        }
                    }]
                })
        },
        btn:
            L.easyButton ({
                states: [
                {
                    stateName:  'collapse',
                    icon:       '<i class="material-icons md-18">edit</i>',
                    title:      'Edit and delete data points from the series',
                    onClick:    function(btn, map){
                        edit.btn.state('expand');
                        edit.deletePoint.btn.enable();
                        edit.cut.btn.enable();
                        edit.addData.btn.enable();
                        edit.addZeroGrowth.btn.enable();
                        edit.addBreak.btn.enable();

                        annotation.collapse();
                        create.collapse();
                        time.collapse();
                        data.collapse();
                    }
                },
                {
                    stateName:  'expand',
                    icon:       '<i class="material-icons md-18">expand_less</i>',
                    title:      'Collapse',
                    onClick:    function(btn, map){
                        edit.collapse();
                    }
                }]
            })
    }

    //all annotations tools will fall under the annotation object
    var annotation = {
        index:
            0,
        markers:
            new Array(),
        layer:
            L.layerGroup().addTo(map),
        newAnnotation:
            function(i){
                ref = annotations[i];
                if(ref.dateMarker){
                    this.markers.push(L.circle(ref.latLng, {radius: .0002, color: "#000", weight: '6'}));
                    this.markers[i].on('click', function(e){
                        annotation.deleteAnnotation.action(i);
                    })
                    this.layer.addLayer(this.markers[i]);
                }
                else if(ref.lineMarker){
                    this.markers.push(L.polyline([ref.first_point, ref.second_point], {color: '#000', weight: '6'}));
                    this.markers[i].on('click', function(e){
                        annotation.deleteAnnotation.action(i);
                    });
                    this.layer.addLayer(this.markers[i]);
                }
            },
        reload:
            function(){
                this.layer.clearLayers();
                this.markers = new Array();
                this.index = 0;
                if(annotations != undefined){
                    var reduced = Object.values(annotations).filter(e => e != undefined);
                    annotations = {};
                    reduced.map((e, i) => annotations[i] = e);

                    Object.values(annotations).map(function(e, i){annotation.newAnnotation(i);annotation.index++});
                }
            },
        collapse:
            function(){
                this.btn.state('collapse');
                this.dateMarker.btn.disable();
                this.lineMarker.btn.disable();
                this.deleteAnnotation.btn.disable();

                this.dateMarker.disable();
                this.lineMarker.disable();
                this.deleteAnnotation.disable();
            },
        dateMarker: {
            action:
                function(i, latLng){
                    annotations[i] = {'dateMarker': true, 'lineMarker': false, 'latLng': latLng};
                    annotation.newAnnotation(i);
                },
            enable:
                function(){
                    this.btn.state('active');
                    var self = this;
                    $(map._container).click(function(e){
                        latLng = map.mouseEventToLatLng(e);
                        self.action(annotation.index, latLng);
                        annotation.index++;
                    });
                },
            disable:
                function(){
                    this.btn.state('inactive');
                    $(map._container).off('click');
                },
            btn:
                L.easyButton ({
                    states: [
                    {
                        stateName:  'inactive',
                        icon:       '<i class="material-icons md-18">fiber_manual_record</i>',
                        title:      'Create a circle marker',
                        onClick:    function(btn, map){
                            annotation.deleteAnnotation.disable();
                            annotation.lineMarker.disable();
                            annotation.dateMarker.enable();
                        }
                    },
                    {
                        stateName:  'active',
                        icon:       '<i class="material-icons md-18">clear</i>',
                        title:      'Cancel',
                        onClick:    function(btn, map){
                            annotation.dateMarker.disable();
                        }
                    }]
                })
        },
        lineMarker: {
            active:
                false,
            action:
                function(i, first_point, second_point){
                    annotations[i] = {'dateMarker': false, 'lineMarker': true, 'first_point': first_point, 'second_point': second_point};
                    annotation.newAnnotation(i);
                },
            enable:
                function(){
                    this.btn.state('active');
                    var start = true;
                    var self = this;
                    $(map._container).click(function(e){
                        if(start){
                            first_point = map.mouseEventToLatLng(e);
                            self.active = true;
                            interactiveMouse.lineFrom(first_point);
                            start = false;
                        }
                        else{
                            second_point = map.mouseEventToLatLng(e);
                            self.action(annotation.index, first_point, second_point);
                            annotation.index++;
                            self.disable();
                        }
                    })
                },
            disable:
                function(){
                    $(map._container).off('click');
                    this.btn.state('inactive');
                    this.active = false;
                    interactiveMouse.layer.clearLayers();
                },
            btn:
                L.easyButton({
                    states: [
                    {
                        stateName:  'inactive',
                        icon:       '<i class="material-icons md-18">format_clear</i>',
                        title:      'Create a line marker',
                        onClick:    function(btn, map){
                            annotation.dateMarker.disable();
                            annotation.deleteAnnotation.disable();
                            annotation.lineMarker.enable();
                        } 
                    },
                    {
                        stateName:  'active',
                        icon:       '<i class="material-icons md-18">clear</i>',
                        title:      'Cancel',
                        onClick:    function(btn, map){
                            annotation.lineMarker.disable();
                        }
                    }]
                })
        },
        deleteAnnotation: {
            active:
                false,
            action:
                function(i){
                    if(this.active){
                        delete annotations[i]
                        annotation.reload();
                        this.disable();
                    }
                },
            enable: 
                function(){
                    annotation.dateMarker.btn.state('inactive');
                    $(map._container).off('click');
                    this.btn.state('active');
                    this.active = true;
                },
            disable:
                function(){
                    $(map._container).off('click');
                    this.btn.state('inactive');
                    this.active = false;
                },
            btn:
                L.easyButton ({
                    states: [
                    {
                        stateName:  'inactive',
                        icon:       '<i class="material-icons md-18">delete</i>',
                        title:      'Delete an annotation',
                        onClick:    function(btn, map){
                            annotation.dateMarker.disable();
                            annotation.lineMarker.disable();
                            annotation.deleteAnnotation.enable();
                        }
                    },
                    {
                        stateName:  'active',
                        icon:       '<i class="material-icons md-18">clear</i>',
                        title:      'Cancel',
                        onClick:    function(btn, map){
                            annotation.deleteAnnotation.disable();
                        }
                    }]
                })
        },
        btn:
            L.easyButton ({
                states: [
                {
                    stateName:  'collapse',
                    icon:       '<i class="material-icons md-18">message</i>',
                    title:      'Create annotations',
                    onClick:    function(btn, map){
                        btn.state('expand');
                        annotation.dateMarker.btn.enable();
                        annotation.lineMarker.btn.enable();
                        annotation.deleteAnnotation.btn.enable();

                        edit.collapse();
                        create.collapse();
                        time.collapse();
                        data.collapse();
                    }
                },
                {
                    stateName:  'expand',
                    icon:       '<i class="material-icons md-18">expand_less</i>',
                    title:      'Collapse',
                    onClick:    function(btn, map){
                        annotation.collapse();
                    }
                }]
            })
    }

    //displaying and dowloading data fall under the data object
    var data = {
        download: {
            //the following three functions are used for formating data for download
            toFourCharString: 
                function(n){
                    var string = n.toString();

                    if(string.length == 1){
                        string = "   " + string;
                    }
                    else if(string.length == 2){
                        string = "  " + string;
                    }
                    else if(string.length == 3){
                        string = " " + string;
                    }
                    else if(string.length == 4){
                        string = string;
                    }
                    else if(string.length >= 5){
                        alert("Value exceeds 4 characters");
                        throw "error in toFourCharString(n)";
                    }
                    else{
                        alert("toSixCharString(n) unknown error");
                        throw "error";
                    }
                    return string;
                },
            toSixCharString:
                function(n){
                    var string = n.toString();

                    if(string.length == 1){
                        string = "     " + string;
                    }
                    else if(string.length == 2){
                        string = "    " + string;
                    }
                    else if(string.length == 3){
                        string = "   " + string;
                    }
                    else if(string.length == 4){
                        string = "  " + string;
                    }
                    else if(string.length == 5){
                        string = " " + string;
                    }
                    else if(string.length >= 6){
                        alert("Value exceeds 5 characters");
                        throw "error in toSixCharString(n)";
                    }
                    else{
                        alert("toSixCharString(n) unknown error");
                        throw "error";
                    }
                    return string;
                },
            toEightCharString: 
                function(n){
                    var string = n.toString();
                    if(string.length == 0){
                        string = string + "        ";
                    }
                    else if(string.length == 1){
                        string = string + "       ";
                    }
                    else if(string.length == 2){
                        string = string + "      ";
                    }
                    else if(string.length == 3){
                        string = string + "     ";
                    }
                    else if(string.length == 4){
                        string = string + "    ";
                    }
                    else if(string.length == 5){
                        string = string + "   ";
                    }
                    else if(string.length == 6){
                        string = string + "  ";
                    }
                    else if(string.length == 7){
                        string = string + " ";
                    }
                    else if(string.length >= 8){
                        alert("Value exceeds 7 characters");
                        throw "error in toEightCharString(n)";
                    }
                    else{
                        alert("toSixCharString(n) unknown error");
                        throw "error";
                    }
                    return string;
                },
            action:
                function(){
                    if(points != undefined && points[1] != undefined){
                        var sum_string = "";
                        var ew_string = "";
                        var lw_string = "";

                        y = points[1].year;
                        sum_points = Object.values(points).filter(function(e){
                            if(e.earlywood != undefined){
                                return !(e.earlywood);
                            }
                            else{
                                return true;
                            }
                        });

                        if(sum_points[1].year%10 > 0){
                            sum_string = sum_string.concat(data.download.toEightCharString(assetName) + data.download.toFourCharString(sum_points[1].year));
                        }
                        sum_points.map(function(e, i, a){
                            if(!e.start){
                                if(e.year%10 == 0){
                                    sum_string = sum_string.concat("\r\n" + data.download.toEightCharString(assetName) + data.download.toFourCharString(e.year));
                                }
                                while(e.year > y){
                                    sum_string = sum_string.concat("    -1");
                                    y++;
                                    if(y%10 == 0){
                                        sum_string = sum_string.concat("\r\n" + data.download.toFourCharString(e.year));
                                    }
                                }
                                if(e.skip){
                                    sum_string = sum_string.concat("     0");
                                    y++;
                                }
                                else{
                                    length = Math.round(map.distance(last_latLng, e.latLng)*1000000)
                                    if(length == 9999){
                                        length = 9998;
                                    }
                                    if(length == 999){
                                        length = 998;
                                    }

                                    length_string = data.download.toSixCharString(length); 

                                    sum_string = sum_string.concat(length_string);
                                    last_latLng = e.latLng;
                                    y++;
                                }
                            }
                            else{
                                last_latLng = e.latLng;
                            }
                        });
                        sum_string = sum_string.concat(" -9999");

                        y = points[1].year;

                        if(points[1].year%10 > 0){
                            ew_string = ew_string.concat(data.download.toEightCharString(assetName) + data.download.toFourCharString(points[1].year));
                            lw_string = lw_string.concat(data.download.toEightCharString(assetName) + data.download.toFourCharString(points[1].year));
                        }

                        Object.values(points).map(function(e, i, a){
                            if(!e.start){
                                if(e.year%10 == 0){
                                    if(e.skip){
                                        ew_string = ew_string.concat("\r\n" + data.download.toEightCharString(assetName) + data.download.toFourCharString(e.year));
                                        lw_string = lw_string.concat("\r\n" + data.download.toEightCharString(assetName) + data.download.toFourCharString(e.year));
                                    }
                                    else if(e.earlywood){
                                        ew_string = ew_string.concat("\r\n" + data.download.toEightCharString(assetName) + data.download.toFourCharString(e.year));
                                    }
                                    else{
                                        lw_string = lw_string.concat("\r\n" + data.download.toEightCharString(assetName) + data.download.toFourCharString(e.year));
                                    }
                                }
                                while(e.year > y){
                                    ew_string = ew_string.concat("    -1");
                                    lw_string = lw_string.concat("    -1");
                                    y++;
                                    if(y%10 == 0){
                                        ew_string = ew_string.concat("\r\n" + data.download.toEightCharString(assetName) + data.download.toFourCharString(e.year));
                                        lw_string = lw_string.concat("\r\n" + data.download.toEightCharString(assetName) + data.download.toFourCharString(e.year));
                                    }
                                }
                                if(e.skip){
                                    if(e.earlywood){
                                        ew_string = ew_string.concat("     0");
                                    }
                                    else{
                                        lw_string = lw_string.concat("     0");
                                        y++;
                                    }
                                }
                                else{
                                    length = Math.round(map.distance(last_latLng, e.latLng)*1000000)
                                    if(length == 9999){
                                        length = 9998;
                                    }
                                    if(length == 999){
                                        length = 998;
                                    }

                                    length_string = data.download.toSixCharString(length); 

                                    if(e.earlywood){
                                        ew_string = ew_string.concat(length_string);
                                        last_latLng = e.latLng;
                                    }
                                    else{
                                        lw_string = lw_string.concat(length_string);
                                        last_latLng = e.latLng;
                                        y++;
                                    }
                                }
                            }
                            else{
                                last_latLng = e.latLng;
                            }
                        });
                        ew_string = ew_string.concat(" -9999");
                        lw_string = lw_string.concat(" -9999");
                    
                        console.log(sum_string);
                        console.log(ew_string);
                        console.log(lw_string);

                        var zip = new JSZip();
                        zip.file((assetName+'.raw'), sum_string);
                        zip.file((assetName+'.lwr'), lw_string);
                        zip.file((assetName+'.ewr'), ew_string);

                        zip.generateAsync({type:"blob"})
                        .then(function (blob) {
                            saveAs(blob, (assetName+'.zip'));
                        });
                    }
                    else{
                        alert("There is no data to download");
                    }
                },
            btn:
                L.easyButton ({
                    states: [
                    {
                        stateName:  'download',
                        icon:       '<i class="material-icons md-18">file_download</i>',
                        title:      'download formated data',
                        onClick:    function(btn, map){
                            data.download.action();
                        }
                    }]
                })
        },
        dialog:
            L.control.dialog({'size': [240, 350], 'anchor': [5, 50], 'initOpen': false})
                .setContent('<h3>There are no data points to measure</h3>')
                .addTo(map),
        action:
            function(){
                if(points[0] != undefined){
                    var y = points[1].year;
                    string = "<table><tr><th style='width: 45%;'>Year</th><th style='width: 70%;'>Length</th></tr>";
                    Object.values(points).map(function(e, i, a){
                        if(e.start){
                            last_point = e;
                        }
                        else if(e.break){
                            break_length = Math.round(map.distance(last_point.latLng, e.latLng)*1000000)/1000;
                            last_point = e;
                        }
                        else{
                            while(e.year > y){
                                string = string.concat("<tr><td>" + y + "-</td><td>N/A</td></tr>");
                                y++;
                            }
                            if(e.skip){
                                string = string.concat("<tr><td>"+ e.year + "-</td><td>0 mm</td></tr>");
                            }
                            else{
                                length = Math.round(map.distance(last_point.latLng, e.latLng)*1000000)/1000;
                                if(last_point.break){
                                    length += break_length;
                                }
                                if(length == 9.999){
                                    length = 9.998;
                                }
                                if(e.earlywood){
                                    wood = "e";
                                    row_color = "#00d2e6";
                                }
                                else{
                                    wood = "l";
                                    row_color = "#00838f";
                                    y++;
                                }
                                last_point = e;
                                string = string.concat("<tr style='color:" + row_color + ";'>");
                                string = string.concat("<td>"+ e.year + wood + "</td><td>" + length + " mm</td></tr>");
                            }
                        }
                    });
                    this.dialog.setContent(string + "</table>");
                }
                this.dialog.open();
                return;
            },
        collapse:
            function(){
                this.btn.state('collapse');
                this.dialog.close();
                this.download.btn.disable();
            },
        btn:
            L.easyButton ({
                states: [
                {
                    stateName:  'collapse',
                    icon:       '<i class="material-icons md-18">straighten</i>',
                    title:      'Open Data',
                    onClick:    function(btn, map){
                        btn.state('expand');
                        data.action();
                        data.download.btn.enable();

                        create.collapse();
                        time.collapse();
                        edit.collapse();
                        annotation.collapse();
                    }  
                },
                {
                    stateName:  'expand',
                    icon:       '<i class="material-icons md-18">expand_less</i>',
                    title:      'Collapse',
                    onClick:    function(btn, map){
                        data.collapse();
                    } 
                }]
            })
    }

    //locking the dialog boxes so the cannot be moved or resized
    time.setYearFromStart.dialog.lock();
    time.setYearFromEnd.dialog.lock();

    //grouping the buttons into their respective toolbars
    var undoRedoBar = L.easyBar([undo.btn, redo.btn]);
    undo.btn.disable();
    redo.btn.disable();

    var timeBar = L.easyBar([time.btn, time.setYearFromStart.btn, time.setYearFromEnd.btn, time.shift.forwardBtn, time.shift.backwardBtn]);
    time.setYearFromStart.btn.disable();
    time.setYearFromEnd.btn.disable();
    time.shift.forwardBtn.disable();
    time.shift.backwardBtn.disable();

    var createBar = L.easyBar([create.btn, create.dataPoint.btn, create.zeroGrowth.btn, create.breakPoint.btn]);
    create.dataPoint.btn.disable();
    create.zeroGrowth.btn.disable();
    create.breakPoint.btn.disable();

    var editBar = L.easyBar([edit.btn, edit.deletePoint.btn, edit.cut.btn, edit.addData.btn, edit.addZeroGrowth.btn, edit.addBreak.btn]);
    edit.deletePoint.btn.disable();
    edit.cut.btn.disable();
    edit.addData.btn.disable();
    edit.addZeroGrowth.btn.disable();
    edit.addBreak.btn.disable();

    var annotationBar = L.easyBar([annotation.btn, annotation.dateMarker.btn, annotation.lineMarker.btn, annotation.deleteAnnotation.btn]);
    annotation.dateMarker.btn.disable();
    annotation.lineMarker.btn.disable();
    annotation.deleteAnnotation.btn.disable();

    var dataBar = L.easyBar([data.btn, data.download.btn]);
    data.download.btn.disable();

    //the default minimap is square which doesn't look nice
    var miniMap = new L.Control.MiniMap(miniLayer, {
        width: 500,
        height: 25,
        toggleDisplay: true,
        zoomAnimation: false,
        zoomLevelOffset: -3,
        zoomLevelFixed: -3
    });

    //creating the layer controls
    var baseLayer = {
        "Tree Ring": layer
    };

    var overlay = {
        "Points": visualAsset.markerLayer,
        "H-bar": interactiveMouse.layer,
        "Lines": visualAsset.lineLayer
    };

    //doc_keyUp(e) takes a keyboard event, for keyboard shortcuts
    var doc_keyUp = function(e){
        //ALT + S
        if(e.altKey && (e.keyCode == 83 || e.keycode == 115)){
            create.zeroGrowth.action();
        }
        //ALT + C
        if(e.altKey && (e.keyCode == 67 || e.keycode == 99)){
            if(create.dataPoint.btn._currentState.stateName == 'inactive'){
                create.dataPoint.enable();
            }
            else{
                create.dataPoint.disable();
            }
        }
    };
};