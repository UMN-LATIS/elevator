
<ul>
	<strong><?=$widgetModel->getLabel()?>:</strong>
	<?foreach($widgetModel->fieldContentsArray as $fieldContent):?>
	<li><span class="glyphicon <?=$fieldContent->fieldContents?"glyphicon-ok-circle":"glyphicon-ban-circle"?>"></li>
	<?endforeach?>
</ul>

