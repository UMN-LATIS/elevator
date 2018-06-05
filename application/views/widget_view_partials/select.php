<strong><?=$widgetModel->getLabel()?>:</strong>
<ul>
	<?
	foreach($widgetModel->fieldContentsArray as $fieldContent) {
		if(array_keys($widgetModel->parsedFieldData["selectGroup"]) !== range(0, count($widgetModel->parsedFieldData["selectGroup"]) - 1)) {
			if(is_array($fieldContent->fieldContents)) {
				foreach($fieldContent->fieldContents as $nestedItem) {
					echo "<li>" . getClickToSearchLink($widgetModel,$widgetModel->parsedFieldData["selectGroup"][$nestedItem], $widgetModel->parsedFieldData["selectGroup"][trim($nestedItem)]) . "</li>";
				}
			}
			else {
				echo "<li>" . getClickToSearchLink($widgetModel,$widgetModel->parsedFieldData["selectGroup"][$fieldContent->fieldContents], $widgetModel->parsedFieldData["selectGroup"][trim($fieldContent->fieldContents)]) . "</li>";
			}
		}
		else {
			if(is_array($fieldContent->fieldContents)) {
				foreach($fieldContent->fieldContents as $nestedItem) {
					echo "<li>" . getClickToSearchLink($widgetModel, $nestedItem) . "</li>";
				}
			}
			else {
				echo "<li>" . getClickToSearchLink($widgetModel, $fieldContent->fieldContents) . "</li>";
			}
			
		}
	}
	
?>
</ul>

