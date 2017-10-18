
var leafletTreering = function(map, basePath, saveURL, savePermission, options){
    var Lt = this;

    Lt.map = map;
    Lt.basePath = basePath;
    Lt.saveURL = saveURL;
    Lt.savePermission = savePermission;

    //default
    Lt.initialData = {};
    Lt.assetName = "N/A";
    Lt.datingInner = 0;
    Lt.hasLatewood = true;

    //options
    if(options.initialData != undefined)
        Lt.initialData = options.initialData;
    if(options.assetName != undefined)
        Lt.assetName = options.assetName;
    if(options.datingInner != undefined)
        Lt.datingInner = options.datingInner; 
    if(options.hasLatewood != undefined)
        Lt.hasLatewood = options.hasLatewood;

    var saveDate = {};
    var saveTime = {};

    //after a leafletTreering is defined, loadInterface will be used to load all buttons and any initial data
    Lt.loadInterface = function(){

        autoScroll.on();

        map.on('resize', function(e){
            autoScroll.reset();
        });

        //set up the cursor
        map.on('movestart', function(e){
            document.getElementById('map').style.cursor = 'move';
        });
        map.on('moveend', function(e){
            if(create.dataPoint.active || annotation.lineMarker.active || annotation.comment.active || annotation.dateMarker.active || annotation.deleteAnnotation.active || edit.addBreak.active || edit.addZeroGrowth.active || edit.deletePoint.active || edit.cut.active){
                document.getElementById('map').style.cursor = 'pointer';
            }
            else{
                document.getElementById('map').style.cursor = 'default';
            }
        });

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

        map.on("contextmenu", function(e){
            create.dataPoint.disable();
            create.breakPoint.disable();
            edit.deletePoint.disable();
            edit.cut.disable();
            edit.addData.disable();
            edit.addZeroGrowth.disable();
            edit.addBreak.disable();
            annotation.comment.disable();
            annotation.dateMarker.disable();
            annotation.lineMarker.disable();
        });

        autosave.initialize();

        loadData(Lt.initialData);

        autosave.saveDisplayDate();
    };

    //parses and loads data
    var loadData = function(newData){
        if(newData.saveDate != undefined){
            saveDate = newData.saveDate;
        }
        if(newData.saveTime != undefined){
            saveTime = newData.saveTime;
        }
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

    var autosave = {
        date:
            new Date(),
        timeoutHandle:
            null,
        intervalHandle:
            null,
        saveTimer:
            -1,
        getCurrentTime:
            function(){
                var hour = this.date.getHours();
                var minute = this.date.getMinutes();
                var am_pm;
                if(hour == 0){
                    hour = 12;
                    am_pm = " AM";
                }
                else if(hour <= 11){
                    am_pm = " AM";
                }
                else if(hour == 12){
                    am_pm = " PM";
                }
                else if(hour >= 13){
                    hour = hour - 12;
                    am_pm = " PM";
                }
                return {'hour': hour, 'minute': minute, 'am_pm': am_pm};
            },
        getCurrentDate:
            function(){
                var day = this.date.getDate();
                var month = this.date.getMonth() + 1;
                var year = this.date.getFullYear();
                return {'day': day, 'month': month, 'year': year};
            },
        saveDisplayTime:
            function(){
                this.saveTimer++;
                if(this.saveTimer == 0){
                    document.getElementById("leaflet-save-time-tag").innerHTML = "All changes saved to cloud";
                    seconds_ago = function(){ document.getElementById("leaflet-save-time-tag").innerHTML = "All changes saved seconds ago"; }
                    window.setTimeout(seconds_ago, 5000);
                }
                else if(this.saveTimer == 1){
                    document.getElementById("leaflet-save-time-tag").innerHTML = "Last changes saved less then a minute ago";
                }
                else if(this.saveTimer == 2){
                    document.getElementById("leaflet-save-time-tag").innerHTML = "Last changes saved a minute ago";
                }
                else if(this.saveTimer <= 8){
                    document.getElementById("leaflet-save-time-tag").innerHTML = "Last changes saved minutes ago";
                }
                else{
                    var time = autosave.getCurrentTime();
                    document.getElementById("leaflet-save-time-tag").innerHTML = "Last changes saved at " + time.hour + ":" + ('0' + time.minute).slice(-2) + time.am_pm;
                }
            },
        saveDisplayDate:
            function(){
                var currentDate = this.getCurrentDate();
                if(saveDate != undefined && saveDate.year != undefined){
                    if(saveDate.year == currentDate.year && saveDate.month == currentDate.month){
                        if(saveDate.day == currentDate.day){
                            document.getElementById("leaflet-save-time-tag").innerHTML = "Last changes saved today at " + saveTime.hour + ":" + ('0' + saveTime.minute).slice(-2) + saveTime.am_pm;
                        }
                        else if(saveDate.day == (currentDate.day - 1)){
                            document.getElementById("leaflet-save-time-tag").innerHTML = "Last changes saved yesterday at " + saveTime.hour + ":" + ('0' + saveTime.minute).slice(-2) + saveTime.am_pm;    
                        } 
                        else{
                        document.getElementById("leaflet-save-time-tag").innerHTML = "Last changes saved on " + saveDate.month + "/" + saveDate.day + "/" + saveDate.year + " at " + saveTime.hour + ":" + ('0' + saveTime.minute).slice(-2) + saveTime.am_pm;
                        }
                    }
                    else{
                        document.getElementById("leaflet-save-time-tag").innerHTML = "Last changes saved on " + saveDate.month + "/" + saveDate.day + "/" + saveDate.year + " at " + saveTime.hour + ":" + ('0' + saveTime.minute).slice(-2) + saveTime.am_pm;
                    }
                }
                else{
                    document.getElementById("leaflet-save-time-tag").innerHTML = "Save history unavailable";
                }
            },
        debounce:
            function(){
                if(Lt.savePermission){
                    window.clearTimeout(this.timeoutHandle);
                    this.timeoutHandle = window.setTimeout(this.saveCloud, 5000);
                    window.clearInterval(this.intervalHandle);
                    this.intervalHandle = window.setInterval(this.saveDisplayTime, 30000);
                }
            },
        saveCloud:
            function(){
                /*this.saveTimer = -1;
                autosave.saveDisplayTime();
                console.log("saved");*/
                dataJSON = {'saveDate': autosave.getCurrentDate(), 'saveTime': autosave.getCurrentTime(), 'year': year, 'earlywood': earlywood, 'index': index, 'points': points, 'annotations': annotations};
                $.post(Lt.saveURL, {sidecarContent: JSON.stringify(dataJSON)}).done(function(msg){
                        this.saveTimer = -1;
                        autosave.saveDisplayTime();
                        console.log("saved");
                    })
                    .fail(function(xhr, status, error){
                        alert("Error: failed to save changes");
                    })
            },
        initialize:
            function(){
                var saveTimeDiv = document.createElement("div");
                saveTimeDiv.innerHTML = "<div class='leaflet-control-attribution leaflet-control'><p id='leaflet-save-time-tag'></p></div>";
                document.getElementsByClassName("leaflet-bottom leaflet-left")[0].appendChild(saveTimeDiv);

                if(Lt.savePermission){
                    this.timeoutHandle = window.setTimeout(null, 1000);
                    this.intervalHandle = window.setInterval(null, 1000000000);
                }
            }
    }

    var autoScroll = {
        on:
            function(){
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
                        map.panBy([-200, 0]);
                    }
                    //right bound of the map
                    if(mousePos.x + 40 > mapSize.x && mousePos.y > 100 && oldMousePos.x < mousePos.x){
                        //map.panTo([mapCenter.lat, (mapCenter.lng + .015)]);
                        map.panBy([200, 0]);
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
                });
            },
        off:
            function(){
                map.off('mousemove');
            },
        reset:
            function(){
                this.off();
                this.on();
            }
    } 

    var points = {};            //object with all the point data
    var annotations = {};       //object with all annotations data
    var year = Lt.datingInner;  //year
    var earlywood = true;       //earlywood or latewood
    var index = 0;              //points index

    //creating colored icons for points
    var markerIcon = {
        light_blue: L.icon({
            iconUrl: Lt.basePath + 'images/light_blue_icon_transparent.png',
            iconSize: [32, 32] // size of the icon
        }),
        dark_blue: L.icon({
            iconUrl: Lt.basePath + 'images/dark_blue_icon_transparent.png',
            iconSize: [32, 32] // size of the icon
        }),
        white: L.icon({
            iconUrl: Lt.basePath + 'images/white_icon_transparent.png',
            iconSize: [32, 32] // size of the icon
        }),
        grey: L.icon({
            iconUrl: Lt.basePath + 'images/grey_icon_transparent.png',
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
                        self.layer.addLayer(L.polyline([latLng, mouseLatLng], {color: '#000', opacity: '.5', weight: '6'}));
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

                        self.layer.addLayer(L.polyline([latLng, mouseLatLng], {color: '#00BCD4', opacity: '.5', weight: '5'}));
                        self.layer.addLayer(L.polyline([topLeftPoint, bottomLeftPoint], {color: '#00BCD4', opacity: '.5', weight: '5'}));
                        self.layer.addLayer(L.polyline([topRightPoint, bottomRightPoint], {color: '#00BCD4', opacity: '.5', weight: '5'}));
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
                        if(e != undefined){
                            if(e.latLng != undefined){
                                visualAsset.newLatLng(points, i, e.latLng);
                            }
                            else{
                                visualAsset.newLatLng(points, i, [0, 0]);
                            }
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
                else if(p[i].skip){
                    leafLatLng = L.latLng([p[i-1].latLng.lat + .0005, p[i-1].latLng.lng]);
                    var marker = L.marker(leafLatLng, {icon: markerIcon.grey, draggable: true, title: "Year " + p[i].year + ", None"})
                }
                //check if point is earlywood
                else if(Lt.hasLatewood){
                    if(p[i].earlywood){
                        var marker = L.marker(leafLatLng, {icon: markerIcon.light_blue, draggable: true, title: "Year " + p[i].year + ", earlywood"});         
                    }
                    //otherwise it's latewood
                    else{
                        var marker = L.marker(leafLatLng, {icon: markerIcon.dark_blue, draggable: true, title: "Year " + p[i].year + ", latewood"});
                    }
                }
                else{
                    var marker = L.marker(leafLatLng, {icon: markerIcon.light_blue, draggable: true, title: "Year " + p[i].year}); 
                }

                /*//deal with previous skip point if one exists
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
                }*/

                this.markers[i] = marker;     //add created marker to marker_list
                var self = this;

                //tell marker what to do when being draged
                

                //tell marker what to do when the draggin is done
                if(!p[i].skip){ 
                    this.markers[i].on('drag', function(e){
                        //adjusting the line from the previous and preceeding point if they exist
                        if(!p[i].start){
                            self.lineLayer.removeLayer(self.lines[i]);
                            self.lines[i] = L.polyline([self.lines[i]._latlngs[0], e.target._latlng], {color: '#00BCD4', opacity: '.5', weight: '5'});
                            self.lineLayer.addLayer(self.lines[i]);
                        }
                        if(self.lines[i+1] != undefined){
                            self.lineLayer.removeLayer(self.lines[i+1]);
                            self.lines[i+1] = L.polyline([e.target._latlng, self.lines[i+1]._latlngs[1]], {color: '#00BCD4', opacity: '.5', weight: '5'});
                            self.lineLayer.addLayer(self.lines[i+1]);
                        }
                        else if(self.lines[i+2] != undefined && !p[i+1].start){
                            self.lineLayer.removeLayer(self.lines[i+2]);
                            self.lines[i+2] = L.polyline([e.target._latlng, self.lines[i+2]._latlngs[1]], {color: '#00BCD4', opacity: '.5', weight: '5'});
                            self.lineLayer.addLayer(self.lines[i+2]);
                        }
                    });
                    this.markers[i].on('dragend', function(e){
                        undo.push();
                        p[i].latLng = e.target._latlng;
                    });
                }

                //tell marker what to do when clicked
                this.markers[i].on('click', function(e){
                    if(edit.deletePoint.active){
                        edit.deletePoint.action(i);
                    }
                    if(!p[i].skip){
                        if(edit.cut.active){
                            if(edit.cut.point != -1){
                                edit.cut.action(edit.cut.point, i);
                            }
                            else{
                                edit.cut.point = i;
                            }
                        }
                        if(edit.addData.active){
                            if(p[i].earlywood && Lt.hasLatewood){
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
                    }
                })

                //drawing the line if the previous point exists
                if(p[i-1] != undefined && !p[i].start && !p[i].skip){
                    if(p[i-1].skip && p[i-2] != undefined && !p[i-2].start){
                        this.lines[i] = L.polyline([p[i-2].latLng, leafLatLng], {color: '#00BCD4', opacity: '.5', weight: '5'});
                        this.lineLayer.addLayer(this.lines[i]);
                    }
                    else{
                        this.lines[i] = L.polyline([p[i-1].latLng, leafLatLng], {color: '#00BCD4', opacity: '.5', weight: '5'});
                        this.lineLayer.addLayer(this.lines[i]);
                    }
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

                    autosave.debounce();

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

                autosave.debounce();

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
                                year = new_year;
                                visualAsset.reload();
                            }

                            autosave.debounce();

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
                        title:      'Set the year at a start point and all proceeding points',
                        onClick:    function(btn, map){
                            time.setYearFromEnd.disable();
                            time.setYearFromStart.enable();
                        }
                    },
                    {
                        stateName:  'active',
                        icon:       '<i class="material-icons md-18">clear</i>',
                        title:      'Cancel (Esc)',
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
                                if(points[index-1].earlywood){
                                    year = points[index-1].year;
                                }
                                else{
                                    year = points[index-1].year + 1;
                                }
                                visualAsset.reload();
                            }

                            autosave.debounce();

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
                        title:      'Set the year at an end point and all prior years',
                        onClick:    function(btn, map){
                            time.setYearFromStart.disable();
                            time.setYearFromEnd.enable();
                        }
                    },
                    {
                        stateName:  'active',
                        icon:       '<i class="material-icons md-18">clear</i>',
                        title:      'Cancel (Esc)',
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
                    autosave.debounce();

                    visualAsset.reload();
                },
            forwardBtn:
                L.easyButton ({
                    states: [
                    {
                        stateName:  'year-forward',
                        icon:       '<i class="material-icons md-18">exposure_plus_1</i>',
                        title:      'Shift series forward one year',
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
                        title:      'Shift series backward one year',
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
                            if(Lt.hasLatewood){
                                if(earlywood){
                                    earlywood = false;
                                }
                                else{
                                    earlywood = true;
                                    year++;
                                }
                            }
                            else{
                                year++;
                            }
                        }

                        index++;
                        self.active = true;     //activate dataPoint after one point is made
                        autosave.debounce();
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
                        icon:       '<i class="material-icons md-18">linear_scale</i>',
                        title:      'Create measurable points',
                        onClick:    function(btn, map){
                            create.dataPoint.enable();
                        }
                    },
                    {
                        stateName:  'active',
                        icon:       '<i class="material-icons md-18">clear</i>',
                        title:      'End (Esc)',
                        onClick:    function(btn, map){
                            create.dataPoint.disable();
                        }
                    }]
                })
        },
        zeroGrowth: {
            action:
                function(){
                    if(index){
                        undo.push();

                        points[index] = {'start': false, 'skip': true, 'break': false, 'year':year}; //no point or latlng
                        visualAsset.newLatLng(points, index, [0, 0]);

                        year++;
                        index++;

                        autosave.debounce();
                    }
                    else{
                        alert("First year cannot be missing!")
                    }
                },
            btn:
                L.easyButton ({
                    states: [
                    {
                        stateName:  'skip-year',
                        icon:       '<i class="material-icons md-18">exposure_zero</i>',
                        title:      'Add a zero growth year',
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
                    
                    document.getElementById('map').style.cursor = "pointer";

                    var self = this;
                    $(map._container).click(function(e){
                        document.getElementById('map').style.cursor = "pointer";

                        var latLng = map.mouseEventToLatLng(e);

                        interactiveMouse.hbarFrom(latLng)

                        undo.push();

                        map.dragging.disable();
                        points[index] = {'start': false, 'skip': false, 'break': true, 'latLng':latLng};
                        visualAsset.newLatLng(points, index, latLng);
                        index++;
                        self.disable();

                        autosave.debounce();

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
                        title:      'Create a break point',
                        onClick:    function(btn, map){
                            create.dataPoint.disable();
                            create.breakPoint.enable();
                        }
                    },
                    {
                        stateName:  'active',
                        icon:       '<i class="material-icons md-18">clear</i>',
                        title:      'Cancel (Esc)',
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
                    icon:       '<i class="material-icons md-18">straighten</i>',
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
                            second_points = Object.values(points).splice(i+1, index-1);
                            second_points.map(function(e){
                                if(!i){
                                    points[i] = {'start': true, 'skip': false, 'break': false, 'latLng': e.latLng};
                                }
                                else{
                                    points[i] = e;
                                }
                                i++;
                            });
                            index--;
                            delete points[index];
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
                        index--;
                        delete points[index];
                    }
                    else{
                        if(Lt.hasLatewood){
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
                        else{
                            second_points = Object.values(points).splice(i+1, index-1);
                            second_points.map(function(e){
                                e.year--;
                                points[i] = e;
                                i++;
                            })
                            index--;
                            delete points[index];
                        }
                    }
                    autosave.debounce();

                    visualAsset.reload();
                    this.disable();
                },
            enable:
                function(){
                    this.btn.state('active');
                    this.active = true;
                    document.getElementById('map').style.cursor = 'pointer';
                },
            disable:
                function(){
                    $(map._container).off('click');
                    this.btn.state('inactive');
                    this.active = false;
                    document.getElementById('map').style.cursor = 'default';
                },
            btn:
                L.easyButton ({
                    states: [
                    {
                        stateName:  'inactive',
                        icon:       '<i class="material-icons md-18">delete</i>',
                        title:      'Delete a point',
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
                        title:      'Cancel (Esc)',
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
                                points[k] = {'start': true, 'skip': false, 'break': false, 'latLng': e.latLng};
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

                    autosave.debounce();

                    visualAsset.reload();
                    edit.cut.disable();
                },
            enable:
                function(){
                    this.btn.state('active');
                    this.active = true;
                    document.getElementById('map').style.cursor = 'pointer';
                    this.point = -1;
                },
            disable:
                function(){
                    $(map._container).off('click');
                    this.btn.state('inactive');
                    this.active = false;
                    document.getElementById('map').style.cursor = 'default';
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
                        title:      'Cancel (Esc)',
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

                        if(first_point && Lt.hasLatewood){
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

                            autosave.debounce();

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
                        title:      'Cancel (Esc)',
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

                    autosave.debounce();

                    visualAsset.reload();
                    this.disable();
                },
            enable:
                function(){
                    this.btn.state('active');
                    this.active = true;
                    document.getElementById('map').style.cursor = 'pointer';
                },
            disable:
                function(){
                    $(map._container).off('click');
                    this.btn.state('inactive');
                    document.getElementById('map').style.cursor = 'default';
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
                        icon:       '<i class="material-icons md-18">exposure_zero</i>',
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
                        title:      'Cancel (Esc)',
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

                            autosave.debounce();

                            visualAsset.reload();
                            self.disable();
                        }
                    });
                },
            enable:
                function(){
                    this.btn.state('active');
                    this.active = true;
                    document.getElementById('map').style.cursor = 'pointer';
                },
            disable:
                function(){
                    $(map._container).off('click');
                    this.btn.state('inactive');
                    this.active = false;
                    document.getElementById('map').style.cursor = 'default';
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
                var ref = annotations[i];
                if(ref.dateMarker || ref.comment){
                    var circle = L.circle(ref.latLng, {radius: .0002, color: '#000', weight: '6'})
                    if(ref.comment){
                        circle = L.circle(ref.latLng, {radius: .0002, color: 'red', weight: '6'});
                    }
                    this.markers.push(circle);

                    var self = this;
                    this.markers[i].on('mouseover', function(e){
                        if(ref.comment){
                            self.comment.dialog.options.anchor = [e.containerPoint.y, e.containerPoint.x];
                            self.comment.dialog.setContent(ref.text);
                            self.comment.dialog.lock();
                            self.comment.dialog.open();
                        }
                    });
                    this.markers[i].on('mouseout', function(e){
                        if(ref.comment){
                            self.comment.dialog.close();
                        }
                    });
                    this.markers[i].on('click', function(e){
                        if(self.deleteAnnotation.active){
                            self.deleteAnnotation.action(i);
                            if(ref.comment){
                                self.comment.dialog.close();
                            }
                        }
                    });
                    this.layer.addLayer(this.markers[i]);

                    autosave.debounce();
                }
                else if(ref.lineMarker){
                    this.markers.push(L.polyline([ref.first_point, ref.second_point], {color: '#000', weight: '6'}));
                    var self = this;
                    this.markers[i].on('click', function(e){
                        self.deleteAnnotation.action(i);
                    });
                    this.layer.addLayer(this.markers[i]);

                    autosave.debounce();
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
                this.comment.btn.disable();
                this.dateMarker.btn.disable();
                this.lineMarker.btn.disable();
                this.deleteAnnotation.btn.disable();

                this.comment.disable();
                this.dateMarker.disable();
                this.lineMarker.disable();
                this.deleteAnnotation.disable();
            },
        comment: {
            active: false,
            input: L.control.dialog({'size': [245, 160], 'anchor': [80, 50], 'initOpen': false})
                        .setContent('<textarea class="comment_input" name="message" rows="5" cols="30"></textarea>' +
                                    '<br>' +
                                    '<button class="comment_submit">enter</button>')
                        .addTo(map),
            dialog: L.control.dialog({'size': [200, 140], 'anchor': [80, 50], 'initOpen': false})
                        .setContent('')
                        .addTo(map),
            action:
                function(i, latLng, anchor){
                    this.input.options.anchor = [anchor.y, anchor.x];
                    this.input.lock();
                    this.input.open();
                    var self = this;
                    $('.comment_submit').click(function(){
                        var string = ($('.comment_input').val()).slice(0);
                        annotations[i] = {'comment': true, 'dateMarker': false, 'lineMarker': false, 'latLng': latLng, 'text': string};
                        annotation.newAnnotation(i);
                        self.disable(); 
                    })
                },
            enable:
                function(){
                    this.btn.state('active');
                    this.active = true;
                    document.getElementById('map').style.cursor = "pointer";

                    var self = this;
                    map.doubleClickZoom.disable();
                    $(map._container).dblclick(function(e){
                        latLng = map.mouseEventToLatLng(e);
                        anchor = map.mouseEventToContainerPoint(e);
                        self.action(annotation.index, latLng, anchor);
                        annotation.index++;
                    })
                },
            disable:
                function(){
                    this.btn.state('inactive');
                    map.doubleClickZoom.enable();
                    $(map._container).off('dblclick');
                    $('.comment_submit').off('click');
                    document.getElementById('map').style.cursor = "default";
                    this.input.close();
                    this.active = false;
                },
            btn:
                L.easyButton({
                    states: [
                    {
                        stateName:  'inactive',
                        icon:       '<i class="material-icons md-18">comment</i>',
                        title:      'Make a comment',
                        onClick:    function(btn, map){
                            annotation.deleteAnnotation.disable();
                            annotation.lineMarker.disable();
                            annotation.dateMarker.disable();
                            annotation.comment.enable();
                        }
                    },
                    {
                        stateName:  'active',
                        icon:       '<i class="material-icons md-18">clear</i>',
                        title:      'Cancel (Esc)',
                        onClick:    function(btn, map){
                            annotation.comment.disable();
                        }
                    }]
                })
        },
        dateMarker: {
            active: true,
            action:
                function(i, latLng){
                    annotations[i] = {'comment': false, 'dateMarker': true, 'lineMarker': false, 'latLng': latLng};
                    annotation.newAnnotation(i);
                },
            enable:
                function(){
                    this.btn.state('active');
                    this.active = true;
                    document.getElementById('map').style.cursor = "pointer";


                    map.doubleClickZoom.disable();
                    var self = this;
                    $(map._container).dblclick(function(e){
                        var latLng = map.mouseEventToLatLng(e);
                        self.action(annotation.index, latLng);
                        annotation.index++;
                    });
                },
            disable:
                function(){
                    this.btn.state('inactive');
                    $(map._container).off('dblclick');
                    map.doubleClickZoom.enable();
                    document.getElementById('map').style.cursor = "default";
                    this.active = false;
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
                        title:      'Cancel (Esc)',
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
                    annotations[i] = {'comment': false, 'dateMarker': false, 'lineMarker': true, 'first_point': first_point, 'second_point': second_point};
                    annotation.newAnnotation(i);
                },
            enable:
                function(){
                    this.btn.state('active');
                    document.getElementById('map').style.cursor = "pointer";
                    var start = true;

                    var self = this;
                    $(map._container).click(function(e){
                        document.getElementById('map').style.cursor = "pointer";
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
                    document.getElementById('map').style.cursor = "default";
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
                        title:      'Cancel (Esc)',
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
                        autosave.debounce();
                        annotation.reload();
                    }
                },
            enable: 
                function(){
                    annotation.dateMarker.btn.state('inactive');
                    $(map._container).off('click');
                    this.btn.state('active');
                    this.active = true;
                    document.getElementById('map').style.cursor = 'pointer';
                },
            disable:
                function(){
                    $(map._container).off('click');
                    this.btn.state('inactive');
                    this.active = false;
                    document.getElementById('map').style.cursor = 'default';
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
                        title:      'Cancel (Esc)',
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
                    title:      'Make annotations',
                    onClick:    function(btn, map){
                        btn.state('expand');
                        annotation.comment.btn.enable();
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
                    data.deleteAll.disable();
                    if(points != undefined && points[1] != undefined){
                        if(Lt.hasLatewood){

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
                                sum_string = sum_string.concat(data.download.toEightCharString(Lt.assetName) + data.download.toFourCharString(sum_points[1].year));
                            }
                            sum_points.map(function(e, i, a){
                                if(!e.start){
                                    if(e.year%10 == 0){
                                        sum_string = sum_string.concat("\r\n" + data.download.toEightCharString(Lt.assetName) + data.download.toFourCharString(e.year));
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
                                ew_string = ew_string.concat(data.download.toEightCharString(Lt.assetName) + data.download.toFourCharString(points[1].year));
                                lw_string = lw_string.concat(data.download.toEightCharString(Lt.assetName) + data.download.toFourCharString(points[1].year));
                            }

                            Object.values(points).map(function(e, i, a){
                                if(!e.start){
                                    if(e.year%10 == 0){
                                        if(e.skip){
                                            ew_string = ew_string.concat("\r\n" + data.download.toEightCharString(Lt.assetName) + data.download.toFourCharString(e.year));
                                            lw_string = lw_string.concat("\r\n" + data.download.toEightCharString(Lt.assetName) + data.download.toFourCharString(e.year));
                                        }
                                        else if(e.earlywood){
                                            ew_string = ew_string.concat("\r\n" + data.download.toEightCharString(Lt.assetName) + data.download.toFourCharString(e.year));
                                        }
                                        else{
                                            lw_string = lw_string.concat("\r\n" + data.download.toEightCharString(Lt.assetName) + data.download.toFourCharString(e.year));
                                        }
                                    }
                                    while(e.year > y){
                                        ew_string = ew_string.concat("    -1");
                                        lw_string = lw_string.concat("    -1");
                                        y++;
                                        if(y%10 == 0){
                                            ew_string = ew_string.concat("\r\n" + data.download.toEightCharString(Lt.assetName) + data.download.toFourCharString(e.year));
                                            lw_string = lw_string.concat("\r\n" + data.download.toEightCharString(Lt.assetName) + data.download.toFourCharString(e.year));
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
                            zip.file((Lt.assetName+'.raw'), sum_string);
                            zip.file((Lt.assetName+'.lwr'), lw_string);
                            zip.file((Lt.assetName+'.ewr'), ew_string);

                        }
                        else{
                            var sum_string = "";

                            y = points[1].year;
                            sum_points = Object.values(points);

                            if(sum_points[1].year%10 > 0){
                                sum_string = sum_string.concat(data.download.toEightCharString(Lt.assetName) + data.download.toFourCharString(sum_points[1].year));
                            }
                            sum_points.map(function(e, i, a){
                                if(!e.start){
                                    if(e.year%10 == 0){
                                        sum_string = sum_string.concat("\r\n" + data.download.toEightCharString(Lt.assetName) + data.download.toFourCharString(e.year));
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

                            console.log(sum_string);

                            var zip = new JSZip();
                            zip.file((Lt.assetName+'.raw'), sum_string);
                        }

                        zip.generateAsync({type:"blob"})
                        .then(function (blob) {
                            saveAs(blob, (Lt.assetName+'.zip'));
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
                        title:      'Download formated data',
                        onClick:    function(btn, map){
                            data.download.action();
                        }
                    }]
                })
        },
        deleteAll: {
            dialog: L.control.dialog({'size': [240, 140], 'anchor': [150, 50], 'initOpen': false})
                        .setContent('<p>This action will delete all data points. Annotations will not be effected. Are you sure you want to continue?</p>' +
                                '<p><button class="confirm_delete">confirm</button><button class="cancel_delete">cancel</button></p>')
                        .addTo(map),
            enable:
                function(){
                    data.dialog.close()

                    this.dialog.lock();
                    this.dialog.open();

                    var self = this;

                    $('.confirm_delete').click(function(){
                        undo.push();
                        points = [];
                        visualAsset.reload();
                        data.action();
                        autosave.debounce();
                        self.disable();
                        data.collapse();
                    })
                    $('.cancel_delete').click(function(){
                        self.disable();
                    })
                },
            disable:
                function(){
                    data.dialog.open();
                    this.dialog.close();
                    $('confirm_delete').off('click');
                    $('cancel_delete').off('click');
                },
            btn:
                L.easyButton ({
                    states: [
                    {
                        stateName:  'delete-all',
                        icon:       '<icon class="material-icons md-18">delete_sweep</i>',
                        title:      'Delete all data points',
                        onClick:    function(btn, map){
                            data.deleteAll.enable();
                        }
                    }]
                })
        },
        saveLocal: {
            action:
                function(){
                    data.deleteAll.disable();
                    dataJSON = {'year': year, 'earlywood': earlywood, 'index': index, 'points': points, 'annotations': annotations};
                    var file = new File([JSON.stringify(dataJSON)], (Lt.assetName+'.json'), {type: "text/plain;charset=utf-8"});
                    saveAs(file);
                },
            btn:
                L.easyButton ({
                    states: [
                    {
                        stateName:  'save',
                        icon:       '<i class="material-icons md-18">save</i>',
                        title:      'Save a local copy of measurements and annotation',
                        onClick:    function(btn, map){
                            data.saveLocal.action();
                        }  
                    }]
                })
        },
        loadLocal: {
            input:
                function(){
                    data.deleteAll.disable();
                    var self = this;
                    var input = document.createElement("input");
                    input.type = 'file';
                    input.id = 'file';
                    input.style ='display: none';
                    input.addEventListener("change", function(){self.action(input)});
                    input.click();
                },
            action:
                function(inputElement){
                    var files = inputElement.files;
                    console.log(files);
                    if (files.length <= 0) {
                        return false;
                    }
                  
                    var fr = new FileReader();
                  
                    fr.onload = function(e) { 
                        console.log(e);
                        newDataJSON = JSON.parse(e.target.result);

                        loadData(newDataJSON);
                        data.action();

                    }

                    fr.readAsText(files.item(0));
                },
            btn:
                L.easyButton ({
                    states: [
                    {
                        stateName:  'load',
                        icon:       '<i class="material-icons md-18">file_upload</i>',
                        title:      'Load a local file with measurements and annotations',
                        onClick:    function(btn, map){
                            data.loadLocal.input();
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
                                string = string.concat("<tr><td>"+ e.year + "</td><td>0 mm</td></tr>");
                                y++;
                            }
                            else{
                                length = Math.round(map.distance(last_point.latLng, e.latLng)*1000000)/1000;
                                if(last_point.break){
                                    length += break_length;
                                }
                                if(length == 9.999){
                                    length = 9.998;
                                }
                                if(Lt.hasLatewood){
                                    if(e.earlywood){
                                        wood = "e";
                                        row_color = "#00d2e6";
                                    }
                                    else{
                                        wood = "l";
                                        row_color = "#00838f";
                                        y++;
                                    }
                                    string = string.concat("<tr style='color:" + row_color + ";'>");
                                    string = string.concat("<td>"+ e.year + wood + "</td><td>" + length + " mm</td></tr>");
                                }
                                else{
                                    y++;
                                    string = string.concat("<tr style='color: #00d2e6;'>");
                                    string = string.concat("<td>"+ e.year +"</td><td>" + length + " mm</td></tr>");
                                }
                                last_point = e;
                            }
                        }
                    });
                    this.dialog.setContent(string + "</table>");
                }
                else{
                    this.dialog.setContent('<h3>There are no data points to measure</h3>');
                }
                this.dialog.open();
                return;
            },
        collapse:
            function(){
                this.btn.state('collapse');
                this.dialog.close();
                this.deleteAll.btn.disable();
                this.download.btn.disable();
                this.saveLocal.btn.disable();
                this.loadLocal.btn.disable();
            },
        btn:
            L.easyButton ({
                states: [
                {
                    stateName:  'collapse',
                    icon:       '<i class="material-icons md-18">show_chart</i>',
                    title:      'View and download data',
                    onClick:    function(btn, map){
                        btn.state('expand');
                        data.action();
                        data.deleteAll.btn.enable();
                        data.download.btn.enable();
                        data.saveLocal.btn.enable();
                        data.loadLocal.btn.enable();

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

    var annotationBar = L.easyBar([annotation.btn, annotation.comment.btn, annotation.dateMarker.btn, annotation.lineMarker.btn, annotation.deleteAnnotation.btn]);
    annotation.comment.btn.disable();
    annotation.dateMarker.btn.disable();
    annotation.lineMarker.btn.disable();
    annotation.deleteAnnotation.btn.disable();

    var dataBar = L.easyBar([data.btn, data.deleteAll.btn, data.download.btn, data.saveLocal.btn, data.loadLocal.btn]);
    data.deleteAll.btn.disable();
    data.download.btn.disable();
    data.saveLocal.btn.disable();
    data.loadLocal.btn.disable();

    //the default minimap is square which doesn't look nice
    var miniMap = new L.Control.MiniMap(miniLayer, {
        width: 550,
        height: 50,
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
        "Lines": visualAsset.lineLayer,
        "Annotations": annotation.layer
    };

};