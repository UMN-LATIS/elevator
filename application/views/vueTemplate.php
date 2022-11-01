<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <link rel="icon" href="/favicon.ico" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Work+Sans:ital,wght@0,300;0,400;0,700;1,300;1,400;1,700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />

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
          apiKey: "<?=$this->config->item('arcgis_access_token')?>",
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