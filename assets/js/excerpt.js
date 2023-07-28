
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
	return jwplayer("videoElement").getPosition();
}

// listen for message from parent window to set start and end times
window.addEventListener('message',  function handleSetPlayBoundsMessage(event) {
	if (event.data.type !== 'setPlayBounds') {
		return;
	}

	if (Number.isNaN(event.data.startTime) || Number.isNaN(event.data.endTime)) {
		console.log(event.data.startTime, event.data.endTime);
		throw new Error('start and end times must be set to set play bounds');
	}

	console.log('setting play bounds', event.data.startTime, event.data.endTime);

	setPlayBounds(event.data.startTime, event.data.endTime);
	
	// once we've set the play bounds, 
	// we don't need to listen for this message anymore
	// window.removeEventListener('message', handleSetPlayBoundsMessage);
});

window.addEventListener('message',  function handleGetTimeMessage(event) {
	if (event.data.type !== 'getTime') {
		return;
	}

	window.parent.postMessage({
		type: 'getTimeResponse',
		currentScrubberPosition: getTime(),
	}, '*');
});