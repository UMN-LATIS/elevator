var templateList = "";


$(document).ready(function() {


	jQuery.getJSON(basePath + 'search/getTemplates', {}, function(json, textStatus) {
		templateList = json;
		prepopulate();
	});


	$("#customSaveButton").on("click", function() {
		var result = $("#customSearchForm").serializeForm();
		var searchId = $("#searchId").val();
		var searchTitle = $("#inputSearchTitle").val();
		$.post(basePath + "search/saveSearch", { searchTitle: searchTitle, searchData: result, customSearchId: searchId }, function(data, textStatus, xhr) {
			if(data) {
				$("#searchId").val(data);
				pathAddition = "";
				if(basePath != "/") {
					pathAddition = basePath;
				}
				var newurl = window.location.protocol + "//" + window.location.host + pathAddition + "/search/searchBuilder/"  + data;
				window.history.pushState({path:newurl},'',newurl);
				bootbox.alert("<h2>Saved</h2>");
				window.setTimeout(function(){
					bootbox.hideAll();
				}, 1500);
			}
		});
	});


	$(".addGroup").on('click', function(event) {
		event.preventDefault();
		addNewGroup();
	});


	$("#searchButton").on('click', function(event) {

		storeAndSearch("", $("#customSearchForm"));

	});




});

$(document).on("keypress", 'input', function(e) {
    if (e.keyCode == 13) {
        storeAndSearch("", $("#customSearchForm"));
        return false;
    }
});


var prepopulate = function() {
	if(presetInfo) {

		var templates = presetInfo.specificTemplateId;
		var searchFieldValues = presetInfo.specificSearchField;

		$.each(templates, function(index, val) {

			var targetElement = addNewGroup();
			var templateSelector = $(targetElement).find(".templateSelector");
			var searchField = $(targetElement).find(".searchField");

			$(templateSelector).val(val);
			loadSearchFields($(templateSelector).val(), targetElement, searchFieldValues[index]);

		});

	}

};

var addNewGroup = function() {
	var injectObject = {};
	injectObject.templates = templateList;
	var source   = $("#search-entry").html();
	var template = Handlebars.compile(source);
	var html = template(injectObject);
	var insertObject = $(html);
	var result = $(".addButtonGroup").before(insertObject);
	return $(insertObject);
};

var loadSearchFields = function(templateId, parentGroup, targetValue) {
	$.getJSON(basePath + 'search/getFields/' + templateId, {}, function(json, textStatus) {
		var targetGroup = $(parentGroup).find(".searchField");
		$(targetGroup).empty();
		$.each(json, function(index, val) {
			$(targetGroup).append($('<option>', {value: index}).text(val));
		});
		if(targetValue) {
			$(targetGroup).val(targetValue);
			$(targetGroup).trigger("change");
		}
	});
};

$(document).on('change', ".templateSelector", function(event) {

	var templateId = $(this).val();
	var parentGroup = $(this).closest('.searchGroup');
	loadSearchFields(templateId, parentGroup, null);
});

$(document).on('change', ".searchField", function(event) {
	var parentGroup = $(this).closest('.searchGroup');
	var fieldTitle = $(this).val();
	var templateId = $(parentGroup).find(".templateSelector").val();
	var targetGroup = $(parentGroup).find(".specificSearchTextContainer");
	buildSearchForm(fieldTitle,templateId, targetGroup);
});
