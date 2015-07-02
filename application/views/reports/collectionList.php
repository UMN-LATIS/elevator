<div class="row rowContainer">
	<form method="post" action="<?=instance_url("reports/collectionStats")?>">
	<div class="col-sm-3">
		Show Assets Of Type:
	</div>
	<div class="col-sm-5">
		<select name="templateId" class="form-control col-sm-3">
			<option value="">All</option>
			<?foreach($this->instance->getTemplates() as $template):?>
				<option value="<?=$template->getId()?>"><?=$template->getName()?></option>
			<?endforeach?>
			</select>
		</div>
	<div class="col-sm-2">
		<input type="submit" value="Filter" class="btn btn-default">
	</div>
	</form>
</div>

<div class="row rowContainer">
	<div class="col-sm-12">

		<table class="table table-striped">
		<thead>
			<tr>
				<td>Collection</td>
				<td>Count</td>
			</tr>
		</thead>
		<?foreach($collections as $collection):?>
			<tr>
				<td><a href="<?=instance_url("collections/browseCollection/" . $collection["collection"]->getId())?>"><?=$collection["collection"]->getTitle()?></a></td>
				<td><?=$collection["count"]?></td>
			</tr>
		<?endforeach?>
		</table>

	</div>
</div>