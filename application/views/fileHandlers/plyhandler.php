<?
$fileObjectId = $fileObject->getObjectId();

$drawerArray = array();
if($this->user_model->userLoaded) {
    foreach($this->user_model->getDrawers(true) as $drawer) {
        $drawerArray[] = $drawer;
    }
}
?>
<?


$embedLink = instance_url("asset/getEmbed/" . $fileObjectId . "/null/true");
$embedLink = str_replace("http:", "", $embedLink);
$embedLink = str_replace("https:", "", $embedLink);

$embed = htmlentities('<iframe width="560" height="480" src="' . $embedLink . '" frameborder="0" allowfullscreen></iframe>', ENT_QUOTES);


?>
<link type="text/css" rel="stylesheet" href="/assets/3dviewer/stylesheet/3dhop.css"/>
<!--SPIDERGL-->
<script type="text/javascript" src="/assets/3dviewer/js/spidergl.js"></script>
<!--PRESENTER-->
<script type="text/javascript" src="/assets/3dviewer/js/presenter.js"></script>
<!--3D MODELS LOADING AND RENDERING-->
<script type="text/javascript" src="/assets/3dviewer/js/nexus.js"></script>
<script type="text/javascript" src="/assets/3dviewer/js/ply.js"></script>
<!--TRACKBALLS-->
<script type="text/javascript" src="/assets/3dviewer/js/trackball_sphere.js"></script>
<script type="text/javascript" src="/assets/3dviewer/js/trackball_turntable.js"></script>
<script type="text/javascript" src="/assets/3dviewer/js/trackball_pantilt.js"></script>
<!--UTILITY-->
<script type="text/javascript" src="/assets/3dviewer/js/init.js"></script>


<?if(!$embedded):?>
<div class="row assetViewRow">
  <div class="col-md-12">
<?endif?>
  <? if(!isset($fileContainers) || count($fileContainers) == 1):?>
    <p class="alert alert-info">No derivatives found.
      <?if(!$this->user_model->userLoaded):?>
      You may have access to additional derivatives if you log in.
      <?endif?>
    </p>

    <?else:?>
    <div class="threedelementcontainer">
<div id="3dhop" class="tdhop" onmousedown="if (event.preventDefault) event.preventDefault()"><div id="tdhlg"></div>
 <div id="toolbar">
  <img id="home"       title="Home"                  src="/assets/3dviewer/skins/dark/home.png"   /><br/>
  <img id="zoomin"     title="Zoom In"               src="/assets/3dviewer/skins/dark/zoomin.png" /><br/>
  <img id="zoomout"    title="Zoom Out"              src="/assets/3dviewer/skins/dark/zoomout.png"/><br/>
  <img id="light_on"   title="Disable Light Control" src="/assets/3dviewer/skins/dark/light_on.png" style="position:absolute; visibility:hidden;"/>
  <img id="light"      title="Enable Light Control"  src="/assets/3dviewer/skins/dark/light.png"/><br/>
   <img id="measure_on" title="Disable Measure Tool"  src="/assets/3dviewer/skins/dark/measure_on.png"
                                                          style="position:absolute; visibility:hidden;"/>
  <img id="measure"    title="Enable Measure Tool"   src="/assets/3dviewer/skins/dark/measure.png"/><br/>
 <img id="hotspot_on" title="Hide Hotspots"         src="/assets/3dviewer/skins/dark/pin_on.png"   style="position:absolute; visibility:hidden;"/>
  <img id="hotspot"    title="Show Hotspots"         src="/assets/3dviewer/skins/dark/pin.png"    /><br/>
  <img id="full_on"    title="Exit Full Screen"      src="/assets/3dviewer/skins/dark/full_on.png" style="position:absolute; visibility:hidden;"/>
  <img id="full"       title="Full Screen"           src="/assets/3dviewer/skins/dark/full.png"   />
 </div>
  <div id="measurebox" style="background-color:rgba(125,125,125,0.5);color:#f8f8f8;">Measured length:<br/>
  <span id="measure-output" onmousedown="event.stopPropagation()">0.0</span>
 </div>
 <canvas id="draw-canvas" style="background-image: url(/assets/3dviewer/skins/light/background.png)"/>
</div>
 <canvas id="draw-canvas"/>

 <div id="loadIndicator">
    Loading...
  </div>
</div>
</div>

<style>
#loadIndicator {
  position: absolute;
  top: calc(50% - 10px);
  left:calc(50% - 10px);
  background-color: white;
  border: 1px solid #fff;
  padding: 20px;

}
</style>

<script type="text/javascript">
var presenter = null;

var lookupArray = {};


<?if($widgetObject && $widgetObject->sidecars && array_key_exists("3dpoints", $widgetObject->sidecars) && strlen($widgetObject->sidecars['3dpoints'])>3 ):?>
lookupArray = <?=str_replace("\n", "", $widgetObject->sidecars['3dpoints'])?>;
<?endif?>



