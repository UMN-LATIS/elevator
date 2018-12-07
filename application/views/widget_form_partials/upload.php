<?if($widgetModel->offsetCount==0):?>
<?=$this->load->view("widget_form_partials/widget_header", array("widgetModel"=>$widgetModel), true)?>
<?endif?>
<?for($i=0; $i<$widgetModel->drawCount; $i++):?>

<?

$formFieldName = $widgetModel->getFieldTitle() . "[" . ($widgetModel->offsetCount + $i) . "]";
$formFieldId = $widgetModel->getFieldTitle() . "_" . ($widgetModel->offsetCount + $i) . "";
$labelText = $widgetModel->getLabel();
$toolTip = $widgetModel->getToolTip();
$primaryGlobal = $widgetModel->getFieldTitle() . "[isPrimary]";
$primaryId = $formFieldId . "_isPrimary";

$fileFieldName = $formFieldName . "[fileField]";
$fileFieldId = $formFieldId . "fileField";
$fileDescriptionName = $formFieldName . "[fileDescription]";
$fileDescriptionId = $formFieldId . "_fileDescription";
$regenerateName = $formFieldName . "[regenerate]";
$regenerateId = $formFieldId . "_regenerate";
$searchDataName = $formFieldName . "[searchData]";
$searchDataId = $formFieldId . "_searchData";

$pathName = $formFieldName . "[path]";
$pathId = $formFieldId . "_path";



$fileIdId = $formFieldId . "_fileId";
$fileIdName = $formFieldName . "[fileId]";
$required = null;
if($widgetModel->getRequired()) {
	$required = 'required="required"';
}
$autocomplete="";
$isPrimaryValue = "";
$downloadLink = null;
$fileType = "";
$fileId = "";
$fileDescriptionContents = "";
$searchData = "";
$fileHandler = NULL;
$sidecars = array();
$sidecarView = null;

$fileReady = false;
$standardURL = null;
$retinaURL = null;

if($widgetModel->fieldContentsArray) {
	if($widgetModel->fieldContentsArray[$i]->isPrimary == "on") {
		$isPrimaryValue = " CHECKED ";
	}
	$fileId = $widgetModel->fieldContentsArray[$i]->fileId;
	$fileType = $widgetModel->fieldContentsArray[$i]->fileType;
	$fileDescriptionContents = $widgetModel->fieldContentsArray[$i]->fileDescription;
	$searchData = $widgetModel->fieldContentsArray[$i]->getSearchData();
	if(isset($widgetModel->fieldContentsArray[$i]->sidecars)) {
		$sidecars = $widgetModel->fieldContentsArray[$i]->sidecars;
	}
	$fileHandler = $widgetModel->fieldContentsArray[$i]->getFileHandler();
	if($fileHandler) {
		$sidecarView = $fileHandler->getSidecarView($sidecars, $formFieldName . "[sidecars]");
		$downloadLink = instance_url("fileManager/getOriginal/". $fileHandler->getObjectId());
		try {


			$fileContainer = $fileHandler->getPreviewThumbnail(false);
			
			if(get_class($fileContainer) == "FileContainer") {

				// we got back a pointer to a local file
				$fileReady = false;
			}
			else {
				$fileReady = true;
				$standardURL = $fileContainer->getURLForFile();
				$retinaURL = $fileHandler->getPreviewThumbnail(true)->getURLForFile();
			}
		}
		catch (Exception $e) {

		}
		
	}



}

if($widgetModel->drawCount == 1 && $widgetModel->offsetCount == 0) {
	$isPrimaryValue = "CHECKED";
}

?>




