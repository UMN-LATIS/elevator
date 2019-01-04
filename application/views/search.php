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
  <li><a href="#gallery" data-toggle="tab">Gallery</a></li>
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

	<div class="tab-pane" id="gallery">
		<div class="row">
			<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12" id="galleryContainer">
				<div id="previewFrame" style="width: 100%;">
					<div class="frameFullscreenContainer">
						<div class="frameHeader">

						</div>
						<iframe width="100%" class="galleryIframe"></iframe>
						<div class="scrollbar">
							<div class="handle"></div>
						</div>
						<div class="frame" id="forcecentered">
							<ul class="clearfix">
							</ul>
						</div>	
						<div class="controls center">
							<button class="btn prev"><i class="glyphicon glyphicon-chevron-left"></i> prev</button>
							<button class="btn next">next <i class="glyphicon glyphicon-chevron-right"></i></button>
							<button class="btn fullscreen">fullscreen <i class="glyphicon glyphicon-fullscreen"></i></button>
						</div>
						<div class="resultsData">

						</div>
					</div>

				</div>
			</div>
		</div>
	</div>
	
</div>


<style>

.frameFullscreenContainer {
	height: 100%;
	width: 100%;
}

.galleryIframe {
	min-height: 600px;
	height: calc(100% - 50px - 50px - 100px);
}

/* Frame */
.frame {
	height: 50px;
	line-height: 50px;
	overflow: hidden;
}
.frame ul {
	list-style: none;
	margin: 0;
	padding: 0;
	height: 100%;
	font-size: 50px;

}
.frame ul li {
	float: left;
	width: 50px;
	height: 100%;
	margin: 0 1px 0 0;
	padding: 0;
	background: #333;
	color: #ddd;
	/*text-align: center;*/
	cursor: pointer;
	display: flex;
  	align-items: center;
  	justify-content: center;
}
.frame ul li.active {
	color: #fff;
	background: #a03232;
}

.frameHeader h2 {
	height: 30px;
	line-height: 30px;
}

/* Scrollbar */
.scrollbar {
	margin: 0 0 1em 0;
	height: 5px;
	background: #ccc;
	line-height: 0;
}
.scrollbar .handle {
	width: 100px;
	height: 100%;
	background: #292a33;
	cursor: pointer;
}
.scrollbar .handle .mousearea {
	position: absolute;
	top: -9px;
	left: 0;
	width: 100%;
	height: 20px;
}

/* Controls */
.controls { margin: 25px 0; text-align: center; }

</style>
<?$this->load->view("handlebarsTemplates");?>
