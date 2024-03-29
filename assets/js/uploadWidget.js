

var format_size = function(num_bytes) {
    if(num_bytes <= 1024 * 0.8) {
        return num_bytes + " B";
    } else if(num_bytes <= 1024 * 1024 * 0.8) {
        return parseInt(num_bytes / 1024, 10) + "." + parseInt(num_bytes / 1024 * 10, 10) % 10 + " KB";
    } else if(num_bytes <= 1024 * 1024 * 1024 * 0.8) {
        return parseInt(num_bytes / 1024 / 1024, 10) + "." + parseInt(num_bytes / 1024 / 1024 * 10, 10) % 10 + " MB";
    } else {
        return parseInt(num_bytes / 1024 / 1024 / 1024, 10) + "." + parseInt(num_bytes / 1024 / 1024 / 1024 * 10, 10) % 10 + " GB";
    }
};

var uploadCount=0;

var uploadFile = function(fileElement, uploadSettings, containerElement) {
    var last_update = null;
    var last_uploaded = null;
    var fileObjectId = uploadSettings.fileObjectId;
    $(containerElement).find(".fileObjectId").val(fileObjectId);
    $(containerElement).find(".fileObjectId").trigger('change');
    var localContainer = containerElement;
    var chunkSize = 6*1024*1024;
    if(!pollingForNewImage) {
    	checkForNewImages();	
    }
    
    if(fastUpload) { // if we're in fast upload mode, use big chunks
        chunkSize = 200*1024*1024;
    }
    var settings = {
        key: uploadSettings.path + "/" + fileObjectId.split("").reverse().join("") +"-source",
        access_key: uploadSettings.bucketKey,
        ajax_base: basePath + "uploadBackend/upload",
        bucket: uploadSettings.bucket,
        collectionId: uploadSettings.collectionId,
        num_workers: 6,
        chunk_size: chunkSize,
        content_type: fileElement.type,
        max_size: 50 * (1 << 30), // 50gb
        on_error: function() {
            console.log("ERROR");
            $(localContainer).find(".log").prepend("Error occurred! You can help me fix this by filing a bug report here: https://github.com/cinely/mule-uploader/issues\n");
        },
        on_select: function(fileObj) {
            $(localContainer).find(".log").prepend("File selected\n");
        },
        on_start: function(fileObj) {
            window.uploadCount++;
            $(localContainer).find(".deleteFile").hide();
        },
        on_progress: function(bytesUploaded, bytesTotal) {
            if(!last_update || (new Date() - last_update) > 1000) {
                var percent = bytesUploaded / bytesTotal * 100;
                var speed = (bytesUploaded - last_uploaded) / (new Date() - last_update) * 1000;
                last_update = new Date();
                last_uploaded = bytesUploaded;
                $(localContainer).find('.progress .progress-bar').width(percent / 100 * $(localContainer).find('.progress').width());
                // var log = "Upload progress: " + format_size(bytesUploaded) + " / " + format_size(bytesTotal) + " (" + parseInt(percent, 10) + "." + parseInt(percent * 10, 10) % 10 + "%)";
                // if(speed) {
                //     log += "; speed: " + format_size(speed) + "/s";
                // }
                //$(parentElement).find(".log").prepend(log + "\n");
            }
        },
        on_init: function() {
            //$(parentElement).find(".log").prepend("Uploader initialized\n");
        },
        on_complete: function() {
            window.uploadCount--;
            $.get(basePath+ 'assetManager/completeSourceFile/' + fileObjectId , function(data) {
                $(localContainer).find(".uploadProgress").hide();
            });
            $(localContainer).find(".cancelButton").hide();
            $(localContainer).find(".deleteFile").show();
            $(localContainer).find(".allowResume").hide();

        },
        on_chunk_uploaded: function() {
           // $(parentElement).find(".log").prepend("Chunk finished uploading\n");
        }
    };
    upload = mule_upload(settings);
    $("#collectionId").attr("disabled", true);
    $(localContainer).find(".uploadProgress").show();
    upload.upload_file(fileElement, false);
    return upload;
};


var resetCollection = function() {
    if($("#collectionId").val() != "---" && $("#collectionId").val() !=="" && $("#collectionId").val() != -1) {
        $(".uploadInformation").show();
        $(".uploadWarning").hide();
    }
    else {
        $(".uploadInformation").hide();
        $(".uploadWarning").show();
    }
};

$(document).on("change", "#collectionId", function() {
    resetCollection();
});

