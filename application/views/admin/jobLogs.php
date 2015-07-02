
<div class="row rowContainer">
	<div class="col-md-12">
		<table class="table table-hover" style="table-layout: fixed;
word-wrap: break-word;">
			<thead>
				<tr>
					<th class="col-md-2">Date</th>
					<th>Asset</th>
					<th>Task</th>
					<th>Type</th>
					<th>JobId</th>
					<th class="col-md-5">Message</th>
				</tr>
			</thead>
			<tbody>
				<?foreach($lastErrors as $log):?>

				<tr>
					<td><?=$log->getCreatedAt()->format('Y-m-d H:i:s')?></td>
					<td><a href="<?=instance_url("/search/querySearch/".$log->getAsset())?>"><?=$log->getAsset()?></a></td>
					<td><?=$log->getTask()?></td>
					<td><?=$log->getType()?></td>
					<td><?=$log->getJobId()?></td>
					<td class="autoTruncate"><?=htmlentities($log->getMessage())?></td>
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
