
var MarkerSource;
var MarkerTemplate;
var cachedResults = "";
var cachedDates = null;
var searchId = null;
var currentPageNumber;
var eventSource;
var resultsAvailable = true;
var previousEventComplete = true;
var dataAvailable = true;
var disableHashChange = false;

$(document).ready(function() {

	$(window).scroll(function(){
		if (resultsAvailable && $(document).height() - 50 <= $(window).scrollTop() + $(window).height()) {
			if(($('.nav-tabs .active').text() == "Grid" || $('.nav-tabs .active').text() == "List") && previousEventComplete && dataAvailable) {
				doSearch(searchId, currentPageNumber+1);
			}
		}
	});

	$(window).bind( 'hashchange', function(e) {
		parseHash();
	});

	setTimeout(function() {
		$.getScript("/assets/timeline_js/ext/geochrono/geochrono-api.js");
	}, 1000);


	$(".searchText").on("blur", function() {
		$(".advancedSearchText").val($(".searchText").val());
	});

	$(".advancedSearchText").on("blur", function() {
		$(".searchText").val($(".advancedSearchText").val());
	});

	$('a[href="#timeline"]').on('shown.bs.tab', function() {
		cachedDates = null;
		prepTimeline();
	});
	$('a[href="#map"]').on('shown.bs.tab', function() {
		prepMap();
	});
	$('a[href="#gallery"]').on('shown.bs.tab', function() {
	});
	$('a[href="#grid"]').on('shown.bs.tab', function() {
		$("#results").find("img").trigger("show");
	});
	$('a[href="#list"]').on('shown.bs.tab', function() {
		$("#listResults").find("img").trigger("show");
	});




	$(".previousPage").on("click",function() {
		doSearch(searchId, currentPageNumber-1);
		return false;
	});

	$(".nextPage").on("click",function() {
		doSearch(searchId, currentPageNumber+1);
		return false;
	});


	MarkerSource   = $("#marker-template").html();
	MarkerTemplate = Handlebars.compile(MarkerSource);


});




function parseHash() {
	if(window.location.hash.length>0 && !disableHashChange) {
		$("#results").empty();
		$("#listResults").empty();
		searchId = window.location.hash.substring(1);
		doSearch(searchId, 0);
	}
}

// ignore results is used to force the server to pre-cache the next set of results
function doSearch(searchId, pageNumber, loadAll, ignoreResults) {


	if(!ignoreResults) {
		previousEventComplete = false;
		currentPageNumber = pageNumber;
	}


	loadAll = (loadAll)?"true":"false";

	$.get(basePath + "search/searchResults/" + searchId + "/" + pageNumber + "/" + loadAll, function(data) {
			if(ignoreResults) {
				return;
			}
			var oldMatches = [];
			var oldResults = [];
			try{
				if(cachedResults) {
					oldMatches = cachedResults.matches;
					oldResults = cachedResults.searchResults;
				}
				cachedResults = $.parseJSON(data);

			}
			catch(e){
				alert(e + " " + data);
			}

			if(cachedResults.success === true) {

				if(cachedResults.totalResults == 1 ) {
					// special case - one match, let's just load it
					var objectId = cachedResults.matches[0].objectId;
					$.cookie('lastSearch', searchId);

					window.location.hash = "";
					window.location.pathname = basePath + "/asset/viewAsset/" + objectId;
				}
				processSearchResults(cachedResults);
				newArray = $.merge(cachedResults.matches, oldMatches);
				cachedResults.searchResults = $.merge(cachedResults.searchResults, oldResults);
				cachedResults.matches = newArray;

			}
		});
}

function processSearchResults(cachedResults) {
	populateSearchFields(cachedResults.searchEntry);
	populateSearchResults(cachedResults);
	if(cachedResults.matches.length === 0) {
		dataAvailable = false;
	}
	previousEventComplete = true;
}

$(document).on("click", ".assetLink", function() {
	$.cookie('lastSearch', searchId);

});

$(document).on("click", ".useSuggest", function() {

	$(".searchText").val($(this).text());
	$(".searchForm").submit();

});

$(document).on("change", ".sortBy", function() {
	$(".hiddenSort").val($(this).val());
	$(".searchForm").submit();
});


$(document).on("click", "#loadAllResults", function(e) {
	e.preventDefault();
	if(dataAvailable) {
		doSearch(searchId, currentPageNumber+1, true);
		dataAvailable = false;
		$("#loadAllResults").hide();
	}


});

