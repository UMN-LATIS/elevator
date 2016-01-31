
<!-- Load the handlebar template -->
<script id="entry-template" type="text/x-handlebars-template">
<div class="row widgetItem" id="widget{{id}}">
	<div class="col-md-6">

<div class="form-group ">
			<label for="inputWidget[{{id}}][Label]" class="col-lg-4 control-label">Field label</label>
			<div class="col-lg-8">
				<input type="text" name="widget[{{id}}][label]" id="inputWidget[{{id}}][label]" class="form-control fieldTitle" value="{{label}}">
			</div>
		</div>
	<div class="form-group advancedContent">
		<label for="inputWidget[{{id}}][FieldTitle]" class="col-lg-4 control-label">Internal title</label>
			<div class="col-lg-8">
				<input type="text" name="widget[{{id}}][fieldTitle]" data-lockfieldlabel="{{lockFieldLabel}}" id="inputWidget[{{id}}][FieldTitle]" class="form-control internalTitle" value="{{fieldTitle}}" >
			</div>
		</div>

		<div class="form-group">
			<label for="inputWidget[{{id}}][FieldType]" class="col-lg-4 control-label">Field type</label>
			<div class="col-sm-4">
				<select name="widget[{{id}}][fieldType]" id="inputWidget[{{id}}][FieldType]" class="form-control fieldType">
					{{#each fieldTypes}}
						{{fieldTypeOption}}
					{{/each}}
				</select>
			</div>
		</div>


		<div class="form-group ">
			<label for="inputWidget[{{id}}][tooltip]" class="col-lg-4 control-label">Tooltip</label>
			<div class="col-lg-8">
				<input type="text" name="widget[{{id}}][tooltip]" id="inputWidget[{{id}}][tooltip]" class="form-control" value="{{tooltip}}">
			</div>
		</div>

		<div class="form-group fieldDataGroup">
			<label for="inputWidget[{{id}}][fieldData]" class="col-lg-4 control-label">Field data</label>
			<div class="col-lg-8">
				<textarea rows="5"  name="widget[{{id}}][fieldData]" id="inputWidget[{{id}}][fieldData]" class="form-control fieldData">{{fieldData}}</textarea>
			</div>
		</div>

		<input type="hidden" name="widget[{{id}}][templateOrder]" id="inputWidget[{{id}}][templateOrder]" class="form-control" value="{{templateOrder}}" >
		<input type="hidden" name="widget[{{id}}][viewOrder]" id="inputWidget[{{id}}][viewOrder]" class="form-control" value="{{viewOrder}}">


		<div class="form-group">
			<div class="col-sm-10 col-offset-2">
				<button type="button" class="btn btn-danger deleteWidgetButton">Delete Widget</button>
			</div>
		</div>

	</div>
	<div class="col-md-6">
	<div class="form-group row">
			<div class="col-sm-offset-2 col-sm-8">
				<div class="checkbox">
					<label>
						<input type="checkbox" class="displayWidget" name="widget[{{id}}][display]" id="inputWidget[{{id}}][Display]" {{displayYes}}>
						Display
					</label>
				</div>
			</div>
		</div>

		<div class="form-group ">
			<div class="col-sm-offset-2 col-sm-8">
				<div class="checkbox">
					<label>
						<input type="checkbox" name="widget[{{id}}][required]" id="inputWidget[{{id}}][required]" {{requiredYes}}>
						Required
					</label>
				</div>
			</div>
		</div>

		<div class="form-group ">
			<div class="col-sm-offset-2 col-sm-8">
				<div class="checkbox">
					<label>
						<input type="checkbox" name="widget[{{id}}][searchable]" id="inputWidget[{{id}}][searchable]" {{searchableYes}}>
						Searchable
					</label>
				</div>
			</div>
		</div>

		<div class="form-group ">
			<div class="col-sm-offset-2 col-sm-8">
				<div class="checkbox">
					<label>
						<input type="checkbox" name="widget[{{id}}][attemptAutocomplete]" id="inputWidget[{{id}}][attemptAutocomplete]" {{attemptAutocompleteYes}}>
						Attempt Autocomplete
					</label>
				</div>
			</div>
		</div>

		<div class="form-group ">
			<div class="col-sm-offset-2 col-sm-8">
				<div class="checkbox">
					<label>
						<input type="checkbox" class="displayPreviewWidget" name="widget[{{id}}][displayInPreview]" id="inputWidget[{{id}}][displayInPreview]" {{displayInPreviewYes}}>
						Display in preview
					</label>
				</div>
			</div>
		</div>

		<div class="form-group ">
			<div class="col-sm-offset-2 col-sm-8">
				<div class="checkbox">
					<label>
						<input type="checkbox" name="widget[{{id}}][allowMultiple]" id="inputWidget[{{id}}][allowMultiple]" {{allowMultipleYes}}>
						Allow multiple
					</label>
				</div>
			</div>
		</div>

		<div class="form-group ">
			<div class="col-sm-offset-2 col-sm-8">
				<div class="checkbox">
					<label>
						<input type="checkbox" name="widget[{{id}}][directSearch]" id="inputWidget[{{id}}][directSearch]" {{directSearchYes}}>
						Directly Searchable (advanced search)
					</label>
				</div>
			</div>
		</div>

		<div class="form-group ">
			<div class="col-sm-offset-2 col-sm-8">
				<div class="checkbox">
					<label>
						<input type="checkbox" name="widget[{{id}}][clickToSearch]" id="inputWidget[{{id}}][clickToSearch]" {{clickToSearchYes}}>
						Click To Search
					</label>
				</div>
			</div>
		</div>


		</div>
</div>
</script>
