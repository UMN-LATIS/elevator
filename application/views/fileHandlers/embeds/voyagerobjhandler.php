<?
$fileObjectId = $fileObject->getObjectId();


$downloadArray = [];
$targetAsset = null;
if(isset($fileContainers['glb']) && $fileContainers['glb']->ready) {
  $glbURL = isset($fileContainers['glb'])?$fileContainers['glb']->getProtectedURLForFile():null;
  $targetAsset = stripHTTP($glbURL) . "#.glb";
}

$svxPath = null;

if(isset($widgetObject->sidecars) && array_key_exists("svx", $widgetObject->sidecars) && is_array($widgetObject->sidecars['svx'])) {
    $svxPath = stripHTTP(instance_url("fileManager/getSidecar/" . $fileObjectId . "/svx"));
}


?>
<link rel="shortcut icon" type="image/png" href="https://cdn.jsdelivr.net/gh/smithsonian/dpo-voyager@latest/assets/favicon.png"/>
	
<link href="https://cdn.jsdelivr.net/gh/smithsonian/dpo-voyager@latest/assets/fonts/fonts.css" rel="stylesheet">
<?if($svxPath):?>
<voyager-explorer document="<?=$svxPath?>" model="<?=$targetAsset?>"></voyager-explorer>
<?else:?>
<voyager-explorer model="<?=$targetAsset?>" uiMode="menu|help"></voyager-explorer>
<?endif?>
<!-- This is the required javascript file for the Voyager element. -->
<script src="https://3d-api.si.edu/resources/js/voyager-explorer.min.js"></script>