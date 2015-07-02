<?php $this->load->view('templates/_template_js'); ?>
<?php $this->load->view('templates/_form_fields_hb'); ?>

<input type="hidden" name="templateId" id="inputTemplateId" class="form-control" value="<?= $template->getId(); ?>">

<div class="form-group">
	<label for="inputName" class="col-lg-2 control-label">Name:</label>
	<div class="col-lg-10">
		<input type="text" name="name" id="inputName" class="form-control" value="<?= $template->getName(); ?>" required="required" >
	</div>
</div>
<div class="checkbox">
	<label>
		<input type="checkbox" value="On" name="includeInSearch" <?=$template->getIncludeInSearch()?"checked":null?>>
		Include In Public Search Results
	</label>
</div>
<div class="checkbox">
	<label>
		<input type="checkbox" value="On" name="indexforSearching" <?=$template->getIndexForSearching()?"checked":null?>>
		Index For Searching
	</label>
</div>
<div class="checkbox">
	<label>
		<input type="checkbox" value="On" name="isHidden" <?=$template->getIsHidden()?"checked":null?>>
		Hide from "Add" menu
	</label>
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