$(document).on("click", ".deleteData", function() {

    var fileId = $(this).closest(".widgetContents").find(".fileObjectId").val();
    var self = this;
    $.get(basePath+"fileManager/removeData/"+fileId,function(data) {
        $(self).closest(".widgetContents").find(".extractedData").val("");
    });
});

$(document).on("click", ".deleteFile", function() {

    var self = this;
    bootbox.dialog({
        message: "Delete this upload object?  This action cannot be undone and will delete the source media and any derivatives.",
        title: "Delete Object?",
        backdrop: true,
        buttons: {
            success: {
                label: "Delete",
                className: "btn-danger",
                callback: function() {
                    var fileObjectId = $(self).closest(".widgetContents").find(".fileObjectId").val();
                    $.post(basePath+'fileManager/deleteFileObject', { fileObjectId: fileObjectId }, function(data, textStatus, xhr) {
                        if(data == "success") {
                            $(self).closest(".widgetContents").find(".fileObjectId").val("");
                            $(self).closest(".widgetContents").find(".fileDescription").val("");
                            $(self).closest(".widgetContents").find(".extractedData").val("");
                            $(self).closest(".widgetContents").find(".deleteFile").hide();
                            $(self).closest(".widgetContents").find(".fileLabel").html("Deleted");
                            $(".fileObjectId").trigger("change");
                        }
                        else if(data == "notfound") {
                            $(self).closest(".widgetContents").find(".fileObjectId").val("");
                            $(self).closest(".widgetContents").find(".fileDescription").val("");
                            $(self).closest(".widgetContents").find(".extractedData").val("");
                            $(".fileObjectId").trigger("change");

                        }
                        else {
                            bootbox.dialog({message:"Deletion Error: please reload and try again."});

                        }

                    });
                }
            },
            cancel: {
                label: "Cancel",
                className: "btn-default"
            }
        }
    });


});

function loadSidecars(fileIdElement) {
    fileId = $(fileIdElement).val();

    // already have sidecars, don't load another
    if($(fileIdElement).closest('.widgetContents').find(".sidecars").html().length > 0) {
        return;
    }

    var rootFormField = $(fileIdElement).closest('.widgetContents').find(".rootFormField").val() + "[sidecars]";

    $.post(basePath + 'fileManager/getSidecarViewForObject/', {fileId: fileId, rootFormField: rootFormField}, function(data) {
        $(fileIdElement).closest('.widgetContents').find(".sidecars").append(data);
    });

}


var fileObjectPreview = function(targetElement) {
    var imageContainer = $(targetElement).closest(".widgetContents").find(".imagePreview");
    if($(targetElement).val().length>0) {
        $("#collectionId").attr("disabled", true);
        $(targetElement).closest('.widgetContents').find(".file").attr("disabled", true);
        var self = targetElement;
        loadSidecars(self);
    }
    else {
        // we emptied the fileObject, let's clear things out.
        $(targetElement).closest('.widgetContents').find(".file").removeAttr("disabled");
        $("#collectionId").removeAttr("disabled");
        $(imageContainer).hide();
    }
};

var pollingForNewImage = false;

var checkForNewImages = function() {
	pollingForNewImage = true;

	checkArray = new Array();
	$(".imagePreview img").each(function(index, el) {

		if($(el).data("fileready") !== true) {
			var fileObjectId = $(el).closest(".widgetContents").find(".fileObjectId").val();
			if(fileObjectId) {
				checkArray.push(fileObjectId);	
			}
			
		}
		else {
			container = $(el).closest(".widgetContents").find(".imagePreview");
			$(container).show();
		}

	});

	if(checkArray.length > 0) {
		$.post(basePath + "fileManager/previewImageAvailable/", {checkArray: JSON.stringify(checkArray)}, function(data) {

			decoded = JSON.parse(data);
			if(!decoded) {
				return;
			}

			$(decoded).each(function(index, content) {

				container = $('.fileObjectId').filter(function(index) {
					return $(this).val() == content.fileId;
				}).closest(".widgetContents").find(".imagePreview");

				if (content.status == "false") {
	                // data.redirect contains the string URL to redirect to
	                $(container).find("img").attr("src", "/assets/images/processing.gif");
	                $(container).show();
	            }
	            else if(content.status == "true") {
	                $(container).find("img").attr("src", basePath + "fileManager/previewImageByFileId/" + content.fileId + "?" + Math.random());
	                $(container).find("img").data("fileready", true);
	                $.get(basePath+"fileManager/extractedData/"+content.fileId,function(data) {
	                    $(container).closest(".widgetContents").find(".extractedData").val(data);
	                });

	                $(container).show();
	            }
	            else if(content.status == "icon") {
	                // if it's an icon, we say true but keep polling
	                if(!$(container).find("img").data("icon")) {
	                	$(container).find("img").data("icon", true);
	                	$(container).find("img").attr("src", basePath + "fileManager/previewImageByFileId/" + content.fileId);
	                	$(container).show();	
	                }
	                
	            }
			});
			setTimeout(checkForNewImages, 4000);
		});
	}
	else {
		pollingForNewImage = false;
	}
	


	
}


