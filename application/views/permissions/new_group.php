<?
$groups = $groupObject->getGroupValues();

$groupArray = array();
foreach($groups as $group) {
	$groupArray[] = $group->getGroupValue();
}

$nameArray = array();
if($groupObject->getGroupType() == "User") {
	// let's cache the users.
	//

	foreach($groupArray as $id) {
		if( $this->user_model->getDisplayNameForUserId($id)) {
			$nameArray[$id] = $this->user_model->getDisplayNameForUserId($id);	
		}
		elseif($this->user_model->getUsernameForUserId($id)) {
			$nameArray[$id] = $this->user_model->getUsernameForUserId($id);	
		}
		
	}
}

$hints = array();
$helpText = array();

foreach($this->user_model->userData as $key=>$value) {
	$hints[$key] = $value["hints"];
}

foreach($authHelper->authTypes as $key=>$value) {
	if(isset($value["helpText"])) {
		$helpText[$key] = $value["helpText"];	
	}
	
}

$disableGlobal = null;

// don't let regular users share drawers across a whole instance.
if($permissionType == DRAWER_PERMISSION && !($this->user_model->getAccessLevel("instance",$this->instance)>=PERM_ADMIN)) {
	$disableGlobal = "disabled";
}

?>

<div class="row">
	<div class="col-md-9">
		<form action="<?=instance_url("permissions/createGroup/")?>" id="createGroupForm" method="POST" class="form-horizontal" role="form">
			<?php if($permissionTypeId != null):  ?>
			<input type="hidden" name="permissionTypeId" id="inputPermissionTypeId" class="form-control" value="<?= $permissionTypeId; ?>">
			<?php endif; ?>
			<input type="hidden" name="permissionType" id="inputPermissionType" class="form-control" value="<?= $permissionType; ?>">
			<input type="hidden" name="groupId" id="inputGroupId" class="form-control" value="<?= $groupId; ?>">
			<div class="form-group" id="groupTypeGroup">
				<label for="inputGroupType" class="col-sm-2 control-label">Group Type:</label>
				<div class="col-sm-7">
					<select name="groupType" id="inputGroupType" class="form-control">
						<option value="">-- Select Group Type --</option>
						<option value="All" <?=$disableGlobal?> <?=($groupObject->getGroupType()=="All")?"SELECTED":NULL?>>All Users</option>
						<option value="Authed" <?=$disableGlobal?> <?=($groupObject->getGroupType()=="Authed")?"SELECTED":NULL?>>Authenticated User</option>
						<option value="Authed_remote" <?=$disableGlobal?> <?=($groupObject->getGroupType()=="Authed_remote")?"SELECTED":NULL?>>Centrally Authenticated User</option>
						<?foreach($authHelper->authTypes as $key=>$value): ?>
						<option value="<?=$key?>" <?=($groupObject->getGroupType()==$key)?"SELECTED":NULL?>><?=$value['label']?></option>
						<?endforeach?>
						<option value="User" <?=($groupObject->getGroupType()=="User")?"SELECTED":NULL?>>Specific People</option>
					</select>
				</div>
			</div>

			<div class="form-group" id="groupLabelGroup">
				<label for="inputGroupLabel" class="col-sm-2 control-label">Group Label:</label>
				<div class="col-sm-7">
					<input type="text" name="groupLabel" id="inputGroupLabel" class="form-control" value="<?=$groupObject->getGroupLabel()?>" >
				</div>
			</div>


			<script>

			var existingGroups = <?=json_encode($groupArray)?>;
			var userCache = <?=json_encode($nameArray)?>;
			var hints = <?=json_encode($hints)?>;
			var helpText = <?=json_encode($helpText)?>;

			</script>
			<button type="button" id="addAnotherValue" class="btn btn-primary">Add Another Value</button>
			<button type="submit" id="submitButton" class="btn btn-primary">Save</button>

		</form>
	</div>
</div>

<script id="hint-selector" type="text/x-handlebars-template">
<div class="form-group hintSelectorGroup">
	<label for="inputHintSelector" class="col-sm-2 control-label">Suggested {{hintLabel}}:</label>
	<div class="col-sm-3">
		<select name="hintSelector" class="form-control hintSelector" id="inputHintSelector" class="form-control">
			<option value="">-- Suggested Value --</option>
			{{#each hints}}
			<option value="{{@key}}">{{this}}</option>
			{{/each}}
		</select>
	</div>
</div>
</script>

<script id="group-value" type="text/x-handlebars-template">
<div class="form-group groupValueGroup">
	<label for="inputGroupValue" class="col-sm-2 control-label">Group Value:</label>
	<div class="col-sm-7">
		<input type="text" name="groupValue[]"  class="inputGroupValue form-control" value="{{groupValue}}">
		<span class="help-block">{{groupName}}</span>
	</div>

	<div class="col-sm-3">
		<button type="button" class="btn btn-default removeValueButton">Remove</button>
	</div>
	<p class="help-block" id="helpText"></p>
</div>

</script>