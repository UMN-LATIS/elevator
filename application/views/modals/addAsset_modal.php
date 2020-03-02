
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title" id="addAssetModalLabel">Add Asset</h4>
      </div>
      <form action="<?=instance_url("assetManager/addAsset")?>" method="POST" class="form-horizontal" role="form">
      <div class="modal-body mapContainer">



          <div class="form-group">
            <label for="inputTemplateId" class="col-sm-2 control-label">Template:</label>
            <div class="col-sm-5">
              <select name="templateId" id="inputTemplateId" class="form-control" required="required">
                <?foreach($this->instance->getTemplates() as $template):?>
                  <?if(!$template->getIsHidden()):?>
                  <option value=<?=$template->getId()?>><?=$template->getName()?></option>
                  <?endif?>
                <?endforeach?>
              </select>
            </div>
          </div>


      </div>
      <div class="modal-footer">

          <button type="submit" class="btn btn-primary">Add Asset</button>
        </div>
        </form>


