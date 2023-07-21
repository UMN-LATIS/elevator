

function loadGroup(targetId) {
	var targetArray = sourceContent[targetId];
	$("#"+targetId).find(".multiSelect").each(function(index, el) {
		var selectIndex = $(el).data("cascadenumber");
		var category = $(el).data("category");

		if(selectIndex === 0) {
			var entryList = sourceContent[targetId][category];
			if(entryList) {
				var keys = Object.keys(entryList);
				keys.sort();

				$.each(keys,function(key, value) {
					$(el).append($("<option></option>").attr("value",value).text(value));
				});
				var groupSelected = selectedItems[targetId];
				// console.log(category);
				// console.log(selectedItems[targetId]);
				// console.log(groupSelected);
				// this is a horrible hack.  We store categories with sanitized key names, but our multiselector stores them properly.
				//
				category = category.replace(/[^A-Za-z0-9]/g, '');
				if(groupSelected && groupSelected[category]) {
					$(el).val(groupSelected[category]);
					$(el).trigger("change");
				}

			}
		}
	});
}

$(document).on("change", ".multiSelect", function(e) {

	var selectIndex = $(this).data("cascadenumber");
	var category = $(this).data("category");
	var parentGroup = $(this).closest('.multiselectGroup');
	var selectedElements = $(parentGroup).find("[data-cascadenumber]").filter(function() {
		return $(this).attr("data-cascadenumber") > selectIndex;
	});

	$(selectedElements).each(function(index, el) {
		$(el).val("");
	});


	loadCascadeNumber(parentGroup, selectIndex+1);

});


function loadCascadeNumber(parentGroup, selectIndex) {
	var target = sourceContent[$(parentGroup).attr("id")];
	var groupSelected = selectedItems[$(parentGroup).attr("id")];
	var targetCategory;
	for(i=0; i<selectIndex; i++) {
		var targetElement = $(parentGroup).find("[data-cascadenumber=" + i + "]");
		targetCategory = $(targetElement).data("category");
		var selectedItem = $(targetElement).val();
		target = target[targetCategory][selectedItem];

	}

	var selectElement = $(parentGroup).find("[data-cascadenumber=" + selectIndex + "]");
	
	targetCategory = $(selectElement).data("category");
	if(!targetCategory || !target) {
		return;
	}
	target = target[targetCategory];
	$(selectElement).empty();
	$(selectElement).append("<option></option>");

	if(typeof target === 'undefined') {
		return;
	}

	// sort alphabetically, handle both object and array cases
	var keys = Object.keys(target);
	if(!$.isNumeric(keys[0])) {

		keys.sort();
	}
	else {
		target.sort();
		keys = Object.keys(target);
	}

	length = keys.length;
	for (i = 0; i < length; i++) {
		key = keys[i];
		value = target[key];
		var itemName;
		if($.isNumeric(key)) {
			itemName = value;
		}
		else {
			itemName = key;
		}
		$(selectElement).append($("<option></option>").attr("value",itemName).text(itemName));
	}


	// $.each(target, function(key, value) {


	// });
	targetCategory = targetCategory.replace(/[^A-Za-z0-9]/g, '');
	if(groupSelected && groupSelected[targetCategory]) {
		$(selectElement).val(groupSelected[targetCategory]);
		$(selectElement).trigger("change");

	}


}