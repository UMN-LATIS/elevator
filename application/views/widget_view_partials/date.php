
<strong><?=$widgetModel->getLabel()?>:</strong>
<ul>
	<?foreach($widgetModel->fieldContentsArray as $fieldContent):?>
	<?

	$labelString = null;
	if($fieldContent->label) {
		$labelString = "<span class='date_label'>" . $fieldContent->label."</span>";
	}
	$dateString = "<span class='date_value'>" . $fieldContent->start["text"];
	if($fieldContent->range) {
		$dateString .= " - " . $fieldContent->end["text"];
	}

	$dateString .= "</span>";

	if($labelString) {
		$outputString .= $labelString . " (" . $dateString . ")";
	}
	else {
		$outputString = $dateString;
	}
	
	?>
	<li><?=$widgetModel->getClickToSearch()?getClickToSearchLink($widgetModel, $outputString):$outputString;?></li>
	<?endforeach?>
</ul>