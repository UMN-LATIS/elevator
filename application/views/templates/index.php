<div class="row rowContainer">
	<div class="col-md-9">
		<table class="table table-striped">
			<thead>
				<th>Template Name</th>
				<th>Edit</th>
				<th>Sort Order</th>
				<th>Copy</th>
				<th>Delete</th>
			</thead>
			<tbody>
				<?php foreach ($templates as $template) : ?>
				<tr>
    			<td><?= $template->getName(); ?></td>
    			<td><a href="<?= instance_url("templates/edit/{$template->getId()}"); ?>">Edit</a></td>
    			<td><a href="<?= instance_url("templates/sort/{$template->getId()}"); ?>">Sort Order</a></td>
    			<td><a href="<?= instance_url("templates/copy/{$template->getId()}"); ?>">Duplicate</a></td>
    			<td><a onclick="return confirm('Are you sure you wish to delete this template?')" href="<?= instance_url("templates/delete/{$template->getId()}"); ?>">Delete</a></td>
   			</tr>
			<?php	endforeach; ?>
			</tbody>
		</table>
		<p><a href="<?= instance_url("templates/edit"); ?>">Create New Template</a></p>
	</div>
</div>
