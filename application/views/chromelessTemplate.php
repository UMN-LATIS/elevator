<!DOCTYPE html>
<html>
<head>
  <title><?= $this->template->title->default("Media Elevator"); ?></title>
  <meta charset="utf-8">

  <?=$this->template->meta; ?>
  <?=$this->template->stylesheet; ?>
  <?if(isset($this->instance) && $this->instance->getUseCustomCSS()):?>
  <link rel="stylesheet" href="/assets/instanceAssets/<?=$this->instance->getId()?>.css">
  <?endif?>
<script src="/assets/minifiedjs/jquery.min.js"></script>

<link rel="stylesheet" href="/assets/leaflet/MarkerCluster.css">
<link rel="stylesheet" href="/assets/leaflet/MarkerCluster.Default.css">
<link rel="stylesheet" href="/assets/leaflet/leaflet.css">
<link rel="stylesheet" href="/assets/leaflet/L.Control.Locate.min.css">
<script type="text/javascript" src='/assets/leaflet/leaflet.js'></script>
<script type="text/javascript" src='/assets/leaflet/Leaflet.fullscreen.min.js'></script>
<script type="text/javascript" src='/assets/leaflet/leaflet-measure.min.js'></script>
<script type="text/javascript" src='/assets/leaflet/Control.MiniMap.min.js'></script>
<script type="text/javascript" src='/assets/leaflet/esri-leaflet.js'></script>
<script type="text/javascript" src='/assets/leaflet/leaflet.markercluster.js'></script>
<script type="text/javascript" src='/assets/leaflet/L.Control.Locate.min.js'></script>
</head>

<script>
var basePath = "<?=$this->template->relativePath?>";
var googleAPIKey = "<?=$this->config->item("googleApi")?>";
</script>

  <!-- Bootstrap CSS -->
</head>


<body>
  <style>
.input-group-btn>.btn+.btn {
  margin-left: -5px;
}

.dropdown-menu {
  left:-75px;
}

  </style>

  <div class="container">

    <?php
    // This is the main content partial
    echo $this->template->content;
    ?>

    <hr>
  </div>
<?=$this->template->javascript; ?>
<script>
var lazyInstance;
$(document).ready(function() {
   lazyInstance = $('.lazy').Lazy({ chainable: false });
});
</script>
</body>
</html>
