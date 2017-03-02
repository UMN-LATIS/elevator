<div class="modal fade" id="drawerModal" tabindex="-1" role="dialog" aria-labelledby="drawerModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title" id="drawerModalLabel">Add to Drawer</h4>
      </div>
      <div class="modal-body">
        <?if(!$this->user_model->userLoaded):?>
          <p>You must log in to work with drawers</p>
        <?else:?>

        <form action="<?=instance_url("drawers/addToDrawer")?>" method="POST" id="addToDrawer" class="form-inline" role="form">

          <div class="form-group">
            <label class="sr-only" for="">Drawer:</label>
            <select name="drawerList" id="drawerList" class="form-control">
              <?if($this->user_model->userLoaded): foreach($this->user_model->getDrawers(true) as $drawer):?>
                <option value="<?=$drawer->getId()?>"><?=$drawer->getTitle()?></option>
              <?endforeach; endif;?>
            </select>
          </div>
          <button type="submit" class="btn btn-primary">Add to Drawer</button>
        </form>

        <hr>
        <h5>Create a New Drawer</h5>
        <form action="<?=instance_url("drawers/addDrawer")?>" method="POST" id="addNewDrawer" class="form-inline" role="form">
          <div class="form-group">
            <label class="sr-only" for="">Drawer Title:</label>
            <input type="text" name="drawerTitle" class="form-control" id="" placeholder="Add a Drawer">
          </div>
          <button type="submit" class="btn btn-primary">Create Drawer</button>
          <div class="drawerAddedSuccess hiddenElement alert alert-success " role="alert">
              <a href="#" class="alert-link">Drawer Created</a>
            </div>
        </form>
        <?endif?>
      </div>
      <div class="modal-footer">
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->