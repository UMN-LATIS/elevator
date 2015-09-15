        <script>
            var url = "<?=instance_url("admin/beanstalk/")?>";
            var contentType = "<?php echo isset($contentType) ? $contentType : '' ?>";
        </script>

        <?php if (!empty($errors)): ?>
            <?php foreach ($errors as $item): ?>
                <p class="alert alert-error"><span class="label label-important">Error</span> <?php echo $item ?></p>
            <?php endforeach; ?>
        <?php else: ?>
            <?php if (!$server): ?>
                <?$this->load->view("beanstalk/serversList")?>
            <?php elseif (!$tube): ?>
                <div id="idAllTubes">
                    <?$this->load->view("beanstalk/allTubes", ["console"=>$console])?>
                </div>
                <div id="idAllTubesCopy" style="display:none"></div>
            <?php elseif (!in_array($tube, $tubes)): ?>
                <?php echo sprintf('Tube "%s" not found or it is empty', $tube) ?>
                <br><br><a href="./?server=<?php echo $server ?>"> back </a>
            <?php else: ?>
                <?$this->load->view("beanstalk/currentTube")?>
            <?php endif; ?>
            <?$this->load->view("beanstalk/modalAddJob", ["tube"=>$tube])?>

        <?php endif; ?>

