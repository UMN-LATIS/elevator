<div class="form-group">
	<label for="<?=$formFieldRoot?>_captions" class="col-sm-3 control-label">Captions</label>
	<div class="col-sm-5">
		<textarea class="form-control" id="<?=$formFieldRoot?>_captions"name="<?=$formFieldRoot?>[captions]" placeholder="Captions (WebVTT or SRT)"><?=isset($sidecarData['captions'])?$sidecarData['captions']:null?></textarea>
	</div>
</div>

<div class="form-group">
	<label for="<?=$formFieldRoot?>_chapters" class="col-sm-3 control-label">Chapter Markers</label>
	<div class="col-sm-5">
		<textarea class="form-control" id="<?=$formFieldRoot?>_captions"name="<?=$formFieldRoot?>[chapters]" placeholder="Chapter Markers (WebVTT)"><?=isset($sidecarData['chapters'])?$sidecarData['chapters']:null?></textarea>
	</div>
</div>
