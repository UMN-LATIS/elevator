var src;
var map;

var map = L.map('map', {
    fullscreenControl: true,
    zoomSnap: 0,
    crs: L.CRS.Simple,
    drawControl: true,
    layers: [],
    doubleClickZoom: false,
    zoomControl: false
}).setView([0, 0], 0);

var layer = L.tileLayer.elevator(function(coords, tile, done) {
    var error;
    var params = {Bucket: 'elevator-assets', Key: "testasset5/tiledBase_files/" + coords.z + "/" + coords.x + "_" + coords.y + ".jpeg"};
    tile.onload = (function(done, error, tile) {
        return function() {
            done(error, tile);
        }
    })(done, error, tile);
    tile.src = "https://s3.amazonaws.com/" + params.Bucket + "/" + params.Key;
    //tile.src = params.Key;
    src = tile.src;
    return tile.src;
},
{
    width: 231782,
    height: 4042,
    tileSize :254,
    maxZoom: 19 - 1,
    overlap: 1,
    pixelsPerMillimeter: 468, //(NEW)
    lineColor: 'blue'
}).addTo(map);

//minimap
var miniLayer = new L.tileLayer.elevator(function(coords, tile, done) {
    var error;
    var params = {Bucket: 'elevator-assets', Key: "testasset5/tiledBase_files/" + coords.z + "/" + coords.x + "_" + coords.y + ".jpeg"};
    tile.onload = (function(done, error, tile) {
        return function() {
            done(error, tile)
        }
    })(done, error, tile);
    tile.src = "https://s3.amazonaws.com/" + params.Bucket + "/" + params.Key;
    //tile.src = params.Key;
    src = tile.src;
    return tile.src;
},
{
    width: 231782,
    height: 4042,
    tileSize: 254,
    maxZoom: 13,
    overlap: 1,
});

var miniMap = new L.Control.MiniMap(miniLayer, {
    width: 950,
    height: 50,
    //position: "topright", //in case you would like to change position of the minimap
    toggleDisplay: true,
    zoomAnimation: false,
    zoomLevelOffset: -3,
    zoomLevelFixed: -3
});

miniMap.addTo(map);


