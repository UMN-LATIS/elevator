<?if($widgetModel->offsetCount==0):?>
<?=$this->load->view("widget_form_partials/widget_header", array("widgetModel"=>$widgetModel), true)?>
<?endif?>

<?for($i=0; $i<$widgetModel->drawCount; $i++):?>


<?
	$formFieldName = $widgetModel->getFieldTitle() . "[" .($widgetModel->offsetCount + $i) . "]";
	$formFieldId = $widgetModel->getFieldTitle() . "_" . ($widgetModel->offsetCount + $i) . "";
	$labelText = $widgetModel->getLabel();

	$primaryGlobal = $widgetModel->getFieldTitle() . "[isPrimary]";
	$primaryId = $formFieldId . "_isPrimary";

	$required = null;
	if($widgetModel->getRequired()) {
		$required = 'required="required"';
	}
	$isPrimaryValue = "";
	if($widgetModel->fieldContentsArray) {
		if($widgetModel->fieldContentsArray[$i]->isPrimary == "on") {
			$isPrimaryValue = " CHECKED ";
		}

		if(count($widgetModel->fieldContentsArray)>$i) {
			$fieldContents = $widgetModel->fieldContentsArray[$i]->fieldContents;
		}

	}

	if($widgetModel->drawCount == 1 && $widgetModel->offsetCount == 0) {
		$isPrimaryValue = "CHECKED";
	}

	$previousField = "";

?>

<?

$encodedField = json_encode($widgetModel->getFieldData());

// extract the labels for each dropdwon.
$topLevels = getTopLevels($widgetModel->getFieldData());

?>


<script>


if(typeof selectedItems === 'undefined') {

var selectedItems = {};
}

<?if(isset($fieldContents)): foreach($fieldContents as $key=>$value):?>
	if(typeof selectedItems["<?=$formFieldId?>"] === 'undefined') {
		selectedItems["<?=$formFieldId?>"] = {};
	}
	selectedItems["<?=$formFieldId?>"]["<?=$key?>"] = "<?=$value?>";
<?endforeach; endif;?>


</script>



<div class="panel panel-default widgetContentsContainer">

	<div class="panel-body widgetContents multiselectGroup" id="<?=$formFieldId?>">
		<?=$this->load->view('widget_form_partials/multiselect_inner', ['widgetFieldData'=>$widgetModel->getFieldData(), "formFieldId"=>$formFieldId, "formFieldName"=>$formFieldName], true)?>

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
$(document).ready(function() {
	loadGroup($("#<?=$formFieldId?>").parent());
	$("#<?=$formFieldId?>").find(".multiSelect").on("change", function() {
		markSaveDirty();
	});
});

</script>


<?endfor?>


<?if($widgetModel->offsetCount==0):?>
<?=$this->load->view("widget_form_partials/widget_footer", array("widgetModel"=>$widgetModel), true)?>
<?endif?>