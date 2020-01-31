<?
$fileObjectId = $fileObject->getObjectId();
$token = $fileObject->getSecurityToken("tiled");
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




