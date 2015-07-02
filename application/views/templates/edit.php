<form action="<?= instance_url("templates/update/"); ?>" method="POST" class="form-horizontal" role="form">
<div class="row rowContainer">
		<div class="col-md-12">
			<h3>Edit <?= $template->getName(); ?></h3>
		<?php $this->load->view('templates/_form_fields'); ?>
		</div>
	</div>

	</form>
