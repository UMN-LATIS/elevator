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
if(!sourceContent) {
	var sourceContent = new Array();
}
if(typeof selectedItems === 'undefined') {

	var selectedItems = {};
}

selectedItems["<?=$formFieldId?>"] = {};

<?if(isset($fieldContents)): foreach($fieldContents as $key=>$value):?>
selectedItems["<?=$formFieldId?>"]["<?=$key?>"] = "<?=$value?>";
<?endforeach; endif;?>

sourceContent["<?=$formFieldId?>"] = $.parseJSON('<?=preg_replace( "/\r|\n/", "",addslashes($encodedField))?>');


</script>



<div class="panel panel-default widgetContentsContainer">

	<div class="panel-body widgetContents multiselectGroup" id="<?=$formFieldId?>">

		<?$j=0; foreach($topLevels as $topLevel):?>
		<div class="form-group" id="<?=$formFieldId?>_<?=makeSafeForTitle($topLevel)?>_label">
			<label for="<?=$formFieldId?>_<?=makeSafeForTitle($topLevel)?>" class="col-sm-2 control-label"><?=$topLevel?></label>
			<div class="col-sm-4">
				<select class="form-control <?=($j==0)?"mainWidgetEntry":null?> multiSelect" data-category="<?=$topLevel?>" data-cascadenumber=<?=$j?> id="<?=$formFieldId?>_<?=makeSafeForTitle($topLevel)?>" name="<?=$formFieldName?>[fieldContents][<?=makeSafeForTitle($topLevel)?>]">
				<option></option>
				</select>
			</div>
		</div>

		<?$j++;endforeach?>



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
	loadGroup("<?=$formFieldId?>");
	$("#<?=$formFieldId?>").find(".multiSelect").on("change", function() {
		markSaveDirty();
	});
});

</script>


<?endfor?>


<?if($widgetModel->offsetCount==0):?>
<?=$this->load->view("widget_form_partials/widget_footer", array("widgetModel"=>$widgetModel), true)?>
<?endif?>