
$(document).ready(function() {
	if(typeof startTimeValue !== "undefined" && typeof endTimeValue !== "undefined") {
		// we know we're playing an excerpt, so make sure the "Create excerpt" button his hidden
		$(".createExcerpt").hide();
		$(".movieMetadata").hide();
		var suppressSeek = false;
		var suppressCounter = 0;
		jwplayer("videoElement").onTime(function(e) {
			// flash only seeks on keyframes, so we suppress seeking for a bit in case we end up seeking before our target time
			suppressCounter++;
			if(suppressCounter>10) {
				suppressCounter = 0;
				suppressSeek = false;
			}
			if(suppressSeek) {
				return;
			}

			if(e.position < (startTimeValue-5)) {
				jwplayer("videoElement").seek(startTimeValue);
				suppressSeek = true;
			}
			if(e.position > endTimeValue) {
				jwplayer("videoElement").seek(startTimeValue);
				suppressSeek = true;
				jwplayer("videoElement").stop();
			}
		});
	}

});


$(document).ready(function() {
  $(document).on("click", ".setStart", function() {
    var startTime = jwplayer("videoElement").getPosition();
    $("#startTime").val(startTime);
    $("#startTimeVisible").val(startTime);
    return false;
  });

  $(document).on("click", ".setEnd", function() {
    var endTime = jwplayer("videoElement").getPosition();
    $("#endTime").val(endTime);
    $("#endTimeVisible").val(endTime);
    return false;
  });

  $(document).on("submit", ".excerptForm", function () {
    $.post(basePath+ "drawers/addToDrawer", $("#excerptForm").serialize(), function(data, textStatus, xhr) {
      $("#excerptGroup").collapse('hide');
      $("#endTime").val();
      $("#endTimeVisible").val();
      $("#startTime").val();
      $("#startTimeVisible").val();
      $("#label").val();
    });

    return false;
  });

});