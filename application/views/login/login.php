<script>
$(document).ready(function() {
	if(!document.cookie) {
        window.onload = function(e) {
            document.body.innerHTML = "To load this resource, please click the button below.  If you continue to encounter this issue, your web browser may be blocking cookies.  <input type=button value='Load Resource' onClick='makeRequestWithUserGesture();'>";
        }
    }
});

</script>

<div class="row loginBox">
	<div class="col-md-9">

		<form action="<?=instance_url("loginManager/localLogin")?>" method="POST" class="form-horizontal" role="form">
			
		<?if(!isset($localOnly) || $localOnly == false):?>
			<div class="form-group">
				<legend>Sign In to <?=$this->instance->getName()?></legend>
				
				<A href="<?=instance_url("loginManager/remoteLogin/?redirect=".current_url())?>" class="btn btn-primary loginLink"><?=$this->config->item("remoteLoginLabel")?> Sign In</A>
				<script>

				function isCrossOriginFrame() {
					try {
						return (!window.top.location.hostname);
					} catch (e) {
						return true;
					}
				}

				if(window.location.hash) {
					$('.loginLink').attr('href', $('.loginLink').attr('href') + window.location.hash.replace("#","%23"));
				}

				function childWindowWillClose() {
					location.reload();
				}

				$(document).ready(function() {
					if(isCrossOriginFrame()) {
						$(".loginLink").on('click', function(e) {
							e.preventDefault();
							var myWindow = window.open($(".loginLink").attr("href"), "Login", "width=640,height=480");
							myWindow.name = 'loginRedirectWindow';
						});
					}
				});

				</script>
				<a class="btn btn-info" role="button" data-toggle="collapse" href="#localUser" aria-expanded="false" aria-controls="localUser"><?=$this->config->item("guestLoginLabel")?> User</a>
			</div>

			<div class="collapse" id="localUser">
		<?endif?>
				<div class="form-group">

			    <div class="col-sm-10">
			      <p class="form-control-static">Enter your <?=$this->config->item("guestLoginLabel")?> username and password below.</p></p>
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
				<?if(!isset($redirectURL) || $redirectURL == null) { $redirectURL = current_url(); }?>
				<input type="hidden" name="redirectURL" value="<?=htmlspecialchars($redirectURL)?>" >
				<script>
				if(window.location.hash) {
					$('[name="redirectURL"]').val($('[name="redirectURL"]').val() + window.location.hash);
				}
				</script>
				<div class="form-group">
					<div class="col-sm-6 col-sm-offset-2">
						<button type="submit" class="btn btn-primary">Sign In</button>
					</div>

				</div>
			<?if(!isset($localOnly) || $localOnly == false):?>
				</div>
			<?endif?>
		</form>


	</div>
</div>