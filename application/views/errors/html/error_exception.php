<!DOCTYPE html>
<html>
<head>

  <title>404 Not Found</title>
  <meta charset="utf-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<link rel="shortcut icon" href="/favicon.ico" type="image/x-icon" />
<link rel="stylesheet" href="/assets/leaflet/MarkerCluster.css">
<link rel="stylesheet" href="/assets/leaflet/MarkerCluster.Default.css">
<link rel="stylesheet" href="/assets/leaflet/leaflet.css">
<link rel="stylesheet" href="/assets/leaflet/L.Control.Locate.min.css">
<link rel="stylesheet" href="/assets/minifiedcss/bootstrap.min.css">
<link rel="stylesheet" href="/assets/minifiedcss/screen.min.css">

<script type="text/javascript" src='/assets/leaflet/leaflet.js'></script>
<script type="text/javascript" src='/assets/leaflet/Leaflet.fullscreen.min.js'></script>
<script type="text/javascript" src='/assets/leaflet/leaflet-measure.min.js'></script>
<script type="text/javascript" src='/assets/leaflet/Control.MiniMap.min.js'></script>
<script type="text/javascript" src='/assets/leaflet/L.Control.Locate.min.js'></script>
<script type="text/javascript" src='/assets/leaflet/esri-leaflet.js'></script>
<script type="text/javascript" src='/assets/leaflet/leaflet.markercluster.js'></script>
<script src="/assets/minifiedjs/jquery.min.js"></script>
</head>



<body>



 <nav class="navbar navbar-default" role="navigation">
    <!-- Brand and toggle get grouped for better mobile display -->



        <div class="navbar-header">

      <a class="navbar-brand">
        <div class="headerLogoText">Elevator</div>
        
      </a>
    </div>
    <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">

      <ul class="nav navbar-nav">
        


                <li><a href="http://umn-latis.github.io/elevator/" target="_blank">Help</span></a></li>
             
      </ul>
   

    </div><!-- /.navbar-collapse -->


  </nav>



  <div class="container mainContent jumbotron">
<div style="border:1px solid #990000;padding-left:20px;margin:0 0 10px 0;">

<h4>An uncaught Exception was encountered</h4>

<p>Type: <?php echo get_class($exception); ?></p>
<p>Message: <?php echo $message; ?></p>
<p>Filename: <?php echo $exception->getFile(); ?></p>
<p>Line Number: <?php echo $exception->getLine(); ?></p>
</div>
<?php if (defined('SHOW_DEBUG_BACKTRACE') && SHOW_DEBUG_BACKTRACE === TRUE): ?>

	<p>Backtrace:</p>
	<?php foreach ($exception->getTrace() as $error): ?>

		<?php if (isset($error['file']) && strpos($error['file'], realpath(BASEPATH)) !== 0): ?>

			<p style="margin-left:10px">
			File: <?php echo $error['file']; ?><br />
			Line: <?php echo $error['line']; ?><br />
			Function: <?php echo $error['function']; ?>
			</p>
		<?php endif ?>

	<?php endforeach ?>

<?php endif ?>


</div>
    <footer class="footer">

      <p class="universityFooter">
        <img src="/assets/images/elevatorSolo.png" class="elevatorFooterImage" alt="Grain Elevator Icon">Powered by Elevator, developed by the <A href="http://www.umn.edu" target="_blank">University of Minnesota</a>
      </p>
    </footer>



</body>
</html>

