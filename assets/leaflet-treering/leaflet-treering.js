
var leafletTreering = function(map, basePath, options) {
  var Lt = this;

  Lt.map = map;
  Lt.basePath = basePath;
  
  //options
  Lt.ppm = options.ppm || 468;
  Lt.saveURL = options.saveURL || "";
  Lt.savePermission = options.savePermission || false;
  Lt.popoutUrl = options.popoutUrl || null;
  Lt.initialData = options.initialData || {};
  Lt.assetName = options.assetName || "N/A";
  Lt.hasLatewood = options.hasLatewood || false;

  //after a leafletTreering is defined, loadInterface will be used to load all buttons and any initial data
  Lt.loadInterface = function() {

    autoScroll.on();

    Lt.map.on('resize', function(e) {
      autoScroll.reset();
    });

    //set up the cursor
    Lt.map.on('movestart', function(e) {
      document.getElementById('map').style.cursor = 'move';
    });
    Lt.map.on('moveend', function(e) {
      if (create.dataPoint.active || annotation.active || edit.addBreak.active || edit.addZeroGrowth.active || edit.deletePoint.active || edit.cut.active) {
        document.getElementById('map').style.cursor = 'pointer';
      } else {
        document.getElementById('map').style.cursor = 'default';
      }
    });

    document.getElementById('map').style.cursor = 'default';

    //add all UI elements to map

    if (window.name == "popout") {
      miniMap.addTo(Lt.map);

      data.btn.addTo(Lt.map);
      annotation.btn.addTo(Lt.map);
      createBar.addTo(Lt.map);
      timeBar.addTo(Lt.map);
      editBar.addTo(Lt.map);
      fileBar.addTo(Lt.map);
      undoRedoBar.addTo(Lt.map);
    } else {
      popout.btn.addTo(Lt.map);
      data.btn.addTo(Lt.map);
      fileBar.addTo(Lt.map);
    }

    L.control.layers(baseLayer, overlay).addTo(Lt.map);

    Lt.map.on("contextmenu", function(e) {
      create.dataPoint.disable();
      create.breakPoint.disable();
      edit.deletePoint.disable();
      edit.cut.disable();
      edit.addData.disable();
      edit.addZeroGrowth.disable();
      edit.addBreak.disable();
      annotation.disable();
    });

    loadData(Lt.initialData);

    fileIO.saveCloud.initialize();

    fileIO.saveCloud.displayDate();
  };

  distance = function(p1, p2) {
    lastPoint = Lt.map.project(p1, Lt.map.getMaxZoom());
    newPoint =  Lt.map.project(p2, Lt.map.getMaxZoom());
    // Math.sqrt(Math.pow(Math.abs(this._pixelPos[i - 1][0] - this._pixelPos[i][0]), 2) + Math.pow(Math.abs(this._pixelPos[i - 1][1] - this._pixelPos[i][1]), 2));
    length = Math.sqrt(Math.pow(Math.abs(lastPoint.x - newPoint.x), 2) + Math.pow(Math.abs(newPoint.y - lastPoint.y), 2));
    pixelsPerMillimeter = 1;
    Lt.map.eachLayer(function(layer) {
      if (layer.options.pixelsPerMillimeter >0) {
        pixelsPerMillimeter = Lt.ppm;
      }
    });
    length = length / pixelsPerMillimeter;
    retinaFactor = 1;
    if (L.Browser.retina) {
      retinaFactor = 2; // this is potentially incorrect for 3x+ devices
    }
    return length * retinaFactor;
  };

  //parses and loads  
  var loadData = function(newData) {
    saveDate = newData.saveDate || {};

    index = newData.index || 0;
    year = newData.year || 0;
    earlywood = newData.earlywood || true;
    points = newData.points || {};
    annotations = newData.annotations || {};

    visualAsset.reload();
    annotation.reload();

    time.collapse();
    annotation.disable();
    edit.collapse();
    create.collapse(); 
  };

  var autoScroll = {
    on:
      function() {
        //map scrolling
        var mapSize = Lt.map.getSize();  //size of the map used for map scrolling
        var mousePos = 0;         //an initial mouse position

        Lt.map.on('mousemove', function(e) {
          var oldMousePos = mousePos;    //save the old mouse position
          mousePos = e.containerPoint;  //container point of the mouse
          var mouseLatLng = e.latlng;     //latLng of the mouse
          var mapCenter = Lt.map.getCenter();  //center of the map   

          //left bound of the map
          if (mousePos.x <= 40 && mousePos.y > 450 && oldMousePos.x > mousePos.x) {
            //map.panTo([mapCenter.lat, (mapCenter.lng - .015)]);   //defines where the map view should move to
            Lt.map.panBy([-200, 0]);
          }
          //right bound of the map
          if (mousePos.x + 40 > mapSize.x && mousePos.y > 100 && oldMousePos.x < mousePos.x) {
            //map.panTo([mapCenter.lat, (mapCenter.lng + .015)]);
            Lt.map.panBy([200, 0]);
          }
          //upper bound of the map
          if (mousePos.x + 40 < mapSize.x && mousePos.y < 40 && oldMousePos.y > mousePos.y) {
            //map.panTo([mapCenter.lat, (mapCenter.lng + .015)]);
            Lt.map.panBy([0, -40]);
          }
          //lower bound of the map
          if (mousePos.x >= 40 && mousePos.y > mapSize.y - 40 && oldMousePos.y < mousePos.y) {
            //map.panTo([mapCenter.lat, (mapCenter.lng - .015)]);   //defines where the map view should move to
            Lt.map.panBy([0, 40]);
          }
        });
      },
    off:
      function() {
        Lt.map.off('mousemove');
      },
    reset:
      function() {
        this.off();
        this.on();
      }
  } 

  //creating colored icons for points
  var markerIcon = {
    light_blue: L.icon({
      iconUrl: Lt.basePath + 'images/light_blue_tick_icon.png',
      iconSize: [32, 48] // size of the icon
    }),
    dark_blue: L.icon({
      iconUrl: Lt.basePath + 'images/dark_blue_tick_icon.png',
      iconSize: [32, 48] // size of the icon
    }),
    white: L.icon({
      iconUrl: Lt.basePath + 'images/white_tick_icon.png',
      iconSize: [32, 48] // size of the icon
    }),
    grey: L.icon({
      iconUrl: Lt.basePath + 'images/grey_icon_transparent.png',
      iconSize: [32, 32] // size of the icon
    }),
  };

  //when user adds new markers lines and hbars will be created from the mouse
  var interactiveMouse = {
    layer:
      L.layerGroup().addTo(Lt.map),
    hbarFrom:
      function(latLng) {
        var self = this;
        $(Lt.map._container).mousemove(function lineToMouse(e) {
          if (create.dataPoint.active) {
            self.layer.clearLayers();
            var mousePoint = Lt.map.mouseEventToLayerPoint(e);
            var mouseLatLng = Lt.map.mouseEventToLatLng(e);
            var point = Lt.map.latLngToLayerPoint(latLng);

            //getting the four points for the h bars, this is doing 90 degree rotations on mouse point
            var newX = mousePoint.x + (point.x - mousePoint.x)*Math.cos(Math.PI/2) - (point.y - mousePoint.y)*Math.sin(Math.PI/2);
            var newY = mousePoint.y + (point.x - mousePoint.x)*Math.sin(Math.PI/2) + (point.y - mousePoint.y)*Math.cos(Math.PI/2);
            var topRightPoint = Lt.map.layerPointToLatLng([newX, newY]);

            var newX = mousePoint.x + (point.x - mousePoint.x)*Math.cos(Math.PI/2*3) - (point.y - mousePoint.y)*Math.sin(Math.PI/2*3);
            var newY = mousePoint.y + (point.x - mousePoint.x)*Math.sin(Math.PI/2*3) + (point.y - mousePoint.y)*Math.cos(Math.PI/2*3);
            var bottomRightPoint = Lt.map.layerPointToLatLng([newX, newY]);

            //doing rotations 90 degree rotations on latlng
            var newX = point.x + (mousePoint.x - point.x)*Math.cos(Math.PI/2) - (mousePoint.y - point.y)*Math.sin(Math.PI/2);
            var newY = point.y + (mousePoint.x - point.x)*Math.sin(Math.PI/2) + (mousePoint.y - point.y)*Math.cos(Math.PI/2);
            var topLeftPoint = Lt.map.layerPointToLatLng([newX, newY]);

            var newX = point.x + (mousePoint.x - point.x)*Math.cos(Math.PI/2*3) - (mousePoint.y - point.y)*Math.sin(Math.PI/2*3);
            var newY = point.y + (mousePoint.x - point.x)*Math.sin(Math.PI/2*3) + (mousePoint.y - point.y)*Math.cos(Math.PI/2*3);
            var bottomLeftPoint = Lt.map.layerPointToLatLng([newX, newY]);

            if (earlywood) {
              var color = '#00BCD4';
            } else {
              var color = '#00838f';
            }

            self.layer.addLayer(L.polyline([latLng, mouseLatLng], {color: color, opacity: '.75', weight: '3'}));
            self.layer.addLayer(L.polyline([topLeftPoint, bottomLeftPoint], {color: color, opacity: '.75', weight: '3'}));
            self.layer.addLayer(L.polyline([topRightPoint, bottomRightPoint], {color: color, opacity: '.75', weight: '3'}));
            
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
      L.layerGroup().addTo(Lt.map),
    lineLayer:
      L.layerGroup().addTo(Lt.map),
    reload:
      function() {
        //erase the markers
        this.markerLayer.clearLayers();
        this.markers = new Array();
         //erase the lines
        this.lineLayer.clearLayers();
        this.lines = new Array();

        //plot the data back onto the map
        if (points != undefined) {
          Object.values(points).map(function(e, i) {
            if (e != undefined) {
              if (e.latLng != undefined) {
                visualAsset.newLatLng(points, i, e.latLng);
              } else {
                visualAsset.newLatLng(points, i, [0, 0]);
              }
            }
          });
        }
      },
    previousLatLng:
      undefined,
    newLatLng:
      function(p, i, latLng) {
        leafLatLng = L.latLng(latLng);   //leaflet is stupid and only uses latlngs that are created through L.latlng

        //check if index is the start point
        if (p[i].start) {
          var marker = L.marker(leafLatLng, {icon: markerIcon.white, draggable: true, title: "Start Point", riseOnHover: true});
        } else if (p[i].break) { //check if point is a break
          var marker = L.marker(leafLatLng, {icon: markerIcon.white, draggable: true, title: "Break Point", riseOnHover: true})
        } else if (p[i].skip) {
          leafLatLng = L.latLng([p[i-1].latLng.lat + .0005, p[i-1].latLng.lng]);
          var marker = L.marker(leafLatLng, {icon: markerIcon.grey, draggable: true, title: "Year " + p[i].year + ", None", riseOnHover: true})
        } else if (Lt.hasLatewood) { //check if point is earlywood
          if (p[i].earlywood) {
            var marker = L.marker(leafLatLng, {icon: markerIcon.light_blue, draggable: true, title: "Year " + p[i].year + ", earlywood", riseOnHover: true});
          } else { //otherwise it's latewood
            var marker = L.marker(leafLatLng, {icon: markerIcon.dark_blue, draggable: true, title: "Year " + p[i].year + ", latewood", riseOnHover: true});
          }
        } else {
          var marker = L.marker(leafLatLng, {icon: markerIcon.light_blue, draggable: true, title: "Year " + p[i].year, riseOnHover: true}); 
        }

        this.markers[i] = marker;   //add created marker to marker_list
        var self = this;

        if (!p[i].skip) { 
          //tell marker what to do when being draged
          this.markers[i].on('drag', function(e) {
            //adjusting the line from the previous and preceeding point if they exist
            if (!p[i].start) {
              self.lineLayer.removeLayer(self.lines[i]);
              self.lines[i] = L.polyline([self.lines[i]._latlngs[0], e.target._latlng], {color: '#00BCD4', opacity: '.75', weight: '3'});
              self.lineLayer.addLayer(self.lines[i]);
            }
            if (self.lines[i+1] != undefined) {
              self.lineLayer.removeLayer(self.lines[i+1]);
              self.lines[i+1] = L.polyline([e.target._latlng, self.lines[i+1]._latlngs[1]], {color: '#00BCD4', opacity: '.75', weight: '3'});
              self.lineLayer.addLayer(self.lines[i+1]);
            } else if (self.lines[i+2] != undefined && !p[i+1].start) {
              self.lineLayer.removeLayer(self.lines[i+2]);
              self.lines[i+2] = L.polyline([e.target._latlng, self.lines[i+2]._latlngs[1]], {color: '#00BCD4', opacity: '.75', weight: '3'});
              self.lineLayer.addLayer(self.lines[i+2]);
            }
          });
          //tell marker what to do when the draggin is done
          this.markers[i].on('dragend', function(e) {
            undo.push();
            p[i].latLng = e.target._latlng;
          });
        }

        //tell marker what to do when clicked
        this.markers[i].on('click', function(e) {
          if (edit.deletePoint.active) {
            edit.deletePoint.action(i);
          }
          console.log(p[i])
          if (!p[i].skip) {
            if (edit.cut.active) {
              if (edit.cut.point != -1) {
                edit.cut.action(edit.cut.point, i);
              } else {
                edit.cut.point = i;
              }
            }
            if (edit.addData.active) {
              if (p[i].earlywood && Lt.hasLatewood) {
                alert("must select latewood or start point")
              } else {
                edit.addData.action(i);
              }
            }
            if (edit.addZeroGrowth.active) {
              edit.addZeroGrowth.action(i);
            }
            if (edit.addBreak.active) {
              edit.addBreak.action(i);
            }
            if (time.setYearFromStart.active) {
              time.setYearFromStart.action(i);
            }
            if (time.setYearFromEnd.active) {
              time.setYearFromEnd.action(i);
            }
          }
        })

        //drawing the line if the previous point exists
        if (p[i-1] != undefined && !p[i].start && !p[i].skip) {
          if (p[i-1].skip && p[i-2] != undefined && !p[i-2].start) {
            this.lines[i] = L.polyline([p[i-2].latLng, leafLatLng], {color: '#00BCD4', opacity: '.75', weight: '3'});
            this.lineLayer.addLayer(this.lines[i]);
          } else {
            this.lines[i] = L.polyline([p[i-1].latLng, leafLatLng], {color: '#00BCD4', opacity: '.75', weight: '3'});
            this.lineLayer.addLayer(this.lines[i]);
          }
        }
        
        this.previousLatLng = leafLatLng;
        this.markerLayer.addLayer(this.markers[i]);  //add the marker to the marker layer
      },
  }

  var popout = {
    btn:
      L.easyButton ({
        states: [
        {
          stateName:  'popout',
          icon:       '<i class="material-icons md-18">launch</i>',
          title:      'Popout Window',
          onClick:    function(btn, map) {
            window.open(Lt.popoutUrl, 'popout', 'location=yes,height=600,width=800,scrollbars=yes,status=yes');
          }
        }]
      }) 
  }

  //undo changes to points using a stack
  var undo = {
    stack:
      new Array(),
    push:
      function() {
        this.btn.enable();
        redo.btn.disable();
        redo.stack.length = 0;
        var restore_points = JSON.parse(JSON.stringify(points));
        this.stack.push({'year': year, 'earlywood': earlywood, 'index': index, 'points': restore_points });
      },
    pop:
      function() {
        if (this.stack.length > 0) {
          if (points[index-1].start) {
            create.dataPoint.disable();
          } else {
            interactiveMouse.hbarFrom(points[index-2].latLng);
          }

          redo.btn.enable();
          var restore_points = JSON.parse(JSON.stringify(points));
          redo.stack.push({'year': year, 'earlywood': earlywood, 'index': index, 'points': restore_points});
          dataJSON = this.stack.pop();

          points = JSON.parse(JSON.stringify(dataJSON.points));

          index = dataJSON.index;
          year = dataJSON.year;
          earlywood = dataJSON.earlywood;

          visualAsset.reload();

          if (this.stack.length == 0) {
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
          onClick:    function(btn, map) {
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
      function redo() {
        undo.btn.enable();
        var restore_points = JSON.parse(JSON.stringify(points));
        undo.stack.push({'year': year, 'earlywood': earlywood, 'index': index, 'points': restore_points});
        dataJSON = this.stack.pop();

        points = JSON.parse(JSON.stringify(dataJSON.points));

        index = dataJSON.index;
        year = dataJSON.year;
        earlywood = dataJSON.earlywood;

        visualAsset.reload();

        if (this.stack.length == 0) {
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
          onClick:    function(btn, map) {
            redo.pop();
          }
        }]
      }),
  }

  //all buttons and assets related to changing the time of the series
  var time = {
    collapse:
      function() {
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
          .addTo(Lt.map),
      action:
        function(i) {  
          if (points[i].start) {
            this.dialog.open();  
            var self = this;     

            document.getElementById('year_submit').addEventListener('click', function() {
              new_year = document.getElementById('year_input').value;
              self.dialog.close();

              if (new_year.toString().length > 4) {
                alert("Year cannot exceed 4 digits!");
              } else {
                undo.push();

                i++
                
                while(points[i] != undefined) {
                  if (points[i].start || points[i].break) {
                  } else if (points[i].earlywood) {
                    points[i].year = new_year;
                  } else {
                    points[i].year = new_year++;
                  }
                  i++;
                }
                year = new_year;
                visualAsset.reload();
              }

              self.disable();
            }, false);
          }   
        },
      enable:
        function() {
          this.btn.state('active');
          this.active = true;
        },
      disable:
        function() {
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
            onClick:    function(btn, map) {
              time.setYearFromEnd.disable();
              time.setYearFromStart.enable();
            }
          },
          {
            stateName:  'active',
            icon:       '<i class="material-icons md-18">clear</i>',
            title:      'Cancel',
            onClick:    function(btn, map) {
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
          .addTo(Lt.map),
      action:  
        function(i) {  
          if (!(points[i+1] != undefined) || points[i+1].break || points[i+1].start) {
            this.dialog.open();
            var self = this;    

            document.getElementById('end_year_submit').addEventListener('click', function() {
              new_year = parseInt(document.getElementById('end_year_input').value);
              self.dialog.close();

              if (new_year.toString().length > 4) {
                alert("Year cannot exceed 4 digits!");
              } else {
                undo.push();
                
                if (i == index) {
                  year = new_year;
                }

                while(points[i] != undefined) {
                  if (points[i].start || points[i].break) {
                  } else if (points[i].earlywood) {
                    points[i].year = new_year--;
                  } else {
                    points[i].year = new_year;
                  }
                  i--;
                }
                if (points[index-1].earlywood) {
                  year = points[index-1].year;
                } else {
                  year = points[index-1].year + 1;
                }
                visualAsset.reload();
              }

  

              self.disable();
            }, false);
          }   
        },
      enable:
        function() {
          this.btn.state('active');
          this.active = true;
        },
      disable:
        function() {
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
            onClick:    function(btn, map) {
              time.setYearFromStart.disable();
              time.setYearFromEnd.enable();
            }
          },
          {
            stateName:  'active',
            icon:       '<i class="material-icons md-18">clear</i>',
            title:      'Cancel',
            onClick:    function(btn, map) {
              time.setYearFromEnd.disable();
            }
          }]
        })
    },
    shift: {
      action:
        function(x) {
          undo.push();
          for(i = 0; i < index; i++) {
            if (!points[i].start) {
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
            title:      'Shift series forward one year',
            onClick:    function(btn, map) {
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
            onClick:    function(btn, map) {
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
          onClick:    function(btn, map) {
            annotation.disable();
            edit.collapse();
            create.collapse();

            time.btn.state('expand');
            time.setYearFromStart.btn.enable();
            time.setYearFromEnd.btn.enable();
            time.shift.forwardBtn.enable();
            time.shift.backwardBtn.enable();
            data.disable();
          }
        },
        {
          stateName:  'expand',
          icon:       '<i class="material-icons md-18">expand_less</i>',
          title:      'Collapse',
          onClick:    function(btn, map) {
            time.collapse();
          }
        }]
      }),
  }

  //the main object for creating new data
  var create = {
    collapse:
      function() {
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
        function() {
          this.btn.state('active');

          document.getElementById('map').style.cursor = "pointer";

          var self = this;

          $(document).keyup(function(e) {
            var key = e.which || e.keyCode;
            if (key === 27) {
              self.disable();
            }
          })

          $(Lt.map._container).click(function(e) {
            document.getElementById('map').style.cursor = "pointer";

            var latLng = Lt.map.mouseEventToLatLng(e);

            undo.push();

            if (self.startPoint) {
              points[index] = {'start': true, 'skip': false, 'break': false, 'latLng':latLng};
              self.startPoint = false;
            } else {
              points[index] = {'start': false, 'skip': false, 'break': false, 'year':year, 'earlywood': earlywood, 'latLng':latLng};
            }

            visualAsset.newLatLng(points, index, latLng); //call newLatLng with current index and new latlng 

            interactiveMouse.hbarFrom(latLng); //create the next mouseline from the new latlng

            //avoid incrementing earlywood for start point
            
            if (!points[index].start) {
              if (Lt.hasLatewood) {
                if (earlywood) {
                  earlywood = false;
                } else {
                  earlywood = true;
                  year++;
                }
              } else {
                year++;
              }
            }

            index++;
            self.active = true;   //activate dataPoint after one point is made

          });
        },
      disable:
        function() {
          $(document).off('keyup');
          $(Lt.map._container).off('click');  //turn off the mouse clicks from previous function
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
            onClick:    function(btn, map) {
              create.dataPoint.enable();
            }
          },
          {
            stateName:  'active',
            icon:       '<i class="material-icons md-18">clear</i>',
            title:      'End (Esc)',
            onClick:    function(btn, map) {
              create.dataPoint.disable();
            }
          }]
        })
    },
    zeroGrowth: {
      action:
        function() {
          if (index) {
            undo.push();

            points[index] = {'start': false, 'skip': true, 'break': false, 'year':year}; //no point or latlng
            visualAsset.newLatLng(points, index, [0, 0]);

            year++;
            index++;
          } else {
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
            onClick:    function(btn, map) {
              create.zeroGrowth.action();
            }
          }]
        })
    },
    breakPoint: {
      enable:    
        function() {
          this.btn.state('active');

          create.dataPoint.active = true;
          
          document.getElementById('map').style.cursor = "pointer";

          var self = this;
          $(Lt.map._container).click(function(e) {
            document.getElementById('map').style.cursor = "pointer";

            var latLng = Lt.map.mouseEventToLatLng(e);

            interactiveMouse.hbarFrom(latLng)

            undo.push();

            Lt.map.dragging.disable();
            points[index] = {'start': false, 'skip': false, 'break': true, 'latLng':latLng};
            visualAsset.newLatLng(points, index, latLng);
            index++;
            self.disable();

            create.dataPoint.enable();
          });
        },
      disable:
        function() {
          $(Lt.map._container).off('click');
          this.btn.state('inactive');
          Lt.map.dragging.enable();
        },
      btn:
        L.easyButton ({
          states: [
          {
            stateName:  'inactive',
            icon:       '<i class="material-icons md-18">broken_image</i>',
            title:      'Create a break point',
            onClick:    function(btn, map) {
              create.dataPoint.disable();
              create.breakPoint.enable();
            }
          },
          {
            stateName:  'active',
            icon:       '<i class="material-icons md-18">clear</i>',
            title:      'Cancel',
            onClick:    function(btn, map) {
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
          onClick:    function(btn, map) {
            create.btn.state('expand');
            create.dataPoint.btn.enable();
            create.zeroGrowth.btn.enable();
            create.breakPoint.btn.enable();

            data.disable();
            edit.collapse();
            annotation.disable();
            time.collapse();
          }
        },
        {
          stateName:  'expand',
          icon:       '<i class="material-icons md-18">expand_less</i>',
          title:      'Collapse',
          onClick:    function(btn, map) {
            create.collapse();
          }
        }]
      })
  }

  //all editing tools are a part of the edit object
  var edit = {
    collapse:
      function() {
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
        function(i) {
          undo.push();
          if (points[i].start) { 
            if (points[i-1] != undefined && points[i-1].break) {
              alert("You cannot delete this point!");
            } else {
              second_points = Object.values(points).splice(i+1, index-1);
              second_points.map(function(e) {
                if (!i) {
                  points[i] = {'start': true, 'skip': false, 'break': false, 'latLng': e.latLng};
                } else {
                  points[i] = e;
                }
                i++;
              });
              index--;
              delete points[index];
            }
          } else if (points[i].break) {
            second_points = Object.values(points).splice(i+1, index-1);
            second_points.map(function(e) {
              points[i] = e;
              i++;
            });
            index = index - 1;
            delete points[index];
          } else if (points[i].skip) {
            second_points = Object.values(points).splice(i+1, index-1);
            second_points.map(function(e) {
              e.year--;
              points[i] = e;
              i++
            });
            index--;
            delete points[index];
          } else {
            if (Lt.hasLatewood) {
              if (points[i].earlywood && points[i+1].earlywood != undefined) {
                j = i+1;
              } else if (points[i-1].earlywood != undefined) {
                j = i;
                i--;
              }
              //get the second half of the data
              second_points = Object.values(points).splice(j+1, index-1);
              second_points.map(function(e) {
                e.year--;
                points[i] = e;
                i++;
              })
              index = i-1;
              delete points[i];
              delete points[i+1];
            } else {
              second_points = Object.values(points).splice(i+1, index-1);
              second_points.map(function(e) {
                e.year--;
                points[i] = e;
                i++;
              })
              index--;
              delete points[index];
            }
          }

          visualAsset.reload();
        },
      enable:
        function() {
          this.btn.state('active');
          this.active = true;
          document.getElementById('map').style.cursor = 'pointer';
        },
      disable:
        function() {
          $(Lt.map._container).off('click');
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
            onClick:    function(btn, map) {
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
            title:      'Cancel',
            onClick:    function(btn, map) {
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
        function(i, j) {
          undo.push();

          if (i > j) {
            trimmed_points = Object.values(points).splice(i, index-1);
            var k = 0;
            points = {};
            trimmed_points.map(function(e) {
              if (!k) {
                points[k] = {'start': true, 'skip': false, 'break': false, 'latLng': e.latLng};
              } else {  
                points[k] = e;
              }
              k++;
            })
            index = k;
          } else if (i < j) {
            points = Object.values(points).splice(0, i);
            index = i;
          } else {
            alert("You cannot select the same point");
          }

          visualAsset.reload();
          edit.cut.disable();
        },
      enable:
        function() {
          this.btn.state('active');
          this.active = true;
          document.getElementById('map').style.cursor = 'pointer';
          this.point = -1;
        },
      disable:
        function() {
          $(Lt.map._container).off('click');
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
            onClick:    function(btn, map) {
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
            title:      'Cancel',
            onClick:    function(btn, map) {
              edit.cut.disable()
            }
          }]
        })
    },
    addData: {
      active:
        false,
      action:
        function(i) {
          var new_points = points;
          var second_points = Object.values(points).splice(i+1, index-1);
          var first_point = true;
          var k = i+1;
          var year_adjusted = points[i+1].year;

          document.getElementById('map').style.cursor = "pointer";
          create.dataPoint.active = true;
          interactiveMouse.hbarFrom(points[i].latLng);

          var self = this;
          $(Lt.map._container).click(function(e) {
            var latLng = Lt.map.mouseEventToLatLng(e);
            Lt.map.dragging.disable();

            interactiveMouse.hbarFrom(latLng);

            if (first_point && Lt.hasLatewood) {
              new_points[k] = {'start': false, 'skip': false, 'break': false, 'year': year_adjusted, 'earlywood': true, 'latLng':latLng};
              visualAsset.newLatLng(new_points, k, latLng);
              k++;
              first_point = false;
            } else {
              new_points[k] = {'start': false, 'skip': false, 'break': false, 'year': year_adjusted, 'earlywood': false, 'latLng':latLng};
              year_adjusted++;
              visualAsset.newLatLng(new_points, k, latLng);
              k++;
              second_points.map(function(e) {
                e.year++;
                new_points[k] = e;
                k++;
              })
              $(Lt.map._container).off('click');

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
        function() {
          this.btn.state('active');
          this.active = true;
        },
      disable:
        function() {
          $(Lt.map._container).off('click');
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
            onClick:    function(btn, map) {
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
            onClick:    function(btn, map) {
              edit.addData.disable();
            }
          }]
        })
    },
    addZeroGrowth: {
      active:
        false,
      action:
        function(i) {
          undo.push();

          var second_points = Object.values(points).splice(i+1, index-1);
          points[i+1] = {'start': false, 'skip': true, 'break': false, 'year': points[i].year+1};
          k=i+2;
          second_points.map(function(e) {
            e.year++
            points[k] = e;
            k++;
          })
          $(Lt.map._container).off('click');
          index = k;
          year++;


          visualAsset.reload();
          this.disable();
        },
      enable:
        function() {
          this.btn.state('active');
          this.active = true;
          document.getElementById('map').style.cursor = 'pointer';
        },
      disable:
        function() {
          $(Lt.map._container).off('click');
          this.btn.state('inactive');
          document.getElementById('map').style.cursor = 'default';
          this.active = false;
          Lt.map.dragging.enable();
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
            onClick:    function(btn, map) {
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
            onClick:    function(btn, map) {
              edit.addZeroGrowth.disable();
            }
          }]
        })
    },
    addBreak: {
      active:
        false,
      action:
        function(i) {
          var new_points = points;
          var second_points = Object.values(points).splice(i+1, index-1);
          var first_point = true;
          var k = i+1;

          create.dataPoint.active = true;
          interactiveMouse.hbarFrom(points[i].latLng);

          var self = this;
          $(Lt.map._container).click(function(e) {
            var latLng = Lt.map.mouseEventToLatLng(e);
            Lt.map.dragging.disable();

            interactiveMouse.hbarFrom(latLng);

            if (first_point) {
              new_points[k] = {'start': false, 'skip': false, 'break': true, 'latLng':latLng};
              visualAsset.newLatLng(new_points, k, latLng);
              k++;
              first_point = false;
            } else {
              new_points[k] = {'start': true, 'skip': false, 'break': false, 'latLng':latLng};
              visualAsset.newLatLng(new_points, k, latLng);
              k++;
              second_points.map(function(e) {
                new_points[k] = e;
                k++;
              })
              $(Lt.map._container).off('click');

              undo.push();

              points = new_points;
              index = k;

              visualAsset.reload();
              self.disable();
            }
          });
        },
      enable:
        function() {
          this.btn.state('active');
          this.active = true;
          document.getElementById('map').style.cursor = 'pointer';
        },
      disable:
        function() {
          $(Lt.map._container).off('click');
          this.btn.state('inactive');
          this.active = false;
          document.getElementById('map').style.cursor = 'default';
          Lt.map.dragging.enable();
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
            onClick:    function(btn, map) {
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
            onClick:    function(btn, map) {
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
          onClick:    function(btn, map) {
            edit.btn.state('expand');
            edit.deletePoint.btn.enable();
            edit.cut.btn.enable();
            edit.addData.btn.enable();
            edit.addZeroGrowth.btn.enable();
            edit.addBreak.btn.enable();

            annotation.disable();
            create.collapse();
            time.collapse();
            data.disable();
          }
        },
        {
          stateName:  'expand',
          icon:       '<i class="material-icons md-18">expand_less</i>',
          title:      'Collapse',
          onClick:    function(btn, map) {
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
      L.layerGroup().addTo(Lt.map),
    active:
      false,
    markerClicked:
      false,
    input:
      L.circle([0,0], {radius: .0001, color: 'red', weight: '6'})
        .bindPopup('<textarea class="comment_input" name="message" rows="2" cols="15"></textarea>', {closeButton: false}),
    reload:
      function() {
        self = this;
        self.layer.clearLayers();
        self.markers = new Array();
        self.index = 0;
        if (annotations != undefined) {
          var reduced = Object.values(annotations).filter(e => e != undefined);
          annotations = {};
          reduced.map((e, i) => annotations[i] = e);

          Object.values(annotations).map(function(e, i) {self.newAnnotation(i);self.index++});
        }
      },
    popupMouseover:
      function(e) {
        this.openPopup();
        //self.markers[i].openPopup();
      },
    popupMouseout:
      function(e) {
        this.closePopup();
        //self.markers[i].closePopup();
      },
    newAnnotation:
      function(i) {
        var self = this;

        var ref = annotations[i];
        var circle = L.circle(ref.latLng, {radius: .0001, color: 'red', weight: '6'});
        circle.bindPopup(ref.text, {closeButton: false})
        self.markers[i] = circle;

        $(self.markers[i]).click(function(e) {
          self.markers[i].closePopup();  
        })

        $(self.markers[i]).mouseover(self.popupMouseover);
        $(self.markers[i]).mouseout(self.popupMouseout);

        $(self.markers[i]).dblclick(function() {
          if (!self.active) {
            $(Lt.map._container).click(function(e) {
              self.markers[i].setPopupContent(ref.text);
              $(self.markers[i]).mouseover(self.popupMouseover);
              $(self.markers[i]).mouseout(self.popupMouseout);
              self.disable();
            });
            self.editAnnotation(i);
          } else {
            self.markerClicked = true;
          }
        });

        self.layer.addLayer(self.markers[i]);

        if (ref.text == "") {
          self.deleteAnnotation(i);
        }
      },
    action:
      function(i, latLng) {
        var self = this;
          
        self.input.setLatLng(latLng);
        self.input.addTo(Lt.map);
        self.input.openPopup();

        document.getElementsByClassName('comment_input')[0].select();

        $(document).keypress(function(e) {
          var key = e.which || e.keyCode;
          if (key === 13) {
            var string = ($('.comment_input').val()).slice(0);

            self.input.remove();

            if (string != "") {
              annotations[i] = {'latLng': latLng, 'text': string};
              self.newAnnotation(i);
              self.index++;
              self.markers[i].openPopup();
            }

            $(Lt.map._container).off('click');
            $(document).off('keypress');
          }
        });
      },
    enable:
      function() {
        this.btn.state('active');
        this.active = true;
        document.getElementById('map').style.cursor = "pointer";

        var self = this;
        Lt.map.doubleClickZoom.disable();
        $(Lt.map._container).dblclick(function(e) {
          if (!self.markerClicked) {
            $(Lt.map._container).click(function(e) {
              self.disable();
              self.enable();
            });
            latLng = Lt.map.mouseEventToLatLng(e);
            self.action(self.index, latLng);
          }
          self.markerClicked = false;
        })
      },
    disable:
      function() {
        this.btn.state('inactive');
        Lt.map.doubleClickZoom.enable();
        $(Lt.map._container).off('dblclick');
        $(Lt.map._container).off('click');
        $(document).off('keypress');
        document.getElementById('map').style.cursor = "default";
        this.input.remove();
        this.active = false;
      },
    editAnnotation:
      function(i) {
        var self = this;
        var marker = self.markers[i]; 

        $(marker).off('mouseover');
        $(marker).off('mouseout');

        //marker.closePopup();
        marker.setPopupContent('<textarea id="comment_input" name="message" rows="2" cols="15">' + annotations[i].text + '</textarea>');
        marker.openPopup();
        document.getElementById('comment_input').select();

        $(document).keypress(function(e) {
          var key = e.which// || e.keyCode;
          if (key === 13) {
            if ($('#comment_input').val() != undefined) {
              var string = ($('#comment_input').val()).slice(0);

              if (string != "") {
                annotations[i].text = string;
                marker.setPopupContent(string);
                $(marker).mouseover(self.popupMouseover);
                $(marker).mouseout(self.popupMouseout);
              } else {
                self.deleteAnnotation(i);
              }
            }
            self.disable();
          }
        });
      },
    deleteAnnotation: 
      function(i) {
        this.layer.removeLayer(this.markers[i]);
        delete annotations[i];
        this.reload();
      },
    btn:
      L.easyButton({
        states: [
        {
          stateName:  'inactive',
          icon:       '<i class="material-icons md-18">comment</i>',
          title:      'Make an annotation',
          onClick:    function(btn, map) {
            edit.collapse();
            create.collapse();
            time.collapse();
            data.disable();

            annotation.enable();
          }
        },
        {
          stateName:  'active',
          icon:       '<i class="material-icons md-18">clear</i>',
          title:      'Cancel',
          onClick:    function(btn, map) {
            annotation.disable();
          }
        }]
      })
  }

  var fileIO = {
    saveLocal: {
      action:
        function() {
          dataJSON = {'SaveDate': saveDate, 'year': year, 'earlywood': earlywood, 'index': index, 'points': points, 'annotations': annotations};
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
            onClick:    function(btn, map) {
              fileIO.saveLocal.action();
            }  
          }]
        })
    },
    saveCloud: {
      date:
        new Date(),
      updateDate:
        function() {
          var day = this.date.getDate();
          var month = this.date.getMonth() + 1;
          var year = this.date.getFullYear();
          saveDate = {'day': day, 'month': month, 'year': year};
        },
      displayDate:
        function() {  
          if (saveDate != {}) {
            document.getElementById("leaflet-save-time-tag").innerHTML = "Saved to cloud on " + saveDate.month + "/" + saveDate.day + "/" + saveDate.year;
          } else {
            document.getElementById("leaflet-save-time-tag").innerHTML = "No data saved to cloud";
          }
        },
      action:
        function() {
          if (Lt.savePermission) {
            self = this;
            dataJSON = {'saveDate': saveDate, 'year': year, 'earlywood': earlywood, 'index': index, 'points': points, 'annotations': annotations};
            $.post(Lt.saveURL, {sidecarContent: JSON.stringify(dataJSON)}).done(function(msg) {
                self.updateDate();
                self.displayDate();
                console.log("saved");
              })
              .fail(function(xhr, status, error) {
                alert("Error: failed to save changes");
              })
          } else {
            alert("Authentication Error: save to cloud permission not granted");
          }
        },
      initialize:
        function() {
          var saveTimeDiv = document.createElement("div");
          saveTimeDiv.innerHTML = "<div class='leaflet-control-attribution leaflet-control'><p id='leaflet-save-time-tag'></p></div>";
          document.getElementsByClassName("leaflet-bottom leaflet-left")[0].appendChild(saveTimeDiv);
        },
      btn:
        L.easyButton ({
          states: [
          {
            stateName:  'saveCloud',
            icon:       '<i class="material-icons md-18">cloud_upload</i>',
            title:      'Save to cloud.',
            onClick:    function(btn, map) {
              fileIO.saveCloud.action();
            }
          }]
        })
    },
    loadLocal: {
      input:
        function() {
          var self = this;
          var input = document.createElement("input");
          input.type = 'file';
          input.id = 'file';
          input.style ='display: none';
          input.addEventListener("change", function() {self.action(input)});
          input.click();
        },
      action:
        function(inputElement) {
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
            onClick:    function(btn, map) {
              fileIO.loadLocal.input();
            }
          }]
        })
    },
    collapse:
      function() {
        this.btn.state('collapse');
        this.saveLocal.btn.disable();
        this.loadLocal.btn.disable();
        this.saveCloud.btn.disable();
      },
    btn:
      L.easyButton ({
        states: [
        {
          stateName:  'collapse',
          icon:       '<i class="material-icons md-18">folder_open</i>',
          title:      'View and download data',
          onClick:    function(btn, map) {
            btn.state('expand');
            fileIO.saveLocal.btn.enable();
            fileIO.loadLocal.btn.enable();
            if (Lt.savePermission) {
              fileIO.saveCloud.btn.enable();
            }

            create.collapse();
            time.collapse();
            edit.collapse();
            annotation.disable();
          }  
        },
        {
          stateName:  'expand',
          icon:       '<i class="material-icons md-18">expand_less</i>',
          title:      'Collapse',
          onClick:    function(btn, map) {
            fileIO.collapse();
          } 
        }]
      })
  }

  //displaying and dowloading data fall under the data object
  var data = {
    download: {
      //the following three functions are used for formating data for download
      toFourCharString: 
        function(n) {
          var string = n.toString();

          if (string.length == 1) {
            string = "   " + string;
          } else if (string.length == 2) {
            string = "  " + string;
          } else if (string.length == 3) {
            string = " " + string;
          } else if (string.length == 4) {
            string = string;
          } else if (string.length >= 5) {
            alert("Value exceeds 4 characters");
            throw "error in toFourCharString(n)";
          } else {
            alert("toSixCharString(n) unknown error");
            throw "error";
          }
          return string;
        },
      toSixCharString:
        function(n) {
          var string = n.toString();

          if (string.length == 1) {
            string = "     " + string;
          } else if (string.length == 2) {
            string = "    " + string;
          } else if (string.length == 3) {
            string = "   " + string;
          } else if (string.length == 4) {
            string = "  " + string;
          } else if (string.length == 5) {
            string = " " + string;
          } else if (string.length >= 6) {
            alert("Value exceeds 5 characters");
            throw "error in toSixCharString(n)";
          } else {
            alert("toSixCharString(n) unknown error");
            throw "error";
          }
          return string;
        },
      toEightCharString: 
        function(n) {
          var string = n.toString();
          if (string.length == 0) {
            string = string + "        ";
          } else if (string.length == 1) {
            string = string + "       ";
          } else if (string.length == 2) {
            string = string + "      ";
          } else if (string.length == 3) {
            string = string + "     ";
          } else if (string.length == 4) {
            string = string + "    ";
          } else if (string.length == 5) {
            string = string + "   ";
          } else if (string.length == 6) {
            string = string + "  ";
          } else if (string.length == 7) {
            string = string + " ";
          } else if (string.length >= 8) {
            alert("Value exceeds 7 characters");
            throw "error in toEightCharString(n)";
          } else {
            alert("toSixCharString(n) unknown error");
            throw "error";
          }
          return string;
        },
      action:
        function() {
          if (points != undefined && points[1] != undefined) {
            if (Lt.hasLatewood) {

              var sum_string = "";
              var ew_string = "";
              var lw_string = "";

              y = points[1].year;
              sum_points = Object.values(points).filter(function(e) {
                if (e.earlywood != undefined) {
                  return !(e.earlywood);
                } else {
                  return true;
                }
              });

              if (sum_points[1].year%10 > 0) {
                sum_string = sum_string.concat(data.download.toEightCharString(Lt.assetName) + data.download.toFourCharString(sum_points[1].year));
              }
              sum_points.map(function(e, i, a) {
                if (!e.start) {
                  if (e.year%10 == 0) {
                    sum_string = sum_string.concat("\r\n" + data.download.toEightCharString(Lt.assetName) + data.download.toFourCharString(e.year));
                  }
                  while(e.year > y) {
                    sum_string = sum_string.concat("    -1");
                    y++;
                    if (y%10 == 0) {
                      sum_string = sum_string.concat("\r\n" + data.download.toFourCharString(e.year));
                    }
                  }
                  if (e.skip) {
                    sum_string = sum_string.concat("     0");
                    y++;
                  } else {
                    length = Math.round(distance(last_latLng, e.latLng)*1000)
                    if (length == 9999) {
                      length = 9998;
                    }
                    if (length == 999) {
                      length = 998;
                    }

                    length_string = data.download.toSixCharString(length); 

                    sum_string = sum_string.concat(length_string);
                    last_latLng = e.latLng;
                    y++;
                  }
                } else {
                  last_latLng = e.latLng;
                }
              });
              sum_string = sum_string.concat(" -9999");

              y = points[1].year;

              if (points[1].year%10 > 0) {
                ew_string = ew_string.concat(data.download.toEightCharString(Lt.assetName) + data.download.toFourCharString(points[1].year));
                lw_string = lw_string.concat(data.download.toEightCharString(Lt.assetName) + data.download.toFourCharString(points[1].year));
              }

              Object.values(points).map(function(e, i, a) {
                if (!e.start) {
                  if (e.year%10 == 0) {
                    if (e.skip) {
                      ew_string = ew_string.concat("\r\n" + data.download.toEightCharString(Lt.assetName) + data.download.toFourCharString(e.year));
                      lw_string = lw_string.concat("\r\n" + data.download.toEightCharString(Lt.assetName) + data.download.toFourCharString(e.year));
                    } else if (e.earlywood) {
                      ew_string = ew_string.concat("\r\n" + data.download.toEightCharString(Lt.assetName) + data.download.toFourCharString(e.year));
                    } else {
                      lw_string = lw_string.concat("\r\n" + data.download.toEightCharString(Lt.assetName) + data.download.toFourCharString(e.year));
                    }
                  }
                  while(e.year > y) {
                    ew_string = ew_string.concat("    -1");
                    lw_string = lw_string.concat("    -1");
                    y++;
                    if (y%10 == 0) {
                      ew_string = ew_string.concat("\r\n" + data.download.toEightCharString(Lt.assetName) + data.download.toFourCharString(e.year));
                      lw_string = lw_string.concat("\r\n" + data.download.toEightCharString(Lt.assetName) + data.download.toFourCharString(e.year));
                    }
                  }
                  if (e.skip) {
                    if (e.earlywood) {
                      ew_string = ew_string.concat("     0");
                    } else {
                      lw_string = lw_string.concat("     0");
                      y++;
                    }
                  }
                  else {
                    length = Math.round(distance(last_latLng, e.latLng)*1000)
                    if (length == 9999) {
                      length = 9998;
                    }
                    if (length == 999) {
                      length = 998;
                    }

                    length_string = data.download.toSixCharString(length); 

                    if (e.earlywood) {
                      ew_string = ew_string.concat(length_string);
                      last_latLng = e.latLng;
                    } else {
                      lw_string = lw_string.concat(length_string);
                      last_latLng = e.latLng;
                      y++;
                    }
                  }
                } else {
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

            } else {
              var sum_string = "";

              y = points[1].year;
              sum_points = Object.values(points);

              if (sum_points[1].year%10 > 0) {
                sum_string = sum_string.concat(data.download.toEightCharString(Lt.assetName) + data.download.toFourCharString(sum_points[1].year));
              }
              sum_points.map(function(e, i, a) {
                if (!e.start) {
                  if (e.year%10 == 0) {
                    sum_string = sum_string.concat("\r\n" + data.download.toEightCharString(Lt.assetName) + data.download.toFourCharString(e.year));
                  }
                  while(e.year > y) {
                    sum_string = sum_string.concat("    -1");
                    y++;
                    if (y%10 == 0) {
                      sum_string = sum_string.concat("\r\n" + data.download.toFourCharString(e.year));
                    }
                  }
                  if (e.skip) {
                    sum_string = sum_string.concat("     0");
                    y++;
                  } else {
                    length = Math.round(distance(last_latLng, e.latLng)*1000)
                    if (length == 9999) {
                      length = 9998;
                    }
                    if (length == 999) {
                      length = 998;
                    }

                    length_string = data.download.toSixCharString(length); 

                    sum_string = sum_string.concat(length_string);
                    last_latLng = e.latLng;
                    y++;
                  }
                } else {
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
          } else {
            alert("There is no data to download");
          }
        }
    },
    dialog:
      L.control.dialog({'size': [240, 350], 'anchor': [5, 50], 'initOpen': false})
        .setContent('<h3>There are no data points to measure</h3>')
        .addTo(Lt.map),
    enable:
      function() {
        if (points[0] != undefined) {
          var y = points[1].year;
          var string = "<div><button id='download-button' class='mdc-button mdc-button--unelevated mdc-button-compact'>download</button>" +
            "<button id='delete-button' class='mdc-button mdc-button--unelevated mdc-button-compact'>delete all</button></div>" +
            "<table><tr><th style='width: 45%;'>Year</th><th style='width: 70%;'>Length</th></tr>";
          Object.values(points).map(function(e, i, a) {
            if (e.start) {
              last_point = e;
            } else if (e.break) {
              break_length = Math.round(distance(last_point.latLng, e.latLng)*1000)/1000;
              last_point = e;
            } else {
              while(e.year > y) {
                string = string.concat("<tr><td>" + y + "-</td><td>N/A</td></tr>");
                y++;
              }
              if (e.skip) {
                string = string.concat("<tr><td>"+ e.year + "</td><td>0 mm</td></tr>");
                y++;
              } else {
                length = Math.round(distance(last_point.latLng, e.latLng)*1000)/1000;
                if (last_point.break) {
                  length += break_length;
                }
                if (length == 9.999) {
                  length = 9.998;
                }
                if (Lt.hasLatewood) {
                  if (e.earlywood) {
                    wood = "e";
                    row_color = "#00d2e6";
                  } else {
                    wood = "l";
                    row_color = "#00838f";
                    y++;
                  }
                  string = string.concat("<tr style='color:" + row_color + ";'>");
                  string = string.concat("<td>"+ e.year + wood + "</td><td>" + length + " mm</td></tr>");
                } else {
                  y++;
                  string = string.concat("<tr style='color: #00d2e6;'>");
                  string = string.concat("<td>"+ e.year +"</td><td>" + length + " mm</td></tr>");
                }
                last_point = e;
              }
            }
          });
          this.dialog.setContent(string + "</table>");
        } else {
          var string = "<div><button id='download-button' class='mdc-button mdc-button--unelevated mdc-button-compact' disabled>download</button>" +
            "<button id='delete-button' class='mdc-button mdc-button--unelevated mdc-button-compact'>delete all</button></div>" +
            "<h3>There are no data points to measure</h3>";
          this.dialog.setContent(string);
        }
        this.dialog.lock();
        this.dialog.open();
        var self = this;
        $('#download-button').click(self.download.action);
        $('#delete-button').click(function() {
          self.dialog.setContent('<p>This action will delete all data points. Annotations will not be effected. Are you sure you want to continue?</p>' +
                '<p><button id="confirm-delete" class="mdc-button mdc-button--unelevated mdc-button-compact">confirm</button>' +
                '<button id="cancel-delete" class="mdc-button mdc-button--unelevated mdc-button-compact">cancel</button></p>');

          $('#confirm-delete').click(function() {
            undo.push();

            points = {};
            year = 0;
            earlywood = true;
            index = 0;

            visualAsset.reload();

            self.disable();
          })
          $('#cancel-delete').click(function() {
            self.disable();
            self.enable();
          })
        });
        return;
      },
    disable:
      function() {
        this.btn.state('collapse');
        $('#confirm-delete').off('click');
        $('#cancel-delete').off('click');
        $('#download-button').off('click');
        $('#delete-button').off('click');
        this.dialog.close();
      },
    btn:
      L.easyButton ({
        states: [
        {
          stateName:  'collapse',
          icon:       '<i class="material-icons md-18">view_list</i>',
          title:      'View and download data',
          onClick:    function(btn, map) {
            btn.state('expand');
            data.enable();

            create.collapse();
            time.collapse();
            edit.collapse();
            annotation.disable();
          }  
        },
        {
          stateName:  'expand',
          icon:       '<i class="material-icons md-18">clear</i>',
          title:      'Collapse',
          onClick:    function(btn, map) {
            data.disable();
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

  if (Lt.savePermission) {
    var fileBar = L.easyBar([fileIO.btn, fileIO.saveLocal.btn, fileIO.saveCloud.btn, fileIO.loadLocal.btn]);
  } else {
    var fileBar = L.easyBar([fileIO.btn, fileIO.saveLocal.btn, fileIO.loadLocal.btn]);
  }
  fileIO.saveLocal.btn.disable();
  fileIO.saveCloud.btn.disable();
  fileIO.loadLocal.btn.disable();

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