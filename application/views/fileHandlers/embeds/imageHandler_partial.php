<?
if (isset($fileContainers['tiled-tar'])) {
    $tiledTar = stripHTTP($fileContainers['tiled-tar']->getProtectedURLForFile());
    // safari doesn't want to decode a gzip encoded file if it ends in .gz
    $fileContainers['tiled-index']->originalFilename = 'tiled-index.json';
    $tiledTarIndex = stripHTTP($fileContainers['tiled-index']->getProtectedURLForFile());
}
?>
<script>
    <? if (isset($tiledTar)) : ?>
        var tiledTar = "<?= $tiledTar ?>";
        var tiledTarIndex = "<?= $tiledTarIndex ?>";
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
                if (!fileInfo) {
                    return;
                }
                const {
                    s,
                    e
                } = fileInfo;

                // fetch the file
                var params = {
                    Bucket: '<?= $fileObject->collection->getBucket() ?>',
                    Key: "derivative/<?= $fileContainers['tiled-tar']->getCompositeName() ?>",
                    Range: `bytes=${s}-${e}`
                };
                var url = s3.getSignedUrl('getObject', params)
                const response = await fetch(url, {
                    headers: {
                        Range: `bytes=${s}-${e}`,
                    },
                });

                const buffer = await response.arrayBuffer();
                return buffer
            }
            var url = "";
            getData().then(function(data) {
                if (!data) {
                    return
                }
                url = URL.createObjectURL(new Blob([data], {
                    type: "image/jpeg"
                }));
                tile.src = url;

            });
        }
    <? elseif (isset($fileContainers['tiled-iiif'])) : ?>

        </script>
        <script src="/assets/leaflet/geotiff.js"></script>
        <script>
        var tiff;
        var image;
        var count;
        var subimages = {};
        var maxZoom = <?=isset($fileObject->sourceFile->metadata["dziMaxZoom"])?$fileObject->sourceFile->metadata["dziMaxZoom"]:16?> - 1;
        var loadIndex = async function() {
            tiff = await GeoTIFF.fromUrl("<?=$fileContainers["tiled-iiif"]->getProtectedURLForFile()?>");
            image = await tiff.getImage();
        }

        function hexStringToUint8Array(hexString) {
            if (hexString.length % 2 !== 0) {
                throw "Invalid hexString";
            } /*from  w w w.  j  av a 2s  . c  o  m*/
            var arrayBuffer = new Uint8Array(hexString.length / 2);

            for (var i = 0; i < hexString.length; i += 2) {
                var byteValue = parseInt(hexString.substr(i, 2), 16);
                if (isNaN(byteValue)) {
                    throw "Invalid hexString";
                }
                arrayBuffer[i / 2] = byteValue;
            }

            return arrayBuffer;
        }
        var tileLoadFunction = async function(coords, tile, done) {
            if(subimages[coords.z] == undefined) {
                subimages[coords.z] = await tiff.getImage(maxZoom - coords.z);
            }
            const tileSize = this.options.tileSize;;
            const subimage = subimages[coords.z];
            async function getData() {
                
                
                const numTilesPerRow = Math.ceil(subimage.getWidth() / subimage.getTileWidth());
                const numTilesPerCol = Math.ceil(subimage.getHeight() / subimage.getTileHeight());
                console.log("IIIF load", coords.z, numTilesPerCol, numTilesPerRow);
                const index = (coords.y * numTilesPerRow) + coords.x;
                let offset;
                let byteCount;
                // do this with our own fetch instead of geotiff so that we can get parallel requests
                offset = subimage.fileDirectory.TileOffsets[index];
                byteCount = subimage.fileDirectory.TileByteCounts[index];
                var params = {
                    Bucket: '<?= $fileObject->collection->getBucket() ?>',
                    Key: "derivative/<?= $fileContainers['tiled-iiif']->getCompositeName() ?>",
                    Range: `bytes=${offset}-${offset+byteCount}`
                };
                var url = s3.getSignedUrl('getObject', params)
                const response = await fetch(url, {
                    headers: {
                        Range: `bytes=${offset}-${offset+byteCount}`,
                    },
                });
                const buffer = await response.arrayBuffer();
                return buffer
            }
            getData().then(function (data) {
                // const rawData = await subimage.source.fetch([{offset,length: byteCount}]);
                const uintRaw = new Uint8Array(data);
                
                // magic adobe header which forces the jpeg to be interpreted as RGB instead of YCbCr
                // Note that it's likely this is necessary because when vips writes JPEGs at 90 or greater q,
                // it disables subsampling. Maybe they're just not flagging it right? 
                const rawAdobeHeader = hexStringToUint8Array("FFD8FFEE000E41646F626500640000000000");
                var mergedArray = new Uint8Array(rawAdobeHeader.length + uintRaw.length + subimage.fileDirectory.JPEGTables.length -2 - 2 - 2);
                mergedArray.set(rawAdobeHeader);
                // first two bytes of the quant tables have start of image token which we have to strip, and last two bytes are image end which we gotta strip too
                mergedArray.set(subimage.fileDirectory.JPEGTables.slice(2, -2), rawAdobeHeader.length);
                mergedArray.set(uintRaw.slice(2), rawAdobeHeader.length + subimage.fileDirectory.JPEGTables.length-2 - 2);
                url =URL.createObjectURL(new Blob([mergedArray], {type:"image/jpeg"})); 
                tile.src = url;
            });
            // return tile;
        }

    <? else : ?>
        var loadIndex = async function() {}
        var tileLoadFunction = function(coords, tile, done) {
            var params = {
                Bucket: '<?= $fileObject->collection->getBucket() ?>',
                Key: "derivative/<?= $fileContainers['tiled']->getCompositeName() ?>/tiledBase_files/" + coords.z + "/" + coords.x + "_" + coords.y + ".jpeg"
            };
            var url = s3.getSignedUrl('getObject', params)
            tile.src = url;
            return tile;
        }
    <? endif ?>
</script>