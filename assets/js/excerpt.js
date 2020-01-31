
var embeddedStartTimeValue = null;
var embeddedEndTimeValue = null;
var suppressSeek = false;
var suppressCounter = 0;
$(document).ready(function() {
	jwplayer("videoElement").onTime(function (e) {

		if (!embeddedStartTimeValue || !embeddedEndTimeValue) {
			return;
		}
		suppressCounter++;
		if (suppressCounter > 10) {
			suppressCounter = 0;
			suppressSeek = false;
		}
		if (suppressSeek) {
			return;
		}

		if (e.position < (embeddedStartTimeValue - 5)) {
			jwplayer("videoElement").seek(embeddedStartTimeValue);
			suppressSeek = true;
		}
		if (e.position > embeddedEndTimeValue) {
			jwplayer("videoElement").seek(embeddedStartTimeValue);
			suppressSeek = true;
		}
	});
});


function setPlayBounds(startTime, endTime) {
	embeddedStartTimeValue = startTime;
	embeddedEndTimeValue = endTime;
}

function getTime() {
	var startTime = jwplayer("videoElement").getPosition();
	return startTime;
}

