<style>
.columns {
	background-color: #d9dee2;
	padding: 10px;
	-webkit-column-count: 1; /* Chrome, Safari, Opera */
    -moz-column-count: 1; /* Firefox */
    column-count: 1;
}

@media only screen and (min-width: 768px) {
.columns {
	background-color: #d9dee2;
	padding: 10px;
	-webkit-column-count: 3; /* Chrome, Safari, Opera */
    -moz-column-count: 3; /* Firefox */
    column-count: 3;
}
}


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
	height: 90px;

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
	width: 62%;
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


<script>
$(document).on("hide.bs.collapse", ".collectionGroup", function(e) {
	$(this).find(".expandChildren").first().removeClass('glyphicon-chevron-up').addClass('glyphicon-chevron-down');
});

$(document).on("show.bs.collapse", ".collectionGroup", function(e) {
	$(this).find(".expandChildren").first().removeClass('glyphicon-chevron-down').addClass('glyphicon-chevron-up');
});
</script>
<div class="row rowContainer">
	<div class="columns">
	<?foreach($collections as $collection):?>
		<?=$this->load->view("collection_partial", ["collection"=>$collection],true);?>
	<?endforeach?>
</div>
</div>

