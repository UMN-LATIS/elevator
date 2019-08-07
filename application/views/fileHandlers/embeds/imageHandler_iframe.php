<?
$iframe = $widgetObject->sidecars['iframe'];

$height = "550px";
if($embedded) {
	$height = "100%";
}

?>
<div class="fullscreenImageContainer" style="height: <?=$height?>;">
	<div class="imageContainer panzoom-element">
		<iframe src="<?=$iframe?>" frameborder=0 width="100%" height=100% scrolling="no" allowfullscreen></iframe>
	</div>
</div>


