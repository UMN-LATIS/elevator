<?
$fileObjectId = $fileObject->getObjectId();
$originalFilename = $fileObject->sourceFile->originalFilename;
// override the filename so flash isn't unhappy. (so we don't serve a content disposition);
$fileObject->sourceFile->originalFilename = null;

?>


		<?if($allowOriginal):?>
		<object type="application/x-shockwave-flash" data="<?=stripHTTP($fileObject->sourceFile->getProtectedURLForFile())?>" width="100%" height="480px">
  	<param name="movie" value="<?=stripHTTP($fileObject->sourceFile->getProtectedURLForFile())?>" />
  <param name="quality" value="high"/>
</object>

				<?else:?>
		<p class="alert alert-info">No derivatives found.
			<?if(!$this->user_model->userLoaded):?>
			<?=$this->load->view("errors/loginForPermissions")?>
			<?endif?>
		</p>

		<?endif?>
