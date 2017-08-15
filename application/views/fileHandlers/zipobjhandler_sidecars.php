<div class="form-group">
	<label for="<?=$fileDescriptionId?>" class="col-sm-3 control-label">3D Points</label>
	<div class="col-sm-5">
		<textarea class="form-control" name="<?=$formFieldRoot?>[3dpoints]" placeholder="3D points"><?=isset($sidecarData['3dpoints'])?$sidecarData['3dpoints']:null?></textarea>
	</div>
</div>
