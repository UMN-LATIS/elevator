<p><strong><?=$widgetModel->getLabel()?>:</strong></p>

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
	<div class="col-sm-2 col-xs-4">
		<div class="relatedThumbToggle">
			<div class="relatedThumbContainer">
				<img class="relatedThumbContainerImage loadView lazy" data-fileobjectid="<?=$fieldContent->fileId?>" data-retina="<?=$retina?>" data-src="<?=$standard?>">
			</div>
		</div>
	</div>

	<?endforeach?>


