var layerGroup;
var loadAnnotation = function() {

    layerGroup = L.layerGroup().addTo(map); //add elements to layergroup that runs on top of map instead of the map itself. This way, we can clear the layer group in one go



    // custom arrow key scrolling for smoother interaction.
    // 
    L.PanHandler = L.Handler.extend({
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
        }
    );

    map.addHandler('pan', L.PanHandler);
    map.pan.enable();




    var mapJson = { //map elements that will belong to the layergroup
        'brightness': 50,
        'contrast': 100,
        'arrows': [],
        'annotations': [],
        'shapes': [],
        'scenes': {}
    }
    var colorToggle = L.easyButton ({
        states: [{
            title: 'Blue',
        stateName: 'blue', //current color is blue. By default, the color is blue
        icon: '<i class="glyphicon glyphicon-tint" style="color:blue"></i>',
        onClick: function(btn, map) {
            btn.state('magenta')
            arrowOptions.color = 'magenta' //all subsequent arrows will be magenta when clicked
            layer.options.lineColor = 'magenta' //set layer class option lineColor to magenta (not originally a parameter of layer)
        }
    },
    {
        title: 'Magenta',
        stateName: 'magenta',
        icon: '<i class="glyphicon glyphicon-tint" style="color:magenta"></i>',
        onClick: function(btn, map) {
            btn.state('red')
            arrowOptions.color = 'red'
            layer.options.lineColor = 'red'
        }
    },
    {
        title: 'Red',
        stateName: 'red',
        icon: '<i class="glyphicon glyphicon-tint" style="color:red"></i>',
        onClick: function(btn, map) {
            btn.state('yellow')
            arrowOptions.color = 'yellow'
            layer.options.lineColor = 'yellow'
        }
    },
    {
        title: 'Yellow',
        stateName: 'yellow',
        icon: '<i class="glyphicon glyphicon-tint" style="color:yellow"></i>',
        onClick: function(btn, map) {
            btn.state('green')
            arrowOptions.color = 'green'
            layer.options.lineColor = 'green'
        }
    },
    {
        title: 'Green',
        stateName: 'green',
        icon: '<i class="glyphicon glyphicon-tint" style="color:green"></i>',
        onClick: function(btn, map) {
            btn.state('cyan')
            arrowOptions.color = 'cyan'
            layer.options.lineColor = 'cyan'
        }
    },
    {
        title: 'Cyan',
        stateName: 'cyan',
        icon: '<i class="glyphicon glyphicon-tint" style="color:cyan"></i>',
        onClick: function(btn, map) {
            btn.state('white')
            arrowOptions.color = 'white'
            layer.options.lineColor = 'white'
        }
    },
    {
        title: 'White',
        stateName: 'white',
        icon: '<i class="glyphicon glyphicon-tint" style="color:grey"></i>',
        onClick: function(btn, map) {
            btn.state('blue')
            arrowOptions.color = 'blue'
            layer.options.lineColor = 'blue'
        }
    }]
})

//arrows
var arrowOptions = {
    distanceUnit: 'km', //yeah yeah, by default, the distance is in KILOMETERS because this is mapping software...
    stretchFactor: 1.11,
    popupContent: function(data) {
        return '<em>' + data.title + '</em>'
    },
    arrowheadLength: 0.2,
    color: 'blue'
}

var arrowData = {
    latlng: L.latLng(46.95, 7.4),
    degree: 77,
    distance: 10,
    title: 'Demo'
}

function degreeBetweenTwoLatLngs (latlng1, latlng2) { //calculates degree between the head of the arrow and where your mouse is because this plugin uses polar coordinates
    var dLon = (latlng1.lng - latlng2.lng)

    var y = Math.sin(dLon) * Math.cos(latlng2.lat)
    var x = Math.cos(latlng1.lat) * Math.sin(latlng2.lat) - Math.sin(latlng1.lat)
    * Math.cos(latlng2.lat) * Math.cos(dLon)

    var brng = Math.atan2(y, x)

    brng = (180 / Math.PI) * brng
    brng = (brng + 360) % 360
    brng = 360 - brng // count degrees counter-clockwise - remove to make clockwise

    return brng
}

var arrow
var arrowStarted = false

//arrow button (arrow icon)
var arrowButton = L.easyButton ({
    states: [{
        stateName: 'start-arrow',        // name the state
        icon:      '&#10148;',               // and define its properties
        title:     'Create an arrow',      // like its title
        onClick: function(btn, map) {       // and its callback
            btn.state('cancel-arrow')
            $(map._container).click(function startArrow(e) {
                if (!arrowStarted) {
                    arrowData.latlng = map.mouseEventToLatLng(e)
                    arrow = new L.Arrow(arrowData, arrowOptions) //add arrow head to page
                    arrow.addTo(layerGroup) //add to layerGroup because this is a non-permanent feature
                    arrowStarted = true
                    $(map._container).mousemove(function updateArrow(e) {
                        if (arrowStarted) {
                            map.removeLayer(arrow)
                            var arrowEnd = map.mouseEventToLatLng(e)
                            arrowData.degree = degreeBetweenTwoLatLngs(arrowData.latlng, arrowEnd) //calculate degree between mouse and arrow head
                            arrowData.distance = map.distance(arrowEnd, arrowData.latlng) * 100 + .001 //calculate distance between mouse and arrow head
                            arrow.setData(arrowData)
                            arrow.addTo(layerGroup) //place updated arrow back on page
                            $(map._container).click(function stopArrow(e) {
                                if (arrowStarted) {
                                    map.removeLayer(arrow)
                                    arrow.addTo(layerGroup)
                                    addToJson('arrow', {
                                        leaflet_id: arrow._leaflet_id,
                                        latlng: arrowData.latlng,
                                        degree: arrowData.degree,
                                        distance: arrowData.distance,
                                        color: arrowOptions.color
                                    })
                                    btn.state('start-arrow')
                                    arrow = null
                                    arrowStarted = false
                                    $(map._container).off('click') //we need to get rid of the click event because javascript
                                    $(map._container).off('mousemove', map._container)
                                }
                            })
                        }
                    })
                }
            })
        }
    }, {
        stateName: 'cancel-arrow',
        icon:      '&#10006;',
        title:     'Cancel current arrow',
        onClick: function(btn, map) {
            btn.state('start-arrow')
            if (arrowStarted) {
                arrowStarted = false
                map.removeLayer(arrow) //get rid of arrow early
            }
            $(map._container).off('click')
            $(map._container).off('mousemove', map._container)
        }
    }]
})

//snap to location

//coordinate information in bottom left hand side of map
var coordinatesDiv = document.createElement("div")
coordinatesDiv.innerHTML = "<div class='leaflet-control-attribution leaflet-control'><p id='leaflet-coordinates-tag'></p></div>"
document.getElementsByClassName("leaflet-bottom leaflet-left")[0].appendChild(coordinatesDiv)

$(map._container).mousemove(function showCoordsAndZoom(event) {
    var coords = map.mouseEventToLatLng(event)
    var x = Math.floor(coords.lng * 1000) //not really x and y coordinates, they're arbitrary and based on rounding the latitude and longitude (because this is mapping software)
    var y = Math.floor(coords.lat * 1000)
    document.getElementById("leaflet-coordinates-tag").innerHTML = "X: " + x + " Y: " + y + " Zoom: " + Math.floor(map._zoom)
})

//location button (globe icon)
var locationModal = L.easyButton( '<i title="Location" class="glyphicon glyphicon-globe"></i>', function() {
    map.fire('modal', {

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
        onShow: function(evt) { //show current position of camera over map
            var xField = document.getElementById("x-field")
            var yField = document.getElementById("y-field")
            var zoomField = document.getElementById("zoom-field")
            var currentLatLng = map.getCenter()
            xField.value = Math.floor(currentLatLng.lng * 1000)
            yField.value = Math.floor(currentLatLng.lat * 1000)
            zoomField.value = Math.floor(map.getZoom())
            var snapToLatLng = function() {
                if (xField.value != "" && yField.value != "" && zoomField.value != "") {
                    var latlng = L.latLng((yField.value / 1000), (xField.value / 1000))
                    map.setView(latlng, zoomField.value)
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
        onHide: function(evt){
            //update view on close (even though it's updated every time a field is changed and this code will theoretically never run)
            var x = document.getElementById("x-field").value
            var y = document.getElementById("y-field").value
            var zoom = document.getElementById("zoom-field").value
            if (x != "" && y != "" && zoom != "") {
                var latlng = L.latLng((y / 1000), (x / 1000))
                map.setView(latlng, zoom)
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

//brightness and contrast
var brightnessContrastModal = L.easyButton( '<i title="Brightness and Contrast" class="glyphicon glyphicon-adjust"></i>', function() {
    map.fire('modal', {
        content: '<br> \
        <label style="text-align:center;display:block;">Brightness</label> \
        <input id="brightness-slider" type=range> \
        <label style="text-align:center;display:block;">Contrast</label> \
                <input id="contrast-slider" type=range min=50 max=150>',        // HTML string

        closeTitle: 'close',                 // alt title of the close button
        zIndex: 10000,                       // needs to stay on top of the things
        transitionDuration: 300,             // expected transition duration

        template: '{content}',               // modal body template, this doesn't include close button and wrappers
        onShow: function(evt){
            var brightnessSlider = document.getElementById("brightness-slider")
            brightnessSlider.value = getJson('brightness')
            var contrastSlider = document.getElementById("contrast-slider")
            contrastSlider.value = getJson('contrast')
            $(contrastSlider).change(function changeBrightness () {
                document.getElementsByClassName("leaflet-pane")[0].style.filter = "contrast(" + contrastSlider.value + "%)"
                addToJson('contrast', contrastSlider.value)
            })
            $(brightnessSlider).change(function changeContrast() {
                document.getElementsByClassName("leaflet-pane")[0].style.filter = "brightness(" + brightnessSlider.value / 50 + ")"
                addToJson('brightness', brightnessSlider.value)
            })
        },
        onHide: function(evt){
            var brightnessValue = document.getElementById("brightness-slider").value
            var contrastValue = document.getElementById("contrast-slider").value
            document.getElementsByClassName("leaflet-pane")[0].style.filter  = "brightness(" + brightnessValue / 50 + ")" + "contrast(" + contrastValue + "%)"
            addToJson('brightness', brightnessValue)
            addToJson('contrast', contrastValue)
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

//annotations
var annotationMarker = null
var annotationStarted = false

var annotationButton = L.easyButton({
    states: [
    {
        stateName: 'create-annotation',
        icon: '<i class="glyphicon glyphicon-comment"></i>',
        title: 'Place an annotation marker',
        onClick: function(btn, map) {
            annotationStarted = true
            btn.state('cancel-annotation')
            $(map._container).click(function placeMarker (e) {
                if (annotationStarted) {
                    annotationStarted = false
                    var latlng = map.mouseEventToLatLng(e)
                        annotationMarker = L.marker([latlng.lat, latlng.lng]).addTo(layerGroup).bindPopup("<input type='text' id='annotationMarkerField'/>").openPopup() //place annotation marker with an input field as the text
                        var annotationMarkerField = document.getElementById("annotationMarkerField")
                        $(annotationMarkerField).change(function placeText () { //whenever that HTML input changes, add the text
                            layerGroup.removeLayer(annotationMarker)
                            annotationMarker = L.marker([latlng.lat, latlng.lng]).addTo(layerGroup).bindPopup(annotationMarkerField.value).openPopup()
                            addToJson('annotation', {
                                leaflet_id: annotationMarker._leaflet_id,
                                lat: latlng.lat,
                                lng: latlng.lng,
                                text: annotationMarkerField.value
                            })
                            annotationMarker = null
                            btn.state('create-annotation')
                        })
                    }
                })
        }
    },
    {
        stateName: 'cancel-annotation',
        icon: '&#10006;',
        title: 'Cancel current annotation',
        onClick: function(btn, map) {
            btn.state('create-annotation')
            annotationStarted = false
            $(map._container).off('click')

            if (annotationMarker != null) {
                layerGroup.removeLayer(annotationMarker)
            }
        }
    }
    ]
})

//data about layers
function applyJsonData (obj) {
    layerGroup.clearLayers() //clear current layer group whenever the json string is changed

    //set brightness and contrast
    document.getElementsByClassName("leaflet-pane")[0].style.filter = "brightness(" + obj.brightness / 50 + ")" + "contrast(" + obj.contrast + "%)"
    obj.arrows.forEach(function(arrow) { //load arrows
        arrowOptions.color = arrow.color
        var a = new L.Arrow(arrow, arrowOptions)
        a.addTo(layerGroup)
    })

    //load annotations
    obj.annotations.forEach(function(annotation) {
        var an = new L.marker([annotation.lat, annotation.lng]).bindPopup(annotation.text)
        an.addTo(layerGroup)
        an.openPopup()
    })

    //load shapes
    obj.shapes.forEach(function(shape) {
        if (shape.type == 'polygon' || shape.type == 'rectangle') {
            var polygon = new L.polygon(shape.latlngs, {color: shape.color, fillOpacity: 0}).addTo(layerGroup)
        } else if (shape.type == 'polyline') {
            var polyline = new L.polyline(shape.latlngs, {color: shape.color}).addTo(layerGroup)
        }
    })
}

function appendSceneTag (sceneName) {
    var sceneTag = '<br> \
    <input type="radio" class="select_scene_radio" name="scene_radio" value="' + sceneName + '"> \
    <i class="glyphicon glyphicon-chevron-down" scene="' + sceneName + '"></i> \
    ' + sceneName + ' \
    <div id="' + sceneName + '_info"></div> \
    '
    document.getElementById("scene_tags").insertAdjacentHTML('beforeend', sceneTag)
}

function loadScenes (scenes) {
    //load scenes at the bottom of the page
    document.getElementById("scene_tags").innerHTML = '';
//       document.getElementById("scene_tags").innerHTML = ' \
    // <i class="glyphicon glyphicon-chevron-down" scene="current_scene" state="hide"></i> Current Scene \
    // <div id="current_scene_info"></div> \
    // '
    for (var key in scenes) {
        appendSceneTag(key)
    }
}

function setJsonData (obj) { //set json data whenever the JSON string is changed
    mapJson.brightness = obj.brightness
    mapJson.contrast = obj.contrast
    mapJson.arrows = obj.arrows
    mapJson.annotations = obj.annotations
    mapJson.shapes = obj.shapes
    mapJson.scenes = obj.scenes
    applyJsonData(mapJson)
    loadScenes(mapJson.scenes)
}

function addToJson (type, data) {
    switch (type) {
        case 'arrow':
        mapJson.arrows.push(data)
        break
        case 'brightness':
        mapJson.brightness = data
        break
        case 'contrast':
        mapJson.contrast = data
        break
        case 'annotation':
        mapJson.annotations.push(data)
        break
        case 'shape':
        mapJson.shapes.push(data)
        break
    }
}

function getJson (type) {
    switch (type) {
        case 'brightness':
        return mapJson.brightness
        case 'contrast':
        return mapJson.contrast
    }
}

var jsonModal = L.easyButton( '<i title="Upload/Download" class="glyphicon glyphicon-folder-open"></i>', function() {
    map.fire('modal', {

        content: '<br> \
        <a id="download_button"><button class="btn btn-primary btn-md btn-block">Download Scenes</button></a> \
        <label for="json_file" class="btn btn-secondary btn-md btn-block"> Upload Scenes \
        <input type="file" id="json_file" style="display:none;"> \
        </label>',
        closeTitle: 'close',                 // alt title of the close button
        zIndex: 10000,                       // needs to stay on top of the things
        transitionDuration: 300,             // expected transition duration

        template: '{content}',               // modal body template, this doesn't include close button and wrappers

        onShow: function(evt){
            //handle download of json
            $("#download_button").click(function() { //handle download of json string
                var convertedData = 'text/json;charset=utf-8,' + encodeURIComponent(JSON.stringify(mapJson))

                // Create export
                document.getElementById('download_button').setAttribute('href', 'data:' + convertedData)
                document.getElementById('download_button').setAttribute('download', 'data.json') //data.json is the filetype
            })
            //handle upload of json string
            $("#json_file").change(function() {
                var files = document.getElementById('json_file').files

                if (files.length <= 0) {
                    return false
                }

                var fr = new FileReader()

                //load json from file
                fr.onload = function(e) {
                    var result = JSON.parse(e.target.result)
                    var formatted = JSON.stringify(result, null, 2)
                    setJsonData(result)
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

//bring all easy button components into editing toolbar
var toolbar = L.easyBar([colorToggle, arrowButton, locationModal, brightnessContrastModal, annotationButton, jsonModal])
toolbar.addTo(map) //not layer group, we want this to stay on the map

//Draw

//edit the drawing toolbar
var drawToolbar = document.getElementsByClassName("leaflet-draw-toolbar leaflet-bar leaflet-draw-toolbar-top")[0]
drawToolbar.removeChild(drawToolbar.childNodes[drawToolbar.childNodes.length - 2]) //removes circle from drawing toolbar
drawToolbar.removeChild(drawToolbar.childNodes[drawToolbar.childNodes.length - 1]) //removes marker from drawing toolbar

map.on(L.Draw.Event.CREATED, function (e) {
    var type  = e.layerType,
    shape = e.layer,
    color = layer.options.lineColor //color is set to map layer option that wasn't there originally
    shape.options.color = color //set paintbrush color to the color specified by lineColor
    shape.options.fillOpacity = 0 //make inside opaque
    shape.options.opacity = .5

    console.log(shape);
    console.log(layerGroup);
    layerGroup.addLayer(shape) //add shape to layer

    if (type == 'polygon' || type == 'rectangle') {
        addToJson('shape', {
            leaflet_id: shape._leaflet_id,
            type: type,
            latlngs: shape._latlngs[0],
            color: color
        })
    } else if (type == 'polyline') {
        addToJson('shape', {
            leaflet_id: shape._leaflet_id,
            type: type,
            latlngs: shape._latlngs,
            color: color
        })
    }
})


$(document).on("change", "#scene_tags input:radio", function () {
    //set current scene informations
    var sceneName = this.value
    var selectedScene = mapJson.scenes[sceneName]
    document.getElementById("scene_name_input").value = sceneName

    mapJson.brightness = selectedScene.brightness
    mapJson.contrast = selectedScene.contrast
    mapJson.arrows = $.extend(true, [], selectedScene.arrows)
    mapJson.annotations = $.extend(true, [], selectedScene.annotations)
    mapJson.shapes = $.extend(true, [], selectedScene.shapes)

    applyJsonData(mapJson)
})

$(document).on("click", ".glyphicon-chevron-down", function showInfo () {
    //var state = $(this).attr("state")
    var sceneName = $(this).attr("scene")
    var sceneDiv = document.getElementById(sceneName + "_info")
    sceneDiv.className = "panel panel-default"

    var sceneInfo = document.createElement("div")
    sceneInfo.className = "panel-body"

    $(this).attr("class", "glyphicon glyphicon-chevron-up") //replace with an up arrow
    //$(this).attr("state", "show")
    if (sceneName == "current_scene") {
        var scene = mapJson
    } else {
        var scene = mapJson.scenes[sceneName] //scene now points to an object inside of scenes
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

$(document).on("click", ".glyphicon-chevron-up", function() {
    this.className = "glyphicon glyphicon-chevron-down"
    this.setAttribute("state", "hide")
    var sceneName = $(this).attr("scene")
    var sceneDiv = document.getElementById(sceneName + "_info")
    sceneDiv.innerHTML = "" //clear html string in scene's info div
    sceneDiv.className = "" //clear box
})

var redrawContents = function(sceneName) {

    var sceneDiv = document.getElementById(sceneName + "_info")
    sceneDiv.innerHTML = "" //clear html string in scene's info div
    sceneDiv.className = "" //clear box
    sceneDiv.className = "panel panel-default"

    var sceneInfo = document.createElement("div")
    sceneInfo.className = "panel-body"

    $(this).attr("class", "glyphicon glyphicon-chevron-up") //replace with an up arrow
    //$(this).attr("state", "show")
    if (sceneName == "current_scene") {
        var scene = mapJson
    } else {
        var scene = mapJson.scenes[sceneName] //scene now points to an object inside of scenes
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


$(document).on("click", ".glyphicon-remove", function() {
    //First, find the scene to delete from
    var sceneName = $(this).attr("scene")

    if (sceneName == "current_scene") {
        var scene = mapJson
    } else {
        var scene = mapJson.scenes[sceneName]
    }

    //next, find the element to delete in the scene
    var type = $(this).attr("type")

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

    var leaflet_id = $(this).attr("leaflet_id")

    arr.forEach(function(e, i) {
        if (e.leaflet_id == leaflet_id) {
            arr.splice(i, 1) //remove element from array
            setJsonData(mapJson) //reload json

            $('input[name=scene_radio][value=' + sceneName + ']').prop('checked',true);
            $('input[name=scene_radio][value=' + sceneName + ']').trigger('change');

            return
        }
    })
})


$("#clear_current_scene_button").click(function() {

    mapJson.brightness = 50;
    mapJson.contract = 100;
    mapJson.arrows = [];
    mapJson.annotations = [];
    mapJson.shapes = [];
    setJsonData(mapJson);
})


$("#add_scene_button").click(function() {
    var sceneInput = document.getElementById("scene_name_input")
    var sceneName = sceneInput.value
    sceneName = sceneName.split(" ").join("_")

    if (!(sceneName in mapJson.scenes)) {
        appendSceneTag(sceneName)
    }

    mapJson.scenes[sceneName] = {
        'brightness': mapJson.brightness,
        'contrast': mapJson.contrast,
        'arrows': $.extend(true, [], mapJson.arrows),
        'annotations': $.extend(true, [], mapJson.annotations),
        'shapes': $.extend(true, [], mapJson.shapes),
    }

    $('input[name=scene_radio][value=' + sceneName + ']').prop('checked',true);
    $('input[name=scene_radio][value=' + sceneName + ']').trigger('change');
    console.log($("i[scene=" + sceneName + "]"));
    if($("i[scene=" + sceneName + "]").hasClass("glyphicon-chevron-up")) {
        redrawContents(sceneName);
    }
    
    console.log(mapJson)
    // sceneInput.value = ""
})

};