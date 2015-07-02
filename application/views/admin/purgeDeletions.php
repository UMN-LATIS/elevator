<div class="row rowContainer">
	<div class="col-md-9">
		<ul>
			<?foreach($objectArray as $item):?>
				<li><?=$item["objectId"]?> (<?=$item["filename"]?>)</li>
			<?endforeach?>
		</ul>

		<form action="<?=instance_url("admin/purgeAll")?>" method="POST" class="form-inline" role="form">

			<div class="form-group">
				<label class="sr-only" for="">Serial (ARN)</label>
				<input type="arn" name="arn" class="form-control" id="" placeholder="arn">
			</div>
			<div class="form-group">
				<label class="sr-only" for="">MFA key</label>
				<input type="mfa" name="mfa" class="form-control" id="" placeholder="MFA">
			</div>



			<button type="submit" class="btn btn-primary">Submit</button>
		</form>
	</div>
</div>
