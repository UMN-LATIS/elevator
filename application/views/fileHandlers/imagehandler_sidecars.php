<div class="form-group">
	<label for="<?=$formFieldRoot?>_ppm" class="col-sm-3 control-label">Pixels Per Millimeter</label>
	<div class="col-sm-5">
		<input type="text" name="<?=$formFieldRoot?>[ppm]" id="<?=$formFieldRoot?>_ppm" class="form-control" placeholder="Optional value" value="<?=isset($sidecarData['captions'])?$sidecarData['ppm']:null?>">
	</div>
</div>
