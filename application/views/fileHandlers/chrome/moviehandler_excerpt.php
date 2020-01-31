<div class="row rowPadding excerpt collapse out" id="excerptGroup">
  <form action="" method="POST" class="excerptForm" id="excerptForm" role="form">
    <input type="hidden" name="fileHandlerId" value="<?=$fileObject->getObjectId()?>"/>
    <input type="hidden" name="objectId" value="<?=$fileObject->parentObjectId?>"/>
    <div class="form-group col-md-2">
      <label class="sr-only" for="">Excerpt Title</label>
      <input type="text" id="label" name="label" class="form-control" id="" placeholder="Title">
    </div>
    <div class="form-group col-md-3">
      <div class="input-group ">
        <input type="text" name="startTimeVisible" id="startTimeVisible" class="form-control" id="" placeholder="" disabled>
        <input type="hidden" name="startTime" id="startTime" class="form-control" id="" placeholder="">
        <span class="input-group-btn">
          <button type="button" class="btn btn-primary setStart">Start</button>
        </span>
      </div>
    </div>
    <div class="form-group col-md-3">
      <div class="input-group ">
        <input type="text"  name="endTimeVisible" id="endTimeVisible" class="form-control"  id="" placeholder="" disabled>
        <input type="hidden"  name="endTime" id="endTime" class="form-control"  id="" placeholder="">
        <span class="input-group-btn">
          <button type="button"  class="btn btn-primary setEnd">End</button>
        </span>
      </div>
    </div>
    <div class="form-group col-md-2">
      <label class="sr-only" for="">Drawer:</label>
      <select name="drawerList" id="drawerList" class="form-control">
        <?if($this->user_model->userLoaded && is_array($drawerArray)): foreach($drawerArray as $drawer):?>
        <option value="<?=$drawer->getId()?>"><?=$drawer->getTitle()?></option>
        <?endforeach; endif;?>
      </select>
    </div>
    <div class="col-md-2">
      <button type="submit" class="btn btn-primary btn-block">Save</button>
    </div>
  </form>
</div>