<strong><?=$widgetModel->getLabel()?>:</strong>
<ul>
	<?foreach($widgetModel->fieldContentsArray as $fieldContent):?>
	<li><?=$fieldContent->start["text"]?>
		<?if($fieldContent->range):?>
		 - <?=$fieldContent->end["text"]?>
		<?endif?> <?=$fieldContent->label?"(".$fieldContent->label.")":null?></li>
	<?endforeach?>
</ul>