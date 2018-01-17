<base target="_parent">
<div id="timelineContainer">
	<div id="">
		<div id="timelinePane" style="width: 100%; height: 100%;">


		</div>
	</div>
</div>

<div class="active hide">
	<a href="#timeline"></a>
</div>

<script>

$(document).ready(function() {
	loadAll = true;
});
</script>
<style>
#timelineContainer {
	height: 100%;
	width: 100%;
}
</style>
<?$this->load->view("handlebarsTemplates");?>