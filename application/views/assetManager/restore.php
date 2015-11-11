<p>Restore a previous verison of this asset.  The current version will be saved.</p>

<table>
	<?foreach($assetArray as $asset):?>
	<tr>
		<td><?=date('m/d/y H:i:s', $asset->getGlobalValue("modified")->sec)?></td>
		<td><a class="btn btn-default btn-xs" href="/asset/viewRestore/<?=$asset->getObjectId()?>">Preview</a></td>
		<td><a class="btn btn-primary btn-xs" href="/assetManager/restore/<?=$asset->getObjectId()?>">Restore</a></td>
	</tr>
	<?endforeach?>
</Table>
