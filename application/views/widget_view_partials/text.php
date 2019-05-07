
<ul>
	<strong><?=$widgetModel->getLabel()?>:</strong>
	<?foreach($widgetModel->fieldContentsArray as $fieldContent):?>
	<?
	if( $fieldContent->fieldContents != strip_tags($fieldContent->fieldContents)) {
		$content = $fieldContent->fieldContents;
	}
	else {
		$content = autolink_elevator($fieldContent->fieldContents);
	}?>
	<li><?=$widgetModel->getClickToSearch()?getClickToSearchLink($widgetModel, $fieldContent->fieldContents):$content;?></li>
	<?endforeach?>
</ul>