function getSuggestions(searchTerm) {

	var localSearch = searchTerm;
	$.post(basePath + "search/getSuggestion", {"searchTerm":searchTerm}, function(data, textStatus) {
		var resultArray = $.parseJSON(data);
		if(resultArray.length === 0 ) {
			return;
		}
		$.each(resultArray, function(index, value) {
			var reg = new RegExp(index, 'ig');
			localSearch = localSearch.replace(reg, value);
		});

		var search = $("<a>").addClass("useSuggest").append(localSearch);
		var italics = $("<i>").append(search);
		var insert = $("<div>").addClass('col-sm-12 suggestionText').append("Did you mean: ").append(italics).append(" ?");
		$(".suggest").empty();
		$(".suggest").append(insert);

	});


}

function populateSearchFields(searchEntry) {
	$(".collectionSelector").prop('checked', false);
	$("#showHidden").prop('checked',false);
	$(".advancedOption").val();

	if(searchEntry.searchText.length > 0) {
		getSuggestions(searchEntry.searchText);
	}

	$.each(searchEntry, function(index, value) {
		$("#" + index).val(value);

	});
}



function populateSearchResults(searchObject) {
	// target template is defined by the hosting include - search page versus drawers, etc
	var source   = $(targetTemplate).html();
	var template = Handlebars.compile(source);
	var listSource   = $(listTemplate).html();
	var listTemplateCompiled = Handlebars.compile(listSource);

	$.each(searchObject.matches, function(index, value) {
		value.base_url = basePath;
		value.searchObject = searchObject;
		var html    = template(value);
		var listHTML    = listTemplateCompiled(value);
		$("#results").append(html);
		$("#listResults").append(listHTML);
	});



	if($('a[href="#map"]').parent().hasClass("active")) {
		prepMap();
	}

	if($('a[href="#timeline"]').parent().hasClass("active")) {
		prepTimeline();
	}

	if($('a[href="#gallery"]').parent().hasClass("active")) {
	}

	if($('a[href="#grid"]').parent().hasClass("active")) {
		
	}
	else {
		
	}

	if($('a[href="#list"]').parent().hasClass("active")) {
		
	}
	else {
		
		
	}

	$(".resultsData").html("<p>Total Results: "+ searchObject.totalResults + "</p>");

	if(dataAvailable && searchObject.matches.length < searchObject.totalResults && searchObject.totalResults < 1000) {
		$(".resultsData p").append(" <a href='' id='loadAllResults'>[Load All]</a>");
	}


	if(searchObject.totalResults > searchObject.matches.length) {
		$(".paginationBlock").show();
		resultsAvailable = true;
	}
	else {
		$(".paginationBlock").hide();
		resultsAvailable = false;
	}

	// have the server precache the new results
	if(searchId !== null) {
		doSearch(searchId, currentPageNumber+1, false, true);
	}


}




 var spiderConfig = {
    keepSpiderfied: true,
    event: 'mouseover'
};

function prepMap() {

	$("#mapPane").removeData();

	$("#mapPane").goMap({
		mapTypeControl:true,
		maptype: 'ROADMAP',
		mapTypeControlOptions: {
			position: 'TOP_RIGHT',
			style: 'DROPDOWN_MENU'
		},
		addMarker: "single"

	});

	var markerSpiderfier = new OverlappingMarkerSpiderfier($.goMap.map, spiderConfig);

	if($.goMap.getMarkerCount()>0) {
		$.goMap.clearMarkers();
	}

	$.each(cachedResults.matches, function(index, value) {
		if(value.locations) {
			$.each(value.locations, function (index2, value2) {
				$.each(value2.entries, function(index3, value3) {

					loc = value3.loc.coordinates;
					value.base_url = basePath;
					var html    = MarkerTemplate(value);
					var allMarkers = $.goMap.getMarkers("markers");
					if(loc[1] === 0 && loc[0] === 0) {
						return true;
					}
					latlng = new google.maps.LatLng(loc[1], loc[0]);
					finalLatLng = latlng;
					if (allMarkers.length != 0) {
						for (i=0; i < allMarkers.length; i++) {
							var existingMarker = allMarkers[i];
							var pos = existingMarker.getPosition();
        					//if a marker already exists in the same position as this marker

        					// if (google.maps.geometry.spherical.computeDistanceBetween(latlng,pos)<1) {
            	// 				//update the position of the coincident marker by applying a small multipler to its coordinates
            	// 				var newLat = latlng.lat() + (Math.random() -.5) / 5500;// * (Math.random() * (max - min) + min);
            	// 				var newLng = latlng.lng() + (Math.random() -.5) / 5500;// * (Math.random() * (max - min) + min);
            	// 				finalLatLng = new google.maps.LatLng(newLat,newLng);
            	// 			}
            			}
            		}
            		else {
            			finalLatLng = latlng;
            		}
					var marker = $.goMap.createMarker({
						longitude: finalLatLng.lng(),
						latitude: finalLatLng.lat(),
						html: html
					});
					markerSpiderfier.addMarker(marker);
				});
			});
		}

	});

	var markers = [];

	for (var i in $.goMap.markers) {
		var temp = $($.goMap.mapId).data($.goMap.markers[i]);
		markers.push(temp);
	}
	var iw = new google.maps.InfoWindow();

    markerSpiderfier.addListener('click', function(marker, e) {
    });

    markerSpiderfier.addListener('spiderfy', function(markers) {
    });

	var markerclusterer = new MarkerClusterer($.goMap.map, markers);
	markerclusterer.setMaxZoom(15);
	$.goMap.fitBounds();
}


