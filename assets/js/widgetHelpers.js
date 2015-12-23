
/**
 * register various helpers
 */

var markSaveDirty = function() {
	$(".saveButton").removeClass("btn-primary").addClass("btn-warning");
	$("#entryForm").removeClass("clean").addClass("dirty");
};

var markSaveClean = function() {
	$(".saveButton").removeClass("btn-warning").addClass("btn-primary");
	$("#entryForm").removeClass("dirty").addClass("clean");
};

Handlebars.registerHelper('join', function(val, delimiter, start, end) {
	var arry = [].concat(val);
	delimiter = ( typeof delimiter == "string" ? delimiter : ', ' );
	start = start || 0;
	end = ( end === undefined ? arry.length : end );
	return arry.slice(start, end).join(delimiter);
});


Handlebars.registerHelper('random', function(options) {
	return Math.random();
});

$(window).bind('beforeunload', function(){
	if(uploadCount > 0) {
		return "You have uploads in progress. Are you sure you wish to leave the page and cancel these uploads?";
	}
});

// command-control-h reveals hidden content (anything flagged advancedContent)
Mousetrap.bind('command+ctrl+t', function() {
	toggleTabs();
});


// very simple toggle - we'll make this better eventually right?
var toggleTabs = function() {
  $(".rightPane .tab-content>.tab-pane").toggle();
  $("#general").toggle();
  $(".leftPane").toggle();
};

var relatedAssetPreview = function(relatedAssetId, targetContainer) {
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
					$(self).closest(".widgetContents").find(".autocompletePreview").show();
					$(self).closest(".widgetContents").find(".autocompleteEdit").show();
					$(self).closest(".widgetContents").find(".clearRelated").show();
					$(self).closest(".widgetContents").find(".assetPreview").html(html);
					$(self).closest(".widgetContents").find(".newAssetButton").attr("disabled", true);


				}
			});
	}
};


/**
 * Add checkmarks to sidebar if there's content in their
 */
var showHaveEntries = function() {

	$(".tab-pane").each(function(index, el) {
		var targetId = $(el).attr("id");
		var show = false;
		var requiredContent = false;
		var mainWidgets = $(el).find(".mainWidgetEntry");
		$(mainWidgets).each(function(index, mainWidgetElement) {
			if($(mainWidgetElement).is("input") || $(mainWidgetElement).is("textarea")) {
				if($(mainWidgetElement).val().length> 0) {
					show = true;
				}
			}
			else if($(mainWidgetElement).is("select")) {
				if($(mainWidgetElement).find(":selected").text() !== "") {
					show = true;
				}
			}
			else {
				show = true;
			}

			if($(mainWidgetElement).prop('required')) {
				requiredContent = true;

			}

		});

		var targetHref;
		if(show) {
			targetHref = $('a[href="#' + targetId+ '"]').children('.haveContent').show();
			$('a[href="#' + targetId+ '"]').find('.requiredContent').hide();
		}
		else {
			targetHref = $('a[href="#' + targetId+ '"]').children('.haveContent').hide();
			if(requiredContent) {
				$('a[href="#' + targetId+ '"]').find('.requiredContent').show();
			}
			else {
				$('a[href="#' + targetId+ '"]').find('.requiredContent').hide();
			}
		}


	});

};


$(document).on("change", ".mainWidgetEntry", function() {
	showHaveEntries();
	markSaveDirty();
});


var addAnother = function(target) {
	$(target).attr("disabled",true);
	var parentGroup = $(target).parents(".control-group");
	var widgetTitle = $(parentGroup).attr('id').replace("controlGroup_", '');
	var widgetId = $(parentGroup).children(".widget_id").first().val();
	var offsetCount = $(parentGroup).children("div").length-1;

	var self = target;
	var returnInfo = null;
	$.get(basePath+"assetManager/getWidget/"+widgetId+"/"+offsetCount, function(data){

		window.offsetCount[widgetTitle]++;
		returnInfo = $(data);

		$(parentGroup).append(returnInfo);

		$(parentGroup).find('.isPrimary').show();
		buildAutocomplete();
		$(self).removeAttr("disabled");

		if(parentGroup.height() > $(".mainRow").height()) {
			$(".mainRow").height(parentGroup.height() + 50);
			$(".leftPane").height($(".mainRow").height());
		}

	});
	return returnInfo; // only returns if this is sync
};


/**
 * Add an additional element
 */
$(document).on("click", ".addAnother",function(e) { addAnother(this); });


$(document).on("change", ".templateSelector", function(e) {
	if(parseInt($(this).val()) === 0) {
		$(this).closest(".widgetContents").find('.newAssetButton').attr('disabled', "disabled");
	}
	else {
		$(this).closest(".widgetContents").find('.newAssetButton').attr('disabled', false);
	}

});


