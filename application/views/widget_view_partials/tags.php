<strong><?=$widgetModel->getLabel()?>:</strong>
<ul class="tagList">
	<?foreach($widgetModel->fieldContentsArray as $fieldContent):?>
	<li><?foreach($fieldContent->tags as $tag):?>
		<?=getClickToSearchLink($widgetModel, $tag)?>
	<?endforeach?></li>
	<?endforeach?>
</ul>

