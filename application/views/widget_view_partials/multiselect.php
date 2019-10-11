<strong><?=$widgetModel->getLabel()?>:</strong>
<ul>
	<? $builtList = ""; foreach($widgetModel->fieldContentsArray as $fieldContent):?>
		
		<?$firstItem = true; ?>
				<li>
		<?foreach(array_filter($fieldContent->getSortedValues()) as $outputValue):?>
			<? $currentValue = ($firstItem?null:" : ") . $outputValue;?>
			<? $builtList .= $currentValue; ?>
	
			<? echo getClickToSearchLink($widgetModel, $builtList, $currentValue)?>

			<?$firstItem = false;?>
		<?endforeach?>
					</li>
	<?endforeach?>
</ul>

