<strong><?=$widgetModel->getLabel()?>:</strong>
<ul>
	<?foreach($widgetModel->fieldContentsArray as $fieldContent):?>
	<li><?foreach($fieldContent->tags as $tag):?>
		<A href="<?=instance_url("/search/querySearch/". rawurlencode($tag))?>"><?=$tag?></a>
	<?endforeach?></li>
	<?endforeach?>
</ul>

