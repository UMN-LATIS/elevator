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
            enabled: <?= $this->instance->getEnableThemes()?"true":"false" ?>,
            defaultTheme: "<?= $this->instance->getDefaultTheme() ?>",
          },
        },
        arcgis: {
          apiKey: "<?= $this->config->item('arcgis_access_token') ?>",
        },
        moreLikeThis: {
          maxInlineResults: <?= $this->instance->getMaximumMoreLikeThis() ?>,
        }
      },
    }
  </script>

  <script type="module" crossorigin src="/assets/elevator-ui/dist/assets/index.js?<?=$this->template->currentHash?>"></script>
  <link rel="stylesheet" href="/assets/elevator-ui/dist/assets/index.css?<?=$this->template->currentHash?>">
</head>

<body>
  <div id="app"></div>

</body>

</html>