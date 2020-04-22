<style>
	.columnImageContainer {
		align-items: center;
		width: 30%;
	}

	.columnImageContainer img {
		max-height: 80px;
		margin-left: auto;
		margin-right: auto;

	}

	.columnContainer {
		width: 100%;
		padding-top: 5px;
		padding-bottom: 5px;
	}

	.columnInnerContainer {
		background-color: white;
		border: 1px #000 solid;
		border-radius: 3px;
		min-height: 80px;
		display: flex;
		align-items: center;

	}

	.collectionCollapse {
		padding-left: 15px;
	}

	.bulkContainer {
		width: 60%;
		padding-left: 10px;
	}

	.bulkContainer p {
		font-size: 1.3em;
	}

	.disclosure {
		margin-left: auto;
		padding-right: 10px;
	}

</style>
<? $totalCollections = count($collections);?>
<? $columnSize = ceil($totalCollections/3);?>


<script>
	$(document).on("hide.bs.collapse", ".collectionGroup", function (e) {
		$(this).find(".expandChildren").first().removeClass('glyphicon-chevron-up').addClass(
			'glyphicon-chevron-down');
	});

	$(document).on("show.bs.collapse", ".collectionGroup", function (e) {
		$(this).find(".expandChildren").first().removeClass('glyphicon-chevron-down').addClass(
			'glyphicon-chevron-up');
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