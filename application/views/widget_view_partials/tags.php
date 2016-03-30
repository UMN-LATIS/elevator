<strong><?=$widgetModel->getLabel()?>:</strong>
<ul class="tagList">
	<?foreach($widgetModel->fieldContentsArray as $fieldContent):?>
	<li><?foreach($fieldContent->tags as $tag):?>
		<?=$widgetModel->getClickToSearch()?"<A href=" .instance_url("/search/querySearch/". rawurlencode($tag)) .">".$tag."</a>":$tag?>
	<?endforeach?></li>
	<?endforeach?>
</ul>

