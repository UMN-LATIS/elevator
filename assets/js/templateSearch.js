$(document).on("submit", ".searchForm", function() {
    storeAndSearch("", $(this));
    return false;
});

var storeAndSearch = function(searchId, targetForm) {
    $.post( basePath + "search/searchResults/" + searchId, {storeOnly:true, searchQuery:JSON.stringify($( targetForm ).serializeForm())}, function( data ) {
        try{
            cachedResults = $.parseJSON(data);
            cachedDates = null;
        }
        catch(e){
            if(data === "") {
                bootbox.alert("No Results Found.", function() {
                });
            }
        }
        if(cachedResults.success === true) {
            searchId = cachedResults.searchId;
            window.location = basePath + "search/s/" + searchId;
        }

    });
};

