
// disable cache-busted of embedded JS by jquery
$.ajaxPrefilter(function( options, originalOptions, jqXHR ) {
  if ( options.dataType == 'script' || originalOptions.dataType == 'script' ) {
      options.cache = true;
  }
});


Mousetrap.bind('left', function () {
	$(".relatedThumbHighlight").closest(".col-sm-2").prev().find("img").trigger("click");
});

Mousetrap.bind('right', function () {
	$(".relatedThumbHighlight").closest(".col-sm-2").next().find("img").trigger("click");
});


// handle "next" and "previous" while browsing results
function nextResultLink(targetId) {
	window.location = basePath + "asset/viewAsset/" + targetId;
}

$(document).on("ready", function() {
	registerHandlers();
	// registered in template.js
	loadLastSearch();

	// if we don't support fullscreen, hide hte button entirely
	if(!$.fullscreen.isNativelySupported()) {
		$("head").append('<style> .canFullscreen { display: none;</style>');
	}

	slicePoint = 400;
	if(typeof slicePointDefault !== 'undefined') {
		slicePoint = slicePointDefault;
	}
	$(".textareaView li div").expander({
		slicePoint: slicePoint
	});

	// if they came in with a hash, let's find and load that asset.
	if(window.location.hash) {
		nestedObjectId = window.location.hash.replace("#","");
		var targetAsset = $(document).find('[data-fileobjectid="' + nestedObjectId + '"]');
		if(targetAsset.length>0) {

		}
		else { // maybe it's a related asset, try that
			targetAsset = $(document).find('[data-objectid="' + nestedObjectId + '"]').find(".loadView");
		}

		if(targetAsset.length>0) {
			$(targetAsset).trigger("click");
		}
		else {
			// load a default element
			loadEmbedViewPointer();
		}

	}
	else if(searchId && $(".rightColumn").find(".uploadContent").length == 0 && $(".rightColumn").find(".loadView").length>1) {
		// we have multiple subviews, let's ask the search engine if there's one we should highlight
		$.post(basePath + "search/getHighlight", {searchId: searchId, objectId: objectId}, function(data, textStatus, xhr) {
			var json = $.parseJSON(data);

			var targetAsset = null;
			var haveSelectedTarget = false;
			$.each(json, function(index, element) {
				if(element != $("#embedView").data("objectid")) {
					targetAsset = $(document).find('[data-fileobjectid="' + element + '"]');
					if(targetAsset.length>0) {

					}
					else { // maybe it's a related asset, try that
						targetAsset = $(document).find('[data-objectid="' + element + '"]').find(".loadView");
					}
					if(targetAsset.length>0) {
						$(targetAsset).trigger("click");
						haveSelectedTarget = true;
						return false;
					}
				}

			});
			if(!haveSelectedTarget) {
				// just load a default
				loadEmbedViewPointer();
			}

		});
	}
	else {
		loadEmbedViewPointer();
	}
});


function loadEmbedViewPointer() {

	targetObjectId = $("#embedView").data("objectid");
	var targetAsset = $(document).find('[data-fileobjectid="' + targetObjectId + '"]');
	if(targetAsset.length>0) {

	}
	else { // maybe it's a related asset, try that
		targetAsset = $(document).find('[data-objectid="' + targetObjectId + '"]').find(".loadView");
	}

	if(targetAsset.length > 1) {
		targetAsset = targetAsset.first();
	}
	if ($(targetAsset).hasClass('ignoreForDigitalAsset')) {

	}
	else {
		$(targetAsset).trigger("click");
	}


}


