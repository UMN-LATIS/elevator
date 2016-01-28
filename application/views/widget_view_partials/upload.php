<p><strong><?=$widgetModel->getLabel()?>:</strong></p>
<ul class="fileList">
	<?foreach($widgetModel->fieldContentsArray as $fieldContent):?>
	<li>
		<div class="thumbnailFixedSize">
			<a href="#<?=$fieldContent->fileId?>"><img data-fileobjectid="<?=$fieldContent->fileId?>" class="lazy loadView" data-retina="<?=instance_url("fileManager/tinyImageByFileId/" . $fieldContent->fileId . "/true/true") ?>" data-src="<?=instance_url("fileManager/tinyImageByFileId/" . $fieldContent->fileId . "/false/true") ?>"></a>
		</div>
	</li>
	<?endforeach?>
</ul>

