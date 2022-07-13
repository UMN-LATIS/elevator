<?php
$topLevels = getTopLevels($widgetFieldData);
?>

<?$j=0; foreach($topLevels as $topLevel):?>
 <div class="form-group" id="<?=$formFieldId?>_<?=makeSafeForTitle($topLevel)?>_label">
     <label for="<?=$formFieldId?>_<?=makeSafeForTitle($topLevel)?>"
         class="col-sm-2 control-label"><?=$topLevel?></label>
     <div class="col-sm-4">
         <select class="form-control <?=($j==0)?"mainWidgetEntry":null?> multiSelect" data-category="<?=$topLevel?>"
             data-cascadenumber=<?=$j?> id="<?=$formFieldId?>_<?=makeSafeForTitle($topLevel)?>"
             name="<?=$formFieldName?>[fieldContents][<?=makeSafeForTitle($topLevel)?>]">
             <option></option>
         </select>
     </div>
 </div>

 <?$j++;endforeach?>

<script>

if(typeof selectedItems === 'undefined') {

	var selectedItems = {};
}

selectedItems["<?=$formFieldId?>"] = {};

if(!sourceContent) {
	var sourceContent = new Array();
}
sourceContent["<?=$formFieldId?>"] = $.parseJSON('<?=preg_replace( "/\r|\n/", "",addslashes(json_encode($widgetFieldData)))?>');
</script>