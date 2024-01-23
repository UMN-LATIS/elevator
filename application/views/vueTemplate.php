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

// Helper Function to Build JavaScript Config
function makeJavaScriptConfig($instance, $config, $template) {
  $jsConfig = [
      'instance' => [
          'name' => $instance->getName() ?? 'Elevator',
          'base' => [
              'origin' => '', // Will handle in JS
              'path' => rtrim($template->relativePath, '/'),
              'url' => '', // origin + path; Will handle in JS
          ],
          'theming' => [
              'availableThemes' => $instance->getAvailableThemes(),
              'enabled' => $instance->getEnableThemes(),
              'defaultTheme' => $instance->getDefaultTheme(),
          ],
          'moreLikeThis' => [
              'maxInlineResults' => $instance->getMaximumMoreLikeThis(),
          ],
          'textAreaItem' => [
              'defaultTextTruncationHeight' => $instance->getDefaultTextTruncationHeight(),
          ],
      ],
      'arcgis' => [
          'apiKey' => $config->item('arcgis_access_token'),
      ],
  ];

  return json_encode($jsConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_SLASHES);
}

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
    window.Elevator = {};
    window.Elevator.config =  <?= 
      makeJavaScriptConfig($this->instance, $this->config, $this->template) 
    ?>;

    window.Elevator.config.instance.base.origin = window.location.origin;
    window.Elevator.config.instance.base.url = `${window.location.origin}${window.Elevator.config.instance.base.path}`;
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