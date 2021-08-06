<?php
// all this stuff should be in a JS file, what the hell?
//
// hacky alternative to mysql_real_escape_string
function mres($value)
{
    $search = array("\\",  "\x00", "\n",  "\r",  "'",  '"', "\x1a");
    $replace = array("\\\\","\\0","\\n", "\\r", "\'", '\"', "\\Z");

    return str_replace($search, $replace, $value);
}

?>
<script type="text/javascript">
var instanceId = <?=$this->instance->getId()?>;
var fieldTypeData = new Array();
<?php foreach ($field_types as $field_type): ?>
fieldTypeData[<?=$field_type->getId()?>] = '<?=str_replace("\n", "\\n", $field_type->getSampleFieldData())?>';
<?endforeach?>

$(document).ready(function() {

	$(document).on("change", ".fieldType", function() {
		var fieldType = $(this).val();

		if(fieldTypeData[fieldType] != "") {
			if($(this).closest('.widgetItem').find(".fieldData").val() === "") {
				$(this).closest('.widgetItem').find(".fieldData").val(fieldTypeData[fieldType]);
			}

			$(this).closest('.widgetItem').find(".fieldDataGroup").show();
		}
		else {
			$(this).closest('.widgetItem').find(".fieldDataGroup").hide();
		}

	});

	// Load the handlebars template
	var source = $("#entry-template").html();
	var widget = Handlebars.compile(source);

	// Create a helper to use with the fieldTypies
	Handlebars.registerHelper('fieldTypeOption', function() {
			  return new Handlebars.SafeString(this.option);
			});

	// Load the existing widgets via the handlebars template
	<?php foreach ($template->getWidgets() as $key => $widget): ?>
		var context = {
			id: "<?= $widget->getId(); ?>",
			fieldTypes : [
			<?php foreach ($field_types as $field_type): ?>
					{option : '<option value="<?= $field_type->getId(); ?>" <?php if ($widget->getFieldType() == $field_type): ?>selected<?php endif ?>><?= $field_type->getName(); ?></option>'},
			<?php endforeach ?>
				],
			<?php if ($widget->getDisplay() == 1): ?>displayYes: "checked",<?php endif ?>
			<?php if ($widget->getRequired() == 1): ?>requiredYes: "checked",<?php endif ?>
			<?php if ($widget->getSearchable() == 1): ?>searchableYes: "checked",<?php endif ?>
			<?php if ($widget->getAttemptAutocomplete() == 1): ?>attemptAutocompleteYes: "checked",<?php endif ?>
			<?php if ($widget->getDisplayInPreview() == 1): ?>displayInPreviewYes: "checked",<?php endif ?>
			<?php if ($widget->getAllowMultiple() == 1): ?>allowMultipleYes: "checked",<?php endif ?>
			<?php if ($widget->getDirectSearch() == 1): ?>directSearchYes: "checked",<?php endif ?>
			<?php if ($widget->getClickToSearch() == 1): ?>clickToSearchYes: "checked",<?php endif ?>
			<?php if ($widget->getClickToSearchType() == 0 || $widget->getClickToSearchType() == NULL): ?>clickToSearchTypeZero: "checked",<?php endif ?>
			<?php if ($widget->getClickToSearchType() == 1): ?>clickToSearchTypeOne: "checked",<?php endif ?>
			fieldTitle: "<?= addslashes($widget->getFieldTitle()); ?>",
			label: "<?= addslashes($widget->getLabel()); ?>",
			tooltip: "<?= addslashes($widget->getTooltip()); ?>",
			viewOrder: '<?= $widget->getViewOrder(); ?>',
			templateOrder: '<?= $widget->getTemplateOrder(); ?>',
			fieldData: '<?= str_replace("\n", "\\n", mres(json_encode($widget->getFieldData())) )?>',
			lockFieldLabel: "true"
			};

			var insertHTML = widget(context);
			$('div#widgetList').append(insertHTML);
	<?php endforeach; ?>

	// Add a listener to delete the widget from the template when the delete button is clicked.
	$('.deleteWidgetButton').click(function() {

		$(this).closest(".widgetItem").remove();
	});

	// Add a listener to add a new widget to the template when clicked.
	$('#newWidgetButton').click(function() {
		var currentTime = $.now();
		var context = {
			id: currentTime,
			fieldTypes : [
				<?php foreach ($field_types as $field_type): ?>
						{option : '<option value="<?= $field_type->getId(); ?>"><?= $field_type->getName(); ?></option>'},
				<?php endforeach ?>
					]
			};
		var insertHTML = widget(context);
		$('div#widgetList').append(insertHTML);

	});


	$(document).on("change", ".displayPreviewWidget", function() {
		$("#needsRebuildId").val(1);
	});

	$(document).on("keyup", ".fieldTitle", function() {
		if($(this).closest(".widgetItem").find(".displayPreviewWidget").is(":checked")) {
			$("#needsRebuildId").val(1);
		}

		// we use the instance ID because we want to intentionally ensure collisions between same field title
		// within the same instance. This allows autocompleter to be more flexible, rather than locking to a specific
		// template.  We don't want to autocomplete across instances though, since that could be wonky.
		if($(this).closest(".widgetItem").find(".internalTitle").data('lockfieldlabel') != true) {
			var newValue = $(this).val().replace(/[^a-z0-9_]/gi,'') + "_" + instanceId;
			$(this).closest(".widgetItem").find(".internalTitle").val(newValue.toLowerCase());
		}

	});

	// validate that JSON fields have actual JSON in them.
	$(document).on("blur", ".fieldData", function() {
		jsonContents = $(this).val();
		try {
			JSON.parse(jsonContents);
			$(this).css("background-color", "white");
		}
		catch (e) {
			$(this).css("background-color", "red");
		}
	});


	$(".fieldType").trigger('change');

	$(document).on("change", "input[name=showCollection]", function(event) {
		if($(event.target).is(":checked")) {
			$(".collectionPosition").removeClass("hide");
		}
		else {
			$(".collectionPosition").addClass("hide");
		}
	});

	$(document).on("change", "input[name=showTemplate]", function(event) {
		if($(event.target).is(":checked")) {
			$(".templatePosition").removeClass("hide");
		}
		else {
			$(".templatePosition").addClass("hide");
		}
	});

	$(document).on("click", ".advancedSettings", function(e) {
		e.preventDefault();
		$(".advancedSettingsDialog").toggleClass("hide");
	});

	$("input[name=showCollection]").trigger("change");
	$("input[name=showTemplate]").trigger("change");

});
</script>
