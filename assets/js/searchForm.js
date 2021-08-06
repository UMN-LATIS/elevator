

function resetSearchFrame() {
    $("#results").empty();
    $("#listResults").empty();
    $(".frame ul").empty();
    $(".suggest").empty();
    if(galleryFrame) {
        galleryFrame.destroy();
        galleryFrame = null;    
    }
    $(".galleryIframe").attr("src","");
    
}

var performSearchForButtonPress = function(targetForm) {

    resetSearchFrame();
    searchId = "";
    if(window.location.hash.length > 0) {
        // for now, let's try not persisting search results?
    //    searchId = window.location.hash.substring(1);
    }

    currentPageNumber = 0;
    previousEventComplete = false;
    $.post( basePath + "search/searchResults/" + searchId, {searchQuery:JSON.stringify($( targetForm ).serializeForm())}, function( data ) {
        try{
            cachedResults = $.parseJSON(data);
            cachedDates = null;
        }
        catch(e){
            alert(e + " " + data);
        }


        if(cachedResults.success === true) {

            if(cachedResults.matches.length == 1) {
                // special case - one match, let's just load it
                var objectId = cachedResults.matches[0].objectId;
                $.cookie('lastSearch', searchId);
                window.location.hash = "";
                window.location.pathname = basePath + "/asset/viewAsset/" + objectId;
            }
            searchId = cachedResults.searchId;
            
            cachedResults.totalLoadedCount = cachedResults.matches.length;
            
            disableHashChange = true;
            currentURL = window.location.href.replace(window.location.hash,"");
            currentHash = window.location.hash.replace("#", "");
            var oldSearchId = currentURL.substr(currentURL.lastIndexOf('/') + 1);
            window.history.pushState({}, "Search Results", window.location.href.replace(oldSearchId, searchId));


            dataAvailable = true;
            $("#loadAllResults").show();
            processSearchResults(cachedResults);
            setTimeout(function() {
                disableHashChange = false;
            }, 500);


            if($('#advancedSearchModal').hasClass('in')) {
                $('#advancedSearchModal').modal('hide');
            }

        }
    });
    return false;
}


$(document).ready(function() {

    parseSearch();

    $(document).on("submit", ".searchForm", function() {
        if($(this).attr("id") == "advancedSearchForm") {
            // do nothing
        }
        else {
            // when they hit the global search button, it should be a global search.  We might have ended up with a stored collection though, expunge that.
            // $(this).find("#collection").val('0');
            $(this).find("#specificSearchField").val('');
            $(this).find("#specificSearchText").val('');
            $(".collectionHeader").html('');
        }

        performSearchForButtonPress(this);
        return false;
    });
});