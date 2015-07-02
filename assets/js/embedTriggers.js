/**
 * If we're in an embed view, we may need to trigger views to kick off
 * normally the ajax that loads them takes care of this.
 */

$(document).ready(function() {
	if (typeof loadedCallback == 'function') {
		loadedCallback();
	}
});