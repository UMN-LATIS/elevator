<?
$fileObjectId = $fileObject->getObjectId();


$downloadArray = [];
$targetAsset = null;


$svxPath = null;

$svxPath = stripHTTP(instance_url("fileManager/getSidecar/" . $fileObjectId . "/svx"));


?>
<link rel="shortcut icon" type="image/png" href="https://cdn.jsdelivr.net/gh/smithsonian/dpo-voyager@latest/assets/favicon.png"/>
	
<link href="https://cdn.jsdelivr.net/gh/smithsonian/dpo-voyager@latest/assets/fonts/fonts.css" rel="stylesheet">

<voyager-explorer document="<?=$svxPath?>" bgcolor="white #b5b5b5" bgStyle="RadialGradient" uiMode="menu|help"></voyager-explorer>
<!-- This is the required javascript file for the Voyager element. -->
<script src="https://3d-api.si.edu/resources/js/voyager-explorer.min.js"></script>