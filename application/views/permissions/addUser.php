<div class="row rowContainer">

<script>
$(document).on("ready", function() {

	$("#inputExpires").datepicker();
	$("#expires").on("change", function() {
		if($(this).is(':checked')) {
			$("#expiresGroup").show();
		}
		else {
			$("#expiresGroup").hide();
		}
	});
	$("#expires").trigger("change");

	$("form").on("submit",function() {
		if($("#inputPassword").val() != $("#inputRepeatPassword").val()) {
			alert("Passwords do not match");
			return false;
		}
		if($("#inputPassword").val() === "") {
			alert("Password cannot be blank");
			return false;
		}
	})

	
	$(document).on("click", ".autoSelectField", function() {
		$(this).select();
	});	
});


</script>

<form action="<?=instance_url("permissions/saveUser")?>" method="POST" class="form-horizontal" role="form">
		<div class="form-group">
			<legend>Add/Edit a User</legend>
		</div>
		<input type="hidden" name="userId" id="inputUserId" class="form-control" value="<?=$user->getId()?>">
		<?if($user->getUserType() == "Local"):?>
		<div class="form-group">
			<label for="inputUsername" class="col-sm-2 control-label">Username:</label>
			<div class="col-sm-5">
				<input type="text" name="username" id="inputUsername" class="form-control" value="<?=$user->getUsername()?>" >
			</div>
		</div>
		<?endif?>
		<div class="form-group">
			<label for="inputLabel" class="col-sm-2 control-label">Display Name:</label>
			<div class="col-sm-5">
				<input type="text" name="label" id="inputLabel" class="form-control" value="<?=$user->getDisplayName()?>" >
			</div>
		</div>
		<?if($user->getUserType() == "Local"):?>
		<div class="form-group">
			<label for="inputPassword" class="col-sm-2 control-label">Password:</label>
			<div class="col-sm-5">
				<input type="password" name="password" id="inputPassword" value="<?=$user->getPassword()?"dontchangeme":null?>" class="form-control">
			</div>
		</div>

		<div class="form-group">
			<label for="inputRepeatPassword" class="col-sm-2 control-label">Repeat Password:</label>
			<div class="col-sm-5">
				<input type="password" name="repeatPassword" id="inputRepeatPassword" value="<?=$user->getPassword()?"dontchangeme":null?>" class="form-control">
			</div>
		</div>
		<?endif?>
		<div class="form-group">
			<label for="inputEmail" class="col-sm-2 control-label">Email:</label>
			<div class="col-sm-5">
				<input type="text" name="email" id="inputEmail" class="form-control" value="<?=$user->getEmail()?>" >
			</div>
		</div>

		<?if(isset($instanceList)):?>

		<div class="form-group">
			<label for="inputEmail" class="col-sm-2 control-label">Preferred Instance for Moodle:</label>
			<div class="col-sm-5">
				<select name="apiInstance" class="form-control">
					<option value=0 >None</option>
				<?foreach($instanceList as $instance):?>
					<option <?=($user->getApiInstance() && $instance->getId()==$user->getApiInstance()->getId())?"SELECTED":null?> value=<?=$instance->getId()?>><?=$instance->getName()?></option>
				<?endforeach?>
				</select>
			</div>
		</div>


		<?endif?>

		<div class="form-group" style="display:none;">
			<label for="inputEmail" class="col-sm-2 control-label">Canvas (LTI) URL:</label>
			<div class="col-sm-5">
				<input type="text" value="<?=instance_url("/api/v1/lti/ltiConfig")?>" class="autoSelectField form-control">
			</div>
		</div>
		<div class="form-group">
			<label for="inputAPIKey" class="col-sm-2 control-label">API Key:</label>
			<div class="col-sm-5">
				<input type="text" name="apikey" id="inputAPIKey" class="form-control" value="<?=$apiKey->getApiKey()?>" >
			</div>
		</div>
		<div class="form-group">
			<label for="inputAPISecret" class="col-sm-2 control-label">API Secret:</label>
			<div class="col-sm-5">
				<input type="password" data-toggle="password"  name="apisecret" id="inputAPISecret" class="form-control" value="<?=$apiKey->getAPISecret()?>" >
			</div>
		</div>

<!--

		<div class="form-group">
			<div class="col-sm-offset-2 col-sm-8">
				<label>
					<input type="checkbox" id="fastUpload" name="fastUpload" value="On" <?=$user->getFastUpload()?"checked":null?>>
					High Bandwidth User
				</label>
			</div>
		</div> -->

		<?if($this->user_model->getIsSuperAdmin()):?>
		<div class="form-group">
			<div class="col-sm-offset-2 col-sm-8">
				<label>
					<input type="checkbox" id="isSuperAdmin" name="isSuperAdmin" value="On" <?=$user->getIsSuperAdmin()?"checked":null?>>
					SuperAdmin
				</label>
			</div>
		</div>
		<?endif?>
		<?if($this->user_model->getAccessLevel("instance",$this->instance) >= PERM_ADMIN):?>
		<div class="form-group">
			<div class="col-sm-offset-2 col-sm-8">
				<label>
					<input type="checkbox" id="expires" name="hasExpiry" value="On" <?=$user->getHasExpiry()?"checked":null?>>
					Expires
				</label>
			</div>
		</div>

		<div class="form-group" id="expiresGroup">
			<label for="inputExpires" class="col-sm-2 control-label">Expires:</label>
			<div class="col-sm-5">
				<input type="text" name="expires" id="inputExpires" class="form-control" value="<?=($user->getExpires())?$user->getExpires()->format("m/d/Y"):null?>">
			</div>
		</div>
		<?endif?>
		<div class="form-group">
			<div class="col-sm-5 col-sm-offset-2">
				<button type="submit" class="btn btn-primary pull-left">Save</button>
			</form>
			<?if($user->getId()):?>
			<form action="<?=instance_url("permissions/removeUser")?>" method="POST" class="form-inline" role="form">
				<input type="hidden" name="userId" id="inputUserId" class="form-control" value="<?=$user->getId()?>">
				<button type="submit" class="btn btn-danger buttonWithPadding">Remove</button>
			</form>
			<?endif?>
			</div>
		</div>

		<?if(1==0 && $user->getId() == $this->user_model->getId()):?>
		<pre>
			<?=var_dump($this->user_model->userData);?>
		</pre>
		<?endif?>
</form>

</div>