
<?if($this->instance->getUseVoyagerViewer()):?>
<div class="form-group">
	<label for="<?=$formFieldRoot?>[svx]" class="col-sm-3 control-label">SVX File</label>
	<div class="col-sm-5">
		<textarea class="form-control" name="<?=$formFieldRoot?>[svx]" placeholder="SVX"><?=isset($sidecarData['svx'])?json_encode($sidecarData['svx']):null?></textarea>
	</div>
	<div class="col-sm-3">
		<label for="file-upload" class="btn btn-default">Load from File</label>
		<input id="file-upload" type="file" class="importTextForSidecar" style="display: none;" data-target="<?=$formFieldRoot?>[svx]" />
	</div>
</div>
<?else:?>
<div class="form-group">
	<label for="<?=$formFieldRoot?>[3dpoints]" class="col-sm-3 control-label">3D Points</label>
	<div class="col-sm-5">
		<textarea class="form-control" name="<?=$formFieldRoot?>[3dpoints]" placeholder="3D points"><?=isset($sidecarData['3dpoints'])?$sidecarData['3dpoints']:null?></textarea>
	</div>
</div>
<?endif?>