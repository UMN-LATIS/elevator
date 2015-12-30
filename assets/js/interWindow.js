
var targetFieldId = "";
var hasParent = false;
var unsavedChildren = 0;

$(document).on("click", ".newAssetButton", function(event) {
	event.preventDefault();
	var targetTemplate = $(this).closest(".widgetContents").find(".templateSelector").first().val();
	var targetCollection = $("#collectionId").val();
	var targetField = $(this).closest(".widgetContents").find(".targetAsset").first().attr('id');
	$(this).attr("disabled", true);
	unsavedChildren++;
	var windowPointer = window.open(basePath +"assetManager/addAsset/"+targetTemplate + "/" + targetCollection);
	windowPointer.onload = function( ){
		var targetInfo = { 'status':'open', 'targetField': targetField };
		windowPointer.postMessage(JSON.stringify(targetInfo), "*");
	};

});

function notifyParentOfSave(objectId) {
	var statusNotice = { 'objectId': objectId, 'status':'saved', 'targetFieldId': targetFieldId};
	var targetElement;

	if(window.opener !== null) {
		targetElement = window.opener;
	}
	else {
		// we're in an iframe!
		targetElement = parent;
	}

	targetElement.postMessage(JSON.stringify(statusNotice), "*");
}

$( window ).unload(function() {
	if(hasParent) {
		var statusNotice = { 'objectId': null, 'status':'closed', 'targetFieldId': targetFieldId};
		window.opener.postMessage(JSON.stringify(statusNotice), "*");
	}

});


function listener(event) {
	if(!(typeof event.data === 'string' || event.data instanceof String)) {
		return;
	}
	var messageObject = JSON.parse(event.data);
	if(event.data ) {
		if(messageObject.status == 'open') {
			hasParent = true;
			targetFieldId = messageObject.targetField;
		}
		// TODO, update the UI with this info
		if(messageObject.status == 'saved') {
			// First Save, don't need to count this one anymore
			if($("#"+messageObject.targetFieldId).val() != messageObject.objectId) {
				unsavedChildren--;
			}

			$("#"+messageObject.targetFieldId).val(messageObject.objectId);
			relatedAssetPreview(messageObject.objectId, $("#"+messageObject.targetFieldId).closest(".widgetContents"));
			submitForm();
		}
		if(messageObject.status == 'closed') {
			if($("#"+messageObject.targetFieldId).val() === "") {
				unsavedChildren--;
				$("#"+messageObject.targetFieldId).closest(".widgetContents").find(".newAssetButton").removeAttr("disabled");
			}

		}

	}

}

if (window.addEventListener){

	addEventListener("message", listener, false);
	console.log("listener added");

} else {
  attachEvent("onmessage", listener);
}
