<strong><?=$widgetModel->getLabel()?>:</strong>
<ul>
	<?foreach($widgetModel->fieldContentsArray as $fieldContent):?>
	<?if(array_keys($widgetModel->parsedFieldData["selectGroup"]) !== range(0, count($widgetModel->parsedFieldData["selectGroup"]) - 1)):?>
		<li>
		<?=getClickToSearchLink($widgetModel,$widgetModel->parsedFieldData["selectGroup"][$fieldContent->fieldContents], $widgetModel->parsedFieldData["selectGroup"][trim($fieldContent->fieldContents)]);?></li>
	<?else:?>
		<li>
		<?=getClickToSearchLink($widgetModel, $fieldContent->fieldContents)?></li>
	<?endif?>
	<?endforeach?>
</ul>

