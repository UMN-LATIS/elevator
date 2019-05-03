function LAnnotate (viewer, options,sidecar) {
  this.viewer = viewer;
  this.options = {
    'magnification': options.magnification || null,
    'layerOptions': options.layerOptions,
    'saveURL': options.saveURL || null
    // 'ppm': options.ppm || 468,
  }

  this.sidecar = sidecar || null;

  this.defaultMapJSON = {
    'brightness': 50,
    'contrast': 100,
    'arrows': [],
    'annotations': [],
    'shapes': [],
    'scenes': {}
  };


  this.arrowOptions = {
    distanceUnit: 'km', //yeah yeah, by default, the distance is in KILOMETERS because this is mapping software...
    stretchFactor: 1.11,
    popupContent: function(data) {
      return '<em>' + data.title + '</em>'
    },
    arrowheadLength: 0.6,
    color: 'blue'
  };


  this.layerGroup = L.layerGroup().addTo(this.viewer);
  this.targetColor = null;

  this.panhandler = new Panhandler(this);
  
  this.colorToggle = new ColorToggle(this);
  this.arrowButton = new ArrowButton(this);
  this.locationModal = new LocationModal(this);
  this.brightnessAndContrast = new BrightnessAndContrast(this);
  this.annotations = new Annotations(this);
  this.JsonModal = new JsonModal(this);
  this.toolbar = L.easyBar([this.colorToggle, this.arrowButton, this.locationModal, this.brightnessAndContrast, this.annotations, this.JsonModal]); //, arrowButton, locationModal, brightnessContrastModal, annotationButton, jsonModal
  this.toolbar.addTo(this.viewer) //not layer group, we want this to stay on the map

  this.locationPreview = new LocationPreview(this);

  this.internalJSONrepresentation = jQuery.extend({}, this.defaultMapJSON);

  DrawToolbar(this);
  SceneManager(this);

  if(this.sidecar) {
    this.setJSON(this.sidecar);
  }

}

LAnnotate.prototype.setTargetColor = function(color) {
  this.targetColor = color;
}

LAnnotate.prototype.addToJSON = function(type, data) {
  switch (type) {
    case 'arrow':
    this.internalJSONrepresentation.arrows.push(data)
    break
    case 'brightness':
    this.internalJSONrepresentation.brightness = data
    break
    case 'contrast':
    this.internalJSONrepresentation.contrast = data
    break
    case 'annotation':
    this.internalJSONrepresentation.annotations.push(data)
    break
    case 'shape':
    this.internalJSONrepresentation.shapes.push(data)
    break
  }
};

LAnnotate.prototype.getJSON = function(type) {
  if(type in this.internalJSONrepresentation) {
    return this.internalJSONrepresentation[type];
  }
};


LAnnotate.prototype.loadJSON = function(obj) {
  this.layerGroup.clearLayers() //clear current layer group whenever the json string is changed

  //set brightness and contrast
  document.getElementsByClassName("leaflet-pane")[0].style.filter = "brightness(" + obj.brightness / 50 + ")" + "contrast(" + obj.contrast + "%)"
  obj.arrows.forEach((arrow) => { //load arrows
      var arrowOptions = jQuery.extend({}, this.arrowOptions);
      arrowOptions.color = arrow.color;
      var a = new L.Arrow(arrow, arrowOptions)
      a.addTo(this.layerGroup)
  })

  //load annotations
  obj.annotations.forEach((annotation) => {
      var an = new L.marker([annotation.lat, annotation.lng]).bindPopup(annotation.text)
      an.addTo(this.layerGroup)
      an.openPopup()
  })

  //load shapes
  obj.shapes.forEach((shape) => {
      if (shape.type == 'polygon' || shape.type == 'rectangle') {
          var polygon = new L.polygon(shape.latlngs, {color: shape.color, fillOpacity: 0}).addTo(this.layerGroup)
      } else if (shape.type == 'polyline') {
          var polyline = new L.polyline(shape.latlngs, {color: shape.color}).addTo(this.layerGroup)
      }
  })
}

LAnnotate.prototype.addScene = function(sceneName) {
    var sceneTag = '<br> \
    <input type="radio" class="select_scene_radio" name="scene_radio" value="' + sceneName + '"> \
    <i class="glyphicon glyphicon-chevron-down" scene="' + sceneName + '"></i> \
    ' + sceneName + ' \
    <div id="' + sceneName + '_info"></div> \
    '
    document.getElementById("scene_tags").insertAdjacentHTML('beforeend', sceneTag)
}


LAnnotate.prototype.loadScenes = function(scenes) {
    document.getElementById("scene_tags").innerHTML = '';
    for (var key in scenes) {
        this.addScene(key)
    }
}

