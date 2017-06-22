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

<?if(isset($this->instance) && $this->instance->getUseCustomCSS()):?>
<link rel="stylesheet" href="/assets/instanceAssets/<?=$this->instance->getId()?>.css">
<?else:?>
<link rel="stylesheet" href="/assets/css/screen.css">
<?endif?>
<script src="/assets/minifiedjs/jquery.min.js"></script>


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