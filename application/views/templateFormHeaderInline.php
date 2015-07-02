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
	if($asset->getGlobalValue("availableAfter") && $asset->getGlobalValue("availableAfter")->sec != 0) {
		$availableAfter = Date("m/d/Y", $asset->getGlobalValue("availableAfter")->sec);
	}

}

if(strlen($this->template->collectionId)>0) {
	$collectionId = intval($this->template->collectionId->__toString());
}

?>

<style>
/**
 *  Bootstrap Vertical Tabs Component v1.0.0
 *  http://github.com/dbtek/bootstrap-vertical-tabs
 *  © 2013, Ismail Demirbilek
 *  MIT License
 */



.mainRow {
	background-color: rgb(238, 238,239);
}

.rightPane {
	padding-top:10px;

	height:100%;
}


.widgetContents {
	background-color: white;
}

.widgetContentsContainer {
	margin-left: 10px;
	margin-right: 20px;
	box-shadow: -2px 2px 5px 0px #888888;
}

.minipreviewContainer {
	margin-bottom:10px;
	padding: 5px;
	/*border:1px solid #000 ;*/
}

.tab-content>.tab-pane {
	display: block;
}

.affix {
	position: static;
}



</style>

<form class="form-horizontal clean" role="form" method="post" name="entryForm" id="entryForm" onSubmit="submitForm(); return false;">


<div class="row mainRow">
	<div class="col-sm-12">
		<div class="tab-content" >
				<div class="hide tab-pane active" id="general" data-spy="affix" data-offset-top="0" data-offset-bottom="10">
				<div class="control-group">
				<div class="panel panel-default widgetContentsContainer">
					<div class="panel-body widgetContents">
						<div class="form-group">
							<label for="inputObjectId" class="col-sm-2 control-label">Object Id:</label>
							<div class="col-sm-3">
								<input type="text" name="objectId" displayed id="inputObjectId" class="form-control" disabled value="<?=$objectId?>" >
							</div>

						</div>
						<div class="form-group">
							<label for="templateId" class="col-sm-2 control-label">Template:</label>
							<div class="col-sm-3">
								<select name="templateId" id="inputTemplateId" disabled class="form-control input-large templateSelector">
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
									<?=$this->load->view("collection_select_partial", ["selectCollection"=>$collectionId, "collections"=>$this->instance->getCollectionsWithoutParent(), "allowedCollections"=>$allowedCollections]);?>

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
