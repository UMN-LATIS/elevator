<base target="_parent">
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

<div class="active hide">
	<a href="#gallery"></a>
</div>

<script>

$(document).ready(function() {
	loadAll = true;
});
</script>
<style>
#previewFrame {
	height: 100%;
	width: 100%;
}


.frameFullscreenContainer {
	height: 100%;
	width: 100%;
}

.galleryIframe {
	min-height: 200px;
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