<div class="panel panel-default widgetContentsContainer">

	<div class="panel-body widgetContents">


		<div class="col-md-9 col-sm-9">
			<input type="hidden" name="rootFormField" value="<?=$formFieldName?>" class="rootFormField"/>

			<div class="alert alert-info uploadWarning">
				<strong>You must select a collection before adding files.</strong>
				The collection cannot be changed after files are uploaded.
			</div>
			<div class="form-group advancedContent">
				<label for="<?=$fileIdId?>" class="col-sm-3 control-label">File Id:</label>
				<div class="col-sm-5 ">
					<input type="text" name="<?=$fileIdName?>" id="<?=$fileIdId?>" class="mainWidgetEntry fileObjectId form-control" value="<?=$fileId?>" disabled=true>
				</div>

			</div>
			<?if($fileHandler && !$fileHandler->sourceFile->ready):?>
			<div class="form-group allowResume">
				<label class="col-sm-3 control-label">File:</label>
    			<div class="col-sm-9">
      				<p class="form-control-static"><?=$fileHandler->sourceFile->originalFilename?></p>
      				<span class="help-block">This file has not finished uploading. To resume, select the file again and click upload.</span>
    			</div>
    		</div>
			<?endif?>
			<?if(!$fileHandler || ($fileHandler && !$fileHandler->sourceFile->ready)):?>
			<div class="form-group uploadInformation">
				<label class="col-sm-3 control-label">Upload a File:</label>
				<div class="col-sm-5">
					<input type="file" <?=$widgetModel->getAllowMultiple()?"multiple":null?> class="file form-control" value="Select File" />
				</div>
				<div class="col-sm-3">
					<button type="button" class="deleteFile btn btn-danger">Delete</button>
					<button type="button" class="cancelButton btn btn-primary">Cancel</button>
				</div>
			</div>
			<?else:?>
			<div class="form-group uploadInformation">
				<label class="col-sm-3 control-label">Source File:</label>
				<div class="col-sm-6 fileLabel">
					<p><?=$fileHandler->sourceFile->originalFilename?></p>
					<?if($downloadLink):?>
					<p><a href="<?=$downloadLink?>">Download Source File</a></p>
					<?endif?>
				</div>
				<div class="col-sm-3">
					<button type="button" class="deleteFile btn btn-danger">Delete</button>
					<button type="button" class="cancelButton btn btn-primary">Cancel</button>
				</div>
			</div>
			<?endif?>
			<div class="row uploadProgress">
				<div class="col-sm-5 col-sm-offset-3">
				<div class="progress progress-striped active">
					<div class="progress-bar"></div>
				</div>
			</div>
			</div>
			<textarea style="display:none" class="log" rows="10"></textarea>
			<div class="form-group">
				<label for="<?=$fileDescriptionId?>" class="col-sm-3 control-label">Description</label>
				<div class="col-sm-5">
					<textarea class="fileDescription form-control" id="<?=$fileDescriptionId?>" name="<?=$fileDescriptionName?>" placeholder="<?=$labelText?>"><?=$fileDescriptionContents?></textarea>
				</div>
			</div>
			<div class="form-group advancedContent">
				<label for="<?=$searchDataId?>" class="col-sm-3 control-label">Extracted Data</label>
				<div class="col-sm-5">
					<textarea class="extractedData form-control" id="<?=$searchDataId?>" name="" placeholder="<?=$labelText?>" disabled><?=$searchData?></textarea>
					<textarea style="display:none" class="extractedData form-control" id="<?=$searchDataId?>" name="<?=$searchDataName?>" placeholder="<?=$labelText?>"><?=$searchData?></textarea>
				</div>
				<div class="col-sm-2">
					<button type="button" class="deleteData btn btn-danger advancedContent">Remove Data</button>
				</div>
			</div>

			<div class="form-group advancedContent">
				<div class="col-sm-offset-3 col-sm-8">
					<div class="checkbox">
						<label>
							<input id="<?=$regenerateId?>" class="regenerateDerivatives" value="On" name="<?=$regenerateName?>" type="checkbox">
							Regenerate Derivatives
						</label>
					</div>
				</div>
			</div>

			<div class="form-group advancedContent">
				<div class="col-sm-offset-3 col-sm-8">
					<a href="" class="processingLogs">View Processing Logs for Asset</a>
				</div>
			</div>

			<div class="sidecars"><?=$sidecarView?></div>

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
		<div class="col-md-3 col-sm-3 imagePreview">
			<img class="well img-responsive lazy" data-src="<?=$standardURL?>" data-srcset="<?=$retinaURL?> 2x" data-fileready="<?=$fileReady?"true":"false"?>" />
		</div>
	</div>
</div>

<?

if($fileHandler) {
	$this->doctrine->em->detach($fileHandler->asset);
		
	unset($fileHandler);
	$fileHandler = null;
	$widgetModel->fieldContentsArray[$i]->fileHandler = null;

	gc_collect_cycles();	
}

endfor?>

<script>
$( "#collectionId" ).trigger( "change" );
$("#<?=$fileFieldId?>").trigger("change");

</script>
<?if($widgetModel->offsetCount==0):?>
<?=$this->load->view("widget_form_partials/widget_footer", array("widgetModel"=>$widgetModel), true)?>
<?endif?>
