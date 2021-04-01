var targetTemplate = "#result-template";
var listTemplate = "#list-template";




$(document).on("click", ".addAnotherSpecific", function() {
    $(".searchCombine").removeClass("hide");
    var specificSearch = $(this).closest('.specificSearch');
    var newSpecificSearch = $(specificSearch).clone(false);
    newSpecificSearch.find("input[type='text']").val("");
    $(specificSearch).after(newSpecificSearch);

});

$(document).on("click", ".addAnotherCollection", function() {
    var collectionCopy = $(this).closest('.form-group');
    var newCollection = $(collectionCopy).clone(false);
    newCollection.find("input[type='text']").val("");
    $(collectionCopy).after(newCollection);
});



$(document).on("change", ".searchDropdown", function() {
    var selectedOption = $(this).find(":selected");
    var content = $(selectedOption).val();
    var templateId = $(selectedOption).data('templateid');
    var targetGroup = $(this).closest(".form-group").find(".specificSearchTextContainer");
    $.post(basePath + 'search/getFieldInfo', {fieldTitle: content, template: templateId}, function(data, textStatus, xhr) {
        var results;
        try {
            results = $.parseJSON(data);
        }
        catch(e) {
            console.log("error occurred");
        }

        if(results.type == "text") {
            $(targetGroup).html("");
            $(targetGroup).html('<input type="text" name="specificSearchText[]"  autocomplete="off" class="form-control advancedOption advancedSearchContent" value="">');
        }
        else {
            $(targetGroup).html("");
            $(targetGroup).html('<select name="specificSearchText[]"  autocomplete="off" class="form-control advancedOption advancedSearchContent">');
            selectElement = $(targetGroup).find("select");
            $.each(results.values, function(index, val) {
                if(typeof index == "string") {
                    $(selectElement).append($('<option>', {value: index}).text(val));
                }
                else {
                    $(selectElement).append($('<option>', {value: val}).text(val));
                }
                
            });

        }

    });



});


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