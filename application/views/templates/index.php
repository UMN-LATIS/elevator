<div class="row rowContainer">
	<div class="col-md-9">
		<table class="table table-striped" id="templateList">
			<thead>
				<th>Template Name</th>
				<th>Edit</th>
				<th>Sort Order</th>
				<th>Copy</th>
				<Th>Reindex</th>
				<th>Delete</th>
			</thead>
			<tbody>
				<?php foreach ($templates as $template) : ?>
				<tr>
    			<td><?= $template->getName(); ?></td>
    			<td><a href="<?= instance_url("templates/edit/{$template->getId()}"); ?>">Edit</a></td>
    			<td><a href="<?= instance_url("templates/sort/{$template->getId()}"); ?>">Sort Order</a></td>
    			<td><a href="<?= instance_url("templates/copy/{$template->getId()}"); ?>">Duplicate</a></td>
    			<td><a onclick="return confirm('Are you sure you wish to reindex this template and any related templates?')" href="<?= instance_url("templates/forceRecache/{$template->getId()}"); ?>">Reindex</a></td>
    			<td><a onclick="return confirm('Are you sure you wish to delete this template?')" href="<?= instance_url("templates/delete/{$template->getId()}"); ?>">Delete</a></td>
   			</tr>
			<?php	endforeach; ?>
			</tbody>
		</table>
		<p><a href="<?= instance_url("templates/edit"); ?>">Create New Template</a></p>
		<script>
			$(document).ready(function() {
    $('#templateList').DataTable( {
    	"paging":   false,
    	"ordering": true,
    	"info": false,
        "order": [[ 0, "asc" ]],
        "columns":[
            {
                "sortable": true
            },
            {
                "sortable": false
            },
            {
                "sortable": false
            },
            {
                "sortable": false
            },
            {
                "sortable": false
            },
			{
                "sortable": false
            }
        ]
    } );
} );	</script>
	</div>
</div>
