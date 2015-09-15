

// Load in HTML templates

function loadElement(targetElement, studyJSON) {
  if(typeof studyViewerTemplate == 'undefined' || typeof viewportTemplate == 'undefined') {
    return;
  }
      var studyViewerCopy = studyViewerTemplate.clone();

      var viewportCopy = viewportTemplate.clone();
      studyViewerCopy.find('.imageViewer').append(viewportCopy);


      studyViewerCopy.attr("id", 'x' + "dicomViewer");
      studyViewerCopy.appendTo(targetElement);
      // Now load the study.json

      loadStudy(studyViewerCopy, studyJSON);
}

$(document).ready(function() {
  // Prevent scrolling on iOS
  $(document).on("mousewheel DOMMouseScroll",".viewer", function(e) {
    e.preventDefault();
  });

});
