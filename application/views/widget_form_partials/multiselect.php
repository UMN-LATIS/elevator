<?if($widgetModel->offsetCount==0):?>
<?=$this->load->view("widget_form_partials/widget_header", array("widgetModel"=>$widgetModel), true)?>
<?endif?>


<?

function flatten(array $array) {
    $return = array();
    array_walk_recursive($array, function($a) use (&$return) { $return[] = $a; });
    return $return;
}

function recurseThing($array, $skip) {

	$outputArray = array();
	foreach($array as $key=>$value) {
		if(!$skip) {
			$outputArray[] = $key;
		}
		if(is_array($value) || is_object($value)) {
			$outputArray[] = recurseThing($value, !$skip);
		}


	}

	return $outputArray;


}


/**
 * Strip any special characters so we can use these in form names.
 * @param  [string] $sourceName
 * @return [string] sanitized name
 */
function makeSafeForTitle($sourceName) {
	return preg_replace("/[^a-zA-Z0-9]+/", "", $sourceName);
}



?>



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



$topLevels = array_unique(flatten(recurseThing($widgetModel->getFieldData(),0)));


?>


<script>
if(!sourceContent) {
	var sourceContent = new Array();
}
if(!selectedItems) {
	var selectedItems = new Array();
}

selectedItems["<?=$formFieldId?>"] = new Array();

sourceContent["<?=$formFieldId?>"] = $.parseJSON('<?=preg_replace( "/\r|\n/", "",addslashes($widgetModel->getFieldData()))?>');

<?if(isset($fieldContents)):foreach($fieldContents as $key=>$value):?>
selectedItems["<?=$formFieldId?>"]["<?=$key?>"] = "<?=$value?>";
<?endforeach; endif;?>

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
loadGroup("<?=$formFieldId?>");
</script>


<?endfor?>


<?if($widgetModel->offsetCount==0):?>
<?=$this->load->view("widget_form_partials/widget_footer", array("widgetModel"=>$widgetModel), true)?>
<?endif?>