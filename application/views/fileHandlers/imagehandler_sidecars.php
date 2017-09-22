<div class="form-group">
	<label for="<?=$formFieldRoot?>_ppm" class="col-sm-3 control-label">Pixels Per Millimeter</label>
	<div class="col-sm-5">
		<input type="text" name="<?=$formFieldRoot?>[ppm]" id="<?=$formFieldRoot?>_ppm" class="form-control" placeholder="Optional value" value="<?=isset($sidecarData['ppm'])?$sidecarData['ppm']:null?>">
	</div>
</div>

<?if(isset($sidecarData['dendro'])): // todo, abstract this?>
<div class="form-group">
	<label for="<?=$formFieldRoot?>_dendro" class="col-sm-3 control-label">Dendro Data</label>
	<div class="col-sm-5">
		<textarea class="form-control" id="<?=$formFieldRoot?>_dendro" name="<?=$formFieldRoot?>[dendro]" placeholder=""><?=json_encode($sidecarData['dendro'])?></textarea>
	</div>
</div>
<?endif?>