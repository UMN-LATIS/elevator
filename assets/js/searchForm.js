var targetTemplate = "#result-template";
var listTemplate = "#list-template";




$(document).on("click", ".addAnotherSpecific", function() {

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
    $.post(basePath + '/search/getFieldInfo', {fieldTitle: content, template: templateId}, function(data, textStatus, xhr) {
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
                $(selectElement).append($('<option>', {value: val}).text(val));
            });

        }

    });



});



$(document).ready(function() {

    parseHash();

    $(document).on("submit", ".searchForm", function() {
        $("#results").empty();
        $("#listResults").empty();
        $(".suggest").empty();
        searchId = "";
        if(window.location.hash.length > 0) {
            // for now, let's try not persisting search results?
        //    searchId = window.location.hash.substring(1);
        }

        currentPageNumber = 0;
        previousEventComplete = false;

        $.post( basePath + "search/searchResults/" + searchId, {searchQuery:JSON.stringify($( this ).serializeForm())}, function( data ) {
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
                disableHashChange = true;
                window.location.hash = searchId;

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
    });
});