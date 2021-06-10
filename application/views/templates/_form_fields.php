<?php $this->load->view('templates/_template_js'); ?>
<?php $this->load->view('templates/_form_fields_hb'); ?>

<input type="hidden" name="templateId" id="inputTemplateId" class="form-control" value="<?= $template->getId(); ?>">
<input  name="needsRebuild" id="needsRebuildId" class="advancedContent form-control" value="0">

<div class="form-group">
	<label for="inputName" class="col-lg-2 control-label">Name:</label>
	<div class="col-lg-10">
		<input type="text" name="name" id="inputName" class="form-control" value="<?= $template->getName(); ?>" required="required" >
	</div>
</div>
<div class="checkbox">
	<label>
		<input type="checkbox" value="On" id="includeInSearch" name="includeInSearch" <?=$template->getIncludeInSearch()?"checked":null?>>
		Include In Public Search Results
	</label>
</div>
<div class="checkbox">
	<label>
		<input type="checkbox" value="On" id="indexforSearching" name="indexforSearching" <?=$template->getIndexForSearching()?"checked":null?>>
		Index For Searching
	</label>
</div>
<div class="checkbox">
	<label>
		<input type="checkbox" value="On" name="isHidden" <?=$template->getIsHidden()?"checked":null?>>
		Hide from "Add" menu
	</label>
</div>
<div class="checkbox">
	<label>
		<input type="checkbox" value="On" name="showCollection" <?=$template->getShowCollection()?"checked":null?>>
		Show Collection when viewing asset
	</label>
</div>

<div class="form-group hide collectionPosition">
	<label for="collectionPos" class="col-sm-2 control-label">Collection Position:</label>
	<div class="col-sm-2">
		<select name="collectionPosition" id="collectionPos" class="form-control" required="required">
			<option value="0" <?=$template->getCollectionPosition()==0?"selected":null?>>Bottom</option>
			<option value="1" <?=$template->getCollectionPosition()==1?"selected":null?>>Top</option>
		</select>
	</div>
</div>

<div class="checkbox">
	<label>
		<input type="checkbox" value="On" id="showTemplate" name="showTemplate" <?=$template->getShowTemplate()?"checked":null?>>
		Show Template when viewing asset
	</label>
</div>

<div class="form-group hide templatePosition">
	<label for="templatePos" class="col-sm-2 control-label">Template Position:</label>
	<div class="col-sm-2">
		<select name="templatePosition" id="templatePos" class="form-control" required="required">
			<option value="0" <?=$template->getTemplatePosition()==0?"selected":null?>>Bottom</option>
			<option value="1" <?=$template->getTemplatePosition()==1?"selected":null?>>Top</option>
		</select>
	</div>
</div>

<div class="color">
	<label>
		<input value="<?=$template->getTemplateColor()?$template->getTemplateColor():0?>" name="templateColor">
		Template color (0-8)
	</label>
</div>



<!-- this gets filled with the widgets from handlebars -->
<div id='widgetList'>
</div>

<br>
<button id='newWidgetButton' type="button" class="btn btn-info">Add a new widget</button>

<br><br>
<button type="submit" class="btn btn-primary">Submit</button>
