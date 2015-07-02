<div class="row">
	<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">

		<table class="table table-hover">
			<thead>
				<tr>
					<th>Custom Search</th>
					<th>Delete</th>
				</tr>
			</thead>
			<tbody>
				<?foreach($searches as $search):?>
				<tr>
					<td><a href="<?=instance_url("search/searchBuilder/". $search->getId())?>"><?=$search->getSearchTitle()?></a></td>
					<td><a href="<?=instance_url("search/deleteSearch/". $search->getId())?>">Delete</a></td>
				</tr>
				<?endforeach?>
			</tbody>
		</table>
		<a href="<?=instance_url("search/searchBuilder")?>" class="btn btn-default">Add Entry</a>


	</div>
</div>