<?
$fileObjectId = $fileObject->getObjectId();
$token = $fileObject->getSecurityToken("tiled");
?>
<?


$embedLink = instance_url("asset/getEmbed/" . $fileObjectId . "/null/true");
$embedLink = str_replace("http:", "", $embedLink);
$embedLink = str_replace("https:", "", $embedLink);

$embed = htmlentities('<iframe width="560" height="480" src="' . $embedLink . '" frameborder="0" allowfullscreen></iframe>', ENT_QUOTES);

if(count($fileContainers)>0) {
  $menuArray['embed'] = $embed;
  $menuArray['embedLink'] = $embedLink;
}

$fileInfo = [];
$fileInfo["File Type"] = "Reflectance Transformation Image";
$fileInfo["Original Name"] = $fileObject->sourceFile->originalFilename;
$fileInfo["File Size"] = $fileObject->sourceFile->metadata["filesize"];

$fileInfo["Type"] = $fileObject->sourceFile->metadata["type"];
$fileInfo["Width"] = $fileObject->sourceFile->metadata["width"];
$fileInfo["Height"] = $fileObject->sourceFile->metadata["height"];
$fileInfo["Scale"] = $fileObject->sourceFile->metadata["scale"];
$fileInfo["Bias"] = $fileObject->sourceFile->metadata["bias"];

$menuArray['fileInfo'] = $fileInfo;


$downloadArray = [];

if($allowOriginal) {
  $downloadArray['Download Original'] = instance_url("fileManager/getOriginal/". $fileObjectId);
}

$menuArray['download'] = $downloadArray;


?>
<link type="text/css" href="/assets/webViewer/css/webrtiviewer.css" rel="Stylesheet">
<link type="text/css" href="/assets/css/jquery-ui.css" rel="Stylesheet">
<script src="/assets/js/aws-s3.js"></script>
<?if(defined('ENVIRONMENT') && ENVIRONMENT == "development") :?>


<script type="text/javascript" src="/assets/webViewer/spidergl/spidergl.js"></script>
<script type="text/javascript" src="/assets/webViewer/spidergl/multires.js"></script>


<?else:?>
<script type="text/javascript" src="/assets/webViewer/js/webrti.min.js"></script>
<?endif?>

<?if(!$embedded):?>
<div class="row assetViewRow">
  <div class="col-md-12">
<?endif?>
  <? if(!isset($fileContainers) || !isset($fileContainers['tiled']) ):?>
    <p class="alert alert-info">No derivatives found.
      <?if(!$this->user_model->userLoaded):?>
      <?=$this->load->view("errors/loginForPermissions")?>
      <?if($embedded):?>
      <?=$this->load->view("login/login")?>
      <?endif?>
      <?endif?>
    </p>

    <?else:?>
   <div id="viewerCont">
      
    </div>


<script type="text/javascript">

var AWS;


var loadedCallback = function() {
  if(typeof AWS === 'undefined') {
      console.log("pausing for aws");
      setTimeout(loadedCallback, 200);
      return;
    }

    AWS.config = new AWS.Config();
    AWS.config.update({accessKeyId: "<?=$token['AccessKeyId']?>", secretAccessKey: "<?=$token['SecretAccessKey']?>", sessionToken: "<?=$token['SessionToken']?>"});
    AWS.config.region = '<?=$fileObject->collection->getBucketRegion()?>';
    s3 = new AWS.S3({Bucket: '<?=$fileObject->collection->getBucket()?>'});
    var loadFunction = function(target) {
      var params = {Bucket: '<?=$fileObject->collection->getBucket()?>', Key: "derivative/<?=$fileContainers['tiled']->getCompositeName()?>" + target};
      return s3.getSignedUrl('getObject', params);
    }

    createRtiViewer("viewerCont", loadFunction, 640, 480); 
    setTimeout(function() {
      var evt = document.createEvent('Event');  
      evt.initEvent('load', false, false);  
      window.dispatchEvent(evt);
    }, 200);
    
}



</script>


    <?endif?>
<?if(!$embedded):?>
  </div>
</div>
<?endif?>





<?if(!$embedded):?>


<?=renderFileMenu($menuArray)?>
<script>
$(function ()
{
  $(".infoPopover").popover({trigger: "focus | click"});
  $(".infoPopover").tooltip({ placement: 'top'});

});
</script>

<?endif?>


