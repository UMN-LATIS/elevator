
var MarkerSource;
var MarkerTemplate;
var TimelineTemplate;
var TimelineTemplate;
var cachedResults = "";
var cachedDates = null;
var searchId = null;
var currentPageNumber;
var eventSource;
var resultsAvailable = true;
var previousEventComplete = true;
var dataAvailable = true;
var disableHashChange = false;
var totalResults = 0;
$(document).ready(function() {

	$(window).scroll(function(){
		if (resultsAvailable && $(document).height() - 50 <= $(window).scrollTop() + $(window).height()) {
			if(($('.nav-tabs .active').text() == "Grid" || $('.nav-tabs .active').text() == "List") && previousEventComplete && dataAvailable) {
				doSearch(searchId, currentPageNumber+1);
			}
		}
	});

	$(".searchText").on("blur", function() {
		$(".advancedSearchText").val($(".searchText").val());
	});

	$(".advancedSearchText").on("blur", function() {
		$(".searchText").val($(".advancedSearchText").val());
	});

	$('a[href="#timeline"]').on('shown.bs.tab', function() {
		cachedDates = null;
		// prepTimeline();
	});
	$('a[href="#map"]').on('shown.bs.tab', function() {
		prepMap();
	});
	$('a[href="#gallery"]').on('shown.bs.tab', function() {
		prepGallery();
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

	TimelineSource   = $("#timeline-template").html();
	TimelineTemplate = Handlebars.compile(TimelineSource);


	if (location.hash !== '')  {
		$('a[href="' + location.hash + '"]').tab('show');
	}

    // remember the hash in the URL without jumping
    $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
       if(history.pushState) {
            history.pushState(null, null, '#'+$(e.target).attr('href').substr(1));
       } else {
            location.hash = '#'+$(e.target).attr('href').substr(1);
       }
    });

    function htmlEntities(str) {
    	return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
	}

    $(".embedMap").on("click", function(e) {
    	e.preventDefault();
    	embedPath = window.location.protocol + "//" + window.location.hostname + basePath + "search/map/" + getCurrentSearchId();

    	iFrameContent = '<iframe width="640" height="480" src="' +embedPath + '" frameborder="0" allowfullscreen></iframe>';

    	embedContent = '<input size=50 class="embedControl" value="' + htmlEntities(iFrameContent) + '"">';

    	bootbox.dialog(
		{
			title: "Embed this map",
			message: "Use the HTML below to embed this map in another page: <br>" + embedContent,
			buttons: {
				success: {
					label: "OK",
					className: "btn-primary"
				}
			}
		});

    });

    $(".embedTimeline").on("click", function(e) {
    	e.preventDefault();
    	embedPath = window.location.protocol + "//" + window.location.hostname + basePath + "search/timeline/" + getCurrentSearchId();

    	iFrameContent = '<iframe width="640" height="480" src="' +embedPath + '" frameborder="0" allowfullscreen></iframe>';

    	embedContent = '<input size=50 class="embedControl" value="' + htmlEntities(iFrameContent) + '"">';

    	bootbox.dialog(
		{
			title: "Embed this timeline",
			message: "Use the HTML below to embed this timeline in another page: <br>" + embedContent,
			buttons: {
				success: {
					label: "OK",
					className: "btn-primary"
				}
			}
		});

    });

});


function getCurrentSearchId() {
	currentURL = window.location.href.replace(window.location.hash,"");
	currentHash = window.location.hash.replace("#", "");
	if(currentHash.length == 36) {
		// this is an old hash, we need to keep that
		searchId = currentHash;
		window.history.pushState({}, "Search Results", currentURL + "/s/" +searchId);
	}
	else {
		searchId = currentURL.substr(currentURL.lastIndexOf('/') + 1);
	}
	return searchId;
}


function parseSearch() {
	
	searchId = getCurrentSearchId();

	if(searchId && !disableHashChange) {
		$("#results").empty();
		$("#listResults").empty();
		$(".frame ul").empty();



		// you can set a global var "loadAll" to cause us to load all available results at pageload.
		// leaving this here just to make it explicit
		var localLoadAll = false;
		if(typeof loadAll !== 'undefined' && loadAll == true) {
			localLoadAll = true;
		}

		doSearch(searchId, 0, localLoadAll);
	}

}