var registerHandlers = function() {
	$(window).resize(resizeElement);

	/**
	 * Metadata Handlers
	 */

	$(document).on("click", ".loadView", loadView);

	// flip chevrons for related asset
	$(document).on("hide.bs.collapse", ".relatedListToggle", function (e) {
		$(this).find(".expandRelated").removeClass('glyphicon-chevron-up').addClass('glyphicon-chevron-down');
	});
	// load related asset
	$(document).on("show.bs.collapse", ".relatedListToggle", loadRelatedList);

	// update hash when loading a related asset
	$(document).on("click", ".relatedThumbContainer", function(e) {
		e.stopPropagation();
		var nestedObjectId = $(this).data("objectid");
		if(nestedObjectId !== undefined) {
			window.location.hash = nestedObjectId;
		}
		else {
			nestedObjectId = $(this).data("fileobjectid");
			if(nestedObjectId !== undefined) {
				window.location.hash = nestedObjectId;
			}
		}
	});

	$("#mapModal").on("shown.bs.modal", loadMapModal);

	/**
	 * Digital Asset Handlers
	 */
	$(document).on("click", ".embedControl", function () {
		$(this).select();
	});

	$(document).on("click", ".showDetails", function () {
		$(this).parent().parent().children('.assetDetails').toggle("fast");
	});

	$(document).on("click", ".exifToggle", loadExif);

	/**
	 * Page Level Handlers
	 */

	// flip chevron on 'more like this' accordion
 	$('#relatedAccordian').on('hide.bs.collapse', function (e) {
		$(this).find(".expandRelated").removeClass('glyphicon-chevron-up').addClass('glyphicon-chevron-down');
	});
	
	$('#relatedAccordian').on('show.bs.collapse', loadMoreLikeThis);

	// hide popovers when you click elsehwere on the page
	$('body').on('click', function (e) {
		$('[data-toggle="popover"]').each(function () {
			//the 'is' for buttons that trigger popups
			//the 'has' for icons within a button that triggers a popup
			if (!$(this).is(e.target) && $(this).has(e.target).length === 0 && $('.popover').has(e.target).length === 0) {
				$(this).popover('hide');
			}
		});
	});

	// reset the last search on edit so we can do forward/next in that case too
	$(document).on("click", ".editAssetLink", function (e) {
		$.cookie('lastSearch', searchId, {
			path: "/"
		});
	});
};


/**
 * Digital Asset Methods
 */


var resizeElement = function(maxHeight) {
	var ratio = $(".embedAsset").data("ratio");
	if (!maxHeight) {
		maxHeight = 0.7;
	}
	if(isNaN(ratio)) {
		ratio = 1;
	}
	if(ratio) {
		targetHeight = $(".embedAsset").width() / ratio;
		if (targetHeight > $(window).height() * maxHeight) {
			targetHeight = $(window).height() * maxHeight;
		}
		$(".embedAsset").height(targetHeight);
	}
};

/**
 * Metadata Methods
 */


// main handler for loading assets
var loadView = function (e) {
	if (e.originalEvent === undefined) {
		e.preventDefault();
	}
	fileObjectId = $(this).data("fileobjectid");

	var parent = $(this).closest("[data-objectid]");

	var needLoadNestedView = false;

	if (parent.length > 0 && (!parent.hasClass('objectIdHost') || (parent.hasClass('objectIdHost') && parent.hasClass('sidebarContainer')))) {
		// we're within a nested asset, rather than a straight thumbnail, let's also load the content for that.
		// if we've been loaded in the left column, as a nested view, we want to make sure we load both the view and reload the metadata, which is why
		// we check for the sdiebar container
		parentObject = parent.data("objectid");
		needLoadNestedView = true;
	}
	else {
		parentObject = $(this).closest(".objectIdHost").data("objectid");
	}

	// we pass in the page's objectId so that assets inside drawers load properly.


	$.get(basePath + "asset/getEmbedWithChrome/" + fileObjectId + "/" + objectId, function (data) {
		$("#embedView").html(data);
		$("#embedView").data("objectid", fileObjectId);

		$(document).find(".relatedThumbHighlight").removeClass("relatedThumbHighlight");
		if ($(".rightColumn").find('[data-fileobjectid]').length > 1) {
			var parentContainer = $(document).find('[data-fileobjectid="' + fileObjectId + '"]').parent();
			parentContainer.each(function (index, el) {
				if ($(el).is('div')) {
					$(el).addClass("relatedThumbHighlight");
				}
			});
		}

		var y = $(window).scrollTop();
		var z = $('#embedView').offset().top + 400;
		if (y > z) {
			$("html, body").animate({ scrollTop: 0 }, "fast");
		}

		lazyElements = $("#embedView").find(".lazy");
		lazyInstance.addItems(lazyElements);
		lazyInstance.update();

		if (needLoadNestedView) {
			// delay loading the asest by 50ms to avoid session concurrency issue with the server
			setTimeout(function() {
				$.get(basePath + "asset/viewAssetMetadataOnly/" + objectId + "/" + parentObject, function (data) {
					$("#embedView").append(data);
					lazyElements = $("#embedView").find(".lazy");
					lazyInstance.addItems(lazyElements);
					lazyInstance.update();
				});
			}, 100);
			
		}
		lazyInstance.update();

		if (typeof loadedCallback == 'function') {
			loadedCallback();
		}
	});
};


var loadRelatedList = function (e) {
	e.stopPropagation();
	var nestedObjectId = $(this).data("objectid");
	var targetElement = this;
	$(this).find(".expandRelated").removeClass('glyphicon-chevron-down').addClass('glyphicon-chevron-up');

	if ($(targetElement).parents(".relatedAssetContainer").length > 0) {
		parentObjectId = $(targetElement).parents(".relatedAssetContainer").eq(0).data("objectid");
	}
	else {
		parentObjectId = $(this).closest(".objectIdHost").data("objectid");
	}

	$.get(basePath + "asset/viewAssetMetadataOnly/" + parentObjectId + "/" + nestedObjectId, function (data) {
		$(targetElement).find('.relatedAssetContents').html(data);
		lazyElements = $(targetElement).find('.relatedAssetContents').find(".lazy");
		lazyInstance.addItems(lazyElements);
		lazyInstance.update();
	});
};


