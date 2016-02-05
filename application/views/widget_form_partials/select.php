<?if($widgetModel->offsetCount==0):?>
<?=$this->load->view("widget_form_partials/widget_header", array("widgetModel"=>$widgetModel), true)?>
<?endif?>
<?for($i=0; $i<$widgetModel->drawCount; $i++):?>

<?

	$formFieldName = $widgetModel->getFieldTitle() . "[" . ($widgetModel->offsetCount + $i). "]";
	$formFieldId = $widgetModel->getFieldTitle() . "_" . ($widgetModel->offsetCount + $i) . "";
	$labelText = $widgetModel->getLabel();
	$toolTip = $widgetModel->getToolTip();
	$primaryGlobal = $widgetModel->getFieldTitle() . "[isPrimary]";
	$primaryId = $formFieldId . "_isPrimary";

	$selectName = $formFieldName . "[fieldContents]";
	$selectId = $formFieldId . "_fieldContents";
	$isPrimaryValue = "";
	$required = null;
	if($widgetModel->getRequired()) {
		$required = 'required="required"';
	}
	if($widgetModel->fieldContentsArray) {
		if($widgetModel->fieldContentsArray[$i]->isPrimary == "on") {
			$isPrimaryValue = " CHECKED ";
		}
	}

	if($widgetModel->drawCount == 1 && $widgetModel->offsetCount == 0) {
		$isPrimaryValue = "CHECKED";
	}

?>

<div class="panel panel-default widgetContentsContainer">

	<div class="panel-body widgetContents">
		<div class="form-group">
			<label for="<?=$selectId?>" class="col-sm-2 control-label"><?=$labelText?></label>
			<div class="col-sm-4">
				<select <?=$required?> class="mainWidgetEntry form-control" id="<?=$selectId?>" name="<?=$selectName?>">
		<? foreach($widgetModel->parsedFieldData["selectGroup"] as $key=>$selectOption): ?>
			<?if(!is_numeric($key)):?>
				<option <?=(isset($widgetModel->fieldContentsArray[$i]) && $key==$widgetModel->fieldContentsArray[$i]->fieldContents)?"SELECTED":null?> value="<?=$key?>"><?=$key?></option>
			<?else:?>

			<option <?=(isset($widgetModel->fieldContentsArray[$i]) && $selectOption==$widgetModel->fieldContentsArray[$i]->fieldContents)?"SELECTED":null?>><?=$selectOption?></option>
			<?endif?>
		<? endforeach?>
		</select>
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