function loadCollectionHeader() {
	if($("input[name='collection[]']").length == 1 && $("input[name='collection[]']").val() !== "0" && $("#searchText").val() == "") {
		$.get(basePath + "collections/collectionHeader/" + $("input[name='collection[]']").val(), function(data) {
			$(".collectonHeader").html(data);
		});
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
					$.cookie('lastSearch', searchId, { path: "/"});

					// window.location.hash = "";
					window.location.pathname = basePath + "/asset/viewAsset/" + objectId;
				}
				cachedResults.totalLoadedCount = cachedResults.matches.length + oldMatches.length;
				processSearchResults(cachedResults);
				newArray = $.merge(cachedResults.matches, oldMatches);
				cachedResults.searchResults = $.merge(cachedResults.searchResults, oldResults);
				cachedResults.matches = newArray;

				// we want these to use the full stored set, not just the curret batch, so we run them after merging.
				if($('a[href="#map"]').parent().hasClass("active")) {
					prepMap();
				}

				// we want these to use the full stored set, not just the curret batch, so we run them after merging.
				// if($('a[href="#gallery"]').parent().hasClass("active")) {
				// 	prepGallery();
				// }

				if($('a[href="#timeline"]').parent().hasClass("active")) {
					cachedDates = null;
					prepTimeline();
				}

			}
		});
}

function processSearchResults(cachedResults) {
	populateSearchFields(cachedResults.searchEntry);
	loadCollectionHeader();
	populateSearchResults(cachedResults);
	if(cachedResults.matches.length === 0) {
		dataAvailable = false;
	}
	previousEventComplete = true;
	
}

$(document).on("click", ".assetLink", function(e) {
	$.cookie('lastSearch', searchId, { path: "/"});

});

$(document).on("click", ".useSuggest", function() {

	$(".searchText").val($(this).text());
	$(".searchForm").submit();

});

$(document).on("change", ".sortBy", function() {
	$(".hiddenSort").val($(this).val());
	performSearchForButtonPress($(".searchForm").first());
});


