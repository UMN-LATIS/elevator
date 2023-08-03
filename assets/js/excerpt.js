
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
		PAUSE_PLAYER: 'PAUSE_PLAYER',
	}

	const responses = {
		MEDIAPLAYER_READY: 'MEDIAPLAYER_READY',
		CURRENT_SCRUBBER_POSITION: 'CURRENT_SCRUBBER_POSITION',
		SET_PLAY_BOUNDS_SUCCESS: 'SET_PLAY_BOUNDS_SUCCESS',
		PAUSE_PLAYER_SUCCESS: 'PAUSE_PLAYER_SUCCESS',
	}

	function requestHandler(event) {
		log('message received:', event.data?.type ?? 'unknown' , event.data?.payload ?? '');
		const { type } = event.data;
		if (type === requests.SET_PLAY_BOUNDS) {
			return setPlayBounds(event.data.payload.startTime, event.data.payload.endTime);
		}
		if (type === requests.GET_SCRUBBER_POSITION) {
			return sendScrubberPosition();
		}
		if (type === requests.PAUSE_PLAYER) {
			return pausePlayer();
		}
	}

	function pausePlayer() {
		const player = jwplayer("videoElement");
		if (player.getState() === 'playing') {
			player.pause();
		}
		window.parent.postMessage({
			type: responses.PAUSE_PLAYER_SUCCESS,
			payload: player.getState(),
		}, '*');
	}

	function sendScrubberPosition() {
		window.parent.postMessage({
			type: responses.CURRENT_SCRUBBER_POSITION,
			payload: getTime(),
		}, '*');
	}

	function setPlayBounds(startTime, endTime) {
		embeddedStartTimeValue = startTime;
		embeddedEndTimeValue = endTime;
		const player = jwplayer("videoElement");
		player.once('play', () => player.seek(startTime));

		window.parent.postMessage({
			type: responses.SET_PLAY_BOUNDS_SUCCESS,
		}, '*');
	}

	function sendMediaPlayerReady() {
		window.parent.postMessage({
			type: responses.MEDIAPLAYER_READY,
		}, '*');
	}

	// sends the current scrubber position to the parent window
	// if changes are detected
	let previousScrubberPosition = null;
	function sendScrubberChanges(ms = 1000) {
		const currentScubberPosition = getTime();
		if (currentScubberPosition === previousScrubberPosition) {
			return setTimeout(sendScrubberChanges, ms);
		}
		sendScrubberPosition();
		previousScrubberPosition = currentScubberPosition;
		return setTimeout(sendScrubberChanges, ms);
	}

	$(function () {
		// once the document is ready, setup the iframe messaging
		window.addEventListener('message', requestHandler);
		
		// send any current scrubber position to parent window
		sendScrubberChanges();

		// let parent window knows the media player is ready
		sendMediaPlayerReady();
	});
})();