// TODO: don't pass scenes around
LAnnotate.prototype.setJSON = function(obj) {
    this.internalJSONrepresentation.brightness = obj.brightness
    this.internalJSONrepresentation.contrast = obj.contrast
    this.internalJSONrepresentation.arrows = obj.arrows
    this.internalJSONrepresentation.annotations = obj.annotations
    this.internalJSONrepresentation.shapes = obj.shapes
    this.internalJSONrepresentation.scenes = obj.scenes
    this.loadJSON(this.internalJSONrepresentation)
    this.loadScenes(this.internalJSONrepresentation.scenes)
}

function LocationPreview(La) {
  this.leafletAnnotate = La;
  var coordinatesDiv = document.createElement("div")
  coordinatesDiv.innerHTML = "<div class='leaflet-control-attribution leaflet-control'><p id='leaflet-coordinates-tag'></p></div>"
  document.getElementsByClassName("leaflet-bottom leaflet-left")[0].appendChild(coordinatesDiv)
  
  $(this.leafletAnnotate.viewer._container).mousemove((e)=> {
    var coords = this.leafletAnnotate.viewer.mouseEventToLatLng(e)
    var x = Math.floor(coords.lng * 1000) //not really x and y coordinates, they're arbitrary and based on rounding the latitude and longitude (because this is mapping software)
    var y = Math.floor(coords.lat * 1000);
    
    currentZoom = Math.floor(this.leafletAnnotate.viewer._zoom);
    var appendString = "";
    if(this.leafletAnnotate.options.magnification) {

      zoomDiff =  (this.leafletAnnotate.options.layerOptions.maxNativeZoom) - currentZoom;
      var zoomLevel = 0;
      if(zoomDiff == 0) {
        zoomLevel = this.leafletAnnotate.options.magnification;
      }
      if(zoomDiff > 0) {
        zoomLevel = this.leafletAnnotate.options.magnification / Math.pow(2, zoomDiff);
      }
      if(zoomDiff < 0) {
        zoomLevel = this.leafletAnnotate.options.magnification * Math.pow(2, Math.abs(zoomDiff));
      }

      nativeString = "";
      if(zoomLevel == this.leafletAnnotate.options.magnification) {
        nativeString = ", native size"
      }

      appendString = " (" + zoomLevel.toFixed(2) + " X" + nativeString +")"
    }
    

    document.getElementById("leaflet-coordinates-tag").innerHTML = "X: " + x + " Y: " + y + " Zoom: " + currentZoom + appendString;
  })

};



function Panhandler(La) {
  this.panHandler = L.Handler.extend({
    panAmount: 25,
    panDirection: 0,
    isPanning: false,

    addHooks: function() {
      L.DomEvent.on(window, 'keydown', this._startPanning, this);
      L.DomEvent.on(window, 'keyup', this._stopPanning, this);
    },

    removeHooks: function() {
      L.DomEvent.off(window, 'keydown', this._startPanning, this);
      L.DomEvent.off(window, 'keyup', this._stopPanning, this);
    },

    _startPanning: function(e) {
      if (e.keyCode == '38') {
        this.panDirection = 'up';
      }
      else if (e.keyCode == '40') {
        this.panDirection = 'down';
      }
      else if (e.keyCode == '37') {
        this.panDirection = 'left';
      }
      else if (e.keyCode == '39') {
        this.panDirection = 'right';
      }
      else {
        this.panDirection = null;
      }

      if(this.panDirection) {
        e.preventDefault();
      }

      if(this.panDirection && !this.isPanning) {
        this.isPanning = true;
        requestAnimationFrame(this._doPan.bind(this));   
      }
      return false;
    },

    _stopPanning: function(ev) {
                // Treat Gamma angle as horizontal pan (1 degree = 1 pixel) and Beta angle as vertical pan
                this.isPanning = false;

              },

              _doPan: function() {

                var panArray = [];
                switch(this.panDirection) {
                  case "up":
                  panArray = [0, -1 * this.panAmount];
                  break;
                  case "down":
                  panArray = [0, this.panAmount];
                  break;
                  case "left":
                  panArray = [-1 * this.panAmount, 0];
                  break;
                  case "right":
                  panArray = [this.panAmount, 0];
                  break;
                }


                map.panBy(panArray, {animate: true, delay: 0});
                if(this.isPanning) {
                  requestAnimationFrame(this._doPan.bind(this));    
                }

              }
            });

  La.viewer.addHandler('pan', this.panHandler);
  La.viewer.pan.enable();
}


