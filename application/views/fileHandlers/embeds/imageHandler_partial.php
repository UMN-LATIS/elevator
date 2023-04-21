<?
if(isset($fileContainers['tiled-tar'])) {
	$tiledTar = stripHTTP($fileContainers['tiled-tar']->getProtectedURLForFile());
    // safari doesn't want to decode a gzip encoded file if it ends in .gz
    $fileContainers['tiled-index']->originalFilename = 'tiled-index.json';
	$tiledTarIndex = stripHTTP($fileContainers['tiled-index']->getProtectedURLForFile());
  }
?>
<script>
<?if(isset($tiledTar)):?>
    var tiledTar = "<?=$tiledTar?>";
    var tiledTarIndex = "<?=$tiledTarIndex?>";
    var manifestJson = null;
    async function loadIndex() {
        const manifest = await fetch(tiledTarIndex);
        manifestJson = await manifest.json();
        return manifestJson;
    }
    var tileLoadFunction = async function(coords, tile, done) {
        async function getData() {
            // get the range for the file we want
            filename = "./tiledBase_files/" + coords.z + "/" + coords.x + "_" + coords.y + ".jpeg"
            const fileInfo = manifestJson[filename];
            if(!fileInfo){
                return;
            }
            const { s, e } = fileInfo;

            // fetch the file
            const response = await fetch(tiledTar, {
                headers: {
                Range: `bytes=${s}-${e}`,
                },
            });

            const buffer = await response.arrayBuffer();
            return buffer
        }
        var url = "";
        getData().then(function(data) {
            if(!data) {
                return
            }
            url =URL.createObjectURL(new Blob([data], {type:"image/jpeg"})); 
            tile.src = url;

        });
    }
<?else:?>
    var loadIndex = async function() {
    }
    var tileLoadFunction = function(coords, tile, done) {
        var params = {Bucket: '<?=$fileObject->collection->getBucket()?>', Key: "derivative/<?=$fileContainers['tiled']->getCompositeName()?>/tiledBase_files/" + coords.z + "/" + coords.x + "_" + coords.y + ".jpeg"};
        var url = s3.getSignedUrl('getObject', params)
        tile.src = url;
        return tile;
    }
<?endif?>
</script>