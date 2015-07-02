<? $totalDrawers = count($drawers);?>
<? $columnSize = ceil($totalDrawers/3);?>


<div class="row rowContainer collectionList">
	<div class="col-md-4">
		<ul>
			<?for($i=0; $i<$columnSize; $i++):?>
				<li><a href="<?=instance_url("drawers/viewDrawer/". $drawers[$i]->getId())?>"><?=$drawers[$i]->getTitle()?></a></li>
			<?endfor?>
		</ul>
	</div>
	<?if(count($drawers)>=$columnSize*2):?>
	<div class="col-md-4">
		<ul>
			<?for($i=$columnSize; $i<$columnSize*2; $i++):?>
				<li><a href="<?=instance_url("drawers/viewDrawer/". $drawers[$i]->getId())?>"><?=$drawers[$i]->getTitle()?></a></li>
			<?endfor?>
		</ul>
	</div>
	<?endif?>
	<div class="col-md-4">
		<ul>
			<?for($i=$columnSize*2; $i<$totalDrawers; $i++):?>
				<li><a href="<?=instance_url("drawers/viewDrawer/". $drawers[$i]->getId())?>"><?=$drawers[$i]->getTitle()?></a></li>
			<?endfor?>
		</ul>
	</div>


	</div>
</div>