$(document).ready(function() {
	/**
	 * Enable Parseley
	 */
	$("#entryForm").parsley({
		validators: {
			dateobject: function() {
				return {
					validate: function(val, element) {
						return parseDateString(val, element);
					},
					priority: 2
				};
			}
		},
		messages: {
			dateobject: "We couldn't figure out a way to parse this date."
		}
	});


	// make sure nested items have the same collection
	$("#collectionId").on("change", function(e) {
		var targetCollection = $(this).val();
		$(".nestediIFrame").each(function(index, el) {
			var sourceCollection = $(el).contents().find("#collectionId");
			sourceCollection.val(targetCollection);
			$(sourceCollection).trigger("change");
			$(el).get(0).contentWindow.resetCollection();
		});

	});

	showHaveEntries();

	$(".control-group").each(function(index, el) {
		if($(el).find('.isPrimary').length > 1) {
			$(el).find('.isPrimary').show();
		}
	});

	$('input,select').keypress(function(event) { return event.keyCode != 13; });

	$("#inputAvailableAfter").datepicker();

	if($("#inputObjectId").val() !== "") {
		$(".viewAsset").show();
		loadPreviewAndUpdateTitle($("#inputObjectId").val());
	}


	$(".templateSelector").trigger("change");

	/**
	 * preload the previews for any related assets
	 */
	$(".relatedAssetSelectedItem").each(function(index, el) {

		var relatedAssetId = $(el).val();
		relatedAssetPreview(relatedAssetId, this);
	});


	$("#inputNewTemplateId").on("change", function() {
		var sourceReference = this;
		targetTemplate = $(sourceReference).val();
		sourceTemplate = $("#sourceTemplate").val();
		jQuery.getJSON(basePath + "assetManager/compareTemplates/" + sourceTemplate + "/" + targetTemplate, {}, function(json, textStatus) {

			if(json.length === 0) {
				$("#sourceTemplate").val(targetTemplate);
			}
			else {
				var source   = $("#template-switch").html();
				var template = Handlebars.compile(source);

				bootbox.confirm(template(json), function(result) {
					if(result) {
						$("#sourceTemplate").val(targetTemplate);
					}
					else {
						$(sourceReference).val(sourceTemplate);
					}
				});

			}
		});


	});


	$("#newCollectionId").on("change", function() {
		var sourceReference = this;
		targetCollection = $(sourceReference).val();
		sourceCollection = $("#collectionId").val();
		if(sourceCollection === "-1" || sourceCollection === "") {
			$("#collectionId").val(targetCollection);
			$("#collectionId").trigger("change");
			return;
		}

		jQuery.getJSON(basePath + "assetManager/compareCollections/" + sourceCollection + "/" + targetCollection, {}, function(json, textStatus) {

			if(json.migration === false) {
				$("#collectionId").val(targetCollection);
				$("#collectionId").trigger("change");
			}
			else {
				var source   = $("#collection-switch").html();
				var template = Handlebars.compile(source);

				bootbox.confirm(template(json), function(result) {
					if(result) {
						$("#collectionId").val(targetCollection);
						$("#collectionId").trigger("change");
						$("#collectionMigrationInProcess").val("true");
					}
					else {
						$(sourceReference).val(sourceCollection);
					}
				});

			}
		});


	});



	buildAutocomplete();


	$("#collectionMigrationInProcess").on("change", function() {
		if($(this).val() == "true") {
				$(".saveButton").hide();
				bootbox.dialog({
				message: "Editing is unavailable while collection migration is taking place.",
				title: "Edits Disabled",
				backdrop: true,
				closeButton: false
			});
		}
		else {

		}

	});

	$("#collectionMigrationInProcess").trigger('change');

	$(".toggleTabs").on("click", function() {
		toggleTabs();
	});

});





$(document).on("click", ".autocompletePreview", function(event) {
	var targetItem = $(this).closest(".widgetContents").find(".targetAsset").val();
	window.open(basePath+ "asset/viewAsset/"+targetItem);
	return false;
});

$(document).on("click", ".autocompleteEdit", function(event) {
	var targetItem = $(this).closest(".widgetContents").find(".targetAsset").val();
	window.open(basePath+ "assetManager/editAsset/"+targetItem);
	return false;
});


