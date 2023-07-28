
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

/**
 * The excerpt media player is loaded in an iframe, and we'll
 * need to communicate with the parent window to set the play bounds
 * and get the current scrubber position.
 * This sets up some some simple request/response messaging between
 * between the iframe and the parent window.
 */
(function setupIframeMessaging() {
	const log = (...args) => console.log('[IFRAME] ', ...args);

	const requests = {
		SET_PLAY_BOUNDS: 'SET_PLAY_BOUNDS',
		GET_SCRUBBER_POSITION: 'GET_SCRUBBER_POSITION',
	}

	const responses = {
		MEDIAPLAYER_READY: 'MEDIAPLAYER_READY',
		CURRENT_SCRUBBER_POSITION: 'CURRENT_SCRUBBER_POSITION',
		SET_PLAY_BOUNDS_SUCCESS: 'SET_PLAY_BOUNDS_SUCCESS',
	}

	function requestHandler(event) {
		log('message received:', event.data?.type ?? 'unknown' , event.data?.payload ?? '');
		const { type } = event.data;
		if (type === requests.SET_PLAY_BOUNDS) {
			return setPlayBounds(event.data.payload.startTime, event.data.payload.endTime);
		}
		if (type === requests.GET_SCRUBBER_POSITION) {
			return getScrubberPosition();
		}
	}

	function getScrubberPosition() {
		window.parent.postMessage({
			type: responses.CURRENT_SCRUBBER_POSITION,
			payload: getTime(),
		}, '*');
	}

	function setPlayBounds(startTime, endTime) {
		embeddedStartTimeValue = startTime;
		embeddedEndTimeValue = endTime;

		window.parent.postMessage({
			type: responses.SET_PLAY_BOUNDS_SUCCESS,
		}, '*');
	}

	function sendMediaPlayerReady() {
		window.parent.postMessage({
			type: responses.MEDIAPLAYER_READY,
		}, '*');
	}

	// bootstrap messaging request/response
	$(function () {
		// listen for messages from parent window once the document is ready
		window.addEventListener('message', requestHandler);

		// when jwplayer is ready, send message to parent window
		// so that the parent window knows it can start sending messages
		jwplayer("videoElement").onReady(sendMediaPlayerReady);
	});
})();
