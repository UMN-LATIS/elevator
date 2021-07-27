<?php $this->load->view('templates/_template_js'); ?>
<?php $this->load->view('templates/_form_fields_hb'); ?>

<input type="hidden" name="templateId" id="inputTemplateId" class="form-control" value="<?= $template->getId(); ?>">
<input  name="needsRebuild" id="needsRebuildId" class="advancedContent form-control" value="0">

<div class="form-group">
	<label for="inputName" class="col-lg-2 control-label">Name:</label>
	<div class="col-lg-6">
		<input type="text" name="name" id="inputName" class="form-control" value="<?= $template->getName(); ?>" required="required" >
	</div>
	<div class="col-lg-2">
		<button class="btn btn-primary advancedSettings">Show Advanced Settings</button>
	</div>
</div>



<fieldset class="fieldsetSection hide advancedSettingsDialog">

	<legend>Advanced Settings</legend>
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


	<div class="form-group">
		<label for="inputIndexingDepth" class="col-sm-2 control-label">Indexing Depth:</label>
		<div class="col-sm-2">
			<select name="recursiveIndexDepth" id="recursiveIndexDepth" class="form-control" required="required">
				<option value="0" <?=$template->getRecursiveIndexDepth()==0?"SELECTED":null?>>0</option>
				<option value="1" <?=$template->getRecursiveIndexDepth()==1?"SELECTED":null?>>1</option>
				<option value="2" <?=$template->getRecursiveIndexDepth()==2?"SELECTED":null?>>2</option>
			</select>
		</div>
	</div>

	<div class="form-group">
		<label for="inputIndexingDepth" class="col-sm-2 control-label">Color:</label>
		<div class="col-sm-2">
			<select name="templateColor" id="templateColor" class="form-control">
				<option value="0" <?=$template->getTemplateColor()==0?"SELECTED":null?>>0</option>
				<option value="1" <?=$template->getTemplateColor()==1?"SELECTED":null?>>1</option>
				<option value="2" <?=$template->getTemplateColor()==2?"SELECTED":null?>>2</option>
				<option value="3" <?=$template->getTemplateColor()==3?"SELECTED":null?>>3</option>
				<option value="4" <?=$template->getTemplateColor()==4?"SELECTED":null?>>4</option>
				<option value="5" <?=$template->getTemplateColor()==5?"SELECTED":null?>>5</option>
				<option value="6" <?=$template->getTemplateColor()==6?"SELECTED":null?>>6</option>
				<option value="7" <?=$template->getTemplateColor()==7?"SELECTED":null?>>7</option>
				<option value="7" <?=$template->getTemplateColor()==7?"SELECTED":null?>>7</option>
				<option value="8" <?=$template->getTemplateColor()==8?"SELECTED":null?>>8</option>
			</select>
		</div>
	</div>

</fieldset>




<!-- this gets filled with the widgets from handlebars -->
<div id='widgetList'>
</div>

<br>
<button id='newWidgetButton' type="button" class="btn btn-info">Add a new widget</button>

<br><br>
<button type="submit" class="btn btn-primary">Submit</button>
