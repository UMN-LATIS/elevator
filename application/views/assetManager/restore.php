<p>Restore a previous verison of this asset.  The current version will be saved.</p>

<table>
	<?foreach($assetArray as $asset):?>
	<tr>
		<td><?=$asset->getGlobalValue("modified")->format("m/d/y H:i:s")?></td>
		<td><a class="btn btn-default btn-xs" href="<?=instance_url("/asset/viewRestore/" . $asset->getIndexId())?>">Preview</a></td>
		<td><a class="btn btn-primary btn-xs" href="<?=instance_url("/assetManager/restore/" . $asset->getIndexId())?>">Restore</a></td>
	</tr>
	<?endforeach?>
</Table>
