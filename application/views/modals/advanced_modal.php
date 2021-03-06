<?=$this->template->javascript->content()?>

<div class="modal-header">
  <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
  <h4 class="modal-title" id="advancedSearchModalLabel">Advanced Search</h4>
</div>
<form action="" id="advancedSearchForm" method="POST" class="form-horizontal searchForm" role="form">
  <div class="modal-body mapContainer">
    <div class="form-group">
      <label for="inputSearchText" class="col-sm-2 control-label">Text:</label>
      <div class="col-sm-6">
        <input type="text" name="searchText" autocomplete="off" id="inputSearchText"
          class="form-control advancedOption advancedSearchText" value="">
      </div>
      <div class="col-sm-4">
        <div class="checkbox">
          <label>
            <input type="checkbox" name="fuzzySearch" value="on">
            Fuzzy Search
          </label>
        </div>
      </div>
    </div>
    <hr>
    <div class="specificSearch">
      <div class="form-group">
        <label for="searchField" class="col-sm-2 control-label">Field:</label>
        <div class="col-sm-4">

          <select name="specificSearchField[]" id="searchField" class="form-control advancedOption searchDropdown">
            <option></option>
            <?foreach($searchableWidgets as $title=>$values):?>
            <option data-templateid="<?=$values['template']?>" value="<?=$title?>"><?=$values['label']?></option>
            <?endforeach?>
          </select>
        </div>
        <div class="col-sm-6 specificSearchTextContainer">
          <input type="text" name="specificSearchText[]" disabled autocomplete="off"
            class="form-control advancedOption advancedSearchContent" value="">
        </div>
      </div>
      <div class="form-group">
        <div class="col-sm-4 col-sm-offset-2">
          <div class="checkbox">
            <label>
              <input type="checkbox" onclick="$(this).next().val(this.checked?'1':'0')" />
              <input type="hidden" name="specificSearchFuzzy[]" value="0" />

              Fuzzy Search
            </label>
          </div>
        </div>
        <div class="col-sm-4">
          <button type="button" class="btn btn-default addAnotherSpecific">Add Another</button>
        </div>

      </div>
      </div>
      <div class="form-group searchCombine hide">
              <label for="combineSpecificSearches" class="col-sm-2 control-label">Combine Using:</label>
                 <div class="col-sm-4">

          <select name="combineSpecificSearches" id="combineSpecificSearches" class="form-control advancedOption">
            <option>OR</option>
            <option>AND</option>
          </select>
        </div>
    </div>
    <div class="fileTypes">
      <div class="form-group">
        <label for="searchField" class="col-sm-2 control-label">File Type:</label>
        <div class="col-sm-4">
          <select name="fileTypesSearch" autocomplete="off" class="form-control advancedOption" value="">
            <option value="">All</option>
            <option value="image">Image</option>
            <option value="movie">Video</option>
            <option value="audio">Audio</option>
            <option value="office">Doc</option>
            <option value="txt">TXT</option>
            <option value="pdf">PDF</option>
            <option value="office">PPT</option>
            <option value="zipobj">3D (obj)</option>
            <option value="ply">3D (ply)</option>
            <option value="zipmeddicom">DICOM</option>
            <option value="zipscorm">SCORM</option>
          </select>

        </div>
      </div>
    </div>
    <hr>
    <div class="form-group">
      <label for="sortBy" class="col-sm-2 control-label">Sort By:</label>
      <div class="col-sm-4">

        <select name="sort" id="sortBy" class="form-control advancedOption">
          <option value="0">Best Match</option>
          <option value="lastModified.desc">Modified Date (newest to oldest)</option>
          <option value="lastModified.asc">Modified Date (oldest to newest)</option>
          <?foreach($searchableWidgets as $title=>$values):?>
          <?if(isset($values['type']) && $values['type'] == "date"):?>
          <option value="dateCache.startDate.desc"><?=$values['label']?> (newest to oldest)</option>
          <option value="dateCache.startDate.asc"><?=$values['label']?> (oldest to newest)</option>
          <?else:?>
          <option value="<?=$title?>.raw"><?=$values['label']?></option>
          <?endif?>
          <?endforeach?>
        </select>
      </div>
    </div>
    <hr>




    <div class="form-group">
      <label for="distance" class="col-sm-2 control-label">Within:</label>
      <div class="col-sm-4">
        <input type="text" name="distance" autocomplete="off" id="distance" class="form-control input-sm advancedOption"
          placeholder="Distance" value="100">
      </div>
      <div class="col-sm2">
        miles
      </div>
    </div>
    <div class="form-group">
      <label for="latitude" class="col-sm-2 control-label">Of:</label>
      <div class="col-sm-3">
        <input type="text" name="latitude" autocomplete="off" id="latitude"
          class="latitude form-control input-sm advancedOption" value="" placeholder="Latitude">
      </div>
      <div class="col-sm-3">
        <input type="text" name="longitude" autocomplete="off" id="longitude"
          class="longitude form-control input-sm advancedOption" value="" placeholder="Longitude">
      </div>
      <div class-"col-sm-3">
        <button type="button" class="btn btn-info mapToggle" data-toggle="collapse"
          data-target="#advancedSearchMap">Show Map</button>
      </div>
    </div>
    <?=$this->load->view("mapSelector", ["mapId"=>"advancedSearchMap"], true)?>
    <hr>
    <div class="form-group">
      <label for="inputStartDate" class="col-sm-2 control-label">Between:</label>
      <div class="col-sm-4">
        <input type="text" autocomplete="off" name="startDateText" id="startDateText" placeholder="Start Date"
          class="form-control dateText advancedOption" value="">
        <input type="hidden" name="startDate" id="startDate" placeholder="Start Date"
          class="form-control dateHidden advancedOption" value="">
      </div>
      <div class="col-sm-1">
        and
      </div>
      <div class="col-sm-4">
        <input type="text" autocomplete="off" name="endDateText" id="endDateText" placeholder="End Date"
          class="form-control dateText advancedOption" value="">
        <input type="hidden" name="endDate" id="endDate" placeholder="End Date"
          class="form-control dateHidden advancedOption" value="">
      </div>
    </div>
    <hr>

    <div class="form-group">
      <label for="collection" class="col-sm-2 control-label">Search Collection:</label>
      <div class="col-md-6">

        <select name="collection[]" id="collection" class="form-control">
          <option value="0">All</option>
          <?=$this->load->view("collection_select_partial", ["selectCollection"=>NULL, "allowedCollections"=>$allowedCollections, "collections"=>$this->instance->getCollectionsWithoutParent()], true)?>
        </select>
      </div>
      <div class="col-sm-4">
        <button type="button" class="btn btn-default addAnotherCollection">Add Another</button>
      </div>
    </div>

    <?if($this->instance->getShowTemplateInSearchResults()):?>
     <div class="form-group">
      <label for="templateId" class="col-sm-2 control-label">Search Template:</label>
      <div class="col-md-6">

        <select name="templateId[]" id="templateId" class="form-control">
          <option value="0">All</option>
           <?foreach($this->instance->getTemplates() as $template):?>
              <?if(!$template->getIsHidden()):?>
              <option value=<?=$template->getId()?>><?=$template->getName()?></option>
              <?endif?>
            <?endforeach?>
        </select>
      </div>
      <div class="col-sm-4">
        <button type="button" class="btn btn-default addAnotherTemplate">Add Another</button>
      </div>
    </div>
    <?endif?>

    <?if($this->user_model->userLoaded && $this->user_model->getIsSuperAdmin() || $this->user_model->getAccessLevel("instance",$this->instance)>=PERM_ADDASSETS):?>
    <hr>
    <div class="checkbox">
      <label>
        <input type="checkbox" id="showHidden" name="showHidden" value="on">
        Show Hidden Assets
      </label>
    </div>

    <?endif?>

  </div>
  <div class="modal-footer">
    <button type="submit" class="btn btn-primary">Search</button>
  </div>

</form>