<?if($widgetModel->offsetCount==0):?>
<?=$this->load->view("widget_form_partials/widget_header", array("widgetModel"=>$widgetModel), true)?>
<?endif?>
<?for($i=0; $i<$widgetModel->drawCount; $i++):?>

<?
	$formFieldName = $widgetModel->getFieldTitle() . "[" . ($widgetModel->offsetCount + $i) . "]";
	$formFieldId = $widgetModel->getFieldTitle() . "_" . ($widgetModel->offsetCount + $i) . "";
	$formFieldMapId = $widgetModel->getFieldTitle() . "_" . ($widgetModel->offsetCount + $i) . "_map";
	$labelText = $widgetModel->getLabel();
	$toolTip = $widgetModel->getToolTip();
	$primaryGlobal = $widgetModel->getFieldTitle() . "[isPrimary]";
	$primaryId = $formFieldId . "_isPrimary";


	$latitudeName = $formFieldName . "[latitude]";
	$latitudeId = $formFieldId . "_latitude";
	$longitudeName = $formFieldName . "[longitude]";
	$longitudeId = $formFieldId . "_longitude";
	$labelName = $formFieldName . "[locationLabel]";
	$labelId = $formFieldId . "_locationLabel";
	$addressName = $formFieldName . "[address]";
	$addressId = $formFieldId . "_address";

	$autocomplete = null;
	if($widgetModel->getAttemptAutocomplete()) {
		$autocomplete = "tryAutocomplete";
	}
	$required = null;
	if($widgetModel->getRequired()) {
		$required = 'required="required"';
	}


	$isPrimaryValue = "";
	$addressContents = "";
	if($widgetModel->fieldContentsArray) {
		if($widgetModel->fieldContentsArray[$i]->isPrimary == "on") {
			$isPrimaryValue = " CHECKED ";
		}
		$latitudeContents = $widgetModel->fieldContentsArray[$i]->latitude;
		$longitudeContents = $widgetModel->fieldContentsArray[$i]->longitude;
		$labelContents = $widgetModel->fieldContentsArray[$i]->locationLabel;
		if($widgetModel->fieldContentsArray[$i]->address) {
			$addressContents = $widgetModel->fieldContentsArray[$i]->address;
		}
	}
	else {
		$latitudeContents = "";
		$longitudeContents = "";
		$labelContents = "";
	}


	if($widgetModel->drawCount == 1 && $widgetModel->offsetCount == 0) {
		$isPrimaryValue = "CHECKED";
	}

?>



	<div class="panel panel-default widgetContentsContainer">

		<div class="panel-body widgetContents mapContainer">
			<div class="form-group">
				<label for="<?=$latitudeId?>" class="col-sm-2 control-label <?=$autocomplete?>">Latitude</label>
				<div class="col-sm-4">
					<input type="text"  <?=$required?> autocomplete="off"  class="mainWidgetEntry form-control latitude geoField" id="<?=$latitudeId?>" name="<?=$latitudeName?>" placeholder="Latitude" value="<?=$latitudeContents?>">
				</div>
			</div>

			<div class="form-group">
				<label for="<?=$longitudeId?>" class="col-sm-2 control-label <?=$autocomplete?>">Longitude</label>
				<div class="col-sm-4">
					<input type="text"  <?=$required?> autocomplete="off"  class="form-control longitude geoField" id="<?=$longitudeId?>" name="<?=$longitudeName?>" placeholder="Longitude" value="<?=$longitudeContents?>">
				</div>
			</div>

			<div class="form-group">
				<label for="<?=$labelId?>" class="col-sm-2 control-label  <?=$autocomplete?>">Label</label>
				<div class="col-sm-4">
					<input type="text"  autocomplete="off"  class="form-control mainWidgetEntry" id="<?=$labelId?>" name="<?=$labelName?>" placeholder="Label" value="<?=$labelContents?>">
				</div>
			</div>



			<?=$this->load->view("mapSelector", ["mapId"=>$formFieldMapId, "addressName"=>$addressName, "addressContents"=>$addressContents,"collapse"=>""], true)?>

			<?if($widgetModel->getAllowMultiple()):?>
  			<div class="form-group isPrimary">
    			<div class="col-sm-offset-2 col-sm-10">
					<div class="checkbox">
						<label>
							<input id="<?=$primaryId?>" value=<?=$widgetModel->offsetCount + $i?> name="<?=$primaryGlobal?>" type="radio" <?=$isPrimaryValue?>>
							Primary Entry
						</label>
					</div>
				</div>
			</div>

			<?endif?>
		</div>
	</div>

<script>

$(document).ready(function() {
	mapElement = $("#<?=$formFieldMapId?>").find(".mapWidget");
	latitudeElement= $("#<?=$latitudeId?>");
	longitudeElement = $("#<?=$longitudeId?>");
	
	revealMap(mapElement[0],latitudeElement, longitudeElement);

});
</script>

<?endfor?>
<?if($widgetModel->offsetCount==0):?>
<?=$this->load->view("widget_form_partials/widget_footer", array("widgetModel"=>$widgetModel), true)?>
<?endif?>