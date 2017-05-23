<strong><?=$widgetModel->getLabel()?>:</strong>
<ul>
	<?foreach($widgetModel->fieldContentsArray as $fieldContent):?>
<li><?$content=join(" : ", array_filter($fieldContent->getSortedValues())); echo $widgetModel->getClickToSearch()?"<A href=" .instance_url("/search/querySearch/". rawurlencode($content)) .">".$content."</a>":$content?>
	</li>
	<?endforeach?>
</ul>

