  <script>
  var basePath = "<?=$this->template->relativePath?>";
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

if(window.location.hash  == "#secondFrame" && inIframe()) {
    window.addEventListener("message", function(event) {
        if(event.data == "pageLoaded") {
            location.reload();
        }
    }, false);
}

if(document.cookie && document.cookie.search(/_check_is_passive=/) >= 0){

    if(window.location.hash  == "#firstFrame" && inIframe()) {
        var frames = window.parent.frames;
        for(index = 0; index < frames.length; index++) {
            frames[index].postMessage("pageLoaded", "*");
        }
    }

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
    if(window.location.hash  == "#secondFrame" && inIframe()) {

    }
    else {
        // Mark browser as being isPassive checked

        // safari doesn't allow third party cookies unless the user has accessed the site before, so we need to have them click to launch a popup..
        if(!document.cookie) {
          window.onload = function(e) {
            document.body.innerHTML = "To load this resource, please click the button below. <input type=button value='Load Resource' onClick='popitup();'>";
          }

        }
        else {
          document.cookie = "_check_is_passive=" + window.location + ";path=/";
          // Redirect to Shibboleth handler
          window.location.href = "https://" + window.location.hostname + "/Shibboleth.sso/Login?isPassive=true&target=" + encodeURIComponent("https://"+window.location.hostname + basePath + "/loginManager/remoteLogin/true?redirect=" + encodeURIComponent(window.location));

        }

    }
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