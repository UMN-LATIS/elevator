<div class="row">

	<form action="<?=instance_url("assetManager/processCSV")?>" class="form-horizontal" method="POST" role="form" >
		<legend>Importing CSV: <?=$filename?></legend>
		<input type="hidden" name="filename" value="<?=$filename?>">
		<input type="hidden" name="templateId" value="<?=$template->getId()?>">
		<div class="form-group">
			<div class="col-sm-6">
				Target Collection:
			</div>
			<div class="col-sm-6">
				<select name="collectionId" id="newCollectionId" class="form-control input-large">
					<option val=-1>---</option>
					<?=$this->load->view("collection_select_partial", ["selectCollection"=>0, "collections"=>$this->instance->getCollectionsWithoutParent(), "allowedCollections"=>$this->user_model->getAllowedCollections(PERM_ADDASSETS)],true);?>
				</select>
			</div>
		</div>
		<div class="assetCompleter">
		<div class="form-group">
			<label for="inputParent Element" class="col-sm-2 control-label">Parent Object:</label>
			<div class="col-sm-10">
				<input type="text" name="parentObject" id="inputParentObject" class="relatedAssetSelectedItem form-control tryAutocompleteAsset" value="">
			</div>
		</div>
		</div>
		<script>
		$(document).ready(function() {
			buildAssetAutocomplete($(".assetCompleter"));
			$(".assetCompleter").find(".tryAutocompleteAsset").on("change", function() {
				if($(this).val().length == 24) {

					$(".targetFields").removeClass('hide');					

					$.getJSON(basePath + "asset/viewAsset/" + $(this).val() + "/true", {}, function(json, textStatus) {
						if(json.templateId !== 'undefined') {
							$.getJSON(basePath + "search/getFields/" + json.templateId, {}, function(json, textStatus) {
								$(".targetFieldSelect").find('option').remove();
								for(var key in json) {
									$(".targetFieldSelect").append('<option selected value="' + key +'">' + json[key] + '</option>')
								}
							});
						}
					});
				}

			});
		});
		</script>
		<div class="targetFields form-group hide">
			<label for="inputTargetField" class="col-sm-2 control-label">Related Field:</label>
			<div class="col-sm-2">
				<select name="targetFieldSelect" id="inputTargetField" class="form-control targetFieldSelect">
					<option value=""></option>
				</select>
			</div>
		</div>
		<hr>
		<?foreach($headerRows as $row):?>
		<div class="form-group">
			<div class="col-sm-4">
				<?=$row?>
			</div>
			<div class="col-sm-4">
				<select name="targetField[]" class="form-control">
					<option value="ignore">Don't import</option>
					<option value="objectId">Object ID (for updates)</option>
					<?foreach($template->widgetArray as $widget):?>
					<? $selected = ($widget->getLabel()==$row)?"selected":null; ?>
					<option value="<?=$widget->getFieldTitle()?>" <?=$selected?>><?=$widget->getLabel()?></option>
					<?endforeach?>
				</select>
			</div>
			<div class="col-sm-2">
				<input name="delimiter[]" cass="form-control" placeholder="delimiter">
			</div>
		</div>
		<?endforeach?>

		<button type="submit" class="btn btn-primary">Import</button>
	</form>


	
</div>
<?$this->load->view("handlebarsTemplates");