
<div class="panel panel-default">
	<div class="panel-body">
	<strong>Beta Feature!</strong> We're still working on the CSV import feature.  If you run into trouble, please let us know.
	</div>
</div>

<div class="row">
	<div class="col-xs-5 col-sm-5 col-md-5 col-lg-5">
		<form action="<?=instance_url("assetManager/importFromCSV")?>" class="form-horizontal" method="POST" role="form" enctype="multipart/form-data">
			<legend>CSV Import</legend>


			<div class="form-group">
				<label for="inputTemplateId" class="col-sm-2 control-label">Template:</label>
				<div class="col-sm-10">
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
				<label for="fileInput" class="col-sm-2 control-label">Source CSV: </label>
				<div class="col-sm-10">
				<input id="fileInput" type="file" name="userfile" class="file form-control">
				</div>
			</div>

			<button type="submit" class="btn btn-primary">Upload</button>
		</form>

	</div>
</div>

<hr>
<div class="row">
	<div class="col-sm-12">
	<table class="table">
		<thead>
			<tr>
				<th>ID</th>
				<th>Template</th>
				<th>Collection</th>
				<th>Filename</th>
				<th>Asset Count</th>
		</thead>
		<tbody>
			<?foreach($csvBatches as $batch):?>
			<tr>
				<td><A href="<?=instance_url("/search/scopedQuerySearch/csvBatch/". rawurlencode($batch->getId()))?>"><?=$batch->getId()?></a></td>
				<td><?=$batch->getTemplate()->getName()?></td>
				<td><?=$batch->getCollection()->getTitle()?></td>
				<td><?=$batch->getFilename()?></td> 
				<td><?=$batch->getAssets()->count()?></td>
			</tr>
			<?endforeach?>
		</tbody>

	</table>
	</div>
</div>