function buildAutocomplete() {
	var source   = $("#autocompleter-template").html();
	var template = Handlebars.compile(source);

	/**
	 * This performs the actual autocomplete, doing prefix only search against the same template and field title.
	 */
	if($(".tryAutocomplete").length) {
		$(".tryAutocomplete").each(function(index, value) {
			var self = value;
			$(value).autocomplete({
				source: function(request, response) {
					var templateId = $("#sourceTemplate").val();
					var fieldTitle = $(self).closest(".tab-pane").attr("id");
					var searchTerm = request.term;
					$.post( basePath + "search/autocomplete/", {templateId: templateId, fieldTitle:fieldTitle, searchTerm:searchTerm}, function( data ) {
					try{
						jsonObject = $.parseJSON(data);
					}
					catch(e){
						alert(e + " " + data);
						return;
					}

					response(jsonObject);
				});
			},
			messages: {
				noResults: '',
				results: function() {}
			}
			});
		});
	}


	/**
	 * special autocompleter that does full text search
	 */
	if($(".tryAutocompleteAsset").length) {
		$(".tryAutocompleteAsset").each(function(index, value) {
			if($(value).val().length>0) {
				$(value).closest(".widgetContents").find(".autocompletePreview").show();
				$(value).closest(".widgetContents").find(".autocompleteEdit").show();
				$(value).closest(".widgetContents").find(".clearRelated").show();
				$(value).closest(".widgetContents").find(".newAssetButton").attr("disabled",true);
			}
			$(value).autocomplete({
			source: function(request, response) {
				var searchRequest = { "searchText": request.term};
				var templateId = $(value).closest(".widgetContents").find(".matchAgainstSelector").val();

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
					$(value).val('');
				}

			},
			select: function(event, ui){
				$(value).closest(".widgetContents").find(".relatedAssetSelectedItem").val(ui.item.objectId);
				var html = template(ui.item);
				$(this).trigger("change");
				$(this).closest(".widgetContents").find(".autocompletePreview").show();
				$(this).closest(".widgetContents").find(".autocompleteEdit").show();
				$(this).closest(".widgetContents").find(".clearRelated").show();
				$(this).closest(".widgetContents").find(".newAssetButton").attr("disabled",true);
				$(this).closest(".widgetContents").find(".assetPreview").html(html);
			}
		}).data("ui-autocomplete")._renderItem = function( ul, item ) {
			item.base_url = basePath;
			var html = template(item);
			return $(html).appendTo(ul);
		};
	});
	}
}


/**
 * related asset helpers
 */

$(document).on("click", ".viewAsset", function() {
		window.open(basePath+ "asset/viewAsset/"+$("#inputObjectId").val());
});


$(document).on("click", ".clearRelated", function(e) {
	if(confirm("Are you sure you wish to clear this asset?")) {
		$(this).closest(".widgetContents").find(".relatedAssetInput").val("");
		$(this).closest(".widgetContents").find(".relatedAssetSelectedItem").val("");
		$(this).closest(".widgetContents").find(".relatedAssetInput").trigger("change");
		$(this).closest(".widgetContents").find(".relatedAssetSelectedItem").trigger("change");
		$(this).closest(".widgetContents").find(".autocompletePreview").hide();
		$(this).closest(".widgetContents").find(".autocompleteEdit").hide();
		$(this).closest(".widgetContents").find(".assetPreview").html("");
		$(this).closest(".widgetContents").find(".clearRelated").hide();
		$(this).closest(".widgetContents").find(".nestediIFrame").remove();
		$(this).closest(".widgetContents").find(".newAssetButton").removeAttr("disabled");
	}
});


/**
 * nested asset helper
 */


function loadFrameForNestedElement(el) {
	var templateId = $(el).find(".templateSelector").val();
		var collectionId = $("#inputCollectionId").val();

		var assetId = $(el).find(".targetAsset").val();
		var targetURL = null;

		if(assetId.length > 0) {
			targetURL = basePath + "assetManager/editAsset/" + assetId + "/true";
		}
		else {
			targetURL = basePath + "assetManager/addAsset/" + templateId + "/" + collectionId + "/true";
		}


		var targetElement = $(el).find(".inlineRelatedAsset");

		var element = $("<iframe />", {
			class: "nestediIFrame",
			frameborder: 0,
			width: "100%",
			src: targetURL
		});

		element.appendTo(targetElement);

		element.load(function() {
			iframeLoaded(element);
		});

}

function iframeLoaded(iFrame) {

		var element = $(iFrame).closest(".tab-pane");
		var id = $(element).attr("id");

		if($(element).hasClass("active")) {
			var height = $(iFrame).contents().find("html").height();

			$(iFrame).height(height);
		}
		else {
			$("a[href=#" + id + "]").on('shown.bs.tab', function(e) {

				var height = $(iFrame).contents().find("html").height();

				$(iFrame).height(height);

			});

		}
		$("#collectionId").trigger("change");


}
