<div class="row loginBox">
	<div class="col-md-9">

		<form action="<?=instance_url("loginManager/localLogin")?>" method="POST" class="form-horizontal" role="form">
			
		<?if(!isset($localOnly) || $localOnly == false):?>
			<div class="form-group">
				<legend>Sign In to <?=$this->instance->getName()?></legend>
				
				<A href="<?=instance_url("loginManager/remoteLogin/?redirect=".current_url())?>" class="btn btn-primary">University Sign In</A>
				<a class="btn btn-info" role="button" data-toggle="collapse" href="#localUser" aria-expanded="false" aria-controls="localUser">Local User</a>
			</div>

			<div class="collapse" id="localUser">
		<?endif?>
				<div class="form-group">

			    <div class="col-sm-10">
			      <p class="form-control-static">Enter your Elevator username and password below.</p></p>
			    </div>
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
			<?if(!isset($localOnly) || $localOnly == false):?>
				</div>
			<?endif?>
		</form>


	</div>
</div>