var loadInterface = function() {    

    map.on('movestart', function(e){
        document.getElementById('map').style.cursor = 'move';
    })
    map.on('moveend', function(e){
        if(collect.dataPoint.active || annotation.lineMarker.active){
            document.getElementById('map').style.cursor = 'pointer';
        }
        else{
            document.getElementById('map').style.cursor = 'default';
        }
    })

    document.getElementById('map').style.cursor = 'default';


    //map scrolling
    var mapSize = map.getSize();    //size of the map used for map scrolling
    var mousePos = 0;               //an initial mouse position

    map.on('mousemove', function(e){
        var oldMousePos = mousePos;      //save the old mouse position
        mousePos = e.containerPoint;    //container point of the mouse
        var mouseLatLng = e.latlng;         //latLng of the mouse
        var mapCenter = map.getCenter();    //center of the map   

        //left bound of the map
        if(mousePos.x <= 40 && mousePos.y > 450 && oldMousePos.x > mousePos.x){
            //map.panTo([mapCenter.lat, (mapCenter.lng - .015)]);     //defines where the map view should move to
            map.panBy([-150, 0]);
        }
        //right bound of the map
        if(mousePos.x + 40 > mapSize.x && mousePos.y > 100 && oldMousePos.x < mousePos.x){
            //map.panTo([mapCenter.lat, (mapCenter.lng + .015)]);
            map.panBy([150, 0]);
        }
        //upper bound of the map
        if(mousePos.x + 40 < mapSize.x && mousePos.y < 40 && oldMousePos.y > mousePos.y){
            //map.panTo([mapCenter.lat, (mapCenter.lng + .015)]);
            map.panBy([0, -40]);
        }
        //lower bound of the map
        if(mousePos.x >= 40 && mousePos.y > mapSize.y - 40 && oldMousePos.y < mousePos.y){
            //map.panTo([mapCenter.lat, (mapCenter.lng - .015)]);     //defines where the map view should move to
            map.panBy([0, 40]);
        }
    })


    //coordinate information in bottom left hand side of map
    var coordinatesDiv = document.createElement("div");
    coordinatesDiv.innerHTML = "<div class='leaflet-control-attribution leaflet-control'><p id='leaflet-coordinates-tag'></p></div>";
    document.getElementsByClassName("leaflet-bottom leaflet-left")[0].appendChild(coordinatesDiv);

    $(map._container).mousemove(function showCoordsAndZoom(e) {
        var coords = map.mouseEventToContainerPoint(e);
        var x = Math.floor(coords.x); //not really x and y coordinates, they're arbitrary and based on rounding the latitude and longitude (because this is mapping software)
        var y = Math.floor(coords.y);
        document.getElementById("leaflet-coordinates-tag").innerHTML = "X: " + x + "   Y: " + y;
    });


    //creating colored icons for points
    light_blue_icon = L.icon({
        iconUrl: 'images/light_blue_icon.png',
        iconSize:     [32, 32] // size of the icon
    });
    dark_blue_icon = L.icon({
        iconUrl: 'images/dark_blue_icon.png',
        iconSize:     [32, 32] // size of the icon
    });
    white_icon = L.icon({
        iconUrl: 'images/white_icon.png',
        iconSize:     [32, 32] // size of the icon
    });
    grey_icon = L.icon({
        iconUrl: 'images/grey_icon.png',
        iconSize:     [32, 32] // size of the icon
    });


    var POINTS = {};    //JSON with all the point data

    var YEAR = 0;           //year
    var EARLYWOOD = true;   //earlywood or latewood
    var INDEX = 0;      //points index


    var interactiveMouse = {
        layer:
            L.layerGroup().addTo(map),
        lineFrom:
            function(latLng){
                var self = this;
                $(map._container).mousemove(function lineToMouse(e){
                    //only create lines when collecting data
                    if(annotation.lineMarker.active){
                        self.layer.clearLayers();//continously delete previous lines
                        var mouseLatLng = map.mouseEventToLatLng(e);     //get the mouse pointers latlng
                        var point = map.latLngToLayerPoint(latLng);      //get the layer point of the given latlng

                        //create lines and add them to mouseLine layer
                        self.layer.addLayer(L.polyline([latLng, mouseLatLng], {color: '#000', weight: '6'}));
                    }
                });
            },
        hbarFrom:
            function(latLng){
                var self = this;
                $(map._container).mousemove(function lineToMouse(e){
                    //only create lines when collecting data
                    if(collect.dataPoint.active){
                        self.layer.clearLayers();//continously delete previous lines
                        var mousePoint = map.mouseEventToLayerPoint(e);  //get the mouse pointers layer point
                        var mouseLatLng = map.mouseEventToLatLng(e);     //get the mouse pointers latlng
                        var point = map.latLngToLayerPoint(latLng);      //get the layer point of the given latlng

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

                        //create lines and add them to mouseLine layer
                        self.layer.addLayer(L.polyline([latLng, mouseLatLng], {color: '#00BCD4', weight: '5'}));
                        self.layer.addLayer(L.polyline([topLeftPoint, bottomLeftPoint], {color: '#00BCD4', weight: '5'}));
                        self.layer.addLayer(L.polyline([topRightPoint, bottomRightPoint], {color: '#00BCD4', weight: '5'}));
                    }
                });
            }
    }

    var interactiveData = {
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
                 //erase the lines
                this.lineLayer.clearLayers();

                //plot the data back onto the map
                Object.values(POINTS).map(function(e, i){
                    if(e.latLng != undefined){
                        interactiveData.newLatLng(POINTS, i, e.latLng);
                    }
                });
            },
        previousLatLng:
            undefined,
        newLatLng:
            function(p, i, latLng){
                leafLatLng = L.latLng(latLng);   //leaflet is stupid and only uses latlngs that are created through L.latlng

                //check if index is the start point
                if(p[i].start){
                    var marker = L.marker(leafLatLng, {icon: white_icon, draggable: true, title: "Start Point"});
                }
                //check if point is earlywood
                else if(p[i].break){
                    var marker = L.marker(leafLatLng, {icon: white_icon, draggable: true, title: "Break Point"})
                }
                else if(p[i].earlywood){
                    var marker = L.marker(leafLatLng, {icon: light_blue_icon, draggable: true, title: "Year " + p[i].year + ", earlywood"});         
                }
                //otherwise it's latewood
                else{
                    var marker = L.marker(leafLatLng, {icon: dark_blue_icon, draggable: true, title: "Year " + p[i].year + ", latewood"});
                }

                if(p[i-1] != undefined && p[i-1].skip){
                    if(i-1){
                        average = L.latLng([(leafLatLng.lat + this.previousLatLng.lat)/2, (leafLatLng.lng + this.previousLatLng.lng)/2]);
                    }
                    else{
                        average = L.latLng([leafLatLng.lat, (leafLatLng.lng - .001)]);
                    }
                    skip_marker = L.marker(average, {icon: grey_icon, draggable: true, title: "Year " + p[i-1].year + ", None"});
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

    var undo = {
        stack:
            new Array(),
        push:
            function(){
                this.btn.enable();
                redo.btn.disable();
                redo.stack.length = 0;
                var restore_points = JSON.parse(JSON.stringify(POINTS));
                this.stack.push({'year': YEAR, 'earlywood': EARLYWOOD, 'index': INDEX, 'points': restore_points });
            },
        pop:
            function(){
                if(this.stack.length > 0){
                    redo.btn.enable();
                    var restore_points = JSON.parse(JSON.stringify(POINTS));
                    redo.stack.push({'year': YEAR, 'earlywood': EARLYWOOD, 'index': INDEX, 'points': restore_points});
                    dataJSON = this.stack.pop();

                    POINTS = JSON.parse(JSON.stringify(dataJSON.points));

                    INDEX = dataJSON.index;
                    YEAR = dataJSON.year;
                    EARLYWOOD = dataJSON.earlywood;

                    interactiveData.reload();

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

    var redo = {
        stack:
            new Array(),
        pop:
            function redo(){
                undo.btn.enable();
                var restore_points = JSON.parse(JSON.stringify(POINTS));
                undo.stack.push({'year': YEAR, 'earlywood': EARLYWOOD, 'index': INDEX, 'points': restore_points});
                dataJSON = this.stack.pop();

                POINTS = JSON.parse(JSON.stringify(dataJSON.points));

                INDEX = dataJSON.index;
                YEAR = dataJSON.year;
                EARLYWOOD = dataJSON.earlywood;

                interactiveData.reload();

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
                    if(POINTS[i].start){
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
                                
                                while(POINTS[i] != undefined){
                                    if(POINTS[i].start || POINTS[i].break){
                                    }
                                    else if(POINTS[i].earlywood){
                                        POINTS[i].year = new_year;
                                    }
                                    else{
                                        POINTS[i].year = new_year++;
                                    }
                                    i++;
                                }
                                interactiveData.reload();
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
                    if(!(POINTS[i+1] != undefined) || POINTS[i+1].break || POINTS[i+1].start){
                        this.dialog.open();
                        var self = this;        

                        document.getElementById('end_year_submit').addEventListener('click', function(){
                            new_year = document.getElementById('end_year_input').value;
                            self.dialog.close();

                            if(new_year.toString().length > 4){
                                alert("Year cannot exceed 4 digits!");
                            }
                            else{
                                undo.push();
                                
                                if(i == INDEX){
                                    YEAR = new_year;
                                }

                                while(POINTS[i] != undefined){
                                    if(POINTS[i].start || POINTS[i].break){
                                    }
                                    else if(POINTS[i].earlywood){
                                        POINTS[i].year = new_year--;
                                    }
                                    else{
                                        POINTS[i].year = new_year;
                                    }
                                    i--;
                                }
                                interactiveData.reload();
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
                    for(i = 0; i < INDEX; i++){
                        if(!POINTS[i].start){
                            POINTS[i].year += x;
                        }
                    }
                    interactiveData.reload();
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
                        collect.collapse();
                        loadData.dialog.close();

                        time.btn.state('expand');
                        time.setYearFromStart.btn.enable();
                        time.setYearFromEnd.btn.enable();
                        time.shift.forwardBtn.enable();
                        time.shift.backwardBtn.enable();
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

    time.setYearFromStart.dialog.lock();
    time.setYearFromEnd.dialog.lock();

    var collect = {
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
                    this.btn.state('active');    //change the state of the 

                    //map.dragging.disable();  //leaflet doesn't differentiate between a click and a drag
                    document.getElementById('map').style.cursor = "pointer";

                    var self = this;
                    $(map._container).click(function startLine(e){
                        var latLng = map.mouseEventToLatLng(e);

                        undo.push();

                        if(self.startPoint){
                            POINTS[INDEX] = {'start': true, 'skip': false, 'break': false, 'latLng':latLng};
                            self.startPoint = false;
                        }
                        else{
                            POINTS[INDEX] = {'start': false, 'skip': false, 'break': false, 'year':YEAR, 'earlywood': EARLYWOOD, 'latLng':latLng};
                        }

                        interactiveData.newLatLng(POINTS, INDEX, latLng); //call newLatLng with current index and new latlng 

                        interactiveMouse.hbarFrom(latLng); //create the next mouseline from the new latlng

                        //avoid incrementing earlywood for start point
                        if(!POINTS[INDEX].start){
                            if(EARLYWOOD){
                                EARLYWOOD = false;
                            }
                            else{
                                EARLYWOOD = true;
                                YEAR++;
                            }
                        }

                        INDEX++;
                        self.active = true;     //don't remember why but we need to activate data_collect after one point is made
                    });
                },
            disable:
                function(){
                    $(map._container).off('click');  //turn off the mouse clicks from previous function
                    this.btn.state('inactive');  //switch the button state back to off
                    this.active = false;   //turn data_collect off
                    //map.dragging.enable();  //turn map dragging back on
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
                            collect.dataPoint.enable();
                        }
                    },
                    {
                        stateName:  'active',
                        icon:       '<i class="material-icons md-18">add_circle</i>',
                        title:      'End data collection (Alt+C)',
                        onClick:    function(btn, map){
                            collect.dataPoint.disable();
                        }
                    }]
                })
        },
        zeroGrowth: {
            action:
                function(){
                    undo.push();

                    POINTS[INDEX] = {'start': false, 'skip': true, 'break': false, 'year':YEAR}; //no point or latlng
                    YEAR++;
                    INDEX++;
                },
            btn:
                L.easyButton ({
                    states: [
                    {
                        stateName:  'skip-year',
                        icon:       '<i class="material-icons md-18">update</i>',
                        title:      'Add a zero growth year (Alt+S)',
                        onClick:    function(btn, map){
                            collect.zeroGrowth.action();
                        }
                    }]
                })
        },
        breakPoint: {
            enable:      
                function(){
                    this.btn.state('active');

                    collect.dataPoint.active = true;
                        
                    var self = this;
                    $(map._container).click(function(e){
                        var latLng = map.mouseEventToLatLng(e);

                        interactiveMouse.hbarFrom(latLng)

                        undo.push();

                        map.dragging.disable();
                        POINTS[INDEX] = {'start': false, 'skip': false, 'break': true, 'latLng':latLng};
                        interactiveData.newLatLng(POINTS, INDEX, latLng);
                        first_point = false;
                        INDEX++;
                        self.disable();
                        collect.dataPoint.enable();
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
                            collect.dataPoint.disable();
                            collect.breakPoint.enable();
                        }
                    },
                    {
                        stateName:  'active',
                        icon:       '<i class="material-icons md-18">clear</i>',
                        title:      'Cancel',
                        onClick:    function(btn, map){
                            collect.breakPoint.disable();
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
                        edit.collapse();
                        annotation.collapse();
                        time.collapse();
                        loadData.dialog.close();

                        collect.btn.state('expand');
                        collect.dataPoint.btn.enable();
                        collect.zeroGrowth.btn.enable();
                        collect.breakPoint.btn.enable();
                    }
                },
                {
                    stateName:  'expand',
                    icon:       '<i class="material-icons md-18">expand_less</i>',
                    title:      'Collapse',
                    onClick:    function(btn, map){
                        collect.collapse();
                    }
                }]
            })
    }

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

                    if(POINTS[i].start || POINTS[i].break){
                        second_points = Object.values(POINTS).splice(i+1, INDEX-1);
                        second_points.map(function(e){
                            POINTS[i] = e;
                            i++;
                        });
                        INDEX = INDEX - 1;
                        delete POINTS[INDEX];
                    }
                    else if(POINTS[i].skip){
                        second_points = Object.values(POINTS).splice(i+1, INDEX-1);
                        second_points.map(function(e){
                            e.year--;
                            POINTS[i] = e;
                            i++
                        });
                        INDEX = INDEX - 1;
                        delete POINTS[INDEX];
                    }
                    else{
                        if(POINTS[i].earlywood && POINTS[i+1].earlywood != undefined){
                            j = i+1;
                        }
                        else if(POINTS[i-1].earlywood != undefined){
                            j = i;
                            i--;
                        }
                        //get the second half of the data
                        second_points = Object.values(POINTS).splice(j+1, INDEX-1);
                        second_points.map(function(e){
                            e.year--;
                            POINTS[i] = e;
                            i++;
                        })
                        INDEX = i-1;
                        delete POINTS[i];
                        delete POINTS[i+1];
                    }

                    interactiveData.reload();
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
                        trimmed_points = Object.values(POINTS).splice(i, INDEX-1);
                        var k = 0;
                        POINTS = {};
                        trimmed_points.map(function(e){
                            if(!k){
                                POINTS[k] = {"start": true,"latLng": e.latLng, "measurable": false};
                            }   
                            else{    
                                POINTS[k] = e;
                            }
                            k++;
                        })
                        INDEX = k;
                    }
                    else if(i < j){
                        POINTS = Object.values(POINTS).splice(0, i);
                        INDEX = i;
                    }
                    else{
                        alert("cannot select dame point");
                    }

                    interactiveData.reload();
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
                    var new_points = POINTS
                    var second_points = Object.values(POINTS).splice(i+1, INDEX-1);
                    var first_point = true;
                    var k = i+1;
                    var year_adjusted = POINTS[i+1].year

                    document.getElementById('map').style.cursor = "pointer";
                    collect.dataPoint.active = true;
                    interactiveMouse.hbarFrom(POINTS[i].latLng);

                    var self = this;
                    $(map._container).click(function(e){
                        var latLng = map.mouseEventToLatLng(e);
                        map.dragging.disable();

                        interactiveMouse.hbarFrom(latLng);

                        if(first_point){
                            new_points[k] = {'start': false, 'skip': false, 'break': false, 'year': year_adjusted, 'earlywood': true, 'latLng':latLng};
                            interactiveData.newLatLng(new_points, k, latLng);
                            k++;
                            first_point = false;
                        }
                        else{
                            new_points[k] = {'start': false, 'skip': false, 'break': false, 'year': year_adjusted, 'earlywood': false, 'latLng':latLng};
                            year_adjusted++;
                            interactiveData.newLatLng(new_points, k, latLng);
                            k++;
                            second_points.map(function(e){
                                e.year++;
                                new_points[k] = e;
                                k++;
                            })
                            $(map._container).off('click');

                            undo.push();

                            POINTS = new_points;
                            INDEX = k;
                            YEAR++;

                            interactiveData.reload();
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
                    collect.dataPoint .active = false;
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

                    var second_points = Object.values(POINTS).splice(i+1, INDEX-1);
                    POINTS[i+1] = {'start': false, 'skip': true, 'break': false, 'year': POINTS[i].year+1};
                    k=i+2;
                    second_points.map(function(e){
                        e.year++
                        POINTS[k] = e;
                        k++;
                    })
                    $(map._container).off('click');
                    INDEX = k;
                    YEAR++;

                    interactiveData.reload();
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
                    collect.dataPoint.active = false;
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
                    var new_points = POINTS
                    var second_points = Object.values(POINTS).splice(i+1, INDEX-1);
                    var first_point = true;
                    var k = i+1;

                    document.getElementById('map').style.cursor = "pointer";
                    collect.dataPoint.active = true;
                    interactiveMouse.hbarFrom(POINTS[i].latLng);

                    var self = this;
                    $(map._container).click(function(e){
                        var latLng = map.mouseEventToLatLng(e);
                        map.dragging.disable();

                        interactiveMouse.hbarFrom(latLng);

                        if(first_point){
                            new_points[k] = {'start': false, 'skip': false, 'break': true, 'latLng':latLng};
                            interactiveData.newLatLng(new_points, k, latLng);
                            k++;
                            first_point = false;
                        }
                        else{
                            new_points[k] = {'start': true, 'skip': false, 'break': false, 'latLng':latLng};
                            interactiveData.newLatLng(new_points, k, latLng);
                            k++;
                            second_points.map(function(e){
                                new_points[k] = e;
                                k++;
                            })
                            $(map._container).off('click');

                            undo.push();

                            POINTS = new_points;
                            INDEX = k;

                            interactiveData.reload();
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
                    collect.dataPoint.active = false;
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
                        annotation.collapse();
                        collect.collapse();
                        time.collapse();
                        loadData.dialog.close();

                        edit.btn.state('expand');
                        edit.deletePoint.btn.enable();
                        edit.cut.btn.enable();
                        edit.addData.btn.enable();
                        edit.addZeroGrowth.btn.enable();
                        edit.addBreak.btn.enable();
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

    var annotation = {
        index:
            0,
        markers:
            new Array(),
        layer:
            L.layerGroup().addTo(map),
        reload:
            function(){
                this.layer.clearLayers();

                var reduced = annotation.markers.filter(function (e){return e != undefined});

                this.markers.map(function(e){return annotation.layer.addLayer(e)});
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
                    annotation.markers.push(L.circle(latLng, {radius: .0002, color: "#000", weight: '6'}));
                    annotation.markers[i].on('click', function(e){
                        annotation.deleteAnnotation.action(i);
                    })
                    annotation.layer.addLayer(annotation.markers[i]);
                    this.disable();
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
                    annotation.markers.push(L.polyline([first_point, second_point], {color: '#000', weight: '6'}));
                    annotation.markers[i].on('click', function(e){
                        annotation.deleteAnnotation.action(i);
                    });
                    annotation.layer.addLayer(annotation.markers[i]);
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
                        delete annotation.markers[i];
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
                        loadData.dialog.close();

                        edit.collapse();
                        collect.collapse();
                        time.collapse();
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

    var loadData = {
        dialog:
            L.control.dialog({'size': [240, 350], 'anchor': [5, 50], 'initOpen': false})
                .setContent('<h3>There are no data points to measure</h3>')
                .addTo(map),
        action:
            function(){
                if(POINTS[0] != undefined){
                    var y = POINTS[1].year;
                    string = "<table><tr><th style='width: 45%;'>Year</th><th style='width: 70%;'>Length</th></tr>";
                    Object.values(POINTS).map(function(e, i, a){
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
        btn:
            L.easyButton ({
                states: [
                {
                    stateName:  'closed',
                    icon:       '<i class="material-icons md-18">straighten</i>',
                    title:      'Open Data',
                    onClick:    function(btn, map){
                        loadData.action();
                    }  
                }]
            })
    }



    //group the buttons into a toolbar
    var undoRedoBar = L.easyBar([undo.btn, redo.btn]);
    undoRedoBar.addTo(map);
    undo.btn.disable();
    redo.btn.disable();

    var timeBar = L.easyBar([time.btn, time.setYearFromStart.btn, time.setYearFromEnd.btn, time.shift.forwardBtn, time.shift.backwardBtn]);
    timeBar.addTo(map);
    time.setYearFromStart.btn.disable();
    time.setYearFromEnd.btn.disable();
    time.shift.forwardBtn.disable();
    time.shift.backwardBtn.disable();

    var collectBar = L.easyBar([collect.btn, collect.dataPoint.btn, collect.zeroGrowth.btn, collect.breakPoint.btn]);
    collectBar.addTo(map);
    collect.dataPoint.btn.disable();
    collect.zeroGrowth.btn.disable();
    collect.breakPoint.btn.disable();

    var editBar = L.easyBar([edit.btn, edit.deletePoint.btn, edit.cut.btn, edit.addData.btn, edit.addZeroGrowth.btn, edit.addBreak.btn]);
    editBar.addTo(map);
    edit.deletePoint.btn.disable();
    edit.cut.btn.disable();
    edit.addData.btn.disable();
    edit.addZeroGrowth.btn.disable();
    edit.addBreak.btn.disable();

    var annotationBar = L.easyBar([annotation.btn, annotation.dateMarker.btn, annotation.lineMarker.btn, annotation.deleteAnnotation.btn]);
    annotationBar.addTo(map);
    annotation.dateMarker.btn.disable();
    annotation.lineMarker.btn.disable();
    annotation.deleteAnnotation.btn.disable();

    loadData.btn.addTo(map);


    //creating the layer controls
    var baseLayer = {
        "Tree Ring": layer
    };

    var overlay = {
        "Points": interactiveData.markerLayer,
        "H-bar": interactiveMouse.layer,
        "Lines": interactiveData.lineLayer
    };

    L.control.layers(baseLayer, overlay).addTo(map);    //adding layer controls to the map




    //doc_keyUp(e) takes a keyboard event, for keyboard shortcuts
    function doc_keyUp(e) {
        //ALT + S
        if(e.altKey && (e.keyCode == 83 || e.keycode == 115)){
            collect.zeroGrowth.action();
        }
        //ALT + C
        if(e.altKey && (e.keyCode == 67 || e.keycode == 99)){
            if(collect.dataPoint.btn._currentState.stateName == 'inactive'){
                collect.dataPoint.enable();
            }
            else{
                collect.dataPoint.disable();
            }
        }
    }
    //add event listner for keyboard
    document.addEventListener('keyup', doc_keyUp, false);


    function toFourCharString(n){
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
    }

    function toSixCharString(n){
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
    }

    function toEightCharString(n){
        var string = n.toString();

        if(string.length == 1){
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
    }


    $("#download").click(function(event){
        sum_string = "";
        ew_string = "";
        lw_string = "";
        if(POINTS != undefined){
            y = POINTS[1].year;
            sum_points = Object.values(POINTS).filter(function(e){
                if(e.earlywood != undefined){
                    return !(e.earlywood);
                }
                else{
                    return true;
                }
            });

            if(sum_points[1].year%10 > 0){
                sum_string = sum_string.concat(toFourCharString(sum_points[1].year));
            }
            sum_points.map(function(e, i, a){
                if(!e.start){
                    if(e.year%10 == 0){
                        sum_string = sum_string.concat("\r\n" + toFourCharString(e.year));
                    }
                    while(e.year > y){
                        sum_string = sum_string.concat("    -1");
                        y++;
                        if(y%10 == 0){
                            sum_string = sum_string.concat("\r\n" + toFourCharString(e.year));
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

                        length_string = toSixCharString(length); 

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

            y = POINTS[1].year;

            if(POINTS[1].year%10 > 0){
                ew_string = ew_string.concat(toFourCharString(POINTS[1].year));
                lw_string = lw_string.concat(toFourCharString(POINTS[1].year));
            }

            Object.values(POINTS).map(function(e, i, a){
                if(!e.start){
                    if(e.year%10 == 0){
                        if(e.skip){
                            ew_string = ew_string.concat("\r\n" + toFourCharString(e.year));
                            lw_string = lw_string.concat("\r\n" + toFourCharString(e.year));
                        }
                        else if(e.earlywood){
                            ew_string = ew_string.concat("\r\n" + toFourCharString(e.year));
                        }
                        else{
                            lw_string = lw_string.concat("\r\n" + toFourCharString(e.year));
                        }
                    }
                    while(e.year > y){
                        ew_string = ew_string.concat("    -1");
                        lw_string = lw_string.concat("    -1");
                        y++;
                        if(y%10 == 0){
                            ew_string = ew_string.concat("\r\n" + toFourCharString(e.year));
                            lw_string = lw_string.concat("\r\n" + toFourCharString(e.year));
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

                        length_string = toSixCharString(length); 

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
        }
        console.log(sum_string);
        console.log(ew_string);
        console.log(lw_string);

        var zip = new JSZip();
        zip.file('sample.raw', sum_string);
        zip.file('sample.lwr', lw_string);
        zip.file('sample.ewr', ew_string);

        zip.generateAsync({type:"blob"})
        .then(function (blob) {
            saveAs(blob, "sample.zip");
        });
    });

    //saving the data as a JSON
    $("#save-local").click(function(event){
        //create anoter JSON and store the current counters for year, earlywood, and index, along with points data
        dataJSON = {'year': YEAR, 'earlywood': EARLYWOOD, 'index': INDEX, 'points': POINTS};
        this.href = 'data:plain/text,' + JSON.stringify(dataJSON);
    });

    /*function addSaveButton(targetURL){
        document.getElementById('admin-save').innerHTML = '<a href="#" id="save-cloud"><i class="material-icons md-18">backup</i></a>';

        $("#save-cloud").click(function(event) {
            dataJSON = {'year': YEAR, 'earlywood': EARLYWOOD, 'index': INDEX, 'points': POINTS};
            $.post(targetURL, {sidecarData: dataJSON}, function(data, textStatus, xhr) {
                alert("you did it!");
            });
        });
    }*/


    function loadNewData(newData){
        POINTS = JSON.parse(JSON.stringify(newDataJSON.POINTS));
        INDEX = newDataJSON.index;
        YEAR = newDataJSON.year;
        EARLYWOOD = newDataJSON.earlywood;

        console.log(POINTS);

        time.collapse();
        annotation.collapse();
        edit.collapse();
        collect.collapse();

        interactiveData.reload();
    }

    //importing data fromt he user
    function loadFile(){
        var files = document.getElementById('file').files;
        console.log(files);
        if (files.length <= 0) {
            return false;
        }
      
        var fr = new FileReader();
      
        fr.onload = function(e) { 
            console.log(e);
            newDataJSON = JSON.parse(e.target.result);

            loadNewData(newDataJSON);
        }

        fr.readAsText(files.item(0));
    }
};

loadInterface();