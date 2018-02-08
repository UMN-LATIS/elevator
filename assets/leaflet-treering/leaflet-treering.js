
var leafletTreering = function(map, basePath, options) {
  var Lt = this;

  Lt.map = map;
  Lt.basePath = basePath;

  //options
  Lt.ppm = options.ppm || 468;
  Lt.saveURL = options.saveURL || '';
  Lt.savePermission = options.savePermission || false;
  Lt.popoutUrl = options.popoutUrl || null;
  Lt.initialData = options.initialData || {};
  Lt.assetName = options.assetName || 'N/A';
  Lt.hasLatewood = options.hasLatewood || true;

  if (Lt.ppm == 0) {
    alert('Please set up PPM in asset metadata. PPM will default to 468.');
    Lt.ppm = 468;
  }

  /* after a leafletTreering is defined, loadInterface will be used to
  load all buttons and any initial data */
  Lt.loadInterface = function() {

    autoScroll.on();

    Lt.map.on('resize', function(e) {
      autoScroll.reset();
    });

    document.getElementById('map').style.cursor = 'default';

    //add all UI elements to map

    if (window.name == 'popout') {
      miniMap.addTo(Lt.map);

      data.btn.addTo(Lt.map);
      annotation.btn.addTo(Lt.map);
      setYear.btn.addTo(Lt.map);
      createBar.addTo(Lt.map);
      editBar.addTo(Lt.map);
      fileBar.addTo(Lt.map);
      undoRedoBar.addTo(Lt.map);
    } else {
      popout.btn.addTo(Lt.map);
      data.btn.addTo(Lt.map);
      fileBar.addTo(Lt.map);
    }

    L.control.layers(baseLayer, overlay).addTo(Lt.map);

    Lt.map.on('contextmenu', function(e) {
      if (!create.dataPoint.active && points[0] != undefined &&
          create.btn._currentState.stateName == 'expand') {
        create.dataPoint.startPoint = false;
        create.dataPoint.active = true;
        create.dataPoint.enable();
        interactiveMouse.hbarFrom(points[index - 1].latLng);
      } else {
        create.dataPoint.disable();
        create.breakPoint.disable();
        edit.deletePoint.disable();
        edit.cut.disable();
        edit.addData.disable();
        edit.addZeroGrowth.disable();
        edit.addBreak.disable();
        annotation.disable();
        setYear.disable();
      }
    });

    loadData(Lt.initialData);

    //if (points[index-1] != undefined) {
    //}

    fileIO.saveCloud.initialize();

    fileIO.saveCloud.displayDate();
  };

  distance = function(p1, p2) {
    lastPoint = Lt.map.project(p1, Lt.map.getMaxZoom());
    newPoint = Lt.map.project(p2, Lt.map.getMaxZoom());
    /* Math.sqrt(Math.pow(Math.abs(this._pixelPos[i - 1][0] -
      this._pixelPos[i][0]), 2) + Math.pow(Math.abs(this._pixelPos[i - 1][1] -
      this._pixelPos[i][1]), 2)); */
    length = Math.sqrt(Math.pow(Math.abs(lastPoint.x - newPoint.x), 2) +
        Math.pow(Math.abs(newPoint.y - lastPoint.y), 2));
    pixelsPerMillimeter = 1;
    Lt.map.eachLayer(function(layer) {
      if (layer.options.pixelsPerMillimeter > 0) {
        pixelsPerMillimeter = Lt.ppm;
      }
    });
    length = length / pixelsPerMillimeter;
    retinaFactor = 1;
    // if (L.Browser.retina) {
    //   retinaFactor = 2; // this is potentially incorrect for 3x+ devices
    // }
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

    setYear.disable();
    annotation.disable();
    edit.collapse();
    create.collapse();
  };

  var autoScroll = {
    on:
        function() {
          //map scrolling
          var mapSize = Lt.map.getSize();  // Map size used for map scrolling
          var mousePos = 0;         // An initial mouse position

          Lt.map.on('mousemove', function(e) {
            var oldMousePos = mousePos;    // Save the old mouse position
            mousePos = e.containerPoint;  // Container point of the mouse
            var mouseLatLng = e.latlng;     // latLng of the mouse
            var mapCenter = Lt.map.getCenter();  // Center of the map

            //left bound of the map
            if (mousePos.x <= 40 && mousePos.y > 450 &&
                oldMousePos.x > mousePos.x) {
              Lt.map.panBy([-200, 0]);
            }
            //right bound of the map
            if (mousePos.x + 40 > mapSize.x &&
                mousePos.y > 100 && oldMousePos.x < mousePos.x) {
              Lt.map.panBy([200, 0]);
            }
            //upper bound of the map
            if (mousePos.x > 100 && mousePos.x + 100 < mapSize.x &&
                mousePos.y < 40 && oldMousePos.y > mousePos.y) {
              Lt.map.panBy([0, -70]);
            }
            //lower bound of the map
            if (mousePos.x >= 40 && mousePos.y > mapSize.y - 40 &&
                oldMousePos.y < mousePos.y) {
              Lt.map.panBy([0, 70]);
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
  };

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
    red: L.icon({
      iconUrl: Lt.basePath + 'images/red_dot_icon.png',
      iconSize: [12, 12] // size of the icon
    }),
  };

  //when user adds new markers lines and hbars will be created from the mouse
  var interactiveMouse = {
    layer:
        L.layerGroup().addTo(Lt.map),
    hbarFrom:
        function(latLng) {
          var self = this;
          $(Lt.map._container).mousemove(function(e) {
            if (create.dataPoint.active) {
              self.layer.clearLayers();
              var mousePoint = Lt.map.mouseEventToLayerPoint(e);
              var mouseLatLng = Lt.map.mouseEventToLatLng(e);
              var point = Lt.map.latLngToLayerPoint(latLng);

              /* Getting the four points for the h bars,
            this is doing 90 degree rotations on mouse point */
              var newX = mousePoint.x +
                  (point.x - mousePoint.x) * Math.cos(Math.PI / 2) -
                  (point.y - mousePoint.y) * Math.sin(Math.PI / 2);
              var newY = mousePoint.y +
                  (point.x - mousePoint.x) * Math.sin(Math.PI / 2) +
                  (point.y - mousePoint.y) * Math.cos(Math.PI / 2);
              var topRightPoint = Lt.map.layerPointToLatLng([newX, newY]);

              var newX = mousePoint.x +
                  (point.x - mousePoint.x) * Math.cos(Math.PI / 2 * 3) -
                  (point.y - mousePoint.y) * Math.sin(Math.PI / 2 * 3);
              var newY = mousePoint.y +
                  (point.x - mousePoint.x) * Math.sin(Math.PI / 2 * 3) +
                  (point.y - mousePoint.y) * Math.cos(Math.PI / 2 * 3);
              var bottomRightPoint = Lt.map.layerPointToLatLng([newX, newY]);

              //doing rotations 90 degree rotations on latlng
              var newX = point.x +
                  (mousePoint.x - point.x) * Math.cos(Math.PI / 2) -
                  (mousePoint.y - point.y) * Math.sin(Math.PI / 2);
              var newY = point.y +
                  (mousePoint.x - point.x) * Math.sin(Math.PI / 2) +
                  (mousePoint.y - point.y) * Math.cos(Math.PI / 2);
              var topLeftPoint = Lt.map.layerPointToLatLng([newX, newY]);

              var newX = point.x +
                  (mousePoint.x - point.x) * Math.cos(Math.PI / 2 * 3) -
                  (mousePoint.y - point.y) * Math.sin(Math.PI / 2 * 3);
              var newY = point.y +
                  (mousePoint.x - point.x) * Math.sin(Math.PI / 2 * 3) +
                  (mousePoint.y - point.y) * Math.cos(Math.PI / 2 * 3);
              var bottomLeftPoint = Lt.map.layerPointToLatLng([newX, newY]);

              if (earlywood || !Lt.hasLatewood) {
                var color = '#00BCD4';
              } else {
                var color = '#00838f';
              }

              self.layer.addLayer(L.polyline([latLng, mouseLatLng],
                  {interactive: false, color: color, opacity: '.75',
                    weight: '3'}));
              self.layer.addLayer(L.polyline([topLeftPoint, bottomLeftPoint],
                  {interactive: false, color: color, opacity: '.75',
                    weight: '3'}));
              self.layer.addLayer(L.polyline([topRightPoint, bottomRightPoint],
                  {interactive: false, color: color, opacity: '.75',
                    weight: '3'}));
            }
          });
        }
  };

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
                visualAsset.newLatLng(points, i, e.latLng);
              }
            });
          }
        },
    previousLatLng:
        undefined,
    newLatLng:
        function(p, i, latLng) {
          leafLatLng = L.latLng(latLng);

          if (window.name == 'popout') {
            var draggable = true;
          } else {
            var draggable = false;
          }

          var draggable = true;

          //check if index is the start point
          if (p[i].start) {
            var marker = L.marker(leafLatLng, {icon: markerIcon.white,
              draggable: draggable, title: 'Start Point', riseOnHover: true});
          } else if (p[i].break) { //check if point is a break
            var marker = L.marker(leafLatLng, {icon: markerIcon.white,
              draggable: draggable, title: 'Break Point', riseOnHover: true});
          } else if (Lt.hasLatewood) { //check if point is earlywood
            if (p[i].earlywood) {
              var marker = L.marker(leafLatLng, {icon: markerIcon.light_blue,
                draggable: draggable, title: 'Year ' + p[i].year +
                    ', earlywood', riseOnHover: true});
            } else { //otherwise it's latewood
              var marker = L.marker(leafLatLng, {icon: markerIcon.dark_blue,
                draggable: draggable, title: 'Year ' + p[i].year + ', latewood',
                riseOnHover: true});
            }
          } else {
            var marker = L.marker(leafLatLng, {icon: markerIcon.light_blue,
              draggable: draggable, title: 'Year ' + p[i].year,
              riseOnHover: true});
          }

          this.markers[i] = marker;   //add created marker to marker_list
          var self = this;

          //tell marker what to do when being draged
          this.markers[i].on('drag', function(e) {
            if (!p[i].start) {
              self.lineLayer.removeLayer(self.lines[i]);
              self.lines[i] =
                  L.polyline([self.lines[i]._latlngs[0], e.target._latlng],
                  { color: self.lines[i].options.color,
                    opacity: '.75', weight: '3'});
              self.lineLayer.addLayer(self.lines[i]);
            }
            if (self.lines[i + 1] != undefined) {
              self.lineLayer.removeLayer(self.lines[i + 1]);
              self.lines[i + 1] =
                  L.polyline([e.target._latlng, self.lines[i + 1]._latlngs[1]],
                  { color: self.lines[i + 1].options.color,
                    opacity: '.75',
                    weight: '3'
                  });
              self.lineLayer.addLayer(self.lines[i + 1]);
            } else if (self.lines[i + 2] != undefined && !p[i + 1].start) {
              self.lineLayer.removeLayer(self.lines[i + 2]);
              self.lines[i + 2] =
                  L.polyline([e.target._latlng, self.lines[i + 2]._latlngs[1]],
                  { color: self.lines[i + 2].options.color,
                    opacity: '.75',
                    weight: '3' });
              self.lineLayer.addLayer(self.lines[i + 2]);
            }
          });

          //tell marker what to do when the draggin is done
          this.markers[i].on('dragend', function(e) {
            undo.push();
            p[i].latLng = e.target._latlng;
          });

          //tell marker what to do when clicked
          this.markers[i].on('click', function(e) {
            if (edit.deletePoint.active) {
              edit.deletePoint.action(i);
            }
            console.log(p[i]);

            if (edit.cut.active) {
              if (edit.cut.point != -1) {
                edit.cut.action(edit.cut.point, i);
              } else {
                edit.cut.point = i;
              }
            }
            if (edit.addZeroGrowth.active) {
              if ((p[i].earlywood && Lt.hasLatewood) || p[i].start ||
                  p[i].break) {
                alert('Missing year can only be placed at the end of a year!');
              } else {
                edit.addZeroGrowth.action(i);
              }
            }
            if (edit.addBreak.active) {
              edit.addBreak.action(i);
            }
            if (setYear.active) {
              setYear.action(i);
            }
          });

          //drawing the line if the previous point exists
          if (p[i - 1] != undefined && !p[i].start) {
            if (p[i].earlywood || !Lt.hasLatewood) {
              var color = '#00BCD4';
            } else {
              var color = '#00838f';
            }
            this.lines[i] =
                L.polyline([p[i - 1].latLng, leafLatLng],
                {color: color, opacity: '.75', weight: '3'});
            this.lineLayer.addLayer(this.lines[i]);
          }

          this.previousLatLng = leafLatLng;
          //add the marker to the marker layer
          this.markerLayer.addLayer(this.markers[i]);
        }
  };

  var popout = {
    btn:
        L.easyButton({
          states: [
            {
              stateName: 'popout',
              icon: '<i class="material-icons md-18">launch</i>',
              title: 'Popout Window',
              onClick: function(btn, map) {
                window.open(Lt.popoutUrl, 'popout',
                    'location=yes,height=600,width=800,scrollbars=yes,status=yes');
              }
            }]
        })
  };

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
          this.stack.push({'year': year, 'earlywood': earlywood,
            'index': index, 'points': restore_points });
        },
    pop:
        function() {
          if (this.stack.length > 0) {
            if (points[index - 1].start) {
              create.dataPoint.disable();
            } else {
              interactiveMouse.hbarFrom(points[index - 2].latLng);
            }

            redo.btn.enable();
            var restore_points = JSON.parse(JSON.stringify(points));
            redo.stack.push({'year': year, 'earlywood': earlywood,
              'index': index, 'points': restore_points});
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
        L.easyButton({
          states: [
            {
              stateName: 'undo',
              icon: '<i class="material-icons md-18">undo</i>',
              title: 'Undo',
              onClick: function(btn, map) {
                undo.pop();
              }
            }]
        })
  };

  //redo changes to points from undoing using a second stack
  var redo = {
    stack:
        new Array(),
    pop:
        function redo() {
          undo.btn.enable();
          var restore_points = JSON.parse(JSON.stringify(points));
          undo.stack.push({'year': year, 'earlywood': earlywood,
            'index': index, 'points': restore_points});
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
        L.easyButton({
          states: [
            {
              stateName: 'redo',
              icon: '<i class="material-icons md-18">redo</i>',
              title: 'Redo',
              onClick: function(btn, map) {
                redo.pop();
              }
            }]
        })
  };


  var setYear = {
    active:
        false,
    action:
        function(i) {
          if (points[i].year != undefined) {
            var self = this;

            var popup = L.popup({closeButton: false})
                .setContent(
                '<input type="number" style="border:none;width:50px;" value="' +
                points[i].year + '" id="year_input"></input>')
                .setLatLng(points[i].latLng)
                .openOn(Lt.map);

            document.getElementById('year_input').select();

            $(Lt.map._container).click(function(e) {
              popup.remove(Lt.map);
              self.disable();
            });

            $(document).keypress(function(e) {
              var key = e.which || e.keyCode;
              if (key === 13) {
                new_year =
                    parseInt(document.getElementById('year_input').value);
                popup.remove(Lt.map);

                var date = new Date();
                var max = date.getFullYear();

                if (new_year > max) {
                  alert('Year cannot exceed ' + max + '!');
                } else {
                  undo.push();

                  var shift = new_year - points[i].year;

                  Object.values(points).map(function(e, i) {
                    if (points[i].year != undefined) {
                      points[i].year += shift;
                    }
                  });
                  year += shift;
                  visualAsset.reload();
                }
                self.disable();
              }
            });
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
          $(Lt.map._container).off('click');
          $(document).off('keypress');
          this.active = false;
        },
    btn:
        L.easyButton({
          states: [
            {
              stateName: 'inactive',
              icon: '<i class="material-icons md-18">access_time</i>',
              title: 'Set the year of any point and adjust all other points',
              onClick: function(btn, map) {
                annotation.disable();
                edit.collapse();
                create.collapse();
                setYear.enable();
              }
            },
            {
              stateName: 'active',
              icon: '<i class="material-icons md-18">clear</i>',
              title: 'Cancel',
              onClick: function(btn, map) {
                setYear.disable();
              }
            }]
        })
  };

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

            document.getElementById('map').style.cursor = 'pointer';

            var self = this;

            $(document).keyup(function(e) {
              var key = e.which || e.keyCode;
              if (key === 27) {
                self.disable();
              }
            });

            $(Lt.map._container).click(function(e) {
              document.getElementById('map').style.cursor = 'pointer';

              var latLng = Lt.map.mouseEventToLatLng(e);

              undo.push();

              if (self.startPoint) {
                var popup = L.popup({closeButton: false}).setContent(
                    '<input type="number" style="border:none; width:50px;"' +
                    'value="' + year + '" id="year_input"></input>')
                    .setLatLng(latLng)
                    .openOn(Lt.map);

                document.getElementById('year_input').select();

                $(document).keypress(function(e) {
                  var key = e.which || e.keyCode;
                  if (key === 13) {
                    year =
                        parseInt(document.getElementById('year_input').value);
                    popup.remove(Lt.map);
                  }
                });
                points[index] = {'start': true, 'skip': false, 'break': false,
                  'latLng': latLng};
                self.startPoint = false;
              } else {
                points[index] = {'start': false, 'skip': false, 'break': false,
                  'year': year, 'earlywood': earlywood, 'latLng': latLng};
              }

              //call newLatLng with current index and new latlng
              visualAsset.newLatLng(points, index, latLng);

              //create the next mouseline from the new latlng
              interactiveMouse.hbarFrom(latLng);

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
            // turn off the mouse clicks from previous function
            $(Lt.map._container).off('click');
            this.btn.state('inactive');
            this.active = false;
            interactiveMouse.layer.clearLayers(); //clear the mouseline
            document.getElementById('map').style.cursor = 'default';

            this.startPoint = true;
          },
      btn:
          L.easyButton({
            states: [
              {
                stateName: 'inactive',
                icon: '<i class="material-icons md-18">linear_scale</i>',
                title: 'Create measurable points',
                onClick: function(btn, map) {
                  create.dataPoint.enable();
                }
              },
              {
                stateName: 'active',
                icon: '<i class="material-icons md-18">clear</i>',
                title: 'End (Esc)',
                onClick: function(btn, map) {
                  create.dataPoint.disable();
                }
              }]
          })
    },
    zeroGrowth: {
      action:
          function() {
            if (index) {
              var latLng = points[index - 1].latLng;

              undo.push();

              points[index] = {'start': false, 'skip': false, 'break': false,
                'year': year, 'earlywood': true, 'latLng': latLng};
              visualAsset.newLatLng(points, index, latLng);
              index++;
              if (Lt.hasLatewood) {
                points[index] = {'start': false, 'skip': false, 'break': false,
                  'year': year, 'earlywood': false, 'latLng': latLng};
                visualAsset.newLatLng(points, index, latLng);
                index++;
              }
              year++;
            } else {
              alert('First year cannot be missing!');
            }
          },
      btn:
          L.easyButton({
            states: [
              {
                stateName: 'skip-year',
                icon: '<i class="material-icons md-18">exposure_zero</i>',
                title: 'Add a zero growth year',
                onClick: function(btn, map) {
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

            document.getElementById('map').style.cursor = 'pointer';

            var self = this;
            $(Lt.map._container).click(function(e) {
              document.getElementById('map').style.cursor = 'pointer';

              var latLng = Lt.map.mouseEventToLatLng(e);

              interactiveMouse.hbarFrom(latLng);

              undo.push();

              Lt.map.dragging.disable();
              points[index] = {'start': false, 'skip': false, 'break': true,
                'latLng': latLng};
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
            interactiveMouse.layer.clearLayers();
            create.dataPoint.active = false;
          },
      btn:
          L.easyButton({
            states: [
              {
                stateName: 'inactive',
                icon: '<i class="material-icons md-18">broken_image</i>',
                title: 'Create a break point',
                onClick: function(btn, map) {
                  create.dataPoint.disable();
                  create.breakPoint.enable();
                  interactiveMouse.hbarFrom(points[index - 1].latLng);
                }
              },
              {
                stateName: 'active',
                icon: '<i class="material-icons md-18">clear</i>',
                title: 'Cancel',
                onClick: function(btn, map) {
                  create.breakPoint.disable();
                }
              }]
          })
    },
    btn:
        L.easyButton({
          states: [
            {
              stateName: 'collapse',
              icon: '<i class="material-icons md-18">straighten</i>',
              title: 'Create new data points',
              onClick: function(btn, map) {
                create.btn.state('expand');
                create.dataPoint.btn.enable();
                create.zeroGrowth.btn.enable();
                create.breakPoint.btn.enable();

                data.disable();
                edit.collapse();
                annotation.disable();
                setYear.disable();
              }
            },
            {
              stateName: 'expand',
              icon: '<i class="material-icons md-18">expand_less</i>',
              title: 'Collapse',
              onClick: function(btn, map) {
                create.collapse();
              }
            }]
        })
  };

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
              if (points[i - 1] != undefined && points[i - 1].break) {
                i--;
                second_points = Object.values(points).splice(i + 2, index - 1);
                var shift = points[i + 2].year - points[i - 1].year - 1;
                second_points.map(function(e) {
                  e.year -= shift;
                  points[i] = e;
                  i++;
                });
                year -= shift;
                index = index - 2;
                delete points[index];
                delete points[index + 1];
              } else {
                second_points = Object.values(points).splice(i + 1, index - 1);
                second_points.map(function(e) {
                  if (!i) {
                    points[i] = {'start': true, 'skip': false, 'break': false,
                      'latLng': e.latLng};
                  } else {
                    points[i] = e;
                  }
                  i++;
                });
                index--;
                delete points[index];
              }
            } else if (points[i].break) {
              second_points = Object.values(points).splice(i + 2, index - 1);
              var shift = points[i + 2].year - points[i - 1].year - 1;
              second_points.map(function(e) {
                e.year -= shift;
                points[i] = e;
                i++;
              });
              year -= shift;
              index = index - 2;
              delete points[index];
              delete points[index + 1];
            } else {
              var new_points = points;
              var k = i;
              second_points = Object.values(points).splice(i + 1, index - 1);
              console.log(second_points);
              second_points.map(function(e) {
                if (!e.start && !e.break) {
                  if (Lt.hasLatewood) {
                    e.earlywood = !e.earlywood;
                    if (!e.earlywood) {
                      e.year--;
                    }
                  } else {
                    e.year--;
                  }
                }
                new_points[k] = e;
                k++;
              });

              points = new_points;
              index--;
              delete points[index];
              earlywood = !earlywood;
              if (points[index - 1].earlywood) {
                year--;
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
          L.easyButton({
            states: [
              {
                stateName: 'inactive',
                icon: '<i class="material-icons md-18">delete</i>',
                title: 'Delete a point',
                onClick: function(btn, map) {
                  edit.cut.disable();
                  edit.addData.disable();
                  edit.addZeroGrowth.disable();
                  edit.addBreak.disable();
                  edit.deletePoint.enable();
                }
              },
              {
                stateName: 'active',
                icon: '<i class="material-icons md-18">clear</i>',
                title: 'Cancel',
                onClick: function(btn, map) {
                  edit.deletePoint.disable();
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
              trimmed_points = Object.values(points).splice(i, index - 1);
              var k = 0;
              points = {};
              trimmed_points.map(function(e) {
                if (!k) {
                  points[k] = {'start': true, 'skip': false, 'break': false,
                    'latLng': e.latLng};
                } else {
                  points[k] = e;
                }
                k++;
              });
              index = k;
            } else if (i < j) {
              points = Object.values(points).splice(0, i);
              index = i;
            } else {
              alert('You cannot select the same point');
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
          L.easyButton({
            states: [
              {
                stateName: 'inactive',
                icon: '<i class="material-icons md-18">content_cut</i>',
                title: 'Cut a portion of the series',
                onClick: function(btn, map) {
                  edit.deletePoint.disable();
                  edit.addData.disable();
                  edit.addZeroGrowth.disable();
                  edit.addBreak.disable();
                  edit.cut.enable();
                }
              },
              {
                stateName: 'active',
                icon: '<i class="material-icons md-18">clear</i>',
                title: 'Cancel',
                onClick: function(btn, map) {
                  edit.cut.disable();
                }
              }]
          })
    },
    addData: {
      active:
          false,
      action:
          function() {
            document.getElementById('map').style.cursor = 'pointer';

            var self = this;
            $(Lt.map._container).click(function(e) {
              var latLng = Lt.map.mouseEventToLatLng(e);

              i = 0;
              while (points[i] != undefined &&
                  points[i].latLng.lng < latLng.lng) {
                i++;
              }
              if (points[i] == null) {
                alert('New point must be within existing points.' +
                    'Use the create toolbar to add new points to the series.');
                self.disable();
                return;
              }

              var new_points = points;
              var second_points = Object.values(points).splice(i, index - 1);
              var k = i;
              var year_adjusted = points[i].year;
              earlywood_adjusted = true;

              if (points[i - 1].earlywood && Lt.hasLatewood) {
                year_adjusted = points[i - 1].year;
                earlywood_adjusted = false;
              } else if (points[i - 1].start) {
                year_adjusted = points[i + 1].year;
              } else {
                year_adjusted = points[i - 1].year + 1;
              }
              new_points[k] = {'start': false, 'skip': false, 'break': false,
                'year': year_adjusted, 'earlywood': earlywood_adjusted,
                'latLng': latLng};
              visualAsset.newLatLng(new_points, k, latLng);
              k++;

              second_points.map(function(e) {
                if (!e.start && !e.break) {
                  if (Lt.hasLatewood) {
                    e.earlywood = !e.earlywood;
                    if (e.earlywood) {
                      e.year++;
                    }
                  }
                  else {
                    e.year++;
                  }
                }
                new_points[k] = e;
                k++;
              });

              undo.push();

              points = new_points;
              index = k;
              if (Lt.hasLatewood) {
                earlywood = !earlywood;
              }
              if (!points[index - 1].earlywood || !Lt.hasLatewood) {
                year++;
              }

              visualAsset.reload();
              self.disable();
            });
          },
      enable:
          function() {
            this.btn.state('active');
            this.action();
            this.active = true;
          },
      disable:
          function() {
            $(Lt.map._container).off('click');
            this.btn.state('inactive');
            this.active = false;
            document.getElementById('map').style.cursor = 'default';
            interactiveMouse.layer.clearLayers();
            create.dataPoint.active = false;
          },
      btn:
          L.easyButton({
            states: [
              {
                stateName: 'inactive',
                icon: '<i class="material-icons md-18">add_circle_outline</i>',
                title: 'Add a point in the middle of the series',
                onClick: function(btn, map) {
                  edit.deletePoint.disable();
                  edit.cut.disable();
                  edit.addZeroGrowth.disable();
                  edit.addBreak.disable();
                  edit.addData.enable();
                }
              },
              {
                stateName: 'active',
                icon: '<i class="material-icons md-18">clear</i>',
                title: 'Cancel',
                onClick: function(btn, map) {
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
            var latLng = points[i].latLng;

            var new_points = points;
            var second_points = Object.values(points).splice(i + 1, index - 1);
            var k = i + 1;

            var year_adjusted = points[i].year + 1;

            new_points[k] = {'start': false, 'skip': false, 'break': false,
              'year': year_adjusted, 'earlywood': true, 'latLng': latLng};
            visualAsset.newLatLng(new_points, k, latLng);
            k++;

            if (Lt.hasLatewood) {
              new_points[k] = {'start': false, 'skip': false, 'break': false,
                'year': year_adjusted, 'earlywood': false, 'latLng': latLng};
              visualAsset.newLatLng(new_points, k, latLng);
              k++;
            }

            second_points.map(function(e) {
              if (!e.start && !e.break) {
                e.year++;
              }
              new_points[k] = e;
              k++;
            });

            undo.push();

            points = new_points;
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
          L.easyButton({
            states: [
              {
                stateName: 'inactive',
                icon: '<i class="material-icons md-18">exposure_zero</i>',
                title: 'Add a zero growth year in the middle of the series',
                onClick: function(btn, map) {
                  edit.deletePoint.disable();
                  edit.cut.disable();
                  edit.addData.disable();
                  edit.addBreak.disable();
                  edit.addZeroGrowth.enable();
                }
              },
              {
                stateName: 'active',
                icon: '<i class="material-icons md-18">clear</i>',
                title: 'Cancel',
                onClick: function(btn, map) {
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
            var second_points = Object.values(points).splice(i + 1, index - 1);
            var first_point = true;
            var second_point = false;
            var k = i + 1;

            create.dataPoint.active = true;
            interactiveMouse.hbarFrom(points[i].latLng);

            var self = this;
            $(Lt.map._container).click(function(e) {
              var latLng = Lt.map.mouseEventToLatLng(e);
              Lt.map.dragging.disable();

              if (first_point) {
                interactiveMouse.hbarFrom(latLng);
                new_points[k] = {'start': false, 'skip': false, 'break': true,
                  'latLng': latLng};
                visualAsset.newLatLng(new_points, k, latLng);
                k++;
                first_point = false;
                second_point = true;
              } else if (second_point) {
                second_point = false;
                this.active = false;
                interactiveMouse.layer.clearLayers();

                new_points[k] = {'start': true, 'skip': false, 'break': false,
                  'latLng': latLng};
                visualAsset.newLatLng(new_points, k, latLng);
                k++;

                var popup = L.popup({closeButton: false}).setContent(
                    '<input type="number" style="border:none;width:50px;"' +
                    'value="' + second_points[0].year +
                    '" id="year_input"></input>').setLatLng(latLng)
                    .openOn(Lt.map);

                document.getElementById('year_input').select();

                $(document).keypress(function(e) {
                  var key = e.which || e.keyCode;
                  if (key === 13) {
                    new_year =
                        parseInt(document.getElementById('year_input').value);
                    popup.remove(Lt.map);

                    var shift = new_year - second_points[0].year;

                    second_points.map(function(e) {
                      e.year += shift;
                      new_points[k] = e;
                      k++;
                    });
                    year += shift;

                    $(Lt.map._container).off('click');

                    undo.push();

                    points = new_points;
                    index = k;

                    visualAsset.reload();
                    self.disable();
                  }
                });
              } else {
                self.disable();
                visualAsset.reload();
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
          L.easyButton({
            states: [
              {
                stateName: 'inactive',
                icon: '<i class="material-icons md-18">broken_image</i>',
                title: 'Add a break in the series',
                onClick: function(btn, map) {
                  edit.deletePoint.disable();
                  edit.cut.disable();
                  edit.addData.disable();
                  edit.addZeroGrowth.disable();
                  edit.addBreak.enable();
                }
              },
              {
                stateName: 'active',
                icon: '<i class="material-icons md-18">clear</i>',
                onClick: function(btn, map) {
                  edit.addBreak.disable();
                }
              }]
          })
    },
    btn:
        L.easyButton({
          states: [
            {
              stateName: 'collapse',
              icon: '<i class="material-icons md-18">edit</i>',
              title: 'Edit and delete data points from the series',
              onClick: function(btn, map) {
                edit.btn.state('expand');
                edit.deletePoint.btn.enable();
                edit.cut.btn.enable();
                edit.addData.btn.enable();
                edit.addZeroGrowth.btn.enable();
                edit.addBreak.btn.enable();

                annotation.disable();
                create.collapse();
                setYear.disable();
                data.disable();
              }
            },
            {
              stateName: 'expand',
              icon: '<i class="material-icons md-18">expand_less</i>',
              title: 'Collapse',
              onClick: function(btn, map) {
                edit.collapse();
              }
            }]
        })
  };

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
        L.circle([0, 0], {radius: .0001, color: 'red', weight: '6'})
        .bindPopup('<textarea class="comment_input" name="message" rows="2"' +
        'cols="15"></textarea>', {closeButton: false}),
    reload:
        function() {
          self = this;
          self.layer.clearLayers();
          self.markers = new Array();
          self.index = 0;
          if (annotations != undefined) {
            var reduced =
                Object.values(annotations).filter(e => e != undefined);
            annotations = {};
            reduced.map((e, i) => annotations[i] = e);

            Object.values(annotations).map(function(e, i) {
              self.newAnnotation(i); self.index++});
          }
        },
    popupMouseover:
        function(e) {
          this.openPopup();
        },
    popupMouseout:
        function(e) {
          this.closePopup();
        },
    newAnnotation:
        function(i) {
          var self = this;

          var ref = annotations[i];
          var circle = L.circle(ref.latLng, {radius: .0001, color: 'red',
            weight: '6'});
          circle.bindPopup(ref.text, {closeButton: false});
          self.markers[i] = circle;

          $(self.markers[i]).click(function(e) {
            self.markers[i].closePopup();
          });

          $(self.markers[i]).mouseover(self.popupMouseover);
          $(self.markers[i]).mouseout(self.popupMouseout);

          if (window.name == 'popout') {
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
          }

          self.layer.addLayer(self.markers[i]);

          if (ref.text == '') {
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

              if (string != '') {
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
          document.getElementById('map').style.cursor = 'pointer';

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
          });
        },
    disable:
        function() {
          this.btn.state('inactive');
          Lt.map.doubleClickZoom.enable();
          $(Lt.map._container).off('dblclick');
          $(Lt.map._container).off('click');
          $(document).off('keypress');
          document.getElementById('map').style.cursor = 'default';
          this.input.remove();
          this.active = false;
        },
    editAnnotation:
        function(i) {
          var self = this;
          var marker = self.markers[i];

          $(marker).off('mouseover');
          $(marker).off('mouseout');

          marker.setPopupContent('<textarea id="comment_input" name="message"' +
              'rows="2" cols="15">' + annotations[i].text + '</textarea>');
          marker.openPopup();
          document.getElementById('comment_input').select();

          $(document).keypress(function(e) {
            var key = e.which || e.keyCode;
            if (key === 13) {
              if ($('#comment_input').val() != undefined) {
                var string = ($('#comment_input').val()).slice(0);

                if (string != '') {
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
              stateName: 'inactive',
              icon: '<i class="material-icons md-18">comment</i>',
              title: 'Make an annotation',
              onClick: function(btn, map) {
                edit.collapse();
                create.collapse();
                setYear.disable();
                data.disable();

                annotation.enable();
              }
            },
            {
              stateName: 'active',
              icon: '<i class="material-icons md-18">clear</i>',
              title: 'Cancel',
              onClick: function(btn, map) {
                annotation.disable();
              }
            }]
        })
  };

  var fileIO = {
    saveLocal: {
      action:
          function() {
            dataJSON = {'SaveDate': saveDate, 'year': year,
              'earlywood': earlywood, 'index': index, 'points': points,
              'annotations': annotations};
            var file = new File([JSON.stringify(dataJSON)],
                (Lt.assetName + '.json'), {type: 'text/plain;charset=utf-8'});
            saveAs(file);
          },
      btn:
          L.easyButton({
            states: [
              {
                stateName: 'save',
                icon: '<i class="material-icons md-18">save</i>',
                title: 'Save a local copy of measurements and annotation',
                onClick: function(btn, map) {
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
            var minute = this.date.getMinutes();
            var hour = this.date.getHours();
            saveDate = {'day': day, 'month': month, 'year': year, 'hour': hour,
              'minute': minute};
          },
      displayDate:
          function() {
            if (saveDate.day != undefined && saveDate.hour != undefined) {
              var am_pm = 'am';
              if (saveDate.hour >= 12) {
                saveDate.hour -= 12;
                am_pm = 'pm';
              }
              if (saveDate.hour == 0) {
                saveDate.hour += 12;
              }
              minute_string = saveDate.minute;
              if (saveDate.minute < 10) {
                minute_string = '0' + saveDate.minute;
              }
              document.getElementById('leaflet-save-time-tag').innerHTML =
                  'Saved to cloud at ' + saveDate.hour + ':' + minute_string +
                  am_pm + ' on ' + saveDate.month + '/' + saveDate.day + '/' +
                  saveDate.year;
            } else {
              document.getElementById('leaflet-save-time-tag').innerHTML =
                  'No data saved to cloud';
            }
          },
      action:
          function() {
            if (Lt.savePermission) {
              self = this;
              dataJSON = {'saveDate': saveDate, 'year': year,
                'earlywood': earlywood, 'index': index, 'points': points,
                'annotations': annotations};
              $.post(Lt.saveURL, {sidecarContent: JSON.stringify(dataJSON)})
                  .done(function(msg) {
                    self.updateDate();
                    self.displayDate();
                    console.log('saved');
                  })
                  .fail(function(xhr, status, error) {
                    alert('Error: failed to save changes');
                  });
            } else {
              alert(
                  'Authentication Error: save to cloud permission not granted');
            }
          },
      initialize:
          function() {
            var saveTimeDiv = document.createElement('div');
            saveTimeDiv.innerHTML =
                '<div class="leaflet-control-attribution leaflet-control">' +
                '<p id="leaflet-save-time-tag"></p></div>';
            document.getElementsByClassName('leaflet-bottom leaflet-left')[0]
                .appendChild(saveTimeDiv);
          },
      btn:
          L.easyButton({
            states: [
              {
                stateName: 'saveCloud',
                icon: '<i class="material-icons md-18">cloud_upload</i>',
                title: 'Save to cloud.',
                onClick: function(btn, map) {
                  fileIO.saveCloud.action();
                }
              }]
          })
    },
    loadLocal: {
      input:
          function() {
            var self = this;
            var input = document.createElement('input');
            input.type = 'file';
            input.id = 'file';
            input.style = 'display: none';
            input.addEventListener('change', function() {self.action(input)});
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
            };

            fr.readAsText(files.item(0));
          },
      btn:
          L.easyButton({
            states: [
              {
                stateName: 'load',
                icon: '<i class="material-icons md-18">file_upload</i>',
                title: 'Load a local file with measurements and annotations',
                onClick: function(btn, map) {
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
        L.easyButton({
          states: [
            {
              stateName: 'collapse',
              icon: '<i class="material-icons md-18">folder_open</i>',
              title: 'View and download data',
              onClick: function(btn, map) {
                btn.state('expand');
                fileIO.saveLocal.btn.enable();
                fileIO.loadLocal.btn.enable();
                if (Lt.savePermission) {
                  fileIO.saveCloud.btn.enable();
                }

                create.collapse();
                //time.collapse();
                setYear.disable();
                edit.collapse();
                annotation.disable();
              }
            },
            {
              stateName: 'expand',
              icon: '<i class="material-icons md-18">expand_less</i>',
              title: 'Collapse',
              onClick: function(btn, map) {
                fileIO.collapse();
              }
            }]
        })
  };

  //displaying and dowloading data fall under the data object
  var data = {
    download: {
      //the following three functions are used for formating data for download
      toFourCharString:
          function(n) {
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
          },
      toSixCharString:
          function(n) {
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
          },
      toEightCharString:
          function(n) {
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
          },
      action:
          function() {
            if (points != undefined && points[1] != undefined) {
              if (Lt.hasLatewood) {

                var sum_string = '';
                var ew_string = '';
                var lw_string = '';

                y = points[1].year;
                sum_points = Object.values(points).filter(function(e) {
                  if (e.earlywood != undefined) {
                    return !(e.earlywood);
                  } else {
                    return true;
                  }
                });

                if (sum_points[1].year % 10 > 0) {
                  sum_string = sum_string.concat(
                      data.download.toEightCharString(Lt.assetName) +
                      data.download.toFourCharString(sum_points[1].year));
                }

                break_point = false;
                sum_points.map(function(e, i, a) {
                  if (e.start) {
                    last_latLng = e.latLng;
                  } else if (e.break) {
                    break_length = 
                      Math.round(distance(last_latLng, e.latLng) * 1000);
                      break_point = true;
                  } else {
                    if (e.year % 10 == 0) {
                      sum_string = sum_string.concat('\r\n' +
                          data.download.toEightCharString(Lt.assetName) +
                          data.download.toFourCharString(e.year));
                    }
                    while (e.year > y) {
                      sum_string = sum_string.concat('    -1');
                      y++;
                      if (y % 10 == 0) {
                        sum_string = sum_string.concat('\r\n' +
                            data.download.toFourCharString(e.year));
                      }
                    }

                    length = Math.round(distance(last_latLng, e.latLng) * 1000);
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

                    length_string = data.download.toSixCharString(length);

                    sum_string = sum_string.concat(length_string);
                    last_latLng = e.latLng;
                    y++;
                  }
                });
                sum_string = sum_string.concat(' -9999');

                y = points[1].year;

                if (points[1].year % 10 > 0) {
                  ew_string = ew_string.concat(
                      data.download.toEightCharString(Lt.assetName) +
                      data.download.toFourCharString(points[1].year));
                  lw_string = lw_string.concat(
                      data.download.toEightCharString(Lt.assetName) +
                      data.download.toFourCharString(points[1].year));
                }

                break_point = false;
                Object.values(points).map(function(e, i, a) {
                  if (e.start) {
                    last_latLng = e.latLng;
                  } else if (e.break) {
                    break_length = 
                      Math.round(distance(last_latLng, e.latLng) * 1000);
                    break_point = true;
                  } else {
                    if (e.year % 10 == 0) {
                      if (e.earlywood) {
                        ew_string = ew_string.concat('\r\n' +
                            data.download.toEightCharString(Lt.assetName) +
                            data.download.toFourCharString(e.year));
                      } else {
                        lw_string = lw_string.concat('\r\n' +
                            data.download.toEightCharString(Lt.assetName) +
                            data.download.toFourCharString(e.year));
                      }
                    }
                    while (e.year > y) {
                      ew_string = ew_string.concat('    -1');
                      lw_string = lw_string.concat('    -1');
                      y++;
                      if (y % 10 == 0) {
                        ew_string = ew_string.concat('\r\n' +
                            data.download.toEightCharString(Lt.assetName) +
                            data.download.toFourCharString(e.year));
                        lw_string = lw_string.concat('\r\n' +
                            data.download.toEightCharString(Lt.assetName) +
                            data.download.toFourCharString(e.year));
                      }
                    }

                    length = Math.round(distance(last_latLng, e.latLng) * 1000);
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
                });
                ew_string = ew_string.concat(' -9999');
                lw_string = lw_string.concat(' -9999');

                console.log(sum_string);
                console.log(ew_string);
                console.log(lw_string);

                var zip = new JSZip();
                zip.file((Lt.assetName + '.raw'), sum_string);
                zip.file((Lt.assetName + '.lwr'), lw_string);
                zip.file((Lt.assetName + '.ewr'), ew_string);

              } else {
                var sum_string = '';

                y = points[1].year;
                sum_points = Object.values(points);

                if (sum_points[1].year % 10 > 0) {
                  sum_string = sum_string.concat(
                      data.download.toEightCharString(Lt.assetName) +
                      data.download.toFourCharString(sum_points[1].year));
                }
                sum_points.map(function(e, i, a) {
                  if (!e.start) {
                    if (e.year % 10 == 0) {
                      sum_string = sum_string.concat('\r\n' +
                          data.download.toEightCharString(Lt.assetName) +
                          data.download.toFourCharString(e.year));
                    }
                    while (e.year > y) {
                      sum_string = sum_string.concat('    -1');
                      y++;
                      if (y % 10 == 0) {
                        sum_string = sum_string.concat('\r\n' +
                            data.download.toFourCharString(e.year));
                      }
                    }

                    length = Math.round(distance(last_latLng, e.latLng) * 1000);
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
                  } else {
                    last_latLng = e.latLng;
                  }
                });
                sum_string = sum_string.concat(' -9999');

                console.log(sum_string);

                var zip = new JSZip();
                zip.file((Lt.assetName + '.raw'), sum_string);
              }

              zip.generateAsync({type: 'blob'})
                  .then(function(blob) {
                    saveAs(blob, (Lt.assetName + '.zip'));
                  });
            } else {
              alert('There is no data to download');
            }
          }
    },
    dialog:
        L.control.dialog({'size': [340, 400], 'anchor': [5, 50],
          'initOpen': false})
        .setContent('<h3>There are no data points to measure</h3>')
        .addTo(Lt.map),
    enable:
        function() {
          this.btn.state('expand');
          if (points[0] != undefined) {
            var y = points[1].year;
            var string = '<div><button id="download-button"' +
                'class="mdc-button mdc-button--unelevated mdc-button-compact"' +
                '>download</button><button id="refresh-button"' +
                'class="mdc-button mdc-button--unelevated mdc-button-compact"' +
                '>refresh</button><button id="delete-button"' +
                'class="mdc-button mdc-button--unelevated mdc-button-compact"' +
                '>delete all</button></div><table><tr>' +
                '<th style="width: 45%;">Year</th>' +
                '<th style="width: 70%;">Length</th></tr>';

            break_point = false;
            Object.values(points).map(function(e, i, a) {
              if (e.start) {
                last_latLng = e.latLng;
              } else if (e.break) {
                break_length =
                  Math.round(distance(last_latLng, e.latLng) * 1000) / 1000;
                break_point = true;
              } else {
                while (e.year > y) {
                  string = string.concat('<tr><td>' + y +
                      '-</td><td>N/A</td></tr>');
                  y++;
                }

                length =
                  Math.round(distance(last_latLng, e.latLng) * 1000) / 1000;
                if (break_point) {
                  length += break_length;
                  length = Math.round(length * 1000) / 1000;
                  break_point = false;
                }
                if (length == 9.999) {
                  length = 9.998;
                }
                if (Lt.hasLatewood) {
                  if (e.earlywood) {
                    wood = 'E';
                    row_color = '#00d2e6';
                  } else {
                    wood = 'L';
                    row_color = '#00838f';
                    y++;
                  }
                  string =
                      string.concat('<tr style="color:' + row_color + ';">');
                  string = string.concat('<td>' + e.year + wood + '</td><td>' +
                      length + ' mm</td></tr>');
                } else {
                  y++;
                  string = string.concat('<tr style="color: #00d2e6;">');
                  string = string.concat('<td>' + e.year + '</td><td>' +
                      length + ' mm</td></tr>');
                }
                last_latLng = e.latLng;
              }
            });
            this.dialog.setContent(string + '</table>');
          } else {
            var string = '<div><button id="download-button"' +
                'class="mdc-button mdc-button--unelevated mdc-button-compact"' +
                'disabled>download</button>' +
                '<button id="refresh-button"' +
                'class="mdc-button mdc-button--unelevated mdc-button-compact"' +
                '>refresh</button><button id="delete-button"' +
                'class="mdc-button mdc-button--unelevated mdc-button-compact"' +
                '>delete all</button></div>' +
                '<h3>There are no data points to measure</h3>';
            this.dialog.setContent(string);
          }
          this.dialog.lock();
          this.dialog.open();
          var self = this;
          $('#download-button').click(self.download.action);
          $('#refresh-button').click(function() {
            self.disable();
            self.enable();
          });
          $('#delete-button').click(function() {
            self.dialog.setContent(
                '<p>This action will delete all data points.' +
                'Annotations will not be effected.' +
                'Are you sure you want to continue?</p>' +
                '<p><button id="confirm-delete"' +
                'class="mdc-button mdc-button--unelevated mdc-button-compact"' +
                '>confirm</button><button id="cancel-delete"' +
                'class="mdc-button mdc-button--unelevated mdc-button-compact"' +
                '>cancel</button></p>');

            $('#confirm-delete').click(function() {
              undo.push();

              points = {};
              year = 0;
              earlywood = true;
              index = 0;

              visualAsset.reload();

              self.disable();
            });
            $('#cancel-delete').click(function() {
              self.disable();
              self.enable();
            });
          });
        },
    disable:
        function() {
          $(Lt.map._container).off('click');
          this.btn.state('collapse');
          $('#confirm-delete').off('click');
          $('#cancel-delete').off('click');
          $('#download-button').off('click');
          $('#refresh-button').off('click');
          $('#delete-button').off('click');
          this.dialog.close();
        },
    btn:
        L.easyButton({
          states: [
            {
              stateName: 'collapse',
              icon: '<i class="material-icons md-18">view_list</i>',
              title: 'View and download data',
              onClick: function(btn, map) {
                data.enable();

                create.collapse();
                setYear.disable();
                edit.collapse();
                annotation.disable();
              }
            },
            {
              stateName: 'expand',
              icon: '<i class="material-icons md-18">clear</i>',
              title: 'Collapse',
              onClick: function(btn, map) {
                data.disable();
              }
            }]
        })
  };

  //grouping the buttons into their respective toolbars
  var undoRedoBar = L.easyBar([undo.btn, redo.btn]);
  undo.btn.disable();
  redo.btn.disable();

  var createBar = L.easyBar([create.btn, create.dataPoint.btn,
    create.zeroGrowth.btn, create.breakPoint.btn]);
  create.dataPoint.btn.disable();
  create.zeroGrowth.btn.disable();
  create.breakPoint.btn.disable();

  var editBar = L.easyBar([edit.btn, edit.deletePoint.btn, edit.cut.btn,
    edit.addData.btn, edit.addZeroGrowth.btn, edit.addBreak.btn]);
  edit.deletePoint.btn.disable();
  edit.cut.btn.disable();
  edit.addData.btn.disable();
  edit.addZeroGrowth.btn.disable();
  edit.addBreak.btn.disable();

  if (Lt.savePermission) {
    var fileBar = L.easyBar([fileIO.btn, fileIO.saveLocal.btn,
      fileIO.saveCloud.btn, fileIO.loadLocal.btn]);
  } else {
    var fileBar = L.easyBar([fileIO.btn, fileIO.saveLocal.btn,
      fileIO.loadLocal.btn]);
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
    'Tree Ring': layer
  };

  var overlay = {
    'Points': visualAsset.markerLayer,
    'H-bar': interactiveMouse.layer,
    'Lines': visualAsset.lineLayer,
    'Annotations': annotation.layer
  };
};
