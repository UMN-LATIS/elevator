
<ul class="textareaView">
	<strong><?=$widgetModel->getLabel()?>:</strong>
	<?foreach($widgetModel->fieldContentsArray as $fieldContent):?>
	<li><div><?=($fieldContent->fieldContents);?></div></li>
	<?endforeach?>
</ul>

