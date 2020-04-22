<div class="row rowContainer">
	<div class="col-md-12">
		<table class="table table-striped" id="collectionList">
<thead>
						<th>Collection</th>
						<th>Parent</th>
						<th>Edit Permissions</th>
						<th>Edit Collection</th>
						<th>Share</th>
						<th>Delete</th>
					</thead>
				<tbody>
					
					<?php foreach ($collections as $collection) : ?>
					<tr>
						<td><?= $collection->getTitle(); ?></td>
						<td><?= $collection->getParent()?$collection->getParent()->getTitle():null?></td>
						<td><a href="<?= instance_url("permissions/edit/collection/{$collection->getId()}"); ?>">Edit Permissions</a></td>
						<td><a href="<?= instance_url("collectionManager/edit/{$collection->getId()}"); ?>">Edit</a></td>
						<td><a href="<?= instance_url("collectionManager/share/{$collection->getId()}"); ?>">Share</a></td>
						<td><a onClick="return confirm('Are you sure you wish to delete this collection?')" href="<?= instance_url("collectionManager/delete/{$collection->getId()}"); ?>">Delete</a></td>
					</tr>
				<?php	endforeach; ?>

			</tbody>
		</table>

		<p><a href="<?= instance_url("collectionManager/edit"); ?>">Create New Collection</a></p>
		<script>
			$(document).ready(function() {
    $('#collectionList').DataTable( {
    	"paging":   false,
    	"ordering": true,
    	"info": false,
        "order": [[ 0, "asc" ]],
        "columns":[
            {
                "sortable": true
            },
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
            }
        ]
    } );
} );	</script>
	</div>
</div>