function ColorToggle(La) {
  this.leafletAnnotate = La;
  this.colorToggle = L.easyButton ({
    states: [{
      title: 'Blue',
        stateName: 'blue', //current color is blue. By default, the color is blue
        icon: '<i class="glyphicon glyphicon-tint" style="color:blue"></i>',
        onClick: (btn, map) => {
          btn.state('magenta')
          this.leafletAnnotate.setTargetColor('magenta');
          }
        },
        {
          title: 'Magenta',
          stateName: 'magenta',
          icon: '<i class="glyphicon glyphicon-tint" style="color:magenta"></i>',
          onClick: (btn, map) => {
            btn.state('red')
            this.leafletAnnotate.setTargetColor('red');
          }
        },
        {
          title: 'Red',
          stateName: 'red',
          icon: '<i class="glyphicon glyphicon-tint" style="color:red"></i>',
          onClick: (btn, map) => {
            btn.state('yellow')
            this.leafletAnnotate.setTargetColor('yellow');
          }
        },
        {
          title: 'Yellow',
          stateName: 'yellow',
          icon: '<i class="glyphicon glyphicon-tint" style="color:yellow"></i>',
          onClick: function(btn, map) {
            btn.state('green')
            this.leafletAnnotate.setTargetColor('green');
          }
        },
        {
          title: 'Green',
          stateName: 'green',
          icon: '<i class="glyphicon glyphicon-tint" style="color:green"></i>',
          onClick: function(btn, map) {
            btn.state('cyan')
            this.leafletAnnotate.setTargetColor('cyan');
          }
        },
        {
          title: 'Cyan',
          stateName: 'cyan',
          icon: '<i class="glyphicon glyphicon-tint" style="color:cyan"></i>',
          onClick: function(btn, map) {
            btn.state('white')
            this.leafletAnnotate.setTargetColor('white');
          }
        },
        {
          title: 'White',
          stateName: 'white',
          icon: '<i class="glyphicon glyphicon-tint" style="color:grey"></i>',
          onClick: function(btn, map) {
            btn.state('blue')
            this.leafletAnnotate.setTargetColor('blue');
          }
        }]
      })
  this.leafletAnnotate.setTargetColor("blue");
  return this.colorToggle;
}

function ArrowButton(La) {
  //arrows
  this.leafletAnnotate = La;
  this.viewer = La.viewer;
  this.arrowOptions = {};

  this.arrowData = {
    latlng: L.latLng(46.95, 7.4),
    degree: 77,
    distance: 10,
    title: ''
  };

  this.arrowStart = false;

  this.arrow = null;
  this.inflightArrowData = {};

  this.arrowButton = L.easyButton (
  {
    states: 
    [{
      stateName: 'start-arrow',        // name the state
      icon:      '&#10148;',               // and define its properties
      title:     'Create an arrow',      // like its title
      onClick: () => {       // and its callback
        this.arrowButton.state('cancel-arrow')
        $(this.viewer._container).click(this.startArrow);
      }
    },
    {
      stateName: 'cancel-arrow',
      icon:      '&#10006;',
      title:     'Cancel current arrow',
      onClick: () => {
        this.arrowButton.state('start-arrow')
        if (this.arrowStarted) {
          this.arrowStarted = false;
          this.viewer.removeLayer(this.arrow) //get rid of arrow early
        }
        $(this.viewer._container).off('click');
        $(this.viewer._container).off('mousemove', this.viewer._container);
      }
    }]
  });



  this.startArrow = (e) => {
    if (!this.arrowStarted) {
      this.arrowOptions = {};
      this.arrowOptions = jQuery.extend({}, this.leafletAnnotate.arrowOptions); // clone the object so this arrow can keep a reference
      this.arrowOptions.color = this.leafletAnnotate.targetColor;
      this.inflightArrowData = {};
      this.inflightArrowData = jQuery.extend({}, this.arrowData); // clone the object so this arrow can keep a reference
      this.inflightArrowData.latlng = this.viewer.mouseEventToLatLng(e)
      this.arrow = new L.Arrow(this.inflightArrowData, this.arrowOptions) //add arrow head to page
      this.arrow.addTo(this.leafletAnnotate.layerGroup) //add to layerGroup because this is a non-permanent feature
      this.arrowStarted = true
      $(this.viewer._container).mousemove(this.updateArrow)
    }
  }

  this.updateArrow = (e) => {
    if (this.arrowStarted) {
      this.viewer.removeLayer(this.arrow)
      var arrowEnd = this.viewer.mouseEventToLatLng(e)
      this.inflightArrowData.degree = this.degreeBetweenTwoLatLngs(this.inflightArrowData.latlng, arrowEnd) //calculate degree between mouse and arrow head
      this.inflightArrowData.distance = this.viewer.distance(arrowEnd, this.inflightArrowData.latlng) * 100 + .001 //calculate distance between mouse and arrow head
      this.arrow.setData(this.inflightArrowData)
      this.arrow.addTo(this.leafletAnnotate.layerGroup) //place updated arrow back on page
      $(this.viewer._container).click(this.stopArrow)
    }
  }

  this.stopArrow = (e) => {
    if (this.arrowStarted) {
      this.viewer.removeLayer(this.arrow)
      this.arrow.addTo(this.leafletAnnotate.layerGroup)
      this.leafletAnnotate.addToJSON('arrow', {
        leaflet_id: this.arrow._leaflet_id,
        latlng: this.inflightArrowData.latlng,
        degree: this.inflightArrowData.degree,
        distance: this.inflightArrowData.distance,
        color: this.leafletAnnotate.targetColor
      })
      this.arrowButton.state('start-arrow')
      this.arrow = null
      this.inflightArrowData = {};
      this.arrowStarted = false
      $(this.viewer._container).off('click') //we need to get rid of the click event because javascript
      $(this.viewer._container).off('mousemove', this.viewer._container)
    }
  }

  this.degreeBetweenTwoLatLngs = (latlng1, latlng2) => { //calculates degree between the head of the arrow and where your mouse is because this plugin uses polar coordinates
    var dLon = (latlng1.lng - latlng2.lng)

    var y = Math.sin(dLon) * Math.cos(latlng2.lat)
    var x = Math.cos(latlng1.lat) * Math.sin(latlng2.lat) - Math.sin(latlng1.lat)
    * Math.cos(latlng2.lat) * Math.cos(dLon)

    var brng = Math.atan2(y, x)

    brng = (180 / Math.PI) * brng
    brng = (brng + 360) % 360
    brng = 360 - brng // count degrees counter-clockwise - remove to make clockwise

    return brng;
  }


  this.viewer.on('zoomend', () => {
    var currentZoom = this.viewer.getZoom();
    this.leafletAnnotate.layerGroup.eachLayer((layer) => {
      if(currentZoom <= 11) {
        layer.options.arrowheadLength = 0.8;
      }
      else if(currentZoom <= 14) {
        layer.options.arrowheadLength = 0.5;
      }
      else {
        layer.options.arrowheadLength = 0.3;   
      }

      this.viewer.removeLayer(layer);
      this.viewer.addLayer(layer);
    });
  });
  return this.arrowButton;


}


