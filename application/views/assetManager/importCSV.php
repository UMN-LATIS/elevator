
<div class="row">
	<div class="col-xs-5 col-sm-5 col-md-5 col-lg-5">

		<form action="<?=instance_url("assetManager/importFromCSV")?>" method="POST" role="form">
			<legend>CSV Import</legend>


			<div class="form-group">
				<label for="inputTemplateId" class="col-sm-2 control-label">Template:</label>
				<div class="col-sm-5">
					<select name="templateId" id="inputTemplateId" class="form-control" required="required">
						<?foreach($this->instance->getTemplates() as $template):?>
						<?if(!$template->getIsHidden()):?>
						<option value=<?=$template->getId()?>><?=$template->getName()?></option>
						<?endif?>
						<?endforeach?>
					</select>
				</div>
			</div>

			<div class="form-group">
				<label for="">Source CSV: </label>
				<input id="input-1" type="file" class="file">
			</div>

			<button type="submit" class="btn btn-primary">Upload</button>
		</form>

	</div>
</div>