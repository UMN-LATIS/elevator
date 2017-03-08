

<div class="row suggest" style="padding-top:10px">
</div>


<!-- Nav tabs -->
<ul class="nav nav-tabs searchTabHeader">
  <li class="active"><a href="#grid" data-toggle="tab">Grid</a></li>
  <li><a href="#list" data-toggle="tab">List</a></li>
  <li><a href="#map" data-toggle="tab">Map</a></li>
  <li><a href="#timeline" data-toggle="tab">Timeline</a></li>
  <li><a href="#gallery" data-toggle="tab">Gallery</a></li>
	<li><select class="form-control sortBy">
        <option value="0">Best Match</option>
        <option value="modified.date.asc">Modified Date (oldest to newest)</option>
        <option value="modified.date.desc">Modified Date (newest to oldest)</option>
        <option value="title.raw">Title</option>
  </select>
</ul>



<div class="tab-content searchTabs">
	<div class="tab-pane active" id="grid">
		<div class="row">
			<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12" >

				<div class="resultsData">

				</div>
				<div id="results">

				</div>
				 <div class="clearfix"></div>
				<div class="paginationBlock">
					<ul class="pager">
					  <li><a href="#" class="nextPage">Load More</a></li>
					</ul>
				</div>
			</div>
		</div>
	</div>
		<div class="tab-pane" id="list">
		<div class="row">
			<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12" >
				<div class="resultsData">

				</div>
				<div id="listResults">

				</div>
				 <div class="clearfix"></div>
				<div class="paginationBlock">
					<ul class="pager">
					  <li><a href="#" class="nextPage">Load More</a></li>
					</ul>
				</div>
			</div>
		</div>
	</div>
	<div class="tab-pane" id="map">
		<div class="row">
			<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12" id="mapContainer">
				<div id="mapFrame">
				<div id="mapPane">


				</div>
			</div>
			</div>
		</div>
	</div>
	<div class="tab-pane" id="timeline">
		<div class="row">
			<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12" id="timelineContainer">

				<div id="timelinePane" style="width: 100%; height: 500px;">


				</div>
			</div>
		</div>
	</div>
	<div class="tab-pane" id="gallery">
		<div class="row">
			<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12" id="galleryContainer">
				<p>The Gallery feature has been temporarily disabled.  Please <a href="mailto:elevator@umn.edu">contact us</a> for more information.</p>
			</div>
		</div>
	</div>
</div>



<script>
/**
 * SIMILE is old and sad.  It needs global variables available before the script is loaded.  Sad.
 */
var tl;
var Timeline_ajax_url="<?=site_url("assets/timeline_ajax/simile-ajax-api.js")?>";
var Timeline_urlPrefix='<?=site_url("assets/timeline_js/")?>/';
var Timeline_parameters='bundle=true';
</script>
<script src="<?=site_url("assets/timeline_js/timeline-api.js")?>" type="text/javascript"></script>




<?$this->load->view("handlebarsTemplates");?>