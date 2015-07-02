<strong><?=$widgetModel->getLabel()?>:</strong>
<ul>
	<?foreach($widgetModel->fieldContentsArray as $fieldContent):?>
	<li><?=join(" : ", array_filter($fieldContent->fieldContents))?></li>
	<?endforeach?>
</ul>

