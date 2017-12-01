<style>



.columnImageContainer {
	width: 30%;
	height: 100%;
	float:left;
	/*border: 1px #000 solid;*/
}

.columnImageContainer img {
	max-height: 100%;
	margin-left: auto;
	margin-right: auto;
	padding-top: 5px;
	padding-bottom: 5px;
}


.columnContainer {
	display: block;
	width: 100%;
	height: 80px;

	/*margin-top: 5px;*/
	/*margin-bottom: 5px;*/
    /*margin: 0;*/
    
    -webkit-column-break-inside: avoid; /* Chrome, Safari */
    page-break-inside: avoid;           /* Theoretically FF 20+ */
    break-inside: avoid-column;         /* IE 11 */
    padding-top: 5px;
	padding-bottom: 5px;
}

.columnInnerContainer {
	width: 100%;
	height: 100%;
	background-color:white;
	border: 1px #000 solid;
	border-radius: 3px;	
}

.collectionCollapse {
	padding-left: 15px;
}

.bulkContainer {
	width: 60%;
	float:left;
	height: 100%;
	font-size: 1.5em;
	vertical-align: middle;
	padding-left: 10px;
}

.bulkContainer p {
	/*text-align: center;*/
    position: relative;
    top: 50%;
    -ms-transform: translateY(-50%);
    -webkit-transform: translateY(-50%);
    transform: translateY(-50%);
}
.disclosure {
	/*width: %;*/
	max-width: 8%;
	/*float:left;*/
	height: 100%;
	font-size: 1.5em;
	vertical-align: middle;
	padding-left: 0px;
	padding-right: 30px;
}

.disclosure p {
	position: relative;
    top: 50%;
    -ms-transform: translateY(-50%);
    -webkit-transform: translateY(-50%);
    transform: translateY(-50%);
    padding-right: 5px;
}

</style>
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
