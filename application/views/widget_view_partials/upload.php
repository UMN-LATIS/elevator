<p><strong><?=$widgetModel->getLabel()?>:</strong></p>

	<?foreach($widgetModel->fieldContentsArray as $fieldContent):?>

	<?
			try {
				$fileHandler = $fieldContent->getFileHandler();
				if($fileHandler) {

					$retina = $fileHandler->getPreviewTiny(true)->getURLForFile(true);
					$standard = $fileHandler->getPreviewTiny(false)->getURLForFile(true);
				}
				else {
					throw new Exception("no filehandler");
				}
			}
			catch (Exception $e) {
				$iconPath = getIconPath('tiny');
				$hasIcon = $fileHandler && $fileHandler->icon;

				$retina = $hasIcon 
					? $iconPath . $fileHandler->icon 
					: $iconPath . "_blank.png";

				$standard = $hasIcon
					? $iconPath . $fileHandler->icon
					: $iconPath . "_blank.png";

			}
	?>
	<div class="col-sm-2 col-xs-4">
		<div class="relatedThumbToggle">
			<div class="relatedThumbContainer" >
				<img class="relatedThumbContainerImage uploadContent loadView lazy" role="button" data-fileobjectid="<?=$fieldContent->fileId?>" alt="<?=htmlentities($fieldContent->fileDescription??"", ENT_COMPAT)?>" data-srcset="<?=$retina?> 2x" data-hover="<?=$retina?>" data-src="<?=$standard?>">
			</div>
			<div class="relatedThumbTitle autoTruncate"><?=htmlentities($fieldContent->fileDescription??"", ENT_COMPAT)?></div>
		</div>
	</div>

	<?endforeach?>


