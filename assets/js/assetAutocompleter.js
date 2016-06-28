
var relatedAssetPreview = function(relatedAssetId, targetContainer, targetParent) {
	if(relatedAssetId.length > 0) {
		var source   = $("#autocompleter-template").html();
		var template = Handlebars.compile(source);
		var self = targetContainer;
		$.get( basePath + "asset/getAssetPreview/" + relatedAssetId,  function( data ) {
			try{
				jsonObject = $.parseJSON(data);
			}
			catch(e){
				alert(e + " " + data);
				return;
			}
			if(jsonObject) {
				var responseObject = jsonObject;
				responseObject.base_url = basePath;
				var html = template(responseObject);
				$(self).closest(targetParent).find(".autocompletePreview").show();
				$(self).closest(targetParent).find(".autocompleteEdit").show();
				$(self).closest(targetParent).find(".clearRelated").show();
				$(self).closest(targetParent).find(".assetPreview").html(html);
				$(self).closest(targetParent).find(".newAssetButton").attr("disabled", true);


			}
		});
	}
};

/**
 * special autocompleter that does full text search
 */


 var buildAssetAutocomplete = function(targetContainer) {
 	var source   = $("#autocompleter-template").html();
	var template = Handlebars.compile(source);

 	if($(".tryAutocompleteAsset").length) {
 		$(".tryAutocompleteAsset").each(function(index, value) {
 			if($(value).val().length>0) {
 				$(value).closest(targetContainer).find(".autocompletePreview").show();
 				$(value).closest(targetContainer).find(".autocompleteEdit").show();
 				$(value).closest(targetContainer).find(".clearRelated").show();
 				$(value).closest(targetContainer).find(".newAssetButton").attr("disabled",true);
 			}
 			$(value).autocomplete({
 				source: function(request, response) {
 					var searchRequest = { "searchText": request.term};
 					var templateId = $(value).closest(targetContainer).find(".matchAgainstSelector").val();

 					$.post( basePath + "search/searchResults/", {searchQuery:JSON.stringify(searchRequest), templateId: templateId, suppressRecent:true, showHidden:true}, function( data ) {
 						try{
 							jsonObject = $.parseJSON(data);
 						}
 						catch(e){
 							alert(e + " " + data);
 							return;
 						}

 						if(jsonObject.success === true) {
 							$.each(jsonObject.matches, function(index, value) {
 								if(!value || value.objectId == $("#inputObjectId").val()) {
 									jsonObject.matches.splice(index,1);
 									return true;
 								}
 								value.value = value.objectId;
 								jsonObject.matches[index] = value;
 							});

 							response(jsonObject.matches);
 						}
 					});
 				},
 				messages: {
 					noResults: '',
 					results: function() {}
 				},
 				close: function(event, ui){
 					if(event.which !== undefined) {
 						// if we're not using the same field to query and store the id, clear the query field
 						if(!$(value).hasClass("relatedAssetSelectedItem")) {
 							$(value).val('');	
 						}
 						
 					}

 				},
 				select: function(event, ui){
 					$(value).closest(targetContainer).find(".relatedAssetSelectedItem").val("ui.item.objectId");
 					var html = template(ui.item);
 					$(this).trigger("change");
 					$(this).closest(targetContainer).find(".autocompletePreview").show();
 					$(this).closest(targetContainer).find(".autocompleteEdit").show();
 					$(this).closest(targetContainer).find(".clearRelated").show();
 					$(this).closest(targetContainer).find(".newAssetButton").attr("disabled",true);
 					$(this).closest(targetContainer).find(".assetPreview").html(html);
 				}
 			}).data("ui-autocomplete")._renderItem = function( ul, item ) {
 				item.base_url = basePath;
 				var html = template(item);
 				return $(html).appendTo(ul);
 			};
 		});
 	}
 };