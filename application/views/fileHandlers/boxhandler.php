<?
$fileObjectId = $fileObject->getObjectId();
$signedUrls = $fileObject->getSignedURLs("boxView", true);

?>
<?

$embedLink = instance_url("asset/getEmbed/" . $fileObjectId . "/null/true");
$embedLink = str_replace("http:", "", $embedLink);
$embedLink = str_replace("https:", "", $embedLink);

$embed = htmlentities('<iframe width="560" height="480" src="' . $embedLink . '" frameborder="0" allowfullscreen></iframe>', ENT_QUOTES);


?>
<link type="text/css" rel="stylesheet" href="/assets/minifiedcss/crocodoc.viewer.min.css"/>
<link type="text/css" rel="stylesheet" href="/assets/minifiedcss/fullscreen.min.css"/>
<script type="text/javascript" src="/assets/minifiedjs/crocodoc.viewer.min.js"></script>
<script type="text/javascript" src="/assets/minifiedjs/crocdoc.fullscreen.min.js"></script>



<?if(!$embedded):?>
<div class="row assetViewRow">
  <div class="col-md-12">
<?else:?>

<style>
.boxContainer {
  height:100%;
}
</style>

<?endif?>
  <? if(!isset($fileContainers) || count($fileContainers) == 1):?>
    <p class="alert alert-info">No derivatives found.
      <?if(!$this->user_model->userLoaded):?>
      <?=$this->load->view("errors/loginForPermissions")?>
      <?endif?>
    </p>

    <?elseif(isset($fileContainers['boxView']) && $fileContainers['boxView']->ready):?>

    <div class="box-controls-container">

      <div class="controls controls-center page-controls">
        <button class="btn scroll-previous-btn"><span class="glyphicon glyphicon-chevron-left"></span></button>
        <div class="page">
          <button class="btn page-display">1 / </button>
          <input type="text" pattern="\d*" class="page-input">
        </div>
        <button class="btn scroll-next-btn"><span class="glyphicon glyphicon-chevron-right"></span></button>
      </div>
      <div class="controls controls-right">

        <button class="btn zoom-out-btn"><span class="glyphicon glyphicon-zoom-out"></span></button>
        <button class="btn zoom-in-btn"><span class="glyphicon glyphicon-zoom-in"></span></button>
        <button class="btn fullscreen-btn"><span class="glyphicon glyphicon-resize-full"></span></button>
      </div>
    </div>

          <div class="boxContainer">


  </div>
 <script>


 var preSignedUrls = <?=json_encode($signedUrls)?>;

 var urlMutator = function(url) {
    // loop through signed URLs, find the one that mostly matches this one.
    var processedURL = url;
    url = url.replace("http:","");
    url = url.replace("https:","");
    $.each(preSignedUrls, function(index, val) {
        if(val.indexOf(url) !== -1) {

          processedURL = val;
        }
    });
    return processedURL;
}




$(document).ready(function() {
  $(".fullscreen-btn").click(function(event) {
    viewer.enterFullscreen();
  });

  $(".scroll-next-btn").click(function(event) {
    viewer.scrollTo(Crocodoc.SCROLL_NEXT);
  });

  $(".scroll-previous-btn").click(function(event) {
    viewer.scrollTo(Crocodoc.SCROLL_PREVIOUS);
  });

  $(".zoom-out-btn").click(function(event) {
    viewer.zoom(Crocodoc.ZOOM_OUT);
  });

  $(".zoom-in-btn").click(function(event) {
    viewer.zoom(Crocodoc.ZOOM_IN);
  });

  $(".boxContainer").on("remove", function() {
    viewer.destroy();
  });

Crocodoc.addDataProvider('mutate-urls', function(scope) {
    'use strict';

    var config = scope.getConfig();

    return {
        get: function (objectType, key) {
            this._dp = scope.getDataProvider(objectType);

            // this._objectType = objectType;
            // we need to clobber the getURL function, because it's called
            // from within dp.get()

            if(!this._dp.oldGetURL) {
                this._dp.oldGetURL = this._dp.getURL;
            }
            this._getURL = this._dp.oldGetURL;
            this._dp.getURL = this.getURL.bind(this);

            // use the built-in data provider for whatever type this is
            return this._dp.get(objectType, key);
        },

        getURL: function (pageNum) {
            // return this._dp.getURL(pageNum);
            return config.urlMutation(this._getURL(pageNum));
        }
    };
});


  // Create a viewer instance on the body element
  var viewer = Crocodoc.createViewer($(".boxContainer"), {
      // Specify where the viewer should find the assets for this doc
      url: '<?=stripHTTP($fileContainers['boxView']->getURLForFile(true))?>',
      urlMutation: urlMutator,
      layout: Crocodoc.LAYOUT_PRESENTATION,
      <?if($embedded):?>
      useWindowAsViewport: true,
      <?endif?>
      dataProviders: {
        metadata: 'mutate-urls',
        stylesheet: 'mutate-urls',
        'page-img': 'mutate-urls',
        'page-svg': 'mutate-urls',
        'page-text': 'mutate-urls'
    },
      plugins: {
        fullscreen: {
            element: '.crocodoc-viewport',
            useFakeFullscreen: false
        }
    }
  });
  // Load the assets and render the document!
  viewer.load();

  viewer.on('ready', function (event) {

    $(window).on('keydown', function (ev) {
      if (ev.keyCode === 37) {
        viewer.scrollTo(Crocodoc.SCROLL_PREVIOUS);
      } else if (ev.keyCode === 39) {
        viewer.scrollTo(Crocodoc.SCROLL_NEXT);
      } else {
        return;
      }
      ev.preventDefault();
    });

  });

  viewer.on("pagefocus", function (event) {

    $(".page-display").text(event.data.page + " / " + event.data.numPages);
  });

});


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
      <li class="list-group-item"><strong>File Type: </strong> Document</li>
      <li class="list-group-item assetDetails"><strong>Original Name: </strong><?=htmlentities($fileObject->sourceFile->originalFilename, ENT_QUOTES)?></li>
      <li class="list-group-item assetDetails"><strong>File Size: </strong><?=byte_format($fileObject->sourceFile->metadata["filesize"])?></li>
    </ul>'></span>
      <span class="glyphicon glyphicon-download infoPopover" data-placement="bottom" data-toggle="popover" title="Download" data-html="true" data-content='
          <ul>
            <?if(isset($fileContainers['pdf'])):?>
            <li class="list-group-item assetDetails"><a href="<?=instance_url("fileManager/getDerivativeById/". $fileObjectId . "/pdf")?>">Download PDF</a></li>
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

