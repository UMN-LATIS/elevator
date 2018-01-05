var targetTemplate = "#drawer-template";
var listTemplate = "#drawer-list-template";

function getDrawer() {
	var url = window.location.href;
	var drawerId = url.substring(url.lastIndexOf('/') + 1);
	return drawerId;
}

$(document).on("click", ".removeButton",function () {
	drawerId= getDrawer();
	if($(this).data("assettype") == "excerpt") {
		$.get(basePath+"drawers/removeExcerpt/"+drawerId + "/" + $(this).data("excerptid"));
		$("."+$(this).data("excerptid")).remove();
	}
	else {
		$.get(basePath+"drawers/removeFromDrawer/"+drawerId + "/" + $(this).data("assetid"));
		$("."+$(this).data("assetid")).remove();
	}



});

$(document).ready(function() {
	loadDrawer();

	$(document).off("change", ".sortBy");
	$(document).on("change", ".sortBy", function() {
		updateDrawerSortAndReload();
	});

	
});

function updateDrawerSortAndReload() {
	drawerSort = $(".sortBy").val();
	drawerId = getDrawer();
	$.get(basePath + "drawers/setSortOrder/" + drawerId + "/" + drawerSort, function() {
		loadDrawer();	
	});
	
}

function loadDrawer() {
	drawerId= getDrawer();
    $("#results").empty();
    $("#listResults").empty();
	$.getJSON(basePath+"drawers/getDrawer/"+drawerId, function(data){
		cachedResults = data;
		cachedDates = null;

		if(cachedResults.success === true) {
            populateSearchResults(cachedResults);
            $("#results").sortable({
    	update: function (e, ui) {
	alert("HEY");

		}
    });

        }
    });
}
