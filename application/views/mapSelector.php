
<div id="<?=$mapId?>" class="collapse maphost">
	<div class="form-group">
		<label for="input" class="col-sm-2 control-label">Address:</label>
		<div class="col-sm-4">
			<input type="text" class="form-control address" <?=isset($addressName)?"name='$addressName'":null?> <?=isset($addressContents)?"value='$addressContents'":null?>  title="">
		</div>
		<div class="col-sm-4">
			<button type="button" class="btn btn-primary searchMap">Search</button>
			<button type="button" class="btn btn-danger clearMarker">Clear</button>
		</div>

	</div>
	<div class="mapWidget">

	</div>
</div>