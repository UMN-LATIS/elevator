
function setupButtons(studyViewer) {
    // Get the button elements
    var buttons = $(studyViewer).find('button');

    // Tool button event handlers that set the new active tool

    // WW/WL
    $(buttons[0]).on('click touchstart', function() {
        disableAllTools();
        forEachViewport(function(element) {
            cornerstoneTools.wwwc.activate(element, 1);
            cornerstoneTools.wwwcTouchDrag.activate(element);
        });
    });

    // Invert
    $(buttons[1]).on('click touchstart', function() {
        disableAllTools();
        forEachViewport(function(element) {
            var viewport = cornerstone.getViewport(element);
            // Toggle invert
            if (viewport.invert === true) {
                viewport.invert = false;
            } else {
                viewport.invert = true;
            }
            cornerstone.setViewport(element, viewport);
        });
    });

    // Zoom
    $(buttons[2]).on('click touchstart', function() {
        disableAllTools();
        forEachViewport(function(element) {
            cornerstoneTools.zoom.activate(element, 5); // 5 is right mouse button and left mouse button
            cornerstoneTools.zoomTouchDrag.activate(element);
        });
    });

    // Pan
    $(buttons[3]).on('click touchstart', function() {
        disableAllTools();
        forEachViewport(function(element) {
            cornerstoneTools.pan.activate(element, 3); // 3 is middle mouse button and left mouse button
            cornerstoneTools.panTouchDrag.activate(element);
        });
    });

    // Stack scroll
    $(buttons[4]).on('click touchstart', function() {
        disableAllTools();
        forEachViewport(function(element) {
            cornerstoneTools.stackScroll.activate(element, 1);
            cornerstoneTools.stackScrollTouchDrag.activate(element);
        });
    });

    // Length measurement
    $(buttons[5]).on('click touchstart', function() {
        disableAllTools();
        forEachViewport(function(element) {
            cornerstoneTools.length.activate(element, 1);
        });
    });

    // Angle measurement
    $(buttons[6]).on('click touchstart', function() {
        disableAllTools();
        forEachViewport(function(element) {
            cornerstoneTools.angle.activate(element, 1);
        });
    });

    // Pixel probe
    $(buttons[7]).on('click touchstart', function() {
        disableAllTools();
        forEachViewport(function(element) {
            cornerstoneTools.probe.activate(element, 1);
        });
    });

    // Elliptical ROI
    $(buttons[8]).on('click touchstart', function() {
        disableAllTools();
        forEachViewport(function(element) {
            cornerstoneTools.ellipticalRoi.activate(element, 1);
        });
    });

    // Rectangle ROI
    $(buttons[9]).on('click touchstart', function() {
        disableAllTools();
        forEachViewport(function (element) {
            cornerstoneTools.rectangleRoi.activate(element, 1);
        });
    });

    // Play clip
    $(buttons[10]).on('click touchstart', function() {
        var frameRate;
        if(typeof stack !== 'undefined' && typeof stack.frameRate !== 'undefined') {
            frameRate = stack.framerate;
        }
        else {
            frameRate = 10;
        }

        forEachViewport(function(element) {
            cornerstoneTools.playClip(element, frameRate);
        });
    });

    // nexdt frame clip
    $(document).on("click touchstart", ".dicomControls .step-forward", function() {

        forEachViewport(function(element) {
            var toolData = cornerstoneTools.getToolState(element, 'stack');
            if(toolData === undefined || toolData.data === undefined || toolData.data.length === 0) {
             return;
            }

            var stackData = toolData.data[0];

            var newImageIdIndex = stackData.currentImageIdIndex + 1;
            if(newImageIdIndex == stackData.imageIds.length) {
                newImageIdIndex = 0;
            }
            newImageIdIndex = Math.min(stackData.imageIds.length - 1, newImageIdIndex);
            newImageIdIndex = Math.max(0, newImageIdIndex);

            if(newImageIdIndex !== stackData.currentImageIdIndex)
            {
                var viewport = cornerstone.getViewport(element);
                cornerstone.loadAndCacheImage(stackData.imageIds[newImageIdIndex]).then(function(image) {
                    stackData = toolData.data[0];
                    if(stackData.newImageIdIndex !== newImageIdIndex) {
                        stackData.currentImageIdIndex = newImageIdIndex;
                        cornerstone.displayImage(element, image, viewport);
                    }
                });
            }
            });
    });

    // nexdt frame clip
    $(document).on("click touchstart", ".dicomControls .step-backward", function() {

        forEachViewport(function(element) {
            var toolData = cornerstoneTools.getToolState(element, 'stack');
            if(toolData === undefined || toolData.data === undefined || toolData.data.length === 0) {
             return;
            }

            var stackData = toolData.data[0];

            var newImageIdIndex = stackData.currentImageIdIndex - 1;
            if(newImageIdIndex < 0) {
                newImageIdIndex = stackData.imageIds.length - 1;
            }
            newImageIdIndex = Math.min(stackData.imageIds.length - 1, newImageIdIndex);
            newImageIdIndex = Math.max(0, newImageIdIndex);

            if(newImageIdIndex !== stackData.currentImageIdIndex)
            {
                var viewport = cornerstone.getViewport(element);
                cornerstone.loadAndCacheImage(stackData.imageIds[newImageIdIndex]).then(function(image) {
                    stackData = toolData.data[0];
                    if(stackData.newImageIdIndex !== newImageIdIndex) {
                        stackData.currentImageIdIndex = newImageIdIndex;
                        cornerstone.displayImage(element, image, viewport);
                    }
                });
            }
            });
    });

    $(document).on("click touchstart", ".dicomControls .fullscreen", function() {
        if($.fullscreen.isFullScreen()) {
            $.fullscreen.exit();
        }
        else {
            $(".dicomEnclosure").width("100%");
            $(".dicomEnclosure").height("100%");
            $(".viewer").width("100%");
            $(".viewer").height("100%");
            $(".dicomViewer").fullscreen();
            $(".dicomViewer").on("fscreenclose", function() {
                $(".dicomEnclosure").width("100%");
                $(".dicomEnclosure").height("500px");
                $(".viewer").width("660px");
                $(".viewer").height("600px");
            });
        }


    });

    // Stop clip
    $(document).on('click touchstart', ".dicomControls .stop", function() {
        forEachViewport(function(element) {
            cornerstoneTools.stopClip(element);
        });
    });

    // Tooltips
    $(buttons[0]).tooltip();
    $(buttons[1]).tooltip();
    $(buttons[2]).tooltip();
    $(buttons[3]).tooltip();
    $(buttons[4]).tooltip();
    $(buttons[5]).tooltip();
    $(buttons[6]).tooltip();
    $(buttons[7]).tooltip();
    $(buttons[8]).tooltip();
    $(buttons[9]).tooltip();
    $(buttons[10]).tooltip();
    $(buttons[11]).tooltip();
    $(buttons[12]).tooltip();

};