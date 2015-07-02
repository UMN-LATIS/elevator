<div class="row rowContainer">
	<div class="col-md-9">
		<form method="post" class="form-horizontal" accept-charset="utf-8" action="<?= instance_url("admin/saveKey/"); ?>" />
			<input type="hidden" name="keyId"  value="<?= isset($key)?$key->getId():null; ?>" /><br />
			<div class="form-group">
				<label for="inputLabel" class="col-sm-2 control-label">Label:</label>
				<div class="col-sm-6">
					<input type="text" name="label" id="inputLabel" class="form-control" value="<?= $key->getLabel() ?>" >
				</div>
			</div>
			<div class="form-group">
				<label for="inputKey" class="col-sm-2 control-label">Key:</label>
				<div class="col-sm-6">
					<input type="text" name="apiKey" id="inputKey" class="form-control" value="<?= $key->getApiKey() ?>" >
				</div>
			</div>
			<div class="form-group">
				<label for="inputSecret" class="col-sm-2 control-label">Secret:</label>
				<div class="col-sm-6">
					<input type="text" name="apiSecret" id="inputSecret" class="form-control" value="<?= $key->getApiSecret() ?>" >
				</div>
			</div>
			<div class="form-group">
				<div class="col-sm-offset-2 col-sm-8">
					<label>
						<input type="checkbox" id="includeInHeader" name="read" value="On" <?=$key->getAllowsRead()?"checked":null?>>
						Read
					</label>
				</div>
			</div>
			<div class="form-group">
				<div class="col-sm-offset-2 col-sm-8">
					<label>
						<input type="checkbox" id="includeInHeader" name="write" value="On" <?=$key->getAllowsWrite()?"checked":null?>>
						Write
					</label>
				</div>
			</div>


			<input type="submit" name="submit" value="Update Key" class='btn btn-primary' />

		</form>
	</div>
</div>
