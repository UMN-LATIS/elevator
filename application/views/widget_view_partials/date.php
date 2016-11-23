
<strong><?=$widgetModel->getLabel()?>:</strong>
<ul>
	<?foreach($widgetModel->fieldContentsArray as $fieldContent):?>
	<?
	$outputString = $fieldContent->start["text"];
	if($fieldContent->range) {
		$outputString .= " - " . $fieldContent->end["text"];
	}

	if($fieldContent->label) {
		$outputString .= " (" . $fieldContent->label.")";
	}
	?>
	<li><?=$widgetModel->getClickToSearch()?getClickToSearchLink($widgetModel, $outputString):$outputString;?></li>
	<?endforeach?>
</ul>