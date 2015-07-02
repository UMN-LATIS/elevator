<div class="row rowContainer">
	<div class="col-md-9">
		<table class="table table-striped">

				<tbody>

					<?php foreach ($keys as $key) : ?>
					<tr>
						<td><?= $key->getLabel(); ?></td>
						<td><a href="<?= instance_url("admin/editAPIkey/{$key->getId()}"); ?>">Edit</a></td>
						<td><a onClick="return confirm('Are you sure you wish to delete this key?')" href="<?= instance_url("admin/removeAPIkey/{$key->getId()}"); ?>">Delete</a></td>
					</tr>
				<?php	endforeach; ?>

			</tbody>
		</table>

		<p><a href="<?= instance_url("admin/editAPIkey"); ?>">Create New Key</a></p>
	</div>
</div>

