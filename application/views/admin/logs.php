
<div class="row rowContainer">
	<div class="col-md-12">
		<table class="table table-hover" style="table-layout: fixed;
word-wrap: break-word;">
			<thead>
				<tr>
					<th class="col-md-2">Date</th>
					<th>Asset</th>
					<th>Task</th>
					<th class="col-md-7">Message</th>
					<th>Instance</th>
					<th>Collection</th>
					<th>User</th>
				</tr>
			</thead>
			<tbody>
				<?foreach($lastErrors as $log):?>

				<tr>
					<td><?=$log->getCreatedAt()->format('Y-m-d H:i:s')?></td>
					<td><?=$log->getAsset()?></td>
					<td><?=$log->getTask()?></td>
					<td class="autoTruncate"><?=htmlentities($log->getMessage())?></td>
					<td><a href="<?=instance_url("/instances/edit/".($log->getInstance()?$log->getInstance()->getId():null))?>"><?=$log->getInstance()?$log->getInstance()->getId():null?></td>
					<td><a href="<?=instance_url("/collections/edit/".($log->getCollection()?$log->getCollection()->getId():null))?>"><?=$log->getCollection()?$log->getCollection()->getId():null?></td>
					<td><a href="<?=instance_url("/admin/user/".($log->getUser()?$log->getUser()->getId():null))?>"><?=$log->getUser()?$log->getUser()->getId():null?></td>
				</tr>
				<?endforeach?>
			</tbody>
		</table>
	</div>
</div>
<script>
$(document).ready(function() {
	$(".autoTruncate").on("click", function() {
		$(this).wrapInner("<pre></pre>");
		$(this).css("white-space", "normal");
		$(this).css("overflow", "visible");
		$(this).off("click");
	})


})
</script>
