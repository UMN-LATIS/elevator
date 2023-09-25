<?php
$path_to_manifest = __DIR__ . '/../../assets/elevator-ui/dist/manifest.json';

if (!file_exists($path_to_manifest)|| !is_readable($path_to_manifest)) {
    throw new Exception("Cannot read JS Manifest: $path_to_manifest");
}

$manifest = json_decode(file_get_contents($path_to_manifest), true);
if (json_last_error() !== JSON_ERROR_NONE) {
    throw new Exception("Failed to decode JSON from JS manifest:" . json_last_error_msg());
}

$indexFile = $manifest['src/main.ts']['file'];
$cssFile = $manifest['src/main.css']['file'];

$customCSSFile = "/assets/instanceAssets/{$this->instance->getId()}.css";
$customCSSHash = $this->instance->getModifiedAt()->getTimestamp();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <link rel="icon" href="/favicon.ico" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>
    <?=
    $this->template->title->default(isset($this->instance)
      ? $this->instance->getName()
      : "Elevator");
    ?>
  </title>

  <script>
    window.Elevator = {
      config: {
        instance: {
          name: "<?=
                  $this->template->title->default(isset($this->instance)
                    ? $this->instance->getName()
                    : 'Elevator');
                  ?>",
          base: {
            origin: window.location.origin,
            path: "<?= rtrim($this->template->relativePath, '/') ?>",
            get url() {
              return `${this.origin}${this.path}`;
            },
          },
          theming: {
            availableThemes: <?= json_encode($this->instance->getAvailableThemes()) ?>,
            enabled: <?= $this->instance->getEnableThemes() ? "true" : "false" ?>,
            defaultTheme: "<?= $this->instance->getDefaultTheme() ?>",
          },
          moreLikeThis: {
            maxInlineResults: <?= $this->instance->getMaximumMoreLikeThis() ?>,
          },
          textAreaItem: {
            defaultTextTruncationHeight: <?=
              $this->instance->getDefaultTextTruncationHeight() 
            ?>,
          },
        },
        arcgis: {
          apiKey: "<?= $this->config->item('arcgis_access_token') ?>",
        },
      },
    }
  </script>

  <link rel="stylesheet" href="/assets/elevator-ui/dist/<?= $cssFile ?>">
  <?php if (isset($this->instance) && $this->instance->getUseCustomCSS()): ?>
    <link rel="stylesheet" href="<?= $customCSSFile ?>?hash=<?= $customCSSHash ?>">
  <?php endif ?>
  <script type="module" crossorigin src="/assets/elevator-ui/dist/<?= $indexFile ?>"></script>
</head>

<body>
  <div id="app"></div>
</body>

</html>