$(document).on("click", "#loadAllResults", function(e) {
	e.preventDefault();
	if(dataAvailable) {
		doSearch(searchId, currentPageNumber+1, true);
		dataAvailable = false;
		$("#loadAllResults").hide();
		$(".paginationBlock").hide();
		resultsAvailable = false;
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

	$(".sortBy").val(searchEntry.sort);

	$.each(searchEntry, function(index, value) {
		if(Array.isArray(value)) {
			var targetField = $("#" + index);
			$.each(value, function(internalIndex, internalValue) {
				var newField = targetField.clone();
				$(newField).val(internalValue);
				$(newField).appendTo($("#" + index).parent());
			});
			$(targetField).remove();
		}
		else {
			$("#" + index).val(value);	
		}
	
	});

	if($("#collection").val() > 0) {
		targetText = $('[data-collection-id="' + $("#collection").val() + '"]').text();
	    $("#search_concept").text(targetText);
	 }

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

	// todo refactor
	var source   = $("#gallery-template").html();
	var template = Handlebars.compile(source);
	
	$.each(cachedResults.matches, function(index, value) {
		value.base_url = basePath;
		value.searchObject = cachedResults;
		value.isChild = false;
		var hasChildren = false;
		if(value.fileAssets > 1 && value.fileAssets < 20) {
			hasChildren = true;
		}
		value.hasChildren = hasChildren;
		var html    = template(value);
		$("#previewFrame .frame ul").append(html);
		if(hasChildren) {
			for(i=0; i<value.fileAssets-1; i++) {
				value.primaryHandlerId = null;
				value.hasChildren = false;
				value.isChild = true;
				var html    = template(value);
				$("#previewFrame .frame ul").append(html);
			}
		}
	});

	totalResults = searchObject.totalResults;
	$(".resultsData").html("<p>Total Results: "+ searchObject.totalResults + "</p>");

	if(dataAvailable && searchObject.matches.length < searchObject.totalResults && searchObject.totalResults < 1000) {
		$(".resultsData p").append(" <a href='' id='loadAllResults'>[Load All]</a>");
	}


	if(searchObject.totalResults > searchObject.totalLoadedCount) {
		$(".paginationBlock").show();
		resultsAvailable = true;
	}
	else {
		$(".paginationBlock").hide();
		resultsAvailable = false;
	}

	
	if(searchObject.matches.length == 0) {
		$(".paginationBlock").hide();
		resultsAvailable = false;
	}

	if($('a[href="#map"]').parent().hasClass("active")) {
		prepMap();
	}

	if($('a[href="#gallery"]').parent().hasClass("active")) {
		prepGallery();
	}


	if($('a[href="#timeline"]').parent().hasClass("active")) {
		prepTimeline();
	}

	// have the server precache the new results
	if(searchId !== null) {
		doSearch(searchId, currentPageNumber+1, false, true);
	}

	event = new Event("searchUpdated");
  	document.dispatchEvent(event);

}

var galleryFrame = null;

$(document).on("click", ".fullscreen", function() {
	if($.fullscreen.isNativelySupported()) {
		$(".frameFullscreenContainer").first().fullscreen({ "toggleClass": "imageFullscreen"});
	}
});

function prepGallery() {
	if(cachedResults === "") {
		return;
	}

	if(galleryFrame !== null) {
		// galleryFrame.reload();
	}
	else {
		var assetsWithChildren = $(".frame ul").find('[data-haschildren="true"]');

		assetsWithChildren.each(function(index, el) {
			if($(el).data("childrenloaded") == "true") {
				return;
			}

			var localEl = el;
			var primaryHandler = $(el).data('primaryhandler');
			// load the metadata for this record
			$.get(basePath + "asset/viewAsset/" + $(el).data("objectid") + "/true", function(data) {
				parsed = $.parseJSON(data);
				var fileIds = new Array;
				for (var key in parsed) {
					if(Array.isArray(parsed[key])) {
						var newIds = parsed[key].map(function(nestedElem) {
						if(nestedElem.hasOwnProperty('fileId')) {
							return nestedElem.fileId;
						}
						else {
							return null;
						}
						}).filter((elem)=>{ return elem!=null}).filter((elem)=>{ return elem != primaryHandler});
						fileIds = fileIds.concat(newIds);
					}
				}

				for(fileId of fileIds) {
					targetElement = $(".frame ul").find('[data-objectid="' + $(el).data("objectid") + '"]').filter('[data-ischild="true"]').filter('[data-primaryhandler=""]').first();
					targetElement.attr("data-primaryhandler", fileId);
					targetElement.children("img").attr("src", basePath+"fileManager/previewImageByFileId/" + fileId);
					targetElement.children("img").attr("srcset", basePath+"fileManager/previewImageByFileId/" + fileId + "/true 2x");
				}
				$(el).data("childrenloaded", "true");
			});
			
		});


		sourceFrame = $('#forcecentered');

		var $wrap  = sourceFrame.parent();

		// Call Sly on frame
		galleryFrame = new Sly($('#forcecentered'), {
			horizontal: 1,
			itemNav: 'forceCentered',
			keyboardNavBy: 'items',
			smart: 1,
			activateMiddle: 0,
			activateOn: 'click',
			mouseDragging: 1,
			touchDragging: 1,
			releaseSwing: 1,
			startAt: 0,
			scrollBar: $wrap.find('.scrollbar'),
			scrollBy: 1,
			speed: 300,
			elasticBounds: 1,
			easing: 'easeOutExpo',
			dragHandle: 1,
			dynamicHandle: 1,
			clickBar: 1,

			// Buttons
			prev: $wrap.find('.prev'),
			next: $wrap.find('.next')
		}).init();

		galleryFrame.on('active', function(event, item) { 
			loadGalleryElement(item);
		});

		loadGalleryElement(0);
	}
}

function loadGalleryElement(index) {
	targetItem = galleryFrame.items[index].el;
	$(".frameHeader").html("<a href='" + basePath + "/asset/viewAsset/" + $(targetItem).data("objectid") + "'><h2>" + $(targetItem).data("title") + "</h2></a>" );
	$(".galleryIframe").attr("src", basePath + "asset/getEmbed/" + $(targetItem).data("primaryhandler") + "/" + $(targetItem).data("objectid") + "/true");
}

function loadNestedAssets(objectId) {


}


function prepMap() {

	if(cachedResults === "") {
		return;
	}
	if(markers) {
		markers.clearLayers();	
	}
	
	if(!map) {
		loadMap("mapPane");
	}
	markers = L.markerClusterGroup({ showCoverageOnHover: false});

	$.each(cachedResults.matches, function(index, value) {
		if(value.locations) {
			$.each(value.locations, function (index2, value2) {
				$.each(value2.entries, function(index3, value3) {

					loc = value3.loc.coordinates;
					value.base_url = basePath;
					var html    = MarkerTemplate(value);

					if(loc[1] === 0 && loc[0] === 0) {
						return true;
					}

					localMarker = L.marker([loc[1],loc[0]]);  
					localMarker.bindPopup(html);
  					markers.addLayer(localMarker);
					
				});
			});
		}

	});

	map.addLayer(markers);
	map.fitBounds(markers.getBounds().pad(0.5)); 

}


function prepTimeline() {
	if(cachedResults === "") {
		return;
	}

	var compiledDate = {};
	var geoTime = false;

	compiledDate.events = new Array();
	for (var match of cachedResults.matches) {

		// if we don't have a special dates propery, we can ignore this one
		if(!match.hasOwnProperty("dates")) {
			continue;
		}
		
		for(var dates of match.dates) {
			for(var date of dates.dateAsset) {
				startTime = parseInt(date.start["numeric"], 10);
				if(startTime < -6373557595440) {
					geoTime = true;
				}
			}
		}



		for(var dates of match.dates) {
			for(var date of dates.dateAsset) {
				var newItem = {};
				startTime = parseInt(date.start["numeric"], 10);
				newItem.start_date = {};
				if(geoTime) {
					startYear = -1 * Math.round(Math.abs((startTime + (1970*31556900)) / 31556900));
					newItem.start_date.year = startYear;
				}
				else {
					t = new Date(1970,0,1);
					t.setSeconds(startTime);
					formattedStart = Date.utc.create(t);
					newItem.start_date.display_date = date.start["text"];
					newItem.start_date.year = formattedStart.getFullYear();
					newItem.start_date.month = formattedStart.getMonth() + 1;
					newItem.start_date.day = formattedStart.getDay() + 1;

				}
				if(date.end["numeric"] && date.end["numeric"].toString().length>0) {
					newItem.end_date = {};
					endTime = parseInt(date.end["numeric"], 10);

					if(geoTime) {
						endYear = -1 * Math.round(Math.abs((endTime+ (1970*31556900)) / 31556900));
						newItem.end_date.year = endYear;
					}
					else {
						t = new Date(1970,0,1);
						t.setSeconds(endTime);
						formattedEnd = Date.utc.create(t);
						newItem.end_date.display_date = date.end["text"];
						newItem.end_date.year = formattedEnd.getFullYear();
						newItem.end_date.month = formattedEnd.getMonth() + 1;
						newItem.end_date.day = formattedEnd.getDay() + 1;

					}

				}

				var html    = TimelineTemplate(match);

				newItem.text = {};
				newElement = $("<a/>", {"href": basePath + "asset/viewAsset/" + match.objectId, "text": match.title});
				newItem.text.headline = newElement.prop("outerHTML");
				newItem.text.text = html;

				if(match.hasOwnProperty("primaryHandlerThumbnail2x")) {
					newItem.media = {};
					newItem.media.thumb = match.primaryHandlerThumbnail2x;
					newItem.media.url = match.primaryHandlerThumbnail2x;	
				}

				// dedupe our array
				var ignoreItem = false;
				for(var existingDate of compiledDate.events) {
					if(JSON.stringify(existingDate) == JSON.stringify(newItem)) {
						ignoreItem = true;
					}

				}
				
				if(!ignoreItem) {
					compiledDate.events.push(newItem);	
				}
				

			}
		}



	}

	if(geoTime) {
		compiledDate.scale = "cosmological"
		injectStyles(".tl-timeaxis-minor { display: none; }");
	}
	else {
		$("#hackyGeoStyle").remove();
	}

	var timeline = new TL.Timeline('timelinePane', compiledDate, {
        timenav_position: "bottom",
        timenav_height_percentage: "70"
	});


}

function injectStyles(rule) {
  var div = $("<div />", {
    id: "hackyGeoStyle",
    html: '&shy;<style>' + rule + '</style>'
  }).appendTo("body");    
}


$(document).on("click", ".embedControl", function() {
	$(this).select();
});


$(document).on("click", ".exportCSV", function(e) {
	e.preventDefault();
	target = e.target.href;
	window.location = target + "#" + searchId;

});
