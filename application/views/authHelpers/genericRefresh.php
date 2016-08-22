<script>

if(window.location.hash  == "#firstFrame" && inIframe()) {
    var frames = window.parent.frames;
    for(index = 0; index < frames.length; index++) {
        frames[index].postMessage("pageLoaded", "*");
    }
}

</script>