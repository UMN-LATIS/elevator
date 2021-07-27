
$(document).ready(function() {

    $(document).on("submit", ".searchForm", function() {
        // if(window.location.hash.length > 0) {
        //     searchId = window.location.hash.substring(1);
        // }
        // else {
        //     searchId = "";
        // }
        storeAndSearch("", $(this));

        return false;
    });
});


$(document).on("change", ".searchDropdown", function() {
    var selectedOption = $(this).find(":selected");
    var content = $(selectedOption).val();
    var templateId = $(selectedOption).data('templateid');
    var targetGroup = $(this).closest(".form-group").find(".specificSearchTextContainer");

    buildFieldInfo(templateId, content, targetGroup);


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

var buildFieldInfo = function(templateId, fieldTitle, targetGroup) {
    $.post(basePath+'search/getFieldInfo', {fieldTitle: fieldTitle, template: templateId}, function(data, textStatus, xhr) {
        if(typeof data !== "object") {
            console.log("An error occured loading fieldinfo: ", data);
        }

        if(data.type == "text") {
            $(targetGroup).html("");
            $(targetGroup).html('<input type="text" name="specificSearchText[]"  autocomplete="off" class="form-control advancedOption advancedSearchContent" value="">');
        }
        else {
            $(targetGroup).html("");
            $(targetGroup).html('<select name="specificSearchText[]"  autocomplete="off" class="form-control advancedOption advancedSearchContent">');
            selectElement = $(targetGroup).find("select");
            $.each(data.values, function(index, val) {
                var optionValue;
                if($.isNumeric(index)) {
                    optionValue = val;
                }
                else {
                    optionValue = index;
                }
                $(selectElement).append($('<option>', {value: optionValue}).text(val));
            });

        }

    });
};


$(document).on("click", ".addAnotherSpecific", function() {
    $(".searchCombine").removeClass("hide");
    var specificSearch = $(this).closest('.specificSearch');
    var newSpecificSearch = $(specificSearch).clone(false);
    $(newSpecificSearch).find("input[type='text']").val("");
    $(specificSearch).after(newSpecificSearch);

});

$(document).on("click", ".addAnotherCollection", function() {
    var collectionCopy = $(this).closest('.form-group');
    var newCollection = $(collectionCopy).clone(false);
    newCollection.find("input[type='text']").val("");
    $(collectionCopy).after(newCollection);
});
