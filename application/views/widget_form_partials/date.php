<?if($widgetModel->offsetCount==0):?>
<?=$this->load->view("widget_form_partials/widget_header", array("widgetModel"=>$widgetModel), true)?>
<?endif?>
<?for($i=0; $i<$widgetModel->drawCount; $i++):?>

<?

	$labelText = $widgetModel->getLabel();
	$toolTip = $widgetModel->getToolTip();

	$formFieldName = $widgetModel->getFieldTitle() . "[" . ($widgetModel->offsetCount + $i) . "]";
	$formFieldId = $widgetModel->getFieldTitle() . "_" . ($widgetModel->offsetCount + $i) . "";

	$startTextFieldId = $formFieldId . "_start_text";
	$endTextFieldId = $formFieldId . "_end_text";
	$startTextFieldName = $formFieldName . "[start][text]";
	$endTextFieldName = $formFieldName . "[end][text]";
	$startNumericFieldId = $formFieldId . "_start_numeric";
	$endNumericFieldId = $formFieldId . "_end_numeric";
	$startNumericFieldName = $formFieldName . "[start][numeric]";
	$endNumericFieldName = $formFieldName . "[end][numeric]";

	$labelName = $formFieldName . "[label]";
	$labelId = $formFieldId . "_label";

	$primaryGlobal = $widgetModel->getFieldTitle() . "[isPrimary]";
	$primaryId = $formFieldId . "_isPrimary";

	$startTextContents = "";
	$endTextContents = "";
	$startNumericContents = "";
	$endNumericContents = "";
	$isRange = false;
	$isPrimaryValue = "";
	$labelContents = "";

	$autocomplete = null;
	if($widgetModel->getAttemptAutocomplete()) {
		$autocomplete = "tryAutocomplete";
	}
	$required = null;
	if($widgetModel->getRequired()) {
		$required = 'required="required"';
	}

	if($widgetModel->fieldContentsArray) {

		$startTextContents = $widgetModel->fieldContentsArray[$i]->start['text'];
		$startNumericContents = $widgetModel->fieldContentsArray[$i]->start['numeric'];

		$labelContents = $widgetModel->fieldContentsArray[$i]->label;
		if($widgetModel->fieldContentsArray[$i]->isPrimary == "on") {
			$isPrimaryValue = " CHECKED ";
		}

		if($widgetModel->fieldContentsArray[$i]->range == true) {
			$isRange = true;
			$endNumericContents = $widgetModel->fieldContentsArray[$i]->end['numeric'];
			$endTextContents = $widgetModel->fieldContentsArray[$i]->end['text'];
		}
	}

	if($widgetModel->drawCount == 1 && $widgetModel->offsetCount == 0) {
		$isPrimaryValue = "CHECKED";
	}

?>

	<div class="panel panel-default widgetContentsContainer">

		<div class="panel-body widgetContents">
			<div class="form-group">
				<label for="input" class="col-sm-2 control-label">Date Type</label>
				<div class="col-sm-3">
					<select class="rangeModify form-control">
						<option value=0>Moment</option>
						<option value=1 <?=($isRange)?"SELECTED":null?>>Range</option>
					</select>
				</div>
			</div>

			<div class="form-group">
				<label for="<?=$startTextFieldId?>" class="startLabel col-sm-2 control-label">Date</label>
				<div class="col-sm-4">
					<input type="text" <?=$required?> parsley-dateobject parsley-trigger="blur" autocomplete="off"  class="mainWidgetEntry dateText form-control <?=$autocomplete?>" id="<?=$startTextFieldId?>" name="<?=$startTextFieldName?>" placeholder="<?=$labelText?>" value="<?=$startTextContents?>">
					<input type="hidden" class="dateHidden form-control" id="<?=$startNumericFieldId?>" type=text name="<?=$startNumericFieldName?>" value="<?=$startNumericContents?>">
				</div>
			</div>


			<div class="form-group dateRange" id="range_<?=$endTextFieldId?>">
				<label for="<?=$endTextFieldId?>" class="col-sm-2 control-label">End</label>
				<div class="col-sm-4">
					<input type="text"  autocomplete="off"  class="dateText form-control <?=$autocomplete?>" id="<?=$endTextFieldId?>" name="<?=$endTextFieldName?>" placeholder="<?=$labelText?>" value="<?=$endTextContents?>">
					<input type="hidden" class="dateHidden form-control" id="<?=$endNumericFieldId?>" type=text name="<?=$endNumericFieldName?>" value="<?=$endNumericContents?>">
				</div>
			</div>

			<div class="form-group">
				<label for="<?=$labelId?>" class="col-sm-2 control-label <?=$autocomplete?>">Label</label>
				<div class="col-sm-4">
					<input type="text"  autocomplete="off"  class="form-control mainWidgetEntry" id="<?=$labelId?>" name="<?=$labelName?>" placeholder="<?=$labelText?>" value="<?=$labelContents?>">
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

<?endfor?>
<?if($widgetModel->offsetCount==0):?>
<?=$this->load->view("widget_form_partials/widget_footer", array("widgetModel"=>$widgetModel), true)?>
<?endif?>