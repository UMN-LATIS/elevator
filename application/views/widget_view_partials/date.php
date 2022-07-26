
<strong><?=$widgetModel->getLabel()?>:</strong>
<ul>
	<?foreach($widgetModel->fieldContentsArray as $fieldContent):?>
	<?

	$labelString = null;
	if($fieldContent->label) {
		$labelString = "<span class='date_label'>" . $fieldContent->label."</span>";
	}
	$dateString = $fieldContent->start["text"];
	if($fieldContent->range) {
		$dateString .= " - " . $fieldContent->end["text"];
	}

	$outputString = "";
	if($labelString) {
		$outputString .= $labelString . " <span class='date_value'>(" . $dateString . ")</span>";
	}
	else {
		$outputString ="<span class='date_value'>" . $dateString . "</span>";
	}
	
	?>
	<li><?=$widgetModel->getClickToSearch()?getClickToSearchLink($widgetModel, $dateString, $outputString):$outputString;?></li>
	<?endforeach?>
</ul>