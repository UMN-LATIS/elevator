<p><strong><?=$widgetModel->getLabel()?>:</strong></p>
<ul class="fileList">
	<?foreach($widgetModel->fieldContentsArray as $fieldContent):?>

	<?
			try {
				$fileHandler = $fieldContent->getFileHandler();
				if($fileHandler) {

					$retina = $fileHandler->getPreviewTiny(true)->getURLForFile();
					$standard = $fileHandler->getPreviewTiny(false)->getURLForFile();
				}
			}
			catch (Exception $e) {
				if($fileHandler && $fileHandler->icon) {
					$retina = "/assets/icons/48px/" . $fileHandler->icon;
					$standard = "/assets/icons/48px/" . $fileHandler->icon;
				}
				else {
					$retina = "/assets/icons/48px/_blank.png";
					$standard = "/assets/icons/48px/_blank.png";
				}

			}
	?>
	<li>
		<div class="thumbnailFixedSize">
			<a href="#<?=$fieldContent->fileId?>"><img data-fileobjectid="<?=$fieldContent->fileId?>" class="lazy loadView" data-retina="<?=$retina?>" data-src="<?=$standard?>"></a>
		</div>
	</li>
	<?endforeach?>
</ul>

