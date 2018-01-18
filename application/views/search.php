<div class="collectonHeader">
</div>


<div class="row suggest" style="padding-top:10px">
</div>


<!-- Nav tabs -->
<ul class="nav nav-tabs searchTabHeader">
  <li class="active"><a href="#grid" data-toggle="tab">Grid</a></li>
  <li><a href="#list" data-toggle="tab">List</a></li>
  <li><a href="#map" data-toggle="tab">Map</a></li>
  <li><a href="#timeline" data-toggle="tab">Timeline</a></li>
  <?if(!isset($drawerMode) || $drawerMode == false):?>
	<li class="navbar-right"><select class="form-control sortBy input-xs">
        <option value="0">Best Match</option>
        <option value="lastModified.desc">Modified Date (newest to oldest)</option>
        <option value="lastModified.asc">Modified Date (oldest to newest)</option>
        <option value="title.raw">Default Title</option>
        <?foreach($searchableWidgets as $title=>$values):?>
        	<?if($values['type'] == "date"):?>
        		<option value="dateCache.startDate.desc"><?=$values['label']?> (newest to oldest)</option>
        		<option value="dateCache.startDate.asc"><?=$values['label']?> (oldest to newest)</option>
        	<?else:?>
				<option value="<?=$title?>.raw"><?=$values['label']?></option>
			<?endif?>
		<?endforeach?>
  </select>
  <?else:?>
  	<li class="navbar-right"><select class="form-control sortBy input-xs">
        <option value="title.raw" <?=(!$orderBy || $orderBy=="title.raw")?"SELECTED":null?>>Default Title</option>
        <option value="custom" <?=($orderBy=="custom")?"SELECTED":null?>>Custom Order</option>
  </select>
  <?endif?>

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
				<div class="resultsData">

				</div>
				<div id="mapFrame">
				<div id="mapPane">


				</div>

			</div>
			</div>
		</div>
		<div class="row">
			<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
				<a style="padding-left:5px" href="" class="embedMap">Embed Map</a>
			</div>
		</div>
	</div>
	<div class="tab-pane" id="timeline">
		<div class="row">
			<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12" id="timelineContainer">
				<div class="resultsData">

				</div>
				<div id="timelinePane" style="width: 100%; height: 600px;">


				</div>
				<div class="row">
					<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
						<a style="padding-left:5px" href="" class="embedTimeline">Embed Timeline</a>
					</div>
				</div>
			</div>
		</div>
	</div>
	
</div>

<?$this->load->view("handlebarsTemplates");?>
