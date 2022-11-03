<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <link rel="icon" href="/favicon.ico" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>
    <?php
    $this->template->title->default(isset($this->instance)
      ? $this->instance->getName()
      : "Elevator");
    ?>
  </title>

  <script>
    window.Elevator = {
      config: {
        base: {
          origin: window.location.origin,
          path: <?= '"' . rtrim($this->template->relativePath, '/') . '"' ?>,
          get url() {
            return `${this.origin}${this.path}`;
          },
        },
        arcgis: {
          apiKey: "<?= $this->config->item('arcgis_access_token') ?>",
        },
      },
    }
  </script>

  <script type="module" crossorigin src="/assets/elevator-ui/dist/assets/index.js"></script>
  <link rel="stylesheet" href="/assets/elevator-ui/dist/assets/index.css">
</head>

<body>
  <div id="app"></div>

</body>

</html>