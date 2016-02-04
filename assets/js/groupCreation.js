

var loadValues = function(groupArray) {

	$.each(groupArray, function(index, val) {
		addValue(val);
	});

};

var addValue = function(value) {

	var source   = $("#group-value").html();
	var template = Handlebars.compile(source);

	var sourceObject = {};
	sourceObject.groupValue = value;
	if($("#inputGroupType").val() == "User") {
		sourceObject.groupName = userCache[value];
	}
	else {
		sourceObject.groupName = null;
	}

	var html = template(sourceObject);
	$("#addAnotherValue").before(html);
	buildAutocomplete();
};


var removeValue = function(targetElement) {
	$(targetElement).find("input").val("");
	$(targetElement).hide();

};


$(document).on("click", ".removeValueButton", function() {

	removeValue($(this).closest(".groupValueGroup"));

});

$(document).ready(function() {


	loadValues(existingGroups);
	addValue("");

	$("#addAnotherValue").on("click", function() {
		addValue("");
	});




	buildAutocomplete();
	$("#inputGroupType").on("change", function() {


		if($(this).val() === "") {
			$("#submitButton").attr("disabled",true);
		}
		else {
			$("#submitButton").removeAttr("disabled");
		}




		switch($(this).val()) {
			case "":
			case "All":
			case "Authed":
			case "Authed_remote":
			$(".groupValueGroup").hide();
			$("#groupLabelGroup").hide();
			$("#courseList").hide();
			break;
			case "Course":
			$("#courseList").show();
			$(".groupValueGroup").show();
			$("#groupLabelGroup").show();
			break;
			case "JobCode":
			case "User":
			case "Unit":
			$("#courseList").hide();
			$(".groupValueGroup").show();
			$("#groupLabelGroup").show();
			break;
		}

		if($("#inputGroupId").val() === "") {
			$("#inputGroupLabel").attr("placeholder", ($(this).find("option:selected").text()));
		}


	});

	$("$courseListSelect").on("change", function() {
		$(".inputGroupValue").last().val($(this).val());
	});

	$("#createGroupForm").on("submit", function() {

		switch($("#inputGroupType").val()) {
			case "User":
			case "JobCode":
			case "Course":
			var haveValues = false;
			$('input[name="groupValue[]"]').each(function(index, el) {
				if($(el).val() !== "")	{
					haveValues = true;
				}
			});
			if(!haveValues) {
				alert("A group value is required");
				return false;
			}
			break;
		}
		return true;
	});

	$("#inputGroupType").trigger("change");

});



function buildAutocomplete() {
	var source   = $("#person-autocompleter-template").html();
	var template = Handlebars.compile(source);


	$(".inputGroupValue").each(function(index, el) {
		$(el).autocomplete({

			source: function(request, response) {
				$.post( basePath + "permissions/userAutocompleter/", {groupType: $("#inputGroupType").val(), groupValue: request.term}, function( data ) {
					try{
						jsonObject = $.parseJSON(data);
					}
					catch(e){
						alert(e + " " + data);
						return;
					}

					if(jsonObject.success === true) {
						$.each(jsonObject.matches, function(index, value) {

							value.username = value.username;
							value.emplid = value.emplid;
							value.name = value.name;
							value.email = value.email;
							if(value.completionId) {
								value.value = value.completionId;
							}
							else {
								value.value = value.username;
							}

							jsonObject.matches[index] = value;

						});
						response(jsonObject.matches);
					}

				});
			},
			messages: {
				noResults: '',
				results: function() {}
			},
			search: function(event, ui) {
				$('.spinner').show();
			},
			response: function(event, ui) {
				$('.spinner').hide();
			}
		}).data("ui-autocomplete")._renderItem = function( ul, item ) {
			item.base_url = basePath;
			var html = template(item);
			return $(html).appendTo(ul);
		};

		});
}
