<? $totalCollections = count($collections);?>
<? $columnSize = ceil($totalCollections/3);?>

<script>
$(document).on("hide.bs.collapse", ".collectionGroup", function(e) {
	$(this).find(".expandChildren").first().removeClass('glyphicon-chevron-up').addClass('glyphicon-chevron-down');
});

$(document).on("show.bs.collapse", ".collectionGroup", function(e) {
	$(this).find(".expandChildren").first().removeClass('glyphicon-chevron-down').addClass('glyphicon-chevron-up');
});
</script>
<div class="row rowContainer collectionList">
	<div class="col-md-4">
		<ul>
			<?for($i=0; $i<$columnSize; $i++):?>
				<?=$this->load->view("collection_partial", ["collection"=>$collections[$i]], true)?>

			<?endfor?>
		</ul>
	</div>
	<?if(count($collections)>=$columnSize*2):?>
	<div class="col-md-4">
		<ul>
			<?for($i=$columnSize; $i<$columnSize*2; $i++):?>
				<?=$this->load->view("collection_partial", ["collection"=>$collections[$i]], true)?>
			<?endfor?>
		</ul>
	</div>
	<?endif?>
	<div class="col-md-4">
		<ul>
			<?for($i=$columnSize*2; $i<$totalCollections; $i++):?>
				<?=$this->load->view("collection_partial", ["collection"=>$collections[$i]], true)?>
			<?endfor?>
		</ul>
	</div>

	</div>
</div>
