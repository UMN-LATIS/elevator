
var osmUrl = 'http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
    osmAttrib = '&copy; <a href="http://openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    osm = L.tileLayer(osmUrl, {maxZoom: 18, attribution: osmAttrib});

var map = L.map('map')
        .setView([50.5, 30.51], 15)
        .addLayer(osm);

var markers = new L.FeatureGroup().addTo(map),
    markerArr = [];

var clicks = 0;
markers.on('click', function(e) {
    clicks++;
});

function shuffleMarkers() {
    var marker;
    if (markerArr.length > 2000) {
        var i = Math.floor(Math.random() * markerArr.length);
        marker = markerArr.splice(i, 1)[0];
        markers.removeLayer(marker);
    } else {
        marker = L.marker(getRandomLatLng(map))
            .on('mouseover', function() {
                marker.setZIndexOffset(10000);
            })
            .on('mouseout', function() {
                marker.setZIndexOffset(0);
            })
            .addTo(markers);
        markerArr.push(marker);
    }
}

while (markerArr.length < 2000) {
    shuffleMarkers();    
}

setInterval(shuffleMarkers, 20);

