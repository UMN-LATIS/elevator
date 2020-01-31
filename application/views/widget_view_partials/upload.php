<p><strong><?=$widgetModel->getLabel()?>:</strong></p>

	<?foreach($widgetModel->fieldContentsArray as $fieldContent):?>

	<?
			try {
				$fileHandler = $fieldContent->getFileHandler();
				if($fileHandler) {

					$retina = $fileHandler->getPreviewTiny(true)->getURLForFile(true);
					$standard = $fileHandler->getPreviewTiny(false)->getURLForFile(true);
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
			<div class="relatedThumbContainer" >
				<img class="relatedThumbContainerImage loadView lazy" role="button" data-fileobjectid="<?=$fieldContent->fileId?>" alt="<?=htmlentities($fieldContent->fileDescription, ENT_COMPAT)?>" data-srcset="<?=$retina?> 2x" data-hover="<?=$retina?>" data-src="<?=$standard?>">
			</div>
			<div class="relatedThumbTitle autoTruncate"><?=htmlentities($fieldContent->fileDescription, ENT_COMPAT)?></div>
		</div>
	</div>

	<?endforeach?>


