<li>
	<div id="accordion<?=$collection->getId()?>" role="tablist" aria-multiselectable="true">
		<div class="collectionGroup">
			<div role="tab" id="headingOne">
					<?if($collection->hasChildren()):?>
					<a data-toggle="collapse" data-parent="#accordion<?=$collection->getId()?>" href="#collapse<?=$collection->getId()?>" aria-expanded="true" aria-controls="collapse<?=$collection->getId()?>" class="expandChildren glyphicon glyphicon-chevron-down"> </a>
					<?endif?>
					<a href="<?=instance_url("collections/browseCollection/". $collection->getId())?>"><?=$collection->getTitle()?></a>
			</div>
			<div id="collapse<?=$collection->getId()?>" class="collapse collectionCollapse" role="tabpanel" aria-labelledby="headingOne">
				<div>
					<ul>
					<?foreach($collection->getChildren() as $child):?>
					<?=$this->load->view("collection_partial", ["collection"=>$child],true);?>
					<?endforeach?>
					</ul>
				</div>
			</div>
		</div>
	</div>
</li>
