<div class="form-group">
	<label for="<?=$formFieldRoot?>_captions" class="col-sm-3 control-label">Captions</label>
	<div class="col-sm-5">
		<textarea class="form-control" id="<?=$formFieldRoot?>_captions"name="<?=$formFieldRoot?>[captions]" placeholder="Captions (SRT)"><?=isset($sidecarData['captions'])?$sidecarData['captions']:null?></textarea>
	</div>
</div>
