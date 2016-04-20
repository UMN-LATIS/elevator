<div class="row">

<form action="<?=instance_url("assetManager/processCSV")?>" class="form-horizontal" method="POST" role="form" >
	<legend>Importing CSV: <?=$filename?></legend>
	<input type="hidden" name="filename" value="<?=$filename?>">
	<input type="hidden" name="templateId" value="<?=$template->getId()?>">
	<div class="form-group">
		<div class="col-sm-6">
			Target Collection:
		</div>
		<div class="col-sm-6">
<select name="collectionId" id="newCollectionId" class="form-control input-large">
									<option val=-1>---</option>
									<?=$this->load->view("collection_select_partial", ["selectCollection"=>0, "collections"=>$this->instance->getCollectionsWithoutParent(), "allowedCollections"=>$this->user_model->getAllowedCollections(PERM_ADDASSETS)],true);?>
								</select>
		</div>
	</div>
	<hr>
	<?foreach($headerRows as $row):?>
	<div class="form-group">
		<div class="col-sm-4">
			<?=$row?>
		</div>
		<div class="col-sm-4">
			<select name="targetField[]" class="form-control">
				<option value="ignore">Don't import</option>
			<?foreach($template->widgetArray as $widget):?>
				<option value="<?=$widget->getFieldTitle()?>"><?=$widget->getLabel()?></option>
			<?endforeach?>
			</select>
		</div>
		<div class="col-sm-2">
			<input name="delimiter[]" cass="form-control" placeholder="delimiter">
		</div>
	</div>
	<?endforeach?>

	<button type="submit" class="btn btn-primary">Import</button>
</form>


	
</div>