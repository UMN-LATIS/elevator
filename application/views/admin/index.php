
<div class="row rowContainer">
	<div class="col-md-9">
		<ul>
			<li><a href="<?=instance_url("instances")?>">Manage Instances</a></li>
			<li><a href="<?=instance_url("admin/logs")?>">Error Logs</a></li>
			<li><a href="<?=instance_url("admin/reindex")?>">Reindex</a></li>
			<li><a href="<?=instance_url("admin/processingLogs")?>">Processing Logs</a></li>
			<li><a href="<?=instance_url("admin/beanstalk")?>">Job Queue Status</a></li>
			<li><a href="<?=instance_url("admin/showPendingDeletes")?>">Files Pending Deletion</a></li>
			<li><a href="<?=instance_url("admin/updateDateHolds")?>">Update Date Holds</a></li>
			<li><a href="<?=instance_url("admin/hiddenAssets")?>">Hidden Assets</a></li>
			<li><a href="<?=instance_url("admin/recentAssets")?>">Recent Assets</a></li>
			<li><a href="<?=instance_url("admin/deletedAssets")?>">Deleted Assets</a></li>
			<li><a href="<?=instance_url("admin/listAPIkeys")?>">API Keys</a></li>
			<li><form action="admin/userLookup" method="POST" class="form-inline" role="form">

	<div class="form-group">
		<label class="sr-only" for="">User Lookup</label>
		<input type="text" name="inputGroupValue" class="form-control" id="inputGroupValue" placeholder="User Lookup">
		<input type="hidden" name="inputGroupType" class="form-control" value="User" id="inputGroupType">
	</div>
	<button type="submit" class="btn btn-primary">Edit</button>
	<img class="spinner" style="display:none;" src="/assets/images/ajax-loader.gif">
</form>
			</li>
		</ul>
	</div>
</div>
