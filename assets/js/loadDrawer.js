var targetTemplate = "#drawer-template";
var listTemplate = "#drawer-list-template";


$(document).on("click", ".removeButton",function () {
	var url = window.location.href;
	var drawerId = url.substring(url.lastIndexOf('/') + 1);
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


	var url = window.location.href;
	var drawerId = url.substring(url.lastIndexOf('/') + 1);
	$.getJSON(basePath+"drawers/getDrawer/"+drawerId, function(data){
		cachedResults = data;
		cachedDates = null;

		if(cachedResults.success === true) {
            populateSearchResults(cachedResults);
        }

    });




});