$(document).on("change", ".fileObjectId", function() {
    fileObjectPreview(this);
    if($(this).val() !== "") {
        submitFormWithDelay(true);
    }
    // force a save when the fileobjectId changes

});

$(document).on("click", ".processingLogs", function(e) {
     e.preventDefault();
    var parentElement = $(this).closest(".widgetContents");
    var fileObjectId = $(parentElement).find(".fileObjectId").val();
    var win = window.open(basePath + "assetManager/processingLogsForAsset/" + fileObjectId, '_blank');
    if(win){
        //Browser has allowed it to be opened
        win.focus();
    }

});

$(document).ready(function(){
    $( "#collectionId" ).trigger( "change" );
    $(".file").trigger("change");
    checkForNewImages();
    $(".fileObjectId").each(function(index, el) {
        // load previews for all our file objects
        fileObjectPreview(el);

        if($(el).val() !== "") {
            $(el).closest(".widgetContents").find(".deleteFile").show();
        }

    });



    if($(".allowResume").length > 0) {
        $(".allowResume").each(function() {

            $(this).closest(".widgetContents").find(".file").removeAttr("disabled");
            $(this).closest(".widgetContents").find(".startbutton").removeAttr("disabled");

        });
    }

});

$(document).on("change", ".file", function() {
    if($(this).val() !== "") {
        $(this).closest(".widgetContents").find(".cancelButton").show();
        startUpload(this);
    }
    else {
        $(this).closest(".widgetContents").find(".cancelButton").hide();
    }

});

$(document).on("click", ".cancelButton", function() {
    var parentElement = $(this).closest(".widgetContents");
    var fileElement = $(parentElement).find(".file");
    var upload = $(fileElement).data("uploader");
    upload.cancel();
    window.uploadCount--;
    $.get(basePath+ 'assetManager/cancelSourceFile/' + $(parentElement).find(".fileObjectId").val() , function(data) {
        $(parentElement).find(".uploadProgress").hide();
    });
    $(parentElement).find(".cancelButton").hide();
    $(parentElement).find(".fileObjectId").val("");
    $(this).hide();
    $(fileElement).removeAttr("disabled");

});

function startUpload(targetFrame) {
    // only get the first file for now
    var collectionId = $("#collectionId").val();
    var parentElement = $(targetFrame).closest(".widgetContentsContainer");
    var fileElement = $(parentElement).find(".file");

    if(!$.isNumeric(collectionId)) {
        //TODO
        console.log("error");
    }

    jQuery.ajaxSetup({async:false}); // run sync so we wait for the next element
    if($(fileElement).prop("files").length > 1) {
        target = addAnother(targetFrame, $(fileElement).prop("files").length - 1);    
    }
    
    $(target).find(".cancelButton").show();

    jQuery.ajaxSetup({async:true});
    
    var responseArray = [];
    var sendingArray = [];
    var target = null;
    $.each($(fileElement).prop("files"), function(index, el) {
        if(index === 0) { //operate on the real one
            target = parentElement;
        }
        else {
            target = target.nextAll(".panel").eq(0);
        }

        var file = el;
        var internalTarget = target;
        fileObjectId = $(target).find(".fileObjectId").val();
        sendingArray[index] = {index: index, collectionId: collectionId, filename: file.name, fileObjectId: fileObjectId, file:file};
        responseArray[index] = {index: index, collectionId: collectionId, filename: file.name, fileObjectId: fileObjectId, target: internalTarget, file:file};
    });

    $.post(basePath+ 'assetManager/getFileContainer', {containers: JSON.stringify(sendingArray)}, function(data, textStatus, xhr) {
        uploadSettings = $.parseJSON(data);

        $(uploadSettings).each(function(index, item) {
            var sourceArray = responseArray[item.index];
            $(fileElement).data('uploader', uploadFile(sourceArray.file, item, sourceArray.target));
        });
    
    });

   
    return;




}
