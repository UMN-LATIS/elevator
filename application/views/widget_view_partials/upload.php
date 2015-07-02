<p><strong><?=$widgetModel->getLabel()?>:</strong></p>
<ul class="fileList">
	<?foreach($widgetModel->fieldContentsArray as $fieldContent):?>
	<li><a href="#<?=$fieldContent->fileId?>"><img data-fileobjectid="<?=$fieldContent->fileId?>" class="tiny-image img-responsive loadView" data-at2x="<?=instance_url("fileManager/tinyImageByFileId/" . $fieldContent->fileId . "/true/true") ?>" src="<?=instance_url("fileManager/tinyImageByFileId/" . $fieldContent->fileId . "/false/true") ?>"></a></li>
	<?endforeach?>
</ul>

