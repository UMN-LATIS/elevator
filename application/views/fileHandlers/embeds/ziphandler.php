<?
$fileObjectId = $fileObject->getObjectId();
$iconSrc = getIconPath() . "_blank.png";
?>

		<?if($allowOriginal):?>
		<a href="<?=instance_url("fileManager/getOriginal/". $fileObjectId)?>">
			<img src="<?= $iconSrc ?>" class="img-responsive" style="width: 50%; margin-left:auto; margin-right:auto"/>
		</a>
				<?else:?>
		<p class="alert alert-info">No derivatives found.
			<?if(!$this->user_model->userLoaded):?>
			<?=$this->load->view("errors/loginForPermissions")?>
			<?endif?>
		</p>

		<?endif?>


