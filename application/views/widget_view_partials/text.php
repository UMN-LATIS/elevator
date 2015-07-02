
<ul>
	<strong><?=$widgetModel->getLabel()?>:</strong>
	<?foreach($widgetModel->fieldContentsArray as $fieldContent):?>
	<li><?=$widgetModel->getClickToSearch()?"<A href=\"".instance_url("/search/querySearch/". rawurlencode($fieldContent->fieldContents)) ."\">".$fieldContent->fieldContents."</a>":auto_link($fieldContent->fieldContents, 'both', TRUE);?></li>
	<?endforeach?>
</ul>

