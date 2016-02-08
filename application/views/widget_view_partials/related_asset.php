<? /** SOMEONE SHOULD REFACTOR THIS **/ ?>


<div class="panel-group" >
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


			if($fileHandler) {
				$fileObjectId = $fileHandler->getObjectId();
			}
			else {
				$fileObjectId = null;
			}


			// display children inline
			if($widgetModel->collapseNestedChildren && $fieldContent->getRelatedObjectId()):?>
				<div class="panel panel-default collapsedChild" >
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
					<div class="col-sm-2 col-xs-4">
						<div class="relatedThumbToggle">
							<div class="relatedThumbContainer" data-objectid="<?=$fieldContent->getRelatedObjectId()?>">
								<img class="relatedThumbContainerImage loadView lazy" data-fileobjectid="<?=$fileObjectId?>" data-retina="<?=$retina?>" data-hover="<?=$retina?>" data-src="<?=$standard?>">
							</div>
							<div class="relatedThumbTitle autoTruncate"><?$assetTitle = $fieldContent->getRelatedObjectTitle();echo array_shift($assetTitle)?></div>
						</div>
					</div>

				<?
				// standard list view
				elseif($fieldContent->getRelatedObjectId()):?>
					<div class="panel panel-default relatedAssetContainer relatedListToggle"  data-objectid="<?=$fieldContent->getRelatedObjectId()?>" id="accordion<?=$fieldContent->getRelatedObjectId()?>">
						<div class="panel-heading">
							<h4 class="panel-title">
							<?try { $result = $fieldContent->getPrimaryFileHandler(); ?>
							<img class="pull-left super-tiny-image img-responsive img-rounded loadView" data-fileobjectid="<?=$fileObjectId?>" data-at2x="<?=$retina?>" src="<?=$standard ?>">
							<? } catch (Exception $e) { /* no file handler, ignore this */ }?>
							<a class="titleToggle" data-toggle="collapse" data-parent="#accordion<?=$fieldContent->getRelatedObjectId()?>" data-objectId="<?=$fieldContent->getRelatedObjectId()?>" href="#collapse<?=$fieldContent->getRelatedObjectId()?>">
							<div class="truncatedTitle">
							<?

							$title = join(",", $fieldContent->getRelatedObjectTitle());
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
						<div id="collapse<?=$fieldContent->getRelatedObjectId()?>" class="panel-collapse collapse">
							<div class="panel-body relatedAssetContents">

							</div>
						</div>
					</div>
				<?endif?>
			<?endif?>
		<?php endforeach?>
	<?php else:?>
		<ul>
		<?php foreach($widgetModel->fieldContentsArray as $fieldContent):?>
			<li><a href="<?=instance_url("asset/viewAsset/" . $fieldContent->getRelatedObjectId())?>"><?=join(",", $fieldContent->getRelatedObjectTitle())?></a> <?if($fieldContent->label):?>(<?=$fieldContent->label?>)<?endif?></li>
		<?php endforeach;?>
		</ul>
	<?php endif;?>

</div>
