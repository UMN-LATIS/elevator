<?if(!$isOffset):?>
<script>
var offset = 0;
</script>


<div class="row rowContainer">
	<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
		<table class="resultsTable table table-hover">
			<thead>
				<tr>
					<th>Id</th>
					<th>Title</th>
					<th>Template</th>
					<th>Modified</th>
					<th>Ready for Display</th>
				</tr>
			</thead>
			<tbody>
				<?endif?>
				<?foreach($hiddenAssets as $asset):?>
				<tr>
					<?if(isset($asset["deleted"])):?>
					<td><A href="<?=instance_url("/assetManager/restoreAsset/".$asset['objectId'])?>"><?=$asset['objectId']?></a></td>
					<?else:?>
					<td><A href="<?=instance_url("/assetManager/editAsset/".$asset['objectId'])?>"><?=$asset['objectId']?></a></td>
					<?endif?>
					<td><?=$asset['title']?></td>
					<td><?=($this->asset_template->getTemplate($asset['templateId'])!==null)?$this->asset_template->getTemplate($asset['templateId'])->getName():null?></td>
					<td><?=$asset["modifiedDate"]->setTimezone(new DateTimeZone('America/Chicago'))->format("m/d/y H:i:s")?></td>
					<td><span class="glyphicon <?=$asset["readyForDisplay"]?"glyphicon-ok-circle":"glyphicon-ban-circle"?>"></td>

				</tr>
				<?endforeach?>
				<?if(!$isOffset):?>
			</tbody>
		</table>
	</div>
</div>
<?endif?>