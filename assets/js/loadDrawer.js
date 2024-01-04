var targetTemplate = "#drawer-template";
var listTemplate = "#drawer-list-template";

function getDrawer() {
	var url = window.location.href.replace(window.location.hash,"");
	var drawerId = url.substring(url.lastIndexOf('/') + 1);
	return drawerId;
}

$(document).on("click", ".removeButton",function () {
	drawerId= getDrawer();
	if($(this).data("assettype") == "excerpt") {
		$.get(basePath+"drawers/removeExcerpt/"+drawerId + "/" + $(this).data("excerptid"));
	}
	else {
		$.get(basePath+"drawers/removeFromDrawer/"+drawerId + "/" + $(this).data("assetid"));
	}
	$(this).closest('.searchContainer').remove();


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

function serializeDrawerAndUpdate() {
	orderArray = new Array();
	$(".active .searchContainer").each(function(index, el) {
		orderArray.push($(el).data("drawerobjectid"));
	});
	$.post(basePath + "drawers/setCustomOrder/" + drawerId, { orderArray: JSON.stringify(orderArray) }, 
		function() {
		}
	);
}

function loadDrawer() {
	drawerId= getDrawer();
    if(drawerId.length > 10) {
		return;
	}
	$("#results").empty();
    $("#listResults").empty();
	
	$.getJSON(basePath+"drawers/getDrawer/"+drawerId, function(data){
		cachedResults = data;
		cachedDates = null;

		if(cachedResults.success === true) {
            populateSearchResults(cachedResults);
            if($(".sortBy").val() == "custom") {
            	$("#results").sortable({
	    			update: function (e, ui) {
	    				serializeDrawerAndUpdate();
					}
    			});
    			$("#listResults").sortable({
	    			update: function (e, ui) {
	    				serializeDrawerAndUpdate();
					}
    			});
            }
            else {
            	if($("#results").sortable( "instance" )) {
            		$("#results").sortable( "destroy" );
            		$("#listResults").sortable( "destroy" );	
            	}
            }
            
        }
    });
}
