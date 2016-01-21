
var submitTimer = null;


// wait a tick before we fire so we can coalesce saves
function submitForm(ignoreWarnings, supressAlertAndBlock) {

	submitTimer = setTimeout(function() {
		submitFormProtected(ignoreWarnings, supressAlertAndBlock);
	}, 1000);


}

function submitFormProtected(ignoreWarnings, supressAlertAndBlock) {

	if(window.unsavedChildren>0 && !ignoreWarnings) {
		bootbox.dialog({
			message: "You have unsaved nested assets.  Please save or close them before submitting the parent.",
			title: "Unsaved Assets",
			backdrop: true,
			buttons: {
				success: {
					label: "Ignore and Save",
					className: "btn-warning",
					callback: function() {
						submitForm(true);
					}
				},
				cancel: {
					label: "Cancel",
					className: "btn-default"
				}
			}
		});
		return false;
	}

	$("#entryForm").parsley( 'validate' );

	$(".nestediIFrame").each(function(index, el) {

		var frameForm = $(el).contents().find("form");
		if($(frameForm).hasClass('dirty')) {

			$(el)[0].contentWindow.submitForm(true, true);
			var iFrameObjectId = $(el).contents().find("#inputObjectId").val();
			$(el).closest(".widgetContents").find(".targetAsset").val(iFrameObjectId);
		}

	});
	showHaveEntries();

	if($("#collectionId").val() == -1) {
		bootbox.dialog({
			message: "Collection cannot be empty.",
			title: "Invalid Collction",
			backdrop: true,
			buttons: {

				cancel: {
					label: "OK",
					className: "btn-primary"
				}
			}
		});
		return;
	}

	if(!$("#entryForm").parsley('isValid')) {
		bootbox.dialog({
			message: "You have errors or missing/invalid values in your asset.  Your asset cannot be saved until this issues are corrected.",
			title: "Invalid Entry",
			backdrop: true,
			buttons: {

				cancel: {
					label: "OK",
					className: "btn-primary"
				}
			}
		});
		return false;
	}

	// make these operations blocking
	if(supressAlertAndBlock) {
		$.ajaxSetup({
			async: false
		});
	}

	$.post( basePath + "assetManager/submission", {formData: JSON.stringify($( "#entryForm" ).serializeForm())}, function( data ) {

		try{
			jsonObject = $.parseJSON(data);
		}
		catch(e){
			alert(e + " " + data);
		}
		if(jsonObject.success === true) {
			objectId = jsonObject.objectId;
			$(".regenerateDerivatives").prop("checked", false);
			if(!supressAlertAndBlock) {
				bootbox.alert("<h2>Saved</h2>");
				window.setTimeout(function(){
					bootbox.hideAll();
					// trigger the alert that they can't make changes if they just altered a collection
					$("#collectionMigrationInProcess").trigger('change');
				}, 1500);

			}

			if(hasParent) {
				/**
				 * If we've been opened in a window.open, we don't want to redirect, just let the parent know
				 */
				notifyParentOfSave(objectId);
			}

			loadPreviewAndUpdateTitle(objectId);
			markSaveClean();


			$("#inputObjectId").val(objectId);
			$(".viewAsset").show();
			$(".deleteFile").removeAttr("disabled");
			if (history.pushState) {
				pathAddition = "";
				if(basePath != "/") {
					pathAddition = basePath;
				}
				var newurl = window.location.protocol + "//" + window.location.host + pathAddition + "/assetManager/editAsset/"  + objectId;
				window.history.pushState({path:newurl},'',newurl);
			}

		}
		else {
			bootbox.dialog({
				message: "An error was returned while saving your asset.  Check your internet connection and try saving again.  If it continues to fail, you may need to refresh the page.  Any unsaved changes will be lost.",
				title: "Error Occured During Save",
				backdrop: true,
				buttons: {

					cancel: {
						label: "OK",
						className: "btn-primary"
					}
				}
			});
		}
	}).fail(function() {
		bootbox.dialog({
			message: "An error was returned while saving your asset.  Check your internet connection and try saving again.  If it continues to fail, you may need to refresh the page.  Any unsaved changes will be lost.",
			title: "Error Occured During Save",
			backdrop: true,
			buttons: {

				cancel: {
					label: "OK",
					className: "btn-primary"
				}
			}
		});
	});
}

function loadPreviewAndUpdateTitle(objectId) {
	var source   = $("#minipreview-template").html();
	var template = Handlebars.compile(source);
	$.get( basePath + "asset/getAssetPreview/" + objectId,  function( data ) {
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
			$(".miniPreview").html(html);

			var title = responseObject.title;
			if(!title) {
				title = "";
			}

			var replaceableTitle  = document.title.split(" | ");

			document.title = "Edit Asset: " + title + " | " + replaceableTitle[1];


		}
	});
}
