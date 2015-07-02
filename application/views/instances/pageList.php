<div class="row rowContainer">
	<div class="col-md-9">
		<table class="table table-striped">

				<tbody>

					<?php foreach ($pages as $page) : ?>
					<tr>
						<td><a href="<?=instance_url("page/view/" . $page->getId())?>"><?= $page->getTitle(); ?></a></td>
						<td><a href="<?= instance_url("instances/editPage/{$page->getId()}"); ?>">Edit</a></td>
						<td><a onClick="return confirm('Are you sure you wish to delete this Page?')" href="<?= instance_url("instances/deletePage/{$page->getId()}"); ?>">Delete</a></td>
					</tr>
				<?php	endforeach; ?>

			</tbody>
		</table>

		<p><a href="<?= instance_url("instances/editPage"); ?>">Create New Page</a></p>
	</div>
</div>

