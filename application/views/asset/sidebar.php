<div class="sidebarContainer objectIdHost" data-objectid="<?=$sidebarAssetModel->getObjectId()?>">
<?foreach($sidebarAssetModel->assetObjects as $widget):?>

<?if($widget->getDisplay() && $widget->hasContents()):?>
<div class="row">
	<div class="col-md-12 assetWidget">
		<?=$widget->getView()?>
	</div>
</div>
<?endif?>
<?endforeach?>
</div>
<?if($sidebarAssetModel->assetTemplate->getShowCollection()):?>
<?$collection = $this->collection_model->getCollection($sidebarAssetModel->getGlobalValue("collectionId"));?>
<div class="row collectionWidget">
			<div class="col-md-12 assetWidget">
				<ul>
				
					<strong>Collection:</strong>
					<ul class="collectionList">
							<? foreach(array_reverse($this->collection_model->getFullHierarchy($collection->getId())) as $collection):?>
							<li><a href="<?=instance_url("collections/browseCollection/". $collection->getId())?>"><?=$collection->getTitle()?></a>
							</li>
							<?endforeach?>
					</ul>
				</ul>
			</div>
		</div>
<?endif?>