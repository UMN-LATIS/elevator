
var map = {};
var markers = {};

function loadMap(targetElement) {
	var hostElementName = getHostIdForElement(targetElement);
	if(map[hostElementName]) {
		clearMarkers(hostElementName);
		map[hostElementName].off();
		map[hostElementName].remove();
	}
	var layer1= L.esri.basemapLayer("Topographic", {
		"detectRetina": true,
		maxNativeZoom:18,
		maxZoom:21
	});
	var layer2= L.esri.basemapLayer("Imagery", 
		{"detectRetina": true,
		maxNativeZoom:18,
		maxZoom:20
	}
	);

	var layer3 = L.esri.basemapLayer('ImageryLabels');


	map[hostElementName] = L.map(targetElement, {
		layers: [layer1],
	}).setView([40, -94], 5);

	L.control.layers({"Topo": layer1, "Satellite": layer2}, {"Satellite Labels": layer3}).addTo(map[hostElementName]);

	if (L.control.locate) {
		L.control.locate({
			icon: 'glyphicon glyphicon-map-marker'
		}).addTo(map[hostElementName]);

	}
	
	// parsley freaks out and breaks the page if checkboxes don't have namesm but leaflet doesn't assign one for the toggles
	$(".leaflet-control-layers-overlays").find(".leaflet-control-layers-selector").attr("name","satelliteToggle");
	
	map[hostElementName].on('baselayerchange', function(e) {
	  if(e.name == "Satellite") {
	    $(".leaflet-control-layers-overlays").find(".leaflet-control-layers-selector").trigger("click");
	  }
	  else {

	    if($(".leaflet-control-layers-overlays").find(".leaflet-control-layers-selector").prop("checked") == true) {

	      $(".leaflet-control-layers-overlays").find(".leaflet-control-layers-selector").trigger("click"); 
	    }
	   
	  }
	});

}




$(document).on("click",".clearMarker", function() {
	var mapElement = $(this).closest(".mapContainer").find(".mapWidget");
	var hostElementName = getHostIdForElement(mapElement);
	clearMarkers(hostElementName);
});

function clearMarkers(hostElementName) {
	if(markers[hostElementName]) {
		markers[hostElementName].clearLayers();
	}
}

function createMarker(hostElementName, latitude, longitude, latitudeElement, longitudeElement, zoomToElement) {
	if(!markers[hostElementName]) {
		markers[hostElementName] = new L.layerGroup();
	}
	var newMarker = new L.Marker([latitude, longitude], {draggable: true});
	markers[hostElementName].addLayer(newMarker);

	map[hostElementName].addLayer(markers[hostElementName]);
	newMarker.on('dragend', function (e) {
		latitudeElement.val(e.target.getLatLng().lat);
		longitudeElement.val(e.target.getLatLng().lng);
	});

	if(zoomToElement) {
		centerLeafletMapOnMarker(hostElementName,newMarker);
	}
	return newMarker;
}


function centerLeafletMapOnMarker(hostElementName, marker) {
	var latLngs = [ marker.getLatLng() ];
	var markerBounds = L.latLngBounds(latLngs);

	map[hostElementName].fitBounds(markerBounds, {maxZoom: 15});
}

$(document).off("click", ".searchMap");
$(document).on("click",".searchMap", function() {

	var address = $(this).closest(".mapContainer").find(".address").first().val();
	var mapElement = $(this).closest(".mapContainer").find(".mapWidget");
	var latitudeElement = $(this).closest(".mapContainer").find('.latitude');
	var longitudeElement = $(this).closest(".mapContainer").find('.longitude');
	var hostElementName = getHostIdForElement(mapElement);

	clearMarkers(hostElementName);

	var options = {
		position: null
	};
	// TODO: check query limit
	var geocoder = new google.maps.Geocoder();

	geocoder.geocode({'address': address}, function(results, status) {
		if (status == google.maps.GeocoderStatus.OK) {
			options.position  = results[0].geometry.location;
			options.geocode   = true;

			var latitude = results[0].geometry.location.lat();
			var longitude = results[0].geometry.location.lng();

			marker = createMarker(hostElementName, latitude, longitude, latitudeElement, longitudeElement, true);
			latitudeElement.val(latitude);
			longitudeElement.val(longitude);
			$(latitudeElement).trigger("change"); // fire change event so sidebar updates
			$(longitudeElement).trigger("change"); // fire change event so sidebar updates

		}
	});


});


$(document).on("change",".geoField", function() {
	
	

	var latitudeElement = $(this).closest(".mapContainer").find('.latitude');
	var longitudeElement = $(this).closest(".mapContainer").find('.longitude');
	var mapElement = $(this).closest(".mapContainer").find('.mapWidget');
	var hostElementName = getHostIdForElement(mapElement);
	clearMarkers(hostElementName);
	if(latitudeElement.val().length===0 || longitudeElement.val().length===0) {
		return;
	}

	createMarker(hostElementName, latitudeElement.val(), longitudeElement.val(),latitudeElement, longitudeElement, false);

});

$(document).on("hidden.bs.collapse", ".maphost", function(){
	var mapElement = $(this).parent().find(".mapWidget");
	var hostElementName = getHostIdForElement(mapElement);
	clearMarkers(hostElementName);
	map[hostElementName].off();
	map[hostElementName].remove();

	var mapButton = $(this).parent().find(".mapToggle");
	mapButton.html("Reveal Map");

});

$(document).on("shown.bs.collapse", ".maphost", function (){
	var mapButton = $(this).parent().find(".mapToggle");
	mapButton.html("Hide Map");
	var mapElement = $(this).find(".mapWidget");
	var latitudeElement = $(this).parent().find('.latitude');
	var longitudeElement = $(this).parent().find('.longitude');
	revealMap(mapElement[0], latitudeElement, longitudeElement);

});

var getHostIdForElement = function(element) {
	if(typeof element == "object") {
		return $(element).closest(".maphost").attr("id");
	}
	else {
	}
	return element;
	
}

var revealMap = function(parentElement, latitudeElement, longitudeElement) {
	
	loadMap(parentElement);

	var hostElementName = getHostIdForElement(parentElement);

	map[hostElementName].on('click', function(e){
		if(markers[hostElementName].getLayers().length > 0) {
			return;
		}
		createMarker(hostElementName, e.latlng.lat, e.latlng.lng, latitudeElement, longitudeElement, false);
		latitudeElement.val(e.latlng.lat);
		longitudeElement.val(e.latlng.lng);

	});

	var longitude = longitudeElement.val();
	var latitude = latitudeElement.val();

	if(!markers[hostElementName]) {
		markers[hostElementName] = new L.layerGroup();
	}

	if(longitude!=="") {
		createMarker(hostElementName, latitude, longitude, latitudeElement, longitudeElement,true);
	}
	else {
		map[hostElementName].setView([46, -94], 6);
	}



}