function LocationModal(La) {
  this.leafletAnnotate = La;

  this.locationModal = L.easyButton( '<i title="Location" class="glyphicon glyphicon-globe"></i>', () => {
    this.leafletAnnotate.viewer.fire('modal', {
      content: '<br> \
      <div class=form> \
      <label>X: </label> \
      <input id="x-field" class="form-control mb-2 mr-sm-2 mb-sm-0" type=text> \
      <label>Y: </label> \
      <input id="y-field" class="form-control form-control-small" type=text> \
      <label>Zoom Level: </label> \
      <input id="zoom-field" class="form-control mb-2 mr-sm-2 mb-sm-0" type=number> \
      </div>',
        closeTitle: 'close',                 // alt title of the close button
        zIndex: 10000,                       // needs to stay on top of the things
        transitionDuration: 300,             // expected transition duration

        template: '{content}',               // modal body template, this doesn't include close button and wrappers
        onShow: (evt) => { //show current position of camera over map
          var xField = document.getElementById("x-field")
          var yField = document.getElementById("y-field")
          var zoomField = document.getElementById("zoom-field")
          var currentLatLng = map.getCenter()
          xField.value = Math.floor(currentLatLng.lng * 1000)
          yField.value = Math.floor(currentLatLng.lat * 1000)
          zoomField.value = Math.floor(this.leafletAnnotate.viewer.getZoom())
          var snapToLatLng = () => {
            if (xField.value != "" && yField.value != "" && zoomField.value != "") {
              var latlng = L.latLng((yField.value / 1000), (xField.value / 1000))
              this.leafletAnnotate.viewer.setView(latlng, zoomField.value)
            }
          }
            //attach event handlers to update whenever a field changes
            $(xField).change(function() {
              snapToLatLng()
            })
            $(yField).change(function() {
              snapToLatLng()
            })
            $(zoomField).change(function() {
              snapToLatLng()
            })
          },
          onHide: (evt) => {
            //update view on close (even though it's updated every time a field is changed and this code will theoretically never run)
            var x = document.getElementById("x-field").value
            var y = document.getElementById("y-field").value
            var zoom = document.getElementById("zoom-field").value
            if (x != "" && y != "" && zoom != "") {
              var latlng = L.latLng((y / 1000), (x / 1000))
              this.leafletAnnotate.viewer.setView(latlng, zoom)
            }
          },

        // change at your own risk
        OVERLAY_CLS: 'overlay',              // overlay(backdrop) CSS class
        MODAL_CLS: 'modal',                  // all modal blocks wrapper CSS class
        MODAL_CONTENT_CLS: 'modal-content',  // modal window CSS class
        INNER_CONTENT_CLS: 'modal-inner',    // inner content wrapper
        SHOW_CLS: 'show',                    // `modal open` CSS class, here go your transitions
        CLOSE_CLS: 'close'                   // `x` button CSS class
      })
  })

  return this.locationModal;

}


