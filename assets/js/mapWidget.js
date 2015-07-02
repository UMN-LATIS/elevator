
$(document).on("click",".clearMarker", function() {
	var mapElement = $(this).find(".mapWidget");
	mapElement.goMap();
	if($.goMap.getMarkerCount()>0) {
		$.goMap.clearMarkers();
	}
});


$(document).on("click",".searchMap", function() {

	var address = $(this).closest(".mapContainer").find(".address").first().val();
	var mapElement = $(this).closest(".mapContainer").find(".mapWidget");
	var latitudeElement = $(this).closest(".mapContainer").find('.latitude');
	var longitudeElement = $(this).closest(".mapContainer").find('.longitude');

	mapElement.goMap();
	if($.goMap.getMarkerCount()>0) {
		$.goMap.clearMarkers();
	}

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

			var marker = $.goMap.createMarker({
				longitude: longitude,
				latitude: latitude,
				draggable: true,

			});
			if(marker) {
				$.goMap.fitBounds();

				$.goMap.createListener({type:'marker', marker:marker.id}, 'dragend', function() {
					latitudeElement.val(this.position.lat());
					longitudeElement.val(this.position.lng());
				});
				$.goMap.setMap({
					longitude: longitude,
					latitude: latitude
				});
				latitudeElement.val(latitude);
				longitudeElement.val(longitude);
				$(".mainWidgetEntry").trigger("change"); // fire change event so sidebar updates

			}

		}
	});


});

$(document).on("change",".geoField", function() {
	var mapElement = $(this).find(".mapWidget");
	mapElement.goMap();
	if($.goMap.getMarkerCount()>0) {
		$.goMap.clearMarkers();
	}

	var latitudeElement = $(this).closest(".mapContainer").find('.latitude');
	var longitudeElement = $(this).closest(".mapContainer").find('.longitude');

	if(latitudeElement.val().length===0 || longitudeElement.val().length===0) {
		return;
	}

	var marker = $.goMap.createMarker({
		latitude: latitudeElement.val(),
		longitude: longitudeElement.val(),
		draggable: true
	});

	$.goMap.fitBounds();

	$.goMap.createListener({type:'marker', marker:marker.id}, 'dragend', function() {
		latitudeElement.val(this.position.lat());
		longitudeElement.val(this.position.lng());
	});
});

$(document).on("hidden.bs.collapse", ".maphost", function(){
	var mapElement = $(this).find(".mapWidget");
	var mapButton = $(this).parent().find(".mapToggle");
	mapButton.html("Reveal Map");
	mapElement.goMap();
	$.goMap.clearMarkers();
	mapElement.removeData();

});

$(document).on("shown.bs.collapse", ".maphost", function (){
	var mapElement = $(this).find(".mapWidget");
	var mapButton = $(this).parent().find(".mapToggle");
	mapButton.html("Hide Map");

	var latitudeElement = $(this).parent().find('.latitude');
	var longitudeElement = $(this).parent().find('.longitude');

	mapElement.goMap({
		mapTypeControl:true,
		maptype: 'ROADMAP',
		mapTypeControlOptions: {
			position: 'TOP_RIGHT',
			style: 'DROPDOWN_MENU'
		},
		addMarker: "single"

	});

	mapElement.on("clickMarker", function(e){
		var marker = e.marker;
		latitudeElement.val(marker.position.lat());
		longitudeElement.val(marker.position.lng());
		$.goMap.createListener({type:'marker', marker:marker.id}, 'dragend', function() {
			latitudeElement.val(this.position.lat());
			longitudeElement.val(this.position.lng());
		});
	});

	var longitude = longitudeElement.val();
	var latitude = latitudeElement.val();

	if(longitude!=="") {
		$.goMap.setMap({
			latitude: latitude,
			longitude: longitude
		});
		mapElement.goMap();
		var marker = $.goMap.createMarker({
			latitude: latitude,
			longitude: longitude,
			draggable: true
		});
		$.goMap.fitBounds();

		$.goMap.createListener({type:'marker', marker:marker.id}, 'dragend', function() {

			latitudeElement.val(this.position.lat());
			longitudeElement.val(this.position.lng());
		});

	}
	else {
		$.goMap.setMap({address: "Minneapolis, Minnesota"});
	}


});
