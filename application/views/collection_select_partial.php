
<?foreach($collections as $collection):?>
	<?=drawElement($collection, $selectCollection, "", $allowedCollections)?>
<?endforeach?>

<?function drawElement($collection, $selectCollection, $prefix="", $allowedCollections=null) {?>
	<option value="<?=$collection->getId()?>" <?=($collection->getId() == $selectCollection)?"SELECTED":NULL?> <?=(isset($allowedCollections) && !in_array($collection, $allowedCollections))?"disabled":null?>> <?=$prefix?> <?=$collection->getTitle()?></option>
	<?if($collection->hasChildren()):?>
		<?foreach($collection->getChildren() as $child):?>
		<?=drawElement($child, $selectCollection, $prefix . "-", $allowedCollections)?>
		<?endforeach?>
	<?endif?>
<?}?>
