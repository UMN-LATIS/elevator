// Manages the advanced search
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

$(document).on("click", ".addAnotherTemplate", function() {
    var templateCopy = $(this).closest('.form-group');
    var newTemplate = $(templateCopy).clone(false);
    $(templateCopy).after(newTemplate);
});


$(document).on("change", ".searchDropdown", function() {
    var selectedOption = $(this).find(":selected");
    var content = $(selectedOption).val();
    var templateId = $(selectedOption).data('templateid');
    var targetGroup = $(this).closest(".form-group").find(".specificSearchTextContainer");
    $.post(basePath + 'search/getFieldInfo', {fieldTitle: content, template: templateId}, function(data, textStatus, xhr) {
        if(data.type == "text") {
            $(targetGroup).html("");
            $(targetGroup).html('<input type="text" name="specificSearchText[]"  autocomplete="off" class="form-control advancedOption advancedSearchContent" value="">');
        }
        else {
            $(targetGroup).html("");
            $(targetGroup).html('<select name="specificSearchText[]"  autocomplete="off" class="form-control advancedOption advancedSearchContent">');
            selectElement = $(targetGroup).find("select");
            $.each(data.values, function(index, val) {
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
