<!doctype html>
<html lang="en" style="width: 100%; height: 100%">
  <head>
    <title><?= $this->template->title->default(isset($this->instance)?$this->instance->getName():"Elevator"); ?></title>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

  </head>
  <body style="width: 100%; height: 100%">
<script>
  var basePath = "<?=$this->template->relativePath?>";

  if(window.name == 'loginRedirectWindow') {
    window.opener.childWindowWillClose();
    window.close();
  }

  </script>

<?if(isset($this->instance) && $this->instance->getUseCentralAuth()):?>
<script>


  function inIframe () {
    try {
      return window.self !== window.top;
    } catch (e) {
      return true;
    }
  }

  function popitup() {
    newwindow=window.open("https://" + window.location.hostname + "/autoclose.html",'name','height=200,width=150');

    setTimeout(function() { location.reload();}, 1200);
    return false;
  }

//-->
</script>
<?=$this->user_model->getAuthHelper()->templateView();?>

<?endif?>
<?=$this->template->meta; ?>
<?=$this->template->stylesheet; ?>



<link rel="stylesheet" href="/assets/leaflet/MarkerCluster.css">
<link rel="stylesheet" href="/assets/leaflet/MarkerCluster.Default.css">
<link rel="stylesheet" href="/assets/leaflet/leaflet.css">

<?if(isset($this->instance) && $this->instance->getUseCustomCSS()):?>
<link rel="stylesheet" href="/assets/instanceAssets/<?=$this->instance->getId()?>.css">
<?endif?>
<style>
body {
  background-color: transparent;
}
</style>
<script src="/assets/minifiedjs/jquery.min.js"></script>
<script type="text/javascript" src='/assets/leaflet/leaflet.js'></script>
<script type="text/javascript" src='/assets/leaflet/Leaflet.fullscreen.min.js'></script>
<script type="text/javascript" src='/assets/leaflet/Leaflet.elevator.js'></script>
<script type="text/javascript" src='/assets/leaflet/leaflet-measure.min.js'></script>
<script type="text/javascript" src='/assets/leaflet/Control.MiniMap.min.js'></script>
<script type="text/javascript" src='/assets/leaflet/esri-leaflet.js'></script>
<script type="text/javascript" src='/assets/leaflet/leaflet.markercluster.js'></script>

    <?php
    // This is the main content partial
    echo $this->template->content;
    ?>
<?=$this->template->javascript; ?>
<script>
var lazyInstance;
$(document).ready(function() {
   lazyInstance = $('.lazy').Lazy({ chainable: false });
});
</script>
</body>
</html>