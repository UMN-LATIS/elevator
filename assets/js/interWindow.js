
var targetFieldId = "";
var hasParent = false;
var unsavedChildren = 0;

$(document).on("click", ".newAssetButton", function(event) {
	event.preventDefault();
	var targetTemplate = $(this).closest(".widgetContents").find(".templateSelector").first().val();
	var targetCollection = $("#collectionId").val();
	var targetField = $(this).closest(".widgetContents").find(".targetAsset").first().attr('id');
	unsavedChildren++;
	var windowPointer = window.open(basePath +"assetManager/addAsset/"+targetTemplate + "/" + targetCollection);
	windowPointer.onload = function( ){
		var targetInfo = { 'status':'open', 'targetField': targetField };
		windowPointer.postMessage(JSON.stringify(targetInfo), "*");
	};

});

function notifyParentOfSave(objectId) {
	var statusNotice = { 'objectId': objectId, 'status':'saved', 'targetFieldId': targetFieldId};
	window.opener.postMessage(JSON.stringify(statusNotice), "*");
}

$( window ).unload(function() {
	if(hasParent) {
		var statusNotice = { 'objectId': null, 'status':'closed', 'targetFieldId': targetFieldId};
		window.opener.postMessage(JSON.stringify(statusNotice), "*");
	}

});


function listener(event) {
	// if(event.origin !== "http://localhost") {
	// 	console.log("bad origin: " + event.origin);
	// 	return;
	// }
	var messageObject = JSON.parse(event.data);

	if(event.data ) {

		if(messageObject.status == 'open') {
			hasParent = true;
			targetFieldId = messageObject.targetField;
		}
		if(messageObject.status == 'saved') {
			// First Save, don't need to count this one anymore
			if($("#"+messageObject.targetFieldId).val() != messageObject.objectId) {
				unsavedChildren--;
			}
			$("#"+messageObject.targetFieldId).val(messageObject.objectId);
			submitForm();
		}
		if(messageObject.status == 'closed') {
			if($("#"+messageObject.targetFieldId).val() === "") {
				unsavedChildren--;
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
