
<div class="row rowContainer">
	<div class="col-sm-12">

		<table class="table table-striped">
		<thead>
			<tr>
				<td>Drawer Title</td>
				<td>Item Count</td>
			</tr>
		</thead>
		<?foreach($drawers as $drawer):?>
			<tr>
				<td><a href="<?=instance_url("drawers/viewDrawer/" . $drawer->getId())?>"><?=$drawer->getTitle()?></a></td>
				<td><?=$drawer->getItems()->count()?></td>
			</tr>
		<?endforeach?>
		</table>

	</div>
</div>