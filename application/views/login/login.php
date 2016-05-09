<div class="row loginBox">
	<div class="col-md-9">

		<form action="<?=instance_url("loginManager/localLogin")?>" method="POST" class="form-horizontal" role="form">
			<div class="form-group">
				<legend>Log in to <?=$this->instance->getName()?></legend>
				<p class="help-block universityAuth">
					If you're a University user, <A href="<?=instance_url("loginManager/remoteLogin/?redirect=".current_url())?>" class="">click here</a> to login.</a>
				</p>
			</div>

			<div class="form-group">
				<label for="inputUsername" class="col-sm-2 control-label">Username:</label>
				<div class="col-sm-6">
					<input type="text" name="username" id="inputUsername" class="form-control" value="" required="required"  title="">
				</div>
			</div>

			<div class="form-group">
				<label for="inputPassword" class="col-sm-2 control-label">Password:</label>
				<div class="col-sm-6">
					<input type="password" name="password" id="inputPassword" class="form-control" required="required" title="">
				</div>
			</div>

			<input type="hidden" name="redirectURL" value="<?=$redirectURL?>" >
			<script>
			if(window.location.hash) {
				$('[name="redirectURL"]').val($('[name="redirectURL"]').val() + window.location.hash);
			}
			</script>
			<div class="form-group">
				<div class="col-sm-6 col-sm-offset-2">
					<button type="submit" class="btn btn-primary">Login</button>
				</div>

			</div>
		</form>


	</div>
</div>