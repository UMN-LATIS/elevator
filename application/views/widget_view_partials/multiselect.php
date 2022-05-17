<strong><?=$widgetModel->getLabel()?>:</strong>
<ul>
	<? foreach($widgetModel->fieldContentsArray as $fieldContent):?>
		
		<?$firstItem = true; $builtList = ""; ?>
				<li>
		<?foreach(array_filter($fieldContent->getSortedValues()) as $outputValue):?>

			<? $currentValue = ($firstItem?null:" : ") . $outputValue;?>
			<? $builtList .= $currentValue; ?>
			<?if($widgetModel->getClickToSearch()):?>
				<? echo getClickToSearchLink($widgetModel, $builtList, $currentValue)?>
			<?else:?>
				<? echo $currentValue?>
			<?endif?>

			<?$firstItem = false;?>
		<?endforeach?>
					</li>
	<?endforeach?>
</ul>

