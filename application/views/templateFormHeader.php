<script>

window.offsetCount = new Array();

<?if($this->user_model->user->getFastUpload()):?>
var fastUpload = true;
<?else:?>
var fastUpload = false;
<?endif?>

</script>


<?
$objectId = null;
$readyForDisplay = "CHECKED";
$collectionId = null;
$availableAfter = null;

if(isset($asset)) {
	$objectId = $asset->getObjectId();
	$readyForDisplay = $asset->getGlobalValue("readyForDisplay")?"CHECKED":null;
	$collectionId = $asset->getGlobalValue("collectionId");
	if($asset->getGlobalValue("availableAfter") && $asset->getGlobalValue("availableAfter")->getTimestamp() > 0) {
		$availableAfter = $asset->getGlobalValue("availableAfter")->format("Y-m-d");
	}

}

if(strlen($this->template->collectionId)>0) {
	$collectionId = intval($this->template->collectionId->__toString());
}


?>

<?if(isset($asset) && $asset->getGlobalValue("collectionMigration")):?>
<input type="hidden" id="collectionMigrationInProcess" value="true">
<?else:?>
<input type="hidden" id="collectionMigrationInProcess" value="false">
<?endif?>


<form class="form-horizontal clean" role="form" method="post" name="entryForm" id="entryForm" novalidate onSubmit="submitForm(); return false;">


<div class="row theme-<?=$template->getTemplateColor()?>">
	<div class="col-sm-3 leftPane"> <!-- required for floating -->
		<!-- Nav tabs -->
		<div class="row">
			<div class="col-sm-12 miniPreview">
			</div>
		</div>
		<div class="row">
			<div class="col-sm-12">
				<button type="submit" class="btn btn-sm btn-primary saveButton">Save</button>
				<button type="button" class="btn btn-sm btn-info viewAsset">View</button>
			</div>
		</div>
		<div class="row">
			<div class="col-sm-12">
				<ul class="nav nav-tabs tabs-left hidden-xs">
					<li class="active"><a href="#general" data-toggle="tab">General</a></li>
					<? foreach($widgetList as $widgetTitle=>$widgetLabel):?>
					<li><a href="#<?=$widgetTitle?>" data-toggle="tab"><?=$widgetLabel?> <span class="glyphicon glyphicon-ok haveContent"></span><span class="glyphicon glyphicon-exclamation-sign requiredContent"></span></a></li>
					<?endforeach?>
				</ul>
			</div>
		</div>
	</div>

	<div class="col-sm-9 rightPane">
		<div class="tab-content" >
			<div class="tab-pane active" id="general">
				<div class="control-group">
				<div class="panel panel-default widgetContentsContainer">
					<div class="panel-body widgetContents">
						<button type="button" class="btn btn-primary toggleTabs pull-right">Toggle Tabs</button>
						<div class="form-group">
							<label for="inputObjectId" class="col-sm-2 control-label">Object Id:</label>
							<div class="col-sm-3">
								<input type="text" name="objectId" displayed id="inputObjectId" class="form-control" disabled value="<?=$objectId?>" >
							</div>

						</div>
						<div class="form-group">
							<label for="templateId" class="col-sm-2 control-label">Template:</label>
							<div class="col-sm-3">
								<input type="hidden" name="templateId" value="<?=$template->getId()?>" id="sourceTemplate">
								<select name="newTemplateId" id="inputNewTemplateId" class="form-control input-large">
									<option>---</option>
									<? foreach($this->instance->getTemplates() as $internalTemplate):?>
									<option <?=($internalTemplate->getId()==$template->getId())?"SELECTED":null?> value="<?=$internalTemplate->getId()?>"><?=$internalTemplate->getName()?></option>
									<?endforeach?>
								</select>
							</div>
						</div>

						<div class="form-group">
							<label for="inputCollectionId" class="col-sm-2 control-label">Collection:</label>
							<div class="col-sm-3">
							<input type="hidden" name="collectionId" value="<?=$collectionId?>" id="collectionId">
								<select name="newCollectionId" id="newCollectionId" class="form-control input-large">
									<option>---</option>
									<?=$this->load->view("collection_select_partial", ["selectCollection"=>$collectionId, "collections"=>$this->instance->getCollectionsWithoutParent(), "allowedCollections"=>$allowedCollections],true);?>

								</select>
							</div>
						</div>
						<div class="form-group">
							<div class="col-sm-3 col-sm-offset-2">
								<div class="checkbox">
									<label>
										<input value="on" name="readyForDisplay" <?=$readyForDisplay?> type="checkbox">
										Ready For Display
									</label>
								</div>
							</div>
						</div>
						<div class="form-group">
							<label for="inputAvailableAfter" class="col-sm-2 control-label">Available After:</label>
							<div class="col-sm-3">
								<input  name="availableAfter" id="inputAvailableAfter" class="form-control" value="<?=$availableAfter?>">
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>




