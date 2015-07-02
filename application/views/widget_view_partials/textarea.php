
<ul class="textareaView">
	<strong><?=$widgetModel->getLabel()?>:</strong>
	<?foreach($widgetModel->fieldContentsArray as $fieldContent):?>
	<li><div><?=auto_link(nl2br($fieldContent->fieldContents), 'both', TRUE);?></div></li>
	<?endforeach?>
</ul>

