
$(document).ready(function() {

	$(window).scroll(function(){
    	if ($(document).height() - 50 <= $(window).scrollTop() + $(window).height()) {
			loadResults(offset + 100);
    	}
	});

});


function loadResults(offsetValue) {
	offset = offsetValue;

	$.get(basePath + "assetManager/userAssets/" + offsetValue, function(data) {
		$(".resultsTable tbody").append(data);
	});


}