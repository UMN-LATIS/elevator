<div class="row rowContainer">

<form class="form-horizontal"  action="<?=instance_url("instances/save")?>" method="POST" role="form">
	<?$this->load->view( "instances/_form_fields" ); ?>

</form>

</div>


<?$this->load->view("bucketCreation_modal");?>