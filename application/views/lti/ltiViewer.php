<script>

sessionStorage.elevatorPlugin = "Canvas";
sessionStorage.elevatorCallbackType = "lti"
sessionStorage.apiKey = null;
sessionStorage.timeStamp = null;
sessionStorage.entangledSecret = null;
sessionStorage.includeMetadata = null;
sessionStorage.returnURL = "<?=$returnURL?>";
sessionStorage.ltiVersion = "<?=$ltiVersion?>";
sessionStorage.launchId = "<?=$launchId??null?>";
sessionStorage.userId = "<?=$userId??null?>";
document.cookie = '_check_is_passive=;expires=Thu, 01 Jan 1970 00:00:01 GMT;';
window.location = "<?=instance_url("/")?>";

</script>