function BrightnessAndContrast(La) {
  this.leafletAnnotate = La;
  this.brightnessContrastModal = L.easyButton( '<i title="Brightness and Contrast" class="glyphicon glyphicon-adjust"></i>', () => {
    this.leafletAnnotate.viewer.fire('modal', {
      content: '<br> \
      <label style="text-align:center;display:block;">Brightness</label> \
      <input id="brightness-slider" type=range> \
      <label style="text-align:center;display:block;">Contrast</label> \
                  <input id="contrast-slider" type=range min=50 max=150>',        // HTML string

          closeTitle: 'close',                 // alt title of the close button
          zIndex: 10000,                       // needs to stay on top of the things
          transitionDuration: 300,             // expected transition duration

          template: '{content}',               // modal body template, this doesn't include close button and wrappers
          onShow: (evt) => {
            var brightnessSlider = document.getElementById("brightness-slider")
            brightnessSlider.value = this.leafletAnnotate.getJSON('brightness')
            var contrastSlider = document.getElementById("contrast-slider")
            contrastSlider.value = this.leafletAnnotate.getJSON('contrast')
            $(contrastSlider).change(() => {
              document.getElementsByClassName("leaflet-pane")[0].style.filter = "contrast(" + contrastSlider.value + "%) brightness(" + brightnessSlider.value / 50 + ")";
              this.leafletAnnotate.addToJSON('contrast', contrastSlider.value)
            })
            $(brightnessSlider).change(() => {
              document.getElementsByClassName("leaflet-pane")[0].style.filter = "contrast(" + contrastSlider.value + "%) brightness(" + brightnessSlider.value / 50 + ")";
              this.leafletAnnotate.addToJSON('brightness', brightnessSlider.value)
            })
          },
          onHide: (evt) =>{
            var brightnessValue = document.getElementById("brightness-slider").value
            var contrastValue = document.getElementById("contrast-slider").value
            document.getElementsByClassName("leaflet-pane")[0].style.filter  = "brightness(" + brightnessValue / 50 + ")" + "contrast(" + contrastValue + "%)"
            this.leafletAnnotate.addToJSON('brightness', brightnessValue)
            this.leafletAnnotate.addToJSON('contrast', contrastValue)
          },

          // change at your own risk
          OVERLAY_CLS: 'overlay',              // overlay(backdrop) CSS class
          MODAL_CLS: 'modal',                  // all modal blocks wrapper CSS class
          MODAL_CONTENT_CLS: 'modal-content',  // modal window CSS class
          INNER_CONTENT_CLS: 'modal-inner',    // inner content wrapper
          SHOW_CLS: 'show',                    // `modal open` CSS class, here go your transitions
          CLOSE_CLS: 'close'                   // `x` button CSS class
        })
  });
  return this.brightnessContrastModal;
}

/**
 * Annotations
 */

function Annotations(La) {
  this.leafletAnnotate = La;
  this.annotationMarker = null
  this.annotationStarted = false
  this.annotationMarkerField = null;
  this.annotationButton = L.easyButton({
    states: [
    {
        stateName: 'create-annotation',
        icon: '<i class="glyphicon glyphicon-comment"></i>',
        title: 'Place an annotation marker',
        onClick: (btn, map) => {
            this.annotationStarted = true
            btn.state('cancel-annotation');
            $(this.leafletAnnotate.viewer._container).click(this.placeMarker);
        }
    },
    {
        stateName: 'cancel-annotation',
        icon: '&#10006;',
        title: 'Cancel current annotation',
        onClick: (btn, map) => {
            this.btn.state('create-annotation')
            this.notationStarted = false
            $(this.leafletAnnotate.viewer._container).off('click')

            if (this.hannotationMarker != null) {
                this.leafletAnnotate.layerGroup.removeLayer(this.annotationMarker)
            }
        }
    }
    ]
  });

  this.placeMarker = (e) => {
    if (this.annotationStarted) {
      this.annotationStarted = false
      var latlng = this.leafletAnnotate.viewer.mouseEventToLatLng(e)
      this.annotationMarker = L.marker([latlng.lat, latlng.lng]).addTo(this.leafletAnnotate.layerGroup).bindPopup("<input type='text' id='annotationMarkerField'/>").openPopup() //place annotation marker with an input field as the text
      this.annotationMarkerField = document.getElementById("annotationMarkerField")
      $(annotationMarkerField).change(this.placeText);
    }
  }

  this.placeText = () => {
    var latLng = this.annotationMarker.getLatLng();
    this.leafletAnnotate.layerGroup.removeLayer(this.annotationMarker)
    this.annotationMarker = L.marker(latLng).addTo(this.leafletAnnotate.layerGroup).bindPopup(this.annotationMarkerField.value).openPopup()
    this.leafletAnnotate.addToJSON('annotation', {
        leaflet_id: this.annotationMarker._leaflet_id,
        lat: latLng.lat,
        lng: latLng.lng,
        text: this.annotationMarkerField.value
    })
    this.annotationMarker = null
    this.annotationButton.state('create-annotation')
  };

  return this.annotationButton;
}

