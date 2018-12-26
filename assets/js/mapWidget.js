
var map = null;
var markers = null;

function loadMap(targetElement) {
	if(map) {
		clearMarkers();
		map.off();
		map.remove();
	}
	layer1= L.esri.basemapLayer("Topographic", {
		"detectRetina": true,
		maxNativeZoom:18,
		maxZoom:21
	});
	layer2= L.esri.basemapLayer("Imagery", 
		{"detectRetina": true,
		maxNativeZoom:18,
		maxZoom:20
	}
	);

	var layer3 = L.esri.basemapLayer('ImageryLabels');


	map = L.map(targetElement, {
		layers: [layer1],
	}).setView([40, -94], 5);

	L.control.layers({"Topo": layer1, "Satellite": layer2}, {"Satellite Labels": layer3}).addTo(map);

	// parsley freaks out and breaks the page if checkboxes don't have namesm but leaflet doesn't assign one for the toggles
	$(".leaflet-control-layers-overlays").find(".leaflet-control-layers-selector").attr("name","satelliteToggle");
	
	map.on('baselayerchange', function(e) {
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
	clearMarkers();
});

function clearMarkers() {
	if(markers) {
		markers.clearLayers();
	}
}

function createMarker(latitude, longitude, latitudeElement, longitudeElement, zoomToElement) {
	var newMarker = new L.Marker([latitude, longitude], {draggable: true});
	markers.addLayer(newMarker);
	map.addLayer(markers);
	newMarker.on('dragend', function (e) {
		latitudeElement.val(e.target.getLatLng().lat);
		longitudeElement.val(e.target.getLatLng().lng);
	});

	if(zoomToElement) {
		centerLeafletMapOnMarker(newMarker);
	}
	return newMarker;
}


function centerLeafletMapOnMarker(marker) {
	var latLngs = [ marker.getLatLng() ];
	var markerBounds = L.latLngBounds(latLngs);
	map.fitBounds(markerBounds, {maxZoom: 15});
}

$(document).on("click",".searchMap", function() {
	var address = $(this).closest(".mapContainer").find(".address").first().val();
	var mapElement = $(this).closest(".mapContainer").find(".mapWidget");
	var latitudeElement = $(this).closest(".mapContainer").find('.latitude');
	var longitudeElement = $(this).closest(".mapContainer").find('.longitude');

	clearMarkers();

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
			marker = createMarker(latitude, longitude, latitudeElement, longitudeElement, true);
			latitudeElement.val(latitude);
			longitudeElement.val(longitude);
			$(latitudeElement).trigger("change"); // fire change event so sidebar updates
			$(longitudeElement).trigger("change"); // fire change event so sidebar updates

		}
	});


});


$(document).on("change",".geoField", function() {
	clearMarkers();

	var latitudeElement = $(this).closest(".mapContainer").find('.latitude');
	var longitudeElement = $(this).closest(".mapContainer").find('.longitude');

	if(latitudeElement.val().length===0 || longitudeElement.val().length===0) {
		return;
	}

	createMarker(latitudeElement.val(), longitudeElement.val(),latitudeElement, longitudeElement, false);

});

$(document).on("hidden.bs.collapse", ".maphost", function(){
	
	clearMarkers();
	map.off();
	map.remove();

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

var revealMap = function(parentElement, latitudeElement, longitudeElement) {
	
	loadMap(parentElement);


	map.on('click', function(e){
		if(markers.getLayers().length > 0) {
			return;
		}
		createMarker(e.latlng.lat, e.latlng.lng, latitudeElement, longitudeElement, false);
		latitudeElement.val(e.latlng.lat);
		longitudeElement.val(e.latlng.lng);

	});

	var longitude = longitudeElement.val();
	var latitude = latitudeElement.val();

	if(!markers) {
		markers = new L.layerGroup();
	}

	if(longitude!=="") {
		createMarker(latitude, longitude, latitudeElement, longitudeElement,true);
	}
	else {
		map.setView([46, -94], 6);
	}



}
