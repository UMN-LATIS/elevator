  <script>
  var basePath = "<?=$this->template->relativePath?>";
  </script>

<?if(isset($this->instance) && $this->instance->getUseCentralAuth()):?>
<script>
if(document.cookie && document.cookie.search(/_check_is_passive=/) >= 0){
    // If we have the opensaml::FatalProfileException GET arguments
    // redirect to initial location because isPassive failed
    if (
        window.location.search.search(/errorType/) >= 0
        && window.location.search.search(/RelayState/) >= 0
        && window.location.search.search(/requestURL/) >= 0
    ) {
        var startpos = (document.cookie.indexOf('_check_is_passive=')+18);
        var endpos = document.cookie.indexOf(';', startpos);
        window.location = document.cookie.substring(startpos,endpos);
    }
} else {
    // Mark browser as being isPassive checked
    document.cookie = "_check_is_passive=" + window.location;

    // Redirect to Shibboleth handler
    window.location = "/Shibboleth.sso/Login?isPassive=true&target=" + encodeURIComponent(basePath + "/loginManager/remoteLogin/?" + window.location);
}
//-->
</script>
<?endif?>
<?=$this->template->meta; ?>
<?=$this->template->stylesheet; ?>

<?if(isset($this->instance) && $this->instance->getUseCustomCSS()):?>
<link rel="stylesheet" href="/assets/instanceAssets/<?=$this->instance->getId()?>.css">
<?else:?>
<link rel="stylesheet" href="/assets/css/screen.css">
<?endif?>
  <?=$this->template->javascript; ?>


    <?php
    // This is the main content partial
    echo $this->template->content;
    ?>
