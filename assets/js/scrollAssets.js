
$(document).ready(function() {

	$(window).scroll(function(){
    	if ($(document).height() - 50 <= $(window).scrollTop() + $(window).height()) {
    		if(!loading) {
    			loadResults(offset + 100);	
    		}
			
    	}
	});

});
var loading = false;

function loadResults(offsetValue) {
	offset = offsetValue;
	loading = true;
	$.get(basePath + "assetManager/userAssets/" + offsetValue, function(data) {
		
		var localdata = $(data);
		localdata.each(function(index, element) {
			if(String(element).indexOf("HTML") !== -1) {

				$("#resultsTable").DataTable().row.add(element);	
			}
			
			
			// 
		});
		$("#resultsTable").DataTable().draw();
		loading=false;
	});


}