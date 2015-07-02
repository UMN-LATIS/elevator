	<div id="body">
		<form method="post" accept-charset="utf-8" action="<?= site_url("instances/updateGroup/"); ?>"/>
			<input type="hidden" name="groupId" id="inputGroupId" class="form-control" value="<?= $instanceGroup->getId(); ?>">

			<?php $this->load->view( "instances/_group_form_fields" ); ?>
		</form>
	</div>

	<p class="footer">Page rendered in <strong>{elapsed_time}</strong> seconds</p>
</div>
