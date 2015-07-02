<!DOCTYPE html>
<html>
<head>
  <title><?= $this->template->title->default("Media Elevator"); ?></title>
  <meta charset="utf-8">

  <?=$this->template->meta; ?>
  <?=$this->template->stylesheet; ?>
  <?if(isset($this->instance) && $this->instance->getUseCustomCSS()):?>
  <link rel="stylesheet" href="/assets/instanceAssets/<?=$this->instance->getId()?>.css">
    <?else:?>
    <link rel="stylesheet" href="/assets/css/screen.css">
  <?endif?>
  <?=$this->template->javascript; ?>

  <script>
  var basePath = "<?=$this->template->relativePath?>";
  </script>

  <!-- Bootstrap CSS -->
</head>


<body>
  <style>
.input-group-btn>.btn+.btn {
  margin-left: -5px;
}

.dropdown-menu {
  left:-75px;
}

  </style>

  <div class="container">

    <?php
    // This is the main content partial
    echo $this->template->content;
    ?>

    <hr>
  </div>
</body>
</html>
