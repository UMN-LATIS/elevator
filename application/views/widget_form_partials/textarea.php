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

	$textName = $formFieldName . "[fieldContents]";
	$textId = $formFieldId . "_targetAssetId";

	$autocomplete = null;
	if($widgetModel->getAttemptAutocomplete()) {
		$autocomplete = "tryAutocomplete";
	}
	$required = null;
	if($widgetModel->getRequired()) {
		$required = 'required="required"';
	}
	$isPrimaryValue = "";
	if($widgetModel->fieldContentsArray) {
		if($widgetModel->fieldContentsArray[$i]->isPrimary == "on") {
			$isPrimaryValue = " CHECKED ";
		}
		$fieldContents = $widgetModel->fieldContentsArray[$i]->getContent();
	}
	else {
		$fieldContents = "";
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
					<textarea cols=60 rows=10 class="mainWidgetEntry textAreaWidget form-control <?=$autocomplete?>" id="<?=$textId?>" name="<?=$textName?>" placeholder="<?=$labelText?>"><?=$fieldContents?></textarea>
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


<script type="text/javascript">
$(document).ready(function() {
	tinymce.init({
	    // mode: "specific_textareas",
	    // editor_selector: "textAreaWidget",
	    selector: "textarea#<?=$textId?>",
	    menubar : false,
	    plugins: "link",
	    setup: function(editor) {
 			editor.on('change', function () {
            	tinymce.triggerSave();
        	});
	    }
	 });

});


</script>

<?endfor?>
<?if($widgetModel->offsetCount==0):?>
<?=$this->load->view("widget_form_partials/widget_footer", array("widgetModel"=>$widgetModel), true)?>
<?endif?>