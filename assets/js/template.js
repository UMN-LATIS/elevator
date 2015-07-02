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
