<strong><?=$widgetModel->getLabel()?>:</strong>
<ul>
	<?foreach($widgetModel->fieldContentsArray as $fieldContent):?>
	<?if(array_keys($widgetModel->parsedFieldData["selectGroup"]) !== range(0, count($widgetModel->parsedFieldData["selectGroup"]) - 1)):?>
		<li><?=$widgetModel->getClickToSearch()?"<A href=\"".instance_url("/search/querySearch/". rawurlencode($widgetModel->parsedFieldData["selectGroup"][$fieldContent->fieldContents])) ."\">".$widgetModel->parsedFieldData["selectGroup"][trim($fieldContent->fieldContents)]."</a>":$widgetModel->parsedFieldData["selectGroup"][trim($fieldContent->fieldContents)];?></li>
	<?else:?>
		<li><?=$widgetModel->getClickToSearch()?"<A href=\"".instance_url("/search/querySearch/". rawurlencode($fieldContent->fieldContents)) ."\">".$fieldContent->fieldContents."</a>":$fieldContent->fieldContents;?></li>
	<?endif?>
	<?endforeach?>
</ul>

