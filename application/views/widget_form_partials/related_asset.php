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

	$targetAssetName = $formFieldName . "[targetAssetId]";
	$targetAssetId = $formFieldId . "_targetAssetId";
	$labelName = $formFieldName . "[label]";
	$labelId = $formFieldId . "_label";


	$autocomplete = null;
	if($widgetModel->getAttemptAutocomplete()) {
		$autocomplete = "tryAutocompleteAsset";
	}
	$required = null;
	if($widgetModel->getRequired()) {
		$required = 'required="required"';
	}

	$labelContents = "";
	$assetContents = "";
	$isPrimaryValue = "";
	if($widgetModel->fieldContentsArray) {
		if($widgetModel->fieldContentsArray[$i]->isPrimary == "on") {
			$isPrimaryValue = " CHECKED ";
		}
		$labelContents = $widgetModel->fieldContentsArray[$i]->label;
		$assetContents= $widgetModel->fieldContentsArray[$i]->getContent();
	}

	if($widgetModel->drawCount == 1 && $widgetModel->offsetCount == 0) {
		$isPrimaryValue = "CHECKED";
	}
?>


<div class="panel panel-default widgetContentsContainer">

	<div class="panel-body widgetContents">
		<div class="form-group" >
			<label for="<?=$targetAssetId?>" class="col-sm-2 control-label">Asset Type:</label>
			<div class="col-sm-4">
				<select class="templateSelector form-control input-large">
					<option value=0>Any</option>
					<?foreach($this->instance->getTemplates() as $template):?>
					<option value="<?=$template->getId()?>" <?=($widgetModel->defaultTemplate==$template->getId())?"SELECTED":null?>   ><?=$template->getName()?></option>
					<?endforeach?>
				</select>
			</div>
			<div class="col-sm-2">
				<button type="button" class="btn btn-primary newAssetButton">Create New Asset</button>
			</div>
		</div>
		<div class="form-group advancedContent">
			<label for="<?=$targetAssetId?>" class="col-sm-2 control-label">Searching Against:</label>
			<div class="col-sm-4">
				<select class="matchAgainstSelector form-control input-large" multiple>
					<option value=0>Any</option>
					<?foreach($this->instance->getTemplates() as $template):?>

					<option value="<?=$template->getId()?>" <?=in_array($template->getId(), $widgetModel->matchAgainst)?"SELECTED":null?>   ><?=$template->getName()?></option>
					<?endforeach?>
				</select>
			</div>
		</div>
		<div class="form-group" >
			<label for="<?=$targetAssetId?>" class="col-sm-2 control-label"><?=$labelText?>:</label>
			<div class="col-sm-4">
				<input type="text"  autocomplete="off" class="mainWidgetEntry form-control relatedAssetInput  <?=$autocomplete?>" placeholder="<?=$labelText?>" >
				<input type="hidden"  autocomplete="off" class="mainWidgetEntry form-control relatedAssetSelectedItem targetAsset" id="<?=$targetAssetId?>" name="<?=$targetAssetName?>" placeholder="<?=$labelText?>" value="<?=$assetContents?>">
			</div>
			<div class="col-sm-6">
				<button type="button" class="btn btn-primary autocompletePreview">Preview</button> <button type="button" class="btn btn-primary autocompleteEdit">Edit</button> <button type="button" class="btn btn-primary clearRelated">Clear</button>
			</div>

		</div>
		<div class="form-group">
			<div class="col-sm-8 col-sm-offset-2 assetPreview">
			</div>
		</div>
		<?if($widgetModel->showLabel):?>
		<div class="form-group">
			<label for="<?=$labelId?>" class="col-sm-2 control-label <?=$autocomplete?>">Label</label>
			<div class="col-sm-4">
				<input type="text" autocomplete="off" class="form-control" id="<?=$labelId?>" name="<?=$labelName?>" placeholder="Label" value="<?=$labelContents?>">
			</div>
		</div>
		<?endif?>

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