function setup3dhop() {
  presenter = new Presenter("draw-canvas");


  spotObjects = {};
  $.each(lookupArray, function(index, val) {
    newSpotObject = {};
    newSpotObject.mesh = "Sphere";
    if(!val.hasOwnProperty("scaling")) {
      val.scaling = 0.2;
    }
    if(!val.hasOwnProperty("color")) {
      val.color = [0.0, 0.25, 1.0, 0.4];
    }
    newSpotObject.transform = {matrix: SglMat4.mul(SglMat4.translation(val.coordinates), SglMat4.scaling([val.scaling, val.scaling, val.scaling]))};
    newSpotObject.color = val.color;
    spotObjects[index] = newSpotObject;
  });


  presenter.setScene({
    meshes: {
      "targetAsset" : { url: "<?=stripHTTP($fileContainers['nxs']->getProtectedURLForFile())?>#.nxs" },
      "Sphere" : { url: "/assets/3dviewer/models/singleres/sphere.ply" },
    },
    modelInstances : {
      "targetAsset" : {
        mesh : "targetAsset"
      }
    },
    spots : spotObjects,
    trackball: {
      type : TurnTableTrackball,
      trackOptions : {
        startDistance : 2.0,
        minMaxDist    : [0.2, 15.0],
        minMaxPhi: [-180, 180],
        minMaxTheta   : [-70.0, 70.0]
      }
    }
  });


  presenter.setSpotVisibility(HOP_ALL, false, true);

  presenter._onPickedSpot = onPickedSpot;
  presenter._onPickedInstance = onPickedInstance;
  presenter._onEndMeasurement = onEndMeasure;
  presenter._onLoadedEvent = function() {
    $("#loadIndicator").hide();
  }
}

function actionsToolbar(action) {
  if(action=='home') presenter.resetTrackball();
  else if(action=='zoomin') presenter.zoomIn();
  else if(action=='zoomout') presenter.zoomOut();
  else if(action=='light' || action=='light_on') { presenter.enableLightTrackball(!presenter.isLightTrackballEnabled()); lightSwitch(); }
  else if(action=='measure' || action=='measure_on') { presenter.enableMeasurementTool(!presenter.isMeasurementToolEnabled()); measurementSwitch(); }
  else if(action=='hotspot'|| action=='hotspot_on') { presenter.toggleSpotVisibility(HOP_ALL, true); presenter.enableOnHover(!presenter.isOnHoverEnabled()); hotspotSwitch(); }
  else if(action=='full'  || action=='full_on') fullscreenSwitch();
}

function onPickedSpot(id) {

  var alertText = lookupArray[id].alertText;
  alert(alertText);

}

function onPickedInstance(id) {
  if(presenter._lastPickedSpot!="null") return;
}

function onEndMeasure(measure) {
  $('#measure-output').html(Math.round(measure*1000)/(1000));
}

var timer;

var resizeTarget = function() {
  if($('#full').css("visibility")=="visible"){
    resizeCanvas($('#3dhop').parent().width(),$('#3dhop').parent().height());
    presenter.ui.postDrawEvent();
  }
}

$(window).resize(function(event) {

  clearTimeout(timer);
  timer = setTimeout(resizeTarget(), 100);

});

var loadedCallback = function() {
  init3dhop();
  setup3dhop();
   moveMeasurebox(10,10);
}



</script>


    <?endif?>
<?if(!$embedded):?>
  </div>
</div>
<?endif?>





<?if(!$embedded):?>


<div class="row infoRow ">
  <div class="col-md-12">
    <span class="glyphicon glyphicon-info-sign infoPopover" data-placement="bottom" data-toggle="popover" title="File Info" data-html=true data-content='<ul class="list-group">
      <li class="list-group-item"><strong>File Type: </strong> OBJ</li>
      <li class="list-group-item assetDetails"><strong>Original Name: </strong><?=$fileObject->sourceFile->originalFilename?></li>
      <li class="list-group-item assetDetails"><strong>File Size: </strong><?=byte_format($fileObject->sourceFile->metadata["filesize"])?></li>
    </ul>'></span>
      <span class="glyphicon glyphicon-download infoPopover" data-placement="bottom" data-toggle="popover" title="Download" data-html="true" data-content='
          <ul>
          <?if(isset($fileContainers['nxs'])):?>
            <li class="list-group-item assetDetails"><a href="<?=instance_url("fileManager/getDerivativeById/". $fileObjectId . "/nxs")?>">Download Derivative (nxs)</a></li>
          <?endif?>
          <?if(isset($fileContainers['stl'])):?>
            <li class="list-group-item assetDetails"><a href="<?=instance_url("fileManager/getDerivativeById/". $fileObjectId . "/stl")?>">Download Derivative (stl)</a></li>
          <?endif?>
      <?if($allowOriginal):?>
      <li class="list-group-item assetDetails"><a href="<?=instance_url("fileManager/getOriginal/". $fileObjectId)?>">Download Original</a></li>
      <?endif?>
      </ul>'></span>
    <span class="glyphicon glyphicon-share infoPopover" data-placement="bottom" data-toggle="popover" title="Share" data-html="true" data-content='<ul class="list-group">
        <?if($allowOriginal):?>
            <li class="list-group-item assetDetails"><strong>Embed: </strong><input class="form-control embedControl" value="<?=htmlspecialchars($embed, ENT_QUOTES)?>"></li>
          <?endif?>
    </ul>'></span>
  </div>
</div>

<script>
$(function ()
{
  $(".infoPopover").popover({trigger: "focus | click"});
  $(".infoPopover").tooltip({ placement: 'top'});

});
</script>

<?endif?>