function prepTimeline() {
	var earliestDate = null;
	var latestDate = null;
	if(Timeline === undefined || Timeline.DateTime === undefined || Timeline.DefaultEventSource === undefined || Timeline.strings === undefined || Timeline.GregorianDateLabeller.monthNames === undefined) {
		setTimeout(function() {
			prepTimeline();
		}, 1000);
		return;
	}
	$("#timelinePane").empty();

	var geoTime = false;

	if(cachedDates=== null) {
		cachedDates = {
//			"dateTimeFormat": "iso8601",
			"events": []
		};


		$.each(cachedResults.matches, function(index, value) {
			if(value.dates) {
				$.each(value.dates, function(index, value2) {
					$.each(value2.dateAsset, function(index, value3) {
						if(value3.start) {
							startTime = parseInt(value3.start["numeric"], 10);
							if(startTime < -6373557595440) {
								geoTime = true;
							}
						}

					});
				});
			}
		});

		$.each(cachedResults.matches, function(index, value) {
			if(value.dates) {
				$.each(value.dates, function(index, value2) {
					$.each(value2.dateAsset, function(index, value3) {
						if(value3.start && value3.start["numeric"].toString().length > 0) {
							var startTime = null;
							var endTime = null;
							var formattedStart = null;
							var formattedEnd = null;
							var t = null;
							startTime = parseInt(value3.start["numeric"], 10);

							if(geoTime) {
								/**
								 * at this point, let's work in millions of years ago
								 */
								formattedStart = Timeline.GeochronoUnit.wrapMA(Math.abs(startTime / 31556900000000));

							}
							else {
								t = new Date(1970,0,1);
								t.setSeconds(startTime);
								formattedStart = Date.utc.create(t);
							}


							formattedValue = {
								"miscData": value,
								"title": value.title,
								"id": "/en/"+value.objectId,
								"start": formattedStart,
								"image": basePath+"asset/previewImage/"+value.objectId,
								"link": basePath+"asset/viewAsset/"+value.objectId,
								"description": "Caption goeshere"
							};

							if(value3.end["numeric"] && value3.end["numeric"].length>0) {
								endTime = parseInt(value3.end["numeric"], 10);
								if(geoTime) {
									formattedEnd = Timeline.GeochronoUnit.wrapMA(Math.abs(endTime / 31556900000000));
								}
								else {
									t = new Date(1970,0,1);
									t.setSeconds(endTime);
									formattedEnd = Date.utc.create(t);
								}
								formattedValue.end = formattedEnd;
								formattedValue.durationEvent = true;
							}

							if(earliestDate === null || startTime < earliestDate) {
								earliestDate = startTime;
							}
							if(latestDate === null || startTime > latestDate) {
								latestDate = startTime;
							}
							if (endTime > latestDate) {
								latestDate = endTime;
							}

							cachedDates.events.push(formattedValue);
						}
					});
				});
			}
		});
	}


	if(eventSource) {
		eventSource.clear();
	}


	var oldFillInfoBubble = Timeline.DefaultEventSource.Event.prototype.fillInfoBubble;
	Timeline.DefaultEventSource.Event.prototype.fillInfoBubble = function(elmt, theme, labeller) {
		//oldFillInfoBubble.call(this, elmt, theme, labeller);

		var eventObject = this;

		var div = document.createElement("div");
		div.innerHTML = MarkerTemplate(eventObject._obj.miscData);
		elmt.appendChild(div);
	};




	if(geoTime) {
		eventSource = new Timeline.DefaultEventSource(new SimileAjax.EventIndex(Timeline.GeochronoUnit));
	}
	else {
		eventSource = new Timeline.DefaultEventSource(0);
	}


	eventSource.loadJSON(cachedDates, "localJson");

	var theme = Timeline.ClassicTheme.create();

	var d = null;
	var midPoint = null;
	if(geoTime) {
		midPoint = Math.abs(((earliestDate + latestDate) / 2) / 31556900000000);
		d = Timeline.GeochronoUnit.wrapMA(midPoint);
	}
	else {
		midPoint = (earliestDate + latestDate)/2;
		var t = new Date(1970,0,1);
		t.setSeconds(midPoint);

		d = Date.create(t);
	}
	var range = latestDate - earliestDate;
	var bigBand = null;
	var smallBand = null;
	var smallInterval = 200;
	if(range < 60*60) { // we're on a day scale
		bigBand = Timeline.DateTime.MINUTE;
		smallBand = Timeline.DateTime.HOUR;
	}
	else if(range < 60*60*24) { //month scale
		bigBand = Timeline.DateTime.HOUR;
		smallBand = Timeline.DateTime.DAY;
	}
	else if(range< 60*60*24*30) { //year scale
		bigBand = Timeline.DateTime.DAY;
		smallBand = Timeline.DateTime.MONTH;
	}
	else if(range< 60*60*24*30*12) { //decade
		bigBand = Timeline.DateTime.MONTH;
		smallBand = Timeline.DateTime.YEAR;
	}
	else if(range< 60*60*24*30*12*10) { //century
		bigBand = Timeline.DateTime.YEAR;
		smallBand = Timeline.DateTime.DECADE;
	}
	else if(range< 60*60*24*30*12*10*10) { //millenia
		bigBand = Timeline.DateTime.DECADE;
		smallBand = Timeline.DateTime.CENTURY;
	}
	else if(range< 60*60*24*30*12*10*10*10) { //bigger
		bigBand = Timeline.DateTime.CENTURY;
		smallBand = Timeline.DateTime.MILLENNIUM;

	}
	else {
		bigBand = Timeline.DateTime.MILLENNIUM;
		smallBand = Timeline.DateTime.MILLENNIUM;
		smallInterval = 80;
	}

	if(geoTime) {

		bigBand = Timeline.GeochronoUnit.MA;
		smallBand = Timeline.GeochronoUnit.EPOCH;
	}

	SimileAjax.History.enabled = false;
	var bandInfos;
	if(geoTime) {
        bandInfos  = [
            Timeline.Geochrono.createBandInfo({
                eventSource:    eventSource,
                timeZone: -1 * (new Date().getTimezoneOffset()/60),
                date:           d,
                width:          "86%",
                intervalUnit:   bigBand,
                intervalPixels: 100,
                trackGap:       0.2,
                trackHeight:    1.3,
                theme:          theme,
                eventPainter:   Timeline.CompactEventPainter,
                eventPainterParams: {
					iconLabelGap:     5,
					labelRightMargin: 20,
					iconWidth:        80, // These are for per-event custom icons
					iconHeight:       80,
					stackConcurrentPreciseInstantEvents: {
						limit: 5,
						moreMessageTemplate:    "%0 More Events",
						icon:                   "no-image-80.png", // default icon in stacks
						iconWidth:              80,
						iconHeight:             80
					}
				}
            }),
            Timeline.Geochrono.createBandInfo({
				width:          "10%",
				timeZone: -1 * (new Date().getTimezoneOffset()/60),
				intervalUnit:   smallBand,
				intervalPixels: smallInterval,
				eventSource:    eventSource,
				date:           d,
				theme:          theme,
				overview:         'overview'  // original, overview, detailed
			})
        ];
		bandInfos[1].syncWith = 0;
		bandInfos[1].highlight = true;
	}
	else {
		bandInfos = [
			Timeline.createBandInfo({
				width:          "90%",
				intervalUnit:   bigBand,
				timeZone: -1 * (new Date().getTimezoneOffset()/60),
				intervalPixels: 200,
				eventSource:    eventSource,
				date:           d,
				theme:          theme,
				eventPainter:   Timeline.CompactEventPainter,
				eventPainterParams: {
					iconLabelGap:     5,
					labelRightMargin: 20,
					iconWidth:        80, // These are for per-event custom icons
					iconHeight:       80,
					stackConcurrentPreciseInstantEvents: {
						limit: 5,
						moreMessageTemplate:    "%0 More Events",
						icon:                   "/assets/timeline_js/images/blue-circle.png", // default icon in stacks
						iconWidth:              80,
						iconHeight:             80
					}
				}
			}),
			Timeline.createBandInfo({
				width:          "10%",
				timeZone: -1 * (new Date().getTimezoneOffset()/60),
				intervalUnit:   smallBand,
				intervalPixels: smallInterval,
				eventSource:    eventSource,
				date:           d,
				theme:          theme,
				overview:         'overview'  // original, overview, detailed
			})
		];
		bandInfos[1].syncWith = 0;
		bandInfos[1].highlight = true;
	}


	if(geoTime) {
		tl = Timeline.create(document.getElementById("timelinePane"), bandInfos, Timeline.HORIZONTAL, Timeline.GeochronoUnit);
	}
	else {
		tl = Timeline.create(document.getElementById("timelinePane"), bandInfos, Timeline.HORIZONTAL);
	}


}


