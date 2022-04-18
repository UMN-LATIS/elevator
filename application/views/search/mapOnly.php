<base target="_parent">
<div id="mapContainer">
	<div id="">
		<div id="mapPane">
	</div>
</div>

<div class="active hide">
	<a href="#map"></a>
</div>

<script>

$(document).ready(function() {
	loadAll = true;
});
</script>
<style>
#mapContainer {
	height: 100%;
	width: 100%;
    font-size: 1em;

}
</style>
<?$this->load->view("handlebarsTemplates");?>