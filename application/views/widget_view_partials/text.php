
<ul>
	<strong><?=$widgetModel->getLabel()?>:</strong>
	<?foreach($widgetModel->fieldContentsArray as $fieldContent):?>
	<li><?=$widgetModel->getClickToSearch()?getClickToSearchLink($widgetModel, $fieldContent->fieldContents):auto_link($fieldContent->fieldContents, 'both', TRUE);?></li>
	<?endforeach?>
</ul>