function JsonModal(La) {
  this.leafletAnnotate = La;
  this.jsonModal = L.easyButton( '<i title="Upload/Download" class="glyphicon glyphicon-folder-open"></i>', () => {
    this.leafletAnnotate.viewer.fire('modal', {

        content: '<br> \
        <a id="download_button"><button class="btn btn-primary btn-md btn-block fileOptionButton">Download Scenes</button></a> \
        <a id="save_button"><button class="btn btn-info saveToServer btn-md btn-block fileOptionButton">Save Scenes to Server</button></a> \
        <label for="json_file" class="btn btn-secondary btn-md btn-block fileOptionButton"> Upload Scenes \
        <input type="file" id="json_file" style="display:none;"> \
        </label>',
        closeTitle: 'close',                 // alt title of the close button
        zIndex: 10000,                       // needs to stay on top of the things
        transitionDuration: 300,             // expected transition duration

        template: '{content}',               // modal body template, this doesn't include close button and wrappers

        onShow: (evt) => {
            //handle download of json
            $("#download_button").click(() => { //handle download of json string
                var convertedData = 'text/json;charset=utf-8,' + encodeURIComponent(JSON.stringify(this.leafletAnnotate.internalJSONrepresentation))

                // Create export
                document.getElementById('download_button').setAttribute('href', 'data:' + convertedData)
                document.getElementById('download_button').setAttribute('download', 'data.json') //data.json is the filetype
            })



            if("saveURL" in this.leafletAnnotate.options) {
              $("#save_button").click(() => {
                var convertedData = JSON.stringify(this.leafletAnnotate.internalJSONrepresentation);
                $.post(this.leafletAnnotate.options.saveURL, {sidecarContent: JSON.stringify(convertedData)})
                    .done((msg) => {
                        this.leafletAnnotate.viewer.closeModal();

                    })
                    .fail((xhr, status, error) => {
                        alert('Error: failed to save changes');
                    });
              });
            }
            else {
              $("#save_button").css("display", "none");
            }
            

            //handle upload of json string
            $("#json_file").change(() => {
                var files = document.getElementById('json_file').files

                if (files.length <= 0) {
                    return false
                }

                var fr = new FileReader()

                //load json from file
                fr.onload = (e) => {
                    var result = JSON.parse(e.target.result)
                    this.leafletAnnotate.setJSON(result)
                }

                fr.readAsText(files.item(0))
            })
        },
        // change at your own risk
        OVERLAY_CLS: 'overlay',              // overlay(backdrop) CSS class
        MODAL_CLS: 'modal',                  // all modal blocks wrapper CSS class
        MODAL_CONTENT_CLS: 'modal-content',  // modal window CSS class
        INNER_CONTENT_CLS: 'modal-inner',    // inner content wrapper
        SHOW_CLS: 'show',                    // `modal open` CSS class, here go your transitions
        CLOSE_CLS: 'close'                   // `x` button CSS class
    })
  })

  return this.jsonModal;
}

function DrawToolbar(La) {
  this.leafletAnnotate = La;
  var drawToolbar = document.getElementsByClassName("leaflet-draw-toolbar leaflet-bar leaflet-draw-toolbar-top")[0]
  drawToolbar.removeChild(drawToolbar.childNodes[drawToolbar.childNodes.length - 2]) //removes circle from drawing toolbar
  drawToolbar.removeChild(drawToolbar.childNodes[drawToolbar.childNodes.length - 1]) //removes marker from drawing toolbar

  this.leafletAnnotate.viewer.on(L.Draw.Event.CREATED, (e) => {
    var type  = e.layerType,
    shape = e.layer,
    color = this.leafletAnnotate.targetColor //color is set to map layer option that wasn't there originally
    shape.options.color = color //set paintbrush color to the color specified by lineColor
    shape.options.fillOpacity = 0 //make inside opaque
    shape.options.opacity = .5

    console.log(shape);
    console.log(this.leafletAnnotate.layerGroup);
    this.leafletAnnotate.layerGroup.addLayer(shape) //add shape to layer

    if (type == 'polygon' || type == 'rectangle') {
        this.leafletAnnotate.addToJSON('shape', {
            leaflet_id: shape._leaflet_id,
            type: type,
            latlngs: shape._latlngs[0],
            color: color
        })
    } else if (type == 'polyline') {
        this.leafletAnnotate.addToJSON('shape', {
            leaflet_id: shape._leaflet_id,
            type: type,
            latlngs: shape._latlngs,
            color: color
        })
    }
  })
}

