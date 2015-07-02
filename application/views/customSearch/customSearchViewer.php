

<div class="row">
	<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">

		<form action="<?=instance_url("search/searchResults")?>" method="POST" class="form-horizontal" id="customSearchForm" role="form">
			<input type=hidden id="searchId" name="searchId" value="<?=$searchId?>">
			<input type=hidden id="searchText" name="searchText" value="">
			<script>
			var presetInfo = <?=$searchData?>;
			</script>

			<div class="form-group titleGroup">

				<label for="inputSearchTitle" class="col-sm-2 control-label">Search Title:</label>
				<div class="col-sm-5">
					<input type="text" name="searchTitle" id="inputSearchTitle" class="form-control" value="<?=$searchTitle?>" pattern="" title="">
				</div>
			</div>
			<div class="form-group">

			<label for="inputTemplateId" class="col-sm-2 control-label">Template:</label>
				<div class="col-sm-4">
					<select name="templateId" id="inputTemplateId" class="form-control">
						<option value="0">All</option>
						<?foreach($this->instance->getTemplates() as $template):?>
						<option value="<?=$template->getId()?>" <?=($targetTemplate==$template->getId())?"selected":null?>><?=$template->getName()?></option>
						<?endforeach?>
					</select>
				</div>
			</div>


			<div class="form-group addButtonGroup">
				<div class="col-sm-3">
					<button type="button" class="btn btn-default addGroup">Add Group</button>
				</div>
			</div>
			<div class="form-group">
				<div class="col-xs-3 col-sm-3 col-md-3 col-lg-3">
					<div class="checkbox">
						<label>
							<input type="checkbox" id="showHidden" name="showHidden" value="on" <?=$showHidden?>>
							Show Hidden Assets
						</label>
					</div>

				</div>

			</div>


			<div class="form-group">
				<div class="col-sm-3">
					<button type="button" class="btn btn-primary" id="customSaveButton">Save</button>
					<button type="button" class="btn btn-primary" id="searchButton">Search</button>
					<a href="<?=instance_url("search/searchList")?>" class="btn btn-primary" id="searchButton">Back to List</a>
				</div>
				<div class="col-sm-3">

				</div>
			</div>

		</form>


	</div>
</div>


<script id="search-entry" type="text/x-handlebars-template">


<div class="form-group searchGroup">
<div class="col-sm-3">
<select name="specificTemplateId[]" id="inputSpecificTemplateId" class="form-control templateSelector" required="required">
{{#each templates}}
<option value={{@key}}>{{this}}</option>
{{/each}}
</select>
</div>
<div class="col-sm-3">
<select name="specificSearchField[]" id="inputSpecificSearch" class="form-control searchField" required="required">
</select>
</div>
<div class="col-sm-4 specificSearchTextContainer">
<input type="text" name="specificSearchText[]" disabled autocomplete="off" class="form-control advancedOption advancedSearchContent" value="" placeholder="Search Text">
</div>
<div class="col-sm-2">
<div class="checkbox">
<label>
<input type="checkbox" onclick="$(this).next().val(this.checked?'1':'0')"/>
<input type="hidden" name="specificSearchFuzzy[]" value="0"/>

Fuzzy Search
</label>
</div>

</div>
</div>
</script>