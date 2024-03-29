<? /** SOMEONE SHOULD REFACTOR THIS **/ ?>
<?
$seed = round(microtime(true))// we're using a seed to provide collisions with recursive nested assets.  This is stupid and only a reflection of how old this code is.
?>
<div class="panel-group nestedGroup">
	<p><strong><?=$widgetModel->getLabel()?>:</strong></p>
<?php
	$j=0;
	if($widgetModel->nestData):
		foreach($widgetModel->fieldContentsArray as $fieldContent):
			$retina = "";
			$standard = "";
			$fileHandler = null;
			try {
				$fileHandler = $fieldContent->getPrimaryFileHandler();
				if($fileHandler) {

				$retina = $fileHandler->getPreviewTiny(true)->getURLForFile(true);
				$standard = $fileHandler->getPreviewTiny(false)->getURLForFile(true);
			}
			}
			catch (Exception $e) {
				$iconPath = getIconPath();
				$hasIcon = $fileHandler && $fileHandler->icon;

				$retina = $hasIcon 
					? $iconPath . $fileHandler->icon 
					: $iconPath . "_blank.png";

				$standard = $hasIcon
					? $iconPath . $fileHandler->icon
					: $iconPath . "_blank.png";
			}


			if($fileHandler) {
				$fileObjectId = $fileHandler->getObjectId();
			}
			else {
				$fileObjectId = null;
			}


			// display children inline
			if($widgetModel->collapseNestedChildren && $fieldContent->getRelatedObjectId()):?>
				<div class="collapsedChild" >
					<?if($fieldContent->label):?><?=$fieldContent->label?><?endif?>
					<?if(!$widgetModel->displayInline):?>
					<a href="<?=instance_url("asset/viewAsset/".$fieldContent->getRelatedObjectId())?>" class="btn btn-primary btn-xs" style="color:white">Open</a>
					<?endif?>
					<?=$this->load->view("asset/sidebar", ["sidebarAssetModel"=>$fieldContent->getRelatedAsset()], true);?>
				</div>

			<?else:?>
				<?
				// thumbnail view
				if($widgetModel->thumbnailView && $fieldContent->getRelatedObjectId()):?>
					<?if($fieldContent->getReadyForDisplay()):?>
					<div class="col-sm-2 col-xs-4">
						<div class="relatedThumbToggle">
							<div class="relatedThumbContainer" data-objectid="<?=$fieldContent->getRelatedObjectId()?>">
								<img class="relatedThumbContainerImage loadView lazy <?=$widgetModel->ignoreForDigitalAsset?'ignoreForDigitalAsset':null?>" data-fileobjectid="<?=$fileObjectId?>" role="button" srcset="<?=$retina?> 2x" data-hover="<?=$retina?>" data-src="<?=$standard?>">
							</div>
							<div class="relatedThumbTitle autoTruncate"><?$assetTitle = $fieldContent->getRelatedObjectTitle();echo array_shift($assetTitle)?></div>
						</div>
					</div>
					<?endif?>

				<?
				// standard list view
				elseif($fieldContent->getRelatedObjectId()):?>
					<?$objectTitle = $fieldContent->getRelatedObjectTitle(); $title = reset($objectTitle); if($title):?>
						<div class="panel panel-default relatedAssetContainer relatedListToggle"  data-objectid="<?=$fieldContent->getRelatedObjectId()?>" id="accordion<?=$fieldContent->getRelatedObjectId()?><?=$seed?>">
							<div class="panel-heading">
								<h4 class="panel-title">
								<?try { $result = $fieldContent->getPrimaryFileHandler(); ?>
								<img class="pull-left super-tiny-image img-responsive img-rounded loadView <?=$widgetModel->ignoreForDigitalAsset?'ignoreForDigitalAsset':null?>" data-fileobjectid="<?=$fileObjectId?>" srcsrc="<?=$retina?> 2x" src="<?=$standard ?>">
								<? } catch (Exception $e) { /* no file handler, ignore this */ }?>
								<a class="titleToggle" data-toggle="collapse" data-parent="#accordion<?=$fieldContent->getRelatedObjectId()?><?=$seed?>" data-objectId="<?=$fieldContent->getRelatedObjectId()?>" href="#collapse<?=$fieldContent->getRelatedObjectId()?><?=$seed?>">
								<div class="truncatedTitle">
								<?
								if(!$title) {
									echo "(no title)";
								}
								else {
									echo $title;
								}
								?> <?if($fieldContent->label):?>(<?=$fieldContent->label?>)<?endif?>
								</div>
								<div class="glyphicon glyphicon-chevron-down expandRelated pull-right titleToggle"></div>
								</a>
								<?if(!$widgetModel->displayInline):?>
								<a href="<?=instance_url("asset/viewAsset/".$fieldContent->getRelatedObjectId())?>" class="btn btn-primary btn-xs" style="color:white">Open</a>
								<?endif?>

								</h4>
							</div>
							<div id="collapse<?=$fieldContent->getRelatedObjectId()?><?=$seed?>" class="panel-collapse collapse">
								<div class="panel-body relatedAssetContents">

								</div>
							</div>
						</div>
					<?endif?>
				<?endif?>
			<?endif?>
		<?php endforeach?>
	<?php else:?>
		<div>
			<ul>
			<?php foreach($widgetModel->fieldContentsArray as $fieldContent):?>
				<li><a href="<?=instance_url("asset/viewAsset/" . $fieldContent->getRelatedObjectId())?>"><?=join(",", $fieldContent->getRelatedObjectTitle())?></a> <?if($fieldContent->label):?>(<?=$fieldContent->label?>)<?endif?></li>
			<?php endforeach;?>
			</ul>
		</div>
	<?php endif;?>

</div>


<script>
$(".nestedGroup").each(function(index, el) {
	// if this group doesn't have any children, hide it
	if($(el).children("div").length == 0) {

		$(el).hide();
	}
});

</script>