/*
 * serializeForm
 * https://github.com/danheberden/serializeForm
 *
 * Copyright (c) 2012 Dan Heberden
 * Licensed under the MIT, GPL licenses.
 */
(function( $ ){
  $.fn.serializeForm = function() {

    // don't do anything if we didn't get any elements
    if ( this.length < 1) {
      return false;
    }

    var data = {};
    var lookup = data; //current reference of data
    var selector = ':input[type!="checkbox"][type!="radio"], input:checked';
    var parse = function() {

      // data[a][b] becomes [ data, a, b ]
      var named = this.name.replace(/\[([^\]]+)?\]/g, ',$1').split(',');
      var cap = named.length - 1;
      var $el = $( this );

      // Ensure that only elements with valid `name` properties will be serialized
      if ( named[ 0 ] ) {
        for ( var i = 0; i < cap; i++ ) {
          // move down the tree - create objects or array if necessary
          lookup = lookup[ named[i] ] = lookup[ named[i] ] ||
            ( named[ i + 1 ] === "" ? [] : {} );
        }

        // at the end, push or assign the value
        if ( lookup.length !==  undefined ) {
          lookup.push( $el.val() );
        }else {
          lookup[ named[ cap ] ]  = $el.val();
        }

        // assign the reference back to root
        lookup = data;
      }
    };

    // first, check for elements passed into this function
    this.filter( selector ).each( parse );

    // then parse possible child elements
    this.find( selector ).each( parse );

    // return data
    return data;
  };
}( jQuery ));



function modifyRange(event) {
	if($(this).val() == 1) {
		$(this).closest(".widgetContents").find('.startLabel').text("Start");
		$(this).closest(".widgetContents").find('.dateRange').show('fast', function() {

		});
	}
	else {
		$(this).closest(".widgetContents").find('.startLabel').text("Date");
		$(this).closest(".widgetContents").find('.dateRange').hide('fast', function() {

		});
	}

}

function parseDateString(dateString, event) {
	if(dateString.length > 0) {
		if(Date.create(dateString).isValid()) {
			var dateNumeric = Date.utc.create(dateString).getTime()/1000;
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

					var bceDate = (-1 * yearsAgo * 31556952) - (1970*31556952); // seconds in a year
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
	$(document).on("blur", ".dateText", parseDate);
});
$.mapGetScript = function(url, callback, cache){
	return $.ajax({
		type: "GET",
		url: url,
		success: callback,
		dataType: "script",
		cache: false,
		async: false
	});
};


$(document).ready(function() {
	/**
	 * don't load the map js stuff until we need the advancedSearch modal
	 * @param  {[type]} e [description]
	 * @return {[type]}   [description]
	 */
	$("#advancedSearchModal").on("show.bs.modal" ,function(e) {
		if(!jQuery().goMap) {
			var doc_write = document.write; // Remember original method;
			document.write = function(s) {$(s).appendTo('body');};
			$.mapGetScript("http://maps.google.com/maps/api/js?sensor=false").done(function() {
				setTimeout(function() {
					document.write = doc_write;
					$.mapGetScript("/assets/js/jquery.gomap-1.3.2.js", function(){}, true);
					$.mapGetScript("/assets/js/markerclusterer.js", function(){}, true);
					$.mapGetScript("/assets/js/mapWidget.js", function(){}, true);
				}, 300);
			});
		}
	});

});


var cachedWidth = 0;

 function scrollManage() {
  if(document.documentElement.clientWidth == cachedWidth) {
    return;
  }
  cachedWidth = document.documentElement.clientWidth;
  if(!$('.navbar').length) {
    return;
  }
    var top = $('.navbar').offset().top - parseFloat($('.navbar').css('margin-top').replace(/auto/, 0));

   var _height = $('.navbar').height();
   if(top === 0) {

    if(document.documentElement.clientWidth > 768) {
      $('.mainContent').css('padding-top', 20 + parseInt($('.navbar').css('height')));
    }
    else {
       $('.mainContent').css('padding-top', 0);
    }
        $('.navbar').addClass('navbar-fixed-top');
  }
  else {
   $(window).scroll(function(event) {
    var y = $(this).scrollTop();
    var z = $('.footer').offset().top;

    if(z > $( document ).height()) {
      return;
    }
    if (y >= top && (y+_height) < z) {

      if(document.documentElement.clientWidth > 768) {
        $('.mainContent').css('padding-top', parseInt($('.navbar').css('height')) + 20);
      }
      else {
         $('.mainContent').css('padding-top', 0);
      }
      $('.navbar').addClass('navbar-fixed-top');
    } else {

      if(document.documentElement.clientWidth > 768) {
        $('.mainContent').css('padding-top', 0);
      }
      $('.navbar').removeClass('navbar-fixed-top');
    }
  });
  }
}

// handle sticky header regardless of header size.
$(window).load(function() {
  scrollManage();
});


$(window).resize(function() {
  scrollManage();
});

// command-control-h reveals hidden content (anything flagged advancedContent)
Mousetrap.bind('command+ctrl+h', function() {
  $(".advancedContent").toggle();
});
