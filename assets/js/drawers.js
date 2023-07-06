
$(document).ready(function () {

	$("#addToDrawer").on("submit", function(e) {

		e.preventDefault(); //STOP default action
		var postData = $(this).serializeArray();
		if($("#drawerList").find(":selected").length == 0) {
			alert("You must create a drawer first");
		}
		if(typeof(excerptId) != 'undefined') {
			postData.push({name: 'excerptId', value: excerptId});
		}

		if(typeof(objectId) != 'undefined') {
			postData.push({name: 'objectId', value: objectId});
		}

		if(typeof(cachedResults) != "undefined") {
			postData.push({name: 'objectArray', value: JSON.stringify(cachedResults.searchResults)});
		}

		var formURL = $(this).attr("action");
		$.ajax({
			url : formURL,
			type: "POST",
			data : postData,
			success:function(data, textStatus, jqXHR)
			{
				$("#drawerModal").modal('toggle');
			},
			error: function(jqXHR, textStatus, errorThrown)
			{
				console.log("Error adding Drawer");
			}
		});



	});

	$("#addNewDrawer").on("submit", function(e) {
		var postData = $(this).serializeArray();
		var formURL = $(this).attr("action");
		$.ajax({
			url : formURL,
			type: "POST",
			data : postData,
			success:function(newDrawer, textStatus, jqXHR)
			{
				var drawerId = parseInt(newDrawer.drawerId,10);
				$("#drawerList").append("<option value='" + drawerId + "'>" + newDrawer.drawerTitle + "</option>");

				$('#drawerList').val(drawerId);
				$(".drawerAddedSuccess").fadeIn('400');
				window.setTimeout(function() { $(".drawerAddedSuccess").fadeOut('400'); }, 2000);
			},
			error: function(jqXHR, textStatus, errorThrown)
			{
				console.log("Error adding Drawer");
			}
		});
		e.preventDefault(); //STOP default action
	});

});