// show Exif data for images
var loadExif = function (e) {
	e.preventDefault();
	var fileObject = $(e.target).data("fileobject");
	Handlebars.registerHelper('ifObject', function (item, options) {
		if (typeof item === "object") {
			return options.fn(this);
		} else {
			return options.inverse(this);
		}
	});
	$.getJSON(basePath + 'fileManager/getMetadataForObject/' + fileObject, {}, function (json, textStatus) {
		if (json.exif) {
			Handlebars.registerPartial("exif-template", $("#exif-template").html());
			var source = $("#exif-template").html();
			var template = Handlebars.compile(source);
			var baseDiv = $("<div class='exifDisplay' />");
			// $.each(, function(index, value) {
			var html = template(json.exif);
			baseDiv.append(html);
			// });

			bootbox.dialog(
				{
					title: "EXIF Data",
					message: baseDiv.html(),
					buttons: {
						success: {
							label: "OK",
							className: "btn-primary"
						}
					}
				});
		}
	});
};


// load map modal
var loadMapModal = function (e) {
	var latitude = $(e.relatedTarget).data("latitude");
	var longitude = $(e.relatedTarget).data("longitude");
	var label = $(e.relatedTarget).html();

	$("#mapModalLabel").html(label);

	loadMap("mapModalContainer");
	
	if (markers["mapModalContainer"]) {
		markers["mapModalContainer"].clearLayers();
	}
	else {
		markers["mapModalContainer"] = new L.layerGroup();
	}

	map["mapModalContainer"].setView([latitude, longitude], 10);


	if (!markers) {
		markers["mapModalContainer"] = new L.layerGroup();
	}
	localMarker = L.marker([latitude, longitude]);
	markers["mapModalContainer"].addLayer(localMarker);
	map["mapModalContainer"].addLayer(markers["mapModalContainer"]);


	$("#mapNearby").attr("href", basePath + "search/nearbyAssets/" + latitude + "/" + longitude);

	return false;
};





/**
 * Document level listeners
 */

// use the 2x image for mouseovers
$(document).on("mouseover", ".relatedThumbToggle", function () {
	var image = $(this).find(".relatedThumbContainerImage");
	$(image).data("oldURL", $(image).attr("src"));
	var data = $(image).data("hover");
	$(image).attr("src", data);

});

// hide the 2x image
$(document).on("mouseout", ".relatedThumbToggle", function () {
	var image = $(this).find(".relatedThumbContainerImage");
	var data = $(image).data("oldURL");
	$(image).attr("src", data);

});


/**
 * Page level methods
 */

var loadMoreLikeThis = function (e) {
	$(this).find(".expandRelated").removeClass('glyphicon-chevron-down').addClass('glyphicon-chevron-up');
	var searchRequest = { "searchText": objectId};

	// get related results via search
	$.post( basePath + "search/searchResults/", {"suppressRecent": true, "searchRelated": true, searchQuery:JSON.stringify(searchRequest)}, function( data ) {
		try{
			jsonObject = $.parseJSON(data);
		}
		catch(e){
			alert(e + " " + data);
			return;
		}

		if(jsonObject.success === true) {

			$("#relatedAssets").empty();
			var source   = $("#result-template").html();
			var template = Handlebars.compile(source);
			var foundRelated = false;
			var resultCount = 0;
			$.each(jsonObject.matches, function(index, value) {
				if(value.objectId != objectId) {
					value.base_url = basePath;
					var html    = template(value);
					$("#relatedAssets").append(html);
					foundRelated = true;
					resultCount++;
				}

			});
			if(!foundRelated) {
				$("#relatedAssets").append("<p>No Related Assets Found</p>");
			}
		}
	});
};



// Lightbox code

$(document).on("click", ".triggerLightbox", function() {
	$(".embedAsset").addClass("lightboxAsset");
	
	$(".lightboxContainer").css("display","block");
	resizeElement("0.9");
});

$(document).on("click", ".lightboxCloseButton", function() { triggerClose() });
$(document).on("click", ".lightboxContainer", function () {
	triggerClose();
});
$(document).on('keydown', function (event) {
		if (event.key == "Escape") { 
			triggerClose();
		}
});
var triggerClose = function() {

	$(".lightboxContainer").css("display", "none");
	$(".embedAsset").removeClass("lightboxAsset");
	resizeElement("0.7");
}