function SceneManager(La) {
  this.leafletAnnotate = La;
  $(document).on("change", "#scene_tags input:radio", (e) => {
    //set current scene informations
    var sceneName = e.target.value
    var selectedScene = this.leafletAnnotate.internalJSONrepresentation.scenes[sceneName]
    document.getElementById("scene_name_input").value = sceneName

    selectedSceneCopy = jQuery.extend({}, selectedScene);
    this.leafletAnnotate.internalJSONrepresentation.brightness = selectedSceneCopy.brightness
    this.leafletAnnotate.internalJSONrepresentation.contrast = selectedSceneCopy.contrast
    this.leafletAnnotate.internalJSONrepresentation.arrows = $.extend(true, [], selectedSceneCopy.arrows);
    this.leafletAnnotate.internalJSONrepresentation.annotations =$.extend(true, [], selectedSceneCopy.annotations);
    this.leafletAnnotate.internalJSONrepresentation.shapes = $.extend(true, [], selectedSceneCopy.shapes);

    this.leafletAnnotate.loadJSON(this.leafletAnnotate.internalJSONrepresentation);
  });

  $(document).on("click", ".glyphicon-chevron-down", (e) => {
    //var state = $(this).attr("state")
    var sceneName = $(e.target).attr("scene")
    var sceneDiv = document.getElementById(sceneName + "_info")
    sceneDiv.className = "panel panel-default"

    var sceneInfo = document.createElement("div")
    sceneInfo.className = "panel-body"

    $(e.target).attr("class", "glyphicon glyphicon-chevron-up") //replace with an up arrow
    //$(this).attr("state", "show")
    if (sceneName == "current_scene") {
        var scene = this.leafletAnnotate.internalJSONrepresentation;
    } else {
        var scene = this.leafletAnnotate.internalJSONrepresentation.scenes[sceneName] //scene now points to an object inside of scenes
    }

    var brightnessInfo = "brightness: " + scene.brightness + "<br>"
    sceneInfo.insertAdjacentHTML('beforeend', brightnessInfo)

    var contrastInfo = "contrast: " + scene.contrast + "<br>"
    sceneInfo.insertAdjacentHTML('beforeend', contrastInfo)

    scene.arrows.forEach(function(arrow) {
        var arrowHtml = '<i class="glyphicon glyphicon-remove" scene="' + sceneName + '" leaflet_id="' + arrow.leaflet_id + '" type="arrow"></i> ' + arrow.color + ' arrow <br>'
        sceneInfo.insertAdjacentHTML('beforeend', arrowHtml)
    })

    scene.annotations.forEach(function(annotation) {
        var annotationHtml = '<i class="glyphicon glyphicon-remove" scene="' + sceneName + '" leaflet_id="' + annotation.leaflet_id + '" type="annotation"></i> ' + annotation.text + '<br>'
        sceneInfo.insertAdjacentHTML('beforeend', annotationHtml)
    })

    scene.shapes.forEach(function(shape) {
        var annotationHtml = '<i class="glyphicon glyphicon-remove" scene="' + sceneName + '" leaflet_id="' + shape.leaflet_id + '" type="shape"></i> ' + shape.color + ' ' + shape.type + '<br>'
        sceneInfo.insertAdjacentHTML('beforeend', annotationHtml)
    })

    sceneDiv.appendChild(sceneInfo)
  })

  $(document).on("click", ".glyphicon-chevron-up", (e) => {
    e.target.className = "glyphicon glyphicon-chevron-down"
    e.target.setAttribute("state", "hide")
    var sceneName = $(e.target).attr("scene")
    var sceneDiv = document.getElementById(sceneName + "_info")
    sceneDiv.innerHTML = "" //clear html string in scene's info div
    sceneDiv.className = "" //clear box
  })

  this.redrawContents = (sceneName) => {
    var sceneDiv = document.getElementById(sceneName + "_info")
    sceneDiv.innerHTML = "" //clear html string in scene's info div
    sceneDiv.className = "" //clear box
    sceneDiv.className = "panel panel-default"

    var sceneInfo = document.createElement("div")
    sceneInfo.className = "panel-body"

    // TODO
    // $(this).attr("class", "glyphicon glyphicon-chevron-up") //replace with an up arrow
    //$(this).attr("state", "show")
    if (sceneName == "current_scene") {
        var scene = this.leafletAnnotate.internalJSONrepresentation
    } else {
        var scene = this.leafletAnnotate.internalJSONrepresentation.scenes[sceneName] //scene now points to an object inside of scenes
    }

    var brightnessInfo = "brightness: " + scene.brightness + "<br>"
    sceneInfo.insertAdjacentHTML('beforeend', brightnessInfo)

    var contrastInfo = "contrast: " + scene.contrast + "<br>"
    sceneInfo.insertAdjacentHTML('beforeend', contrastInfo)

    scene.arrows.forEach(function(arrow) {
        var arrowHtml = '<i class="glyphicon glyphicon-remove" scene="' + sceneName + '" leaflet_id="' + arrow.leaflet_id + '" type="arrow"></i> ' + arrow.color + ' arrow <br>'
        sceneInfo.insertAdjacentHTML('beforeend', arrowHtml)
    })

    scene.annotations.forEach(function(annotation) {
        var annotationHtml = '<i class="glyphicon glyphicon-remove" scene="' + sceneName + '" leaflet_id="' + annotation.leaflet_id + '" type="annotation"></i> ' + annotation.text + '<br>'
        sceneInfo.insertAdjacentHTML('beforeend', annotationHtml)
    })

    scene.shapes.forEach(function(shape) {
        var annotationHtml = '<i class="glyphicon glyphicon-remove" scene="' + sceneName + '" leaflet_id="' + shape.leaflet_id + '" type="shape"></i> ' + shape.color + ' ' + shape.type + '<br>'
        sceneInfo.insertAdjacentHTML('beforeend', annotationHtml)
    })

    sceneDiv.appendChild(sceneInfo)

  };


  $(document).on("click", ".glyphicon-remove", (e) => {
    //First, find the scene to delete from
    var sceneName = $(e.target).attr("scene")

    if (sceneName == "current_scene") {
        var scene = this.leafletAnnotate.internalJSONrepresentation
    } else {
        var scene = this.leafletAnnotate.internalJSONrepresentation.scenes[sceneName]
    }

    //next, find the element to delete in the scene
    var type = $(e.target).attr("type")

    switch (type) {
        case "arrow":
        var arr = scene.arrows
        break
        case "annotation":
        var arr = scene.annotations
        break
        case "shape":
        var arr = scene.shapes
        break
    }

    var leaflet_id = $(e.target).attr("leaflet_id")

    arr.forEach((element, i) => {
        if (element.leaflet_id == leaflet_id) {

            arr.splice(i, 1) //remove element from array
            this.leafletAnnotate.setJSON(this.leafletAnnotate.internalJSONrepresentation); //reload json
            $('input[name=scene_radio][value=' + sceneName + ']').prop('checked',true);
            $('input[name=scene_radio][value=' + sceneName + ']').trigger('change');

            return
        }
    })

    
  })


  $("#clear_current_scene_button").click(() => {

    this.leafletAnnotate.internalJSONrepresentation.brightness = 50;
    this.leafletAnnotate.internalJSONrepresentation.contract = 100;
    this.leafletAnnotate.internalJSONrepresentation.arrows = [];
    this.leafletAnnotate.internalJSONrepresentation.annotations = [];
    this.leafletAnnotate.internalJSONrepresentation.shapes = [];

    var sceneInput = document.getElementById("scene_name_input")
    var sceneName = sceneInput.value
    sceneName = sceneName.split(" ").join("_")
    if(sceneName.length > 0) {
        delete this.leafletAnnotate.internalJSONrepresentation.scenes[sceneName];    
    }
    
    this.leafletAnnotate.setJSON(this.leafletAnnotate.internalJSONrepresentation);
  })

  $("#add_scene_button").click(() => {
    var sceneInput = document.getElementById("scene_name_input")
    var sceneName = sceneInput.value
    sceneName = sceneName.split(" ").join("_")

    if (!(sceneName in this.leafletAnnotate.internalJSONrepresentation.scenes)) {
        this.leafletAnnotate.addScene(sceneName);
    }
    console.log(this.leafletAnnotate.internalJSONrepresentation);
    this.leafletAnnotate.internalJSONrepresentation.scenes[sceneName] = {
        'brightness': this.leafletAnnotate.internalJSONrepresentation.brightness,
        'contrast': this.leafletAnnotate.internalJSONrepresentation.contrast,
        'arrows': $.extend(true, [], this.leafletAnnotate.internalJSONrepresentation.arrows),
        'annotations': $.extend(true, [], this.leafletAnnotate.internalJSONrepresentation.annotations),
        'shapes': $.extend(true, [], this.leafletAnnotate.internalJSONrepresentation.shapes),
    }

    console.log(this.leafletAnnotate.internalJSONrepresentation);
    $('input[name=scene_radio][value=' + sceneName + ']').prop('checked',true);
    $('input[name=scene_radio][value=' + sceneName + ']').trigger('change');
    console.log($("i[scene=" + sceneName + "]"));
    if($("i[scene=" + sceneName + "]").hasClass("glyphicon-chevron-up")) {
        this.redrawContents(sceneName);
    }
    
    console.log(this.leafletAnnotate.internalJSONrepresentation)
    // sceneInput.value = ""
  })
}