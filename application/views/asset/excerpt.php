<?
if($this->user_model->userLoaded) {
    foreach($this->user_model->getDrawers(true) as $drawer) {
        $drawerArray[] = $drawer;
    }
}


$embedLink = instance_url("asset/viewExcerpt/" . $excerptId . "/true");
$embedLink = str_replace("http:", "", $embedLink);
$embedLink = str_replace("https:", "", $embedLink);

$embedLink = '<iframe width="560" height="480" src="' . $embedLink . '" frameborder="0" allowfullscreen></iframe>';


?>




<script>
var startTimeValue = <?=$startTime?>;
var endTimeValue = <?=$endTime?>;
var excerptId = <?=$excerptId?>;
var objectId = "<?=$asset->getObjectId()?>";
</script>

<?if(!$isEmbedded):?>
<div class="row">
	<div class="col-md-8 col-md-offset-2">
		<h2><?=$label?><a href="<?=instance_url("asset/viewAsset/".$asset->getObjectId())?>" class="btn btn-primary pull-right">View Asset</a></h2>
<?endif?>
		<?=$embed?>
		<?if(!$isEmbedded):?>
	</div>
</div>

<?endif?>

<script>

$(document).ready(function(){
	//bootstrap pushes these into the data over the span for a popover,so we can't update it until it's revealed.
	$(".infoPopover").on("shown.bs.popover", function() {
		$(".embedControl").val("<?=addslashes($embedLink)?>");
	});



});

</script>