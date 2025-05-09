



function modifyRange(event) {
	if($(this).val() == 1) {
		$(this).closest(".widgetContents").find('.startLabel').text("Start");
		// Firefox fails to handle animation when items are off-screen.  Disable animation.
		// TODO: refactor this in a smarter way.
		if(!(window.mozInnerScreenX == null)) {
			$(this).closest(".widgetContents").find('.dateRange').show();
		}
		else {
			$(this).closest(".widgetContents").find('.dateRange').show('fast', function() {

			});	
		}
		
	}
	else {
		$(this).closest(".widgetContents").find('.startLabel').text("Date");
		if(!(window.mozInnerScreenX == null)) {
			$(this).closest(".widgetContents").find('.dateRange').hide();
		}
		else {
			$(this).closest(".widgetContents").find('.dateRange').hide('fast', function() {

			});
		}
		
	}

}


function parseDateString(dateString, event) {
	if(dateString.length > 0) {
		if(Sugar.Date.isValid(Sugar.Date.create(dateString))) {
			var dateNumeric = Sugar.Date.create(dateString, {fromUTC: true}).getTime()/1000;
			if(event) {
				$(event.target).siblings('.dateHidden').val(dateNumeric);
			}
			return true;
		}
		else {
			if(dateString.toLowerCase().indexOf('bc') != -1) {
				dateString = dateString.replace(/,/g, '');
				var pattern = /[0-9]+/g;
				var matches = dateString.match(pattern);
				if(matches.length>0) {
					var yearsAgo = matches[0];
					if(dateString.toLowerCase().indexOf('century') != -1) {
						yearsAgo = yearsAgo * 100;
					}

					var bceDate = (-1 * yearsAgo * 31556900) - (1970*31556900); // seconds in a year
					if(event) {
						$(event.target).siblings('.dateHidden').val(bceDate);
					}

					return true;
				}
			}
			else {
				//TODO: make this trigger parse error instead!
				return false;
			}
		}
	}
	else {
		$(event.target).siblings('.dateHidden').val("");
	}
}

function parseDate(event) {
	var dateString = $(this).val();
	parseDateString(dateString, event);
}

$(document).ready(function() {
	$(document).on( "change", ".rangeModify", modifyRange );
	$( ".rangeModify" ).trigger( "change" );
	$(document).on("change", ".dateText", parseDate);
});
