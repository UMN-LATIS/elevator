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


<div class="panel panel-default widgetContentsContainer nestedAsset">

	<div class="panel-body widgetContents">
		<div class="form-group hide" >
			<label for="<?=$targetAssetId?>" class="col-sm-2 control-label">Asset Type:</label>
			<div class="col-sm-4">
				<select class="templateSelector form-control input-large">
					<option value=0>Any</option>
					<?foreach($this->instance->getTemplates() as $template):?>
					<option value="<?=$template->getId()?>" <?=($widgetModel->defaultTemplate==$template->getId())?"SELECTED":null?>   ><?=$template->getName()?></option>
					<?endforeach?>
				</select>
			</div>
		</div>
		<div class="form-group hide" >
			<label for="<?=$targetAssetId?>" class="col-sm-2 control-label">Asset:</label>
			<div class="col-sm-4">
				<input type="text"  autocomplete="off" class="mainWidgetEntry form-control relatedAssetInput targetAsset" id="<?=$targetAssetId?>" name="<?=$targetAssetName?>" placeholder="<?=$labelText?>" value="<?=$assetContents?>">
			</div>

		</div>

		<?if($widgetModel->getAllowMultiple()):?>
		<div class="form-group">
			<div class="col-sm-2">
				<button type="button" class="btn btn-primary clearRelated">Clear</button>
			</div>
			<div class="col-sm-10 isPrimary">
				<div class="checkbox">
					<label>
						<input id="<?=$primaryId?>" value=<?=$widgetModel->offsetCount + $i?> name="<?=$primaryGlobal?>" type="radio" <?=$isPrimaryValue?>>
						Primary Entry
					</label>
				</div>
			</div>
		</div>
		<?endif?>

		<div class="inlineRelatedAsset">

		</div>
	</div>
</div>

<script>
// we maybe loaded in an iframe, so we need to trigger some events.
$(document).ready(function() {
	loadFrameForNestedElement($("#<?=$targetAssetId?>").closest('.nestedAsset'));

})

// $(window).load(function() {
// 	parent.iframeLoaded();
// });

</script>

<?endfor?>
<?if($widgetModel->offsetCount==0):?>
<?=$this->load->view("widget_form_partials/widget_footer", array("widgetModel"=>$widgetModel), true)?>
<?endif?>