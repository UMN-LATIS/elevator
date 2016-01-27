<?if($widgetModel->offsetCount==0):?>
<?=$this->load->view("widget_form_partials/widget_header", array("widgetModel"=>$widgetModel), true)?>
<?endif?>
<?for($i=0; $i<$widgetModel->drawCount; $i++):?>

<?

	$formFieldName = $widgetModel->getFieldTitle() . "[" . ($widgetModel->offsetCount + $i) . "]";
	$formFieldId = $widgetModel->getFieldTitle() . "_" . ($widgetModel->offsetCount + $i) . "";
	$labelText = $widgetModel->getLabel();
	$toolTip = $widgetModel->getToolTip();
	$primaryGlobal = $widgetModel->getFieldTitle() . "[isPrimary]";
	$primaryId = $formFieldId . "_isPrimary";

	$autocomplete = null;
	if($widgetModel->getAttemptAutocomplete()) {
		$autocomplete = "tryAutocomplete";
	}

	$textName = $formFieldName . "[tags]";
	$textId = $formFieldId . "_tags";
	$isPrimaryValue = "";
	$required = null;
	if($widgetModel->getRequired()) {
		$required = 'required="required"';
	}
	if($widgetModel->fieldContentsArray) {
		if($widgetModel->fieldContentsArray[$i]->isPrimary == "on") {
			$isPrimaryValue = " CHECKED ";
		}
		$fieldContents = $widgetModel->fieldContentsArray[$i]->getContent();
	}
	else {
		$fieldContents = array();
	}

	if($widgetModel->drawCount == 1 && $widgetModel->offsetCount == 0) {
		$isPrimaryValue = "CHECKED";
	}
?>



<div class="panel panel-default widgetContentsContainer">

	<div class="panel-body widgetContents">
		<div class="form-group">
			<label for="<?=$textId?>" class="col-sm-2 control-label"><?=$labelText?></label>
			<div class="col-sm-8">
				<input type="text" <?=$required?> class="mainWidgetEntry form-control" id="<?=$textId?>" name="<?=$textName?>" placeholder="<?=$labelText?> (comma separated)" value="<?=htmlentities(join(",", $fieldContents))?>">
			</div>
		</div>

		<?if($widgetModel->getAllowMultiple()):?>
		<div class="form-group isPrimary">
			<div class="col-sm-offset-2 col-sm-10">
				<div class="checkbox">
					<label>
						<input id="<?=$primaryId?>" value=<?=$widgetModel->offsetCount + $i?> name="<?=$primaryGlobal?>" type="radio" <?=$isPrimaryValue?>>
						Primary Entry
					</label>
				</div>
			</div>
		</div>
		<?endif?>
	</div>
</div>

<script>

$(document).ready(function($) {

	function scope() {

		var self = $("#<?=$textId?>");
		return function(entry, sync,async) {
			<?if($autocomplete):?>
				var templateId = $("#sourceTemplate").val();
				var fieldTitle = $(self).closest(".tab-pane").attr("id");
				$.post( basePath + "search/autocomplete/", {templateId: templateId, fieldTitle:fieldTitle, searchTerm:entry}, function( data ) {
					try{
						jsonObject = $.parseJSON(data);
					}
					catch(e){
						alert(e + " " + data);
						return;
					}
					var outputArray = [];

					$(jsonObject).each(function(index, el) {
						outputArray.push({value: el});
					});

					async(outputArray);
					// response(jsonObject);
				});
			<?endif?>
			}
	}
capturedScope = scope();
	$("#<?=$textId?>").tagsinput({
		typeaheadjs: {
    		displayKey: 'value',
    		valueKey: 'value',
			source: capturedScope
		}

	});
});
</script>

<?endfor?>
<?if($widgetModel->offsetCount==0):?>
<?=$this->load->view("widget_form_partials/widget_footer", array("widgetModel"=>$widgetModel), true)?>
<?endif?>