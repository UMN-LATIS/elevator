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