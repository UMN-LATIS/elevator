<div id="accordion<?=$collection->getId()?>" role="tablist" aria-multiselectable="true">

	<div class="columnContainer" role="tab" id="headingOne">
		<div class="columnInnerContainer">
			<?if($collection->previewImageHandler):?>
				<div class="columnImageContainer">
					<img class="img-responsive" src="<?=$collection->previewImageHandler->getPreviewThumbnail()->getProtectedURLForFile()?>" srcset="<?=$collection->previewImageHandler->getPreviewThumbnail(true)->getProtectedURLForFile()?> 2x">



				</div>
				<?endif?>
				<div class="bulkContainer">
					<p> <a href="<?=instance_url("collections/browseCollection/". $collection->getId())?>"><?=$collection->getTitle()?></a></p>

				</div>
				<div class="disclosure pull-right">
					<?if($collection->hasChildren()):?>
						<p><a data-toggle="collapse" data-parent="#accordion<?=$collection->getId()?>" href="#collapse<?=$collection->getId()?>" aria-expanded="true" aria-controls="collapse<?=$collection->getId()?>" class="expandChildren glyphicon glyphicon-chevron-down"> </a></p>
						<?endif?>

					</div>

				</div>

			</div>
			<div id="collapse<?=$collection->getId()?>" class="collapse collectionCollapse" role="tabpanel" aria-labelledby="headingOne">
				<div>
					<?foreach($collection->getChildren() as $child):?>
						<?if($child->getShowInBrowse()):?>
							<?=$this->load->view("collection_partial", ["collection"=>$child],true);?>
							<?endif?>
							<?endforeach?>
						</div>
					</div>
				</div>