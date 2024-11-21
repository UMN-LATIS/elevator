<!doctype html>
<html lang="en" style="width: 100%; height: 100%">
  <head>
    <title><?= $this->template->title->default(isset($this->instance)?$this->instance->getName():"Elevator"); ?></title>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

  </head>
  <body style="width: 100%; height: 100%">

  <?=$this->template->meta; ?>


    <?php
    // This is the main content partial
    echo $this->template->content;
    ?>
</body>
</html>