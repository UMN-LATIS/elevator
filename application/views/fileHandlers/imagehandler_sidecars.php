<div class="form-group">
	<label for="<?=$formFieldRoot?>_ppm" class="col-sm-3 control-label">Pixels Per Millimeter</label>
	<div class="col-sm-5">
		<input type="text" name="<?=$formFieldRoot?>[ppm]" id="<?=$formFieldRoot?>_ppm" class="form-control" placeholder="Optional value" value="<?=isset($sidecarData['ppm'])?$sidecarData['ppm']:null?>">
	</div>
</div>

<?

$uploadWidget = $fileHandler->getUploadWidget();


if($uploadWidget && $uploadWidget->parentWidget->enableIframe):
?>

<div class="form-group">
	<label for="<?=$formFieldRoot?>_iframe" class="col-sm-3 control-label">iFrame URL</label>
	<div class="col-sm-5">
		<input type="text" name="<?=$formFieldRoot?>[iframe]" id="<?=$formFieldRoot?>_iframe" class="form-control" placeholder="HTTPS highly recommend" value="<?=isset($sidecarData['iframe'])?$sidecarData['iframe']:null?>">
	</div>
</div>

<?endif?>


<?if(isset($sidecarData['dendro'])): // todo, abstract this?>
<div class="form-group">
	<label for="<?=$formFieldRoot?>_dendro" class="col-sm-3 control-label">Dendro Data</label>
	<div class="col-sm-5">
		<textarea class="form-control" id="<?=$formFieldRoot?>_dendro" name="<?=$formFieldRoot?>[dendro]" placeholder=""><?=json_encode($sidecarData['dendro'])?></textarea>
	</div>
</div>
<?endif?>