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
		$nameArray[$id] = $this->user_model->getDisplayNameForUserId($id);
	}
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
				<div class="col-sm-5">
					<select name="groupType" id="inputGroupType" class="form-control">
						<option value="">-- Select Group Type --</option>
						<option value="All" <?=($groupObject->getGroupType()=="All")?"SELECTED":NULL?>>All Users</option>
						<option value="Authed" <?=($groupObject->getGroupType()=="Authed")?"SELECTED":NULL?>>Authenticated User</option>
						<option value="Authed_remote" <?=($groupObject->getGroupType()=="Authed_remote")?"SELECTED":NULL?>>Centrally Authenticated User</option>
						<option value="Course" <?=($groupObject->getGroupType()=="Course")?"SELECTED":NULL?>>Course</option>
						<option value="Unit" <?=($groupObject->getGroupType()==UNIT_TYPE)?"SELECTED":NULL?>>Unit</option>
						<option value="JobCode" <?=($groupObject->getGroupType()=="JobCode")?"SELECTED":NULL?>>Job Code</option>
						<option value="User" <?=($groupObject->getGroupType()=="User")?"SELECTED":NULL?>>Specific People</option>
					</select>
				</div>
			</div>

			<div class="form-group" id="groupLabelGroup">
				<label for="inputGroupLabel" class="col-sm-2 control-label">Group Label:</label>
				<div class="col-sm-5">
					<input type="text" name="groupLabel" id="inputGroupLabel" class="form-control" value="<?=$groupObject->getGroupLabel()?>" >
				</div>
			</div>

			<div class="form-group" id="courseList">
				<label for="inputGroupType" class="col-sm-2 control-label">Suggested Course IDs:</label>
				<div class="col-sm-5">
					<select name="courseListSelect" id="courseListSelect" class="form-control">
						<option value="">-- Select Course --</option>
						<?foreach($this->user_model->coursesTaught as $courseId=>$courseLabel):?>
						<option value=<?=$courseId?>><?=$courseLabel?></option>
						<?endforeach?>
					</select>
				</div>
			</div>
			<script>

			var existingGroups = <?=json_encode($groupArray)?>;
			var userCache = <?=json_encode($nameArray)?>;
			</script>
			<button type="button" id="addAnotherValue" class="btn btn-primary">Add Another Value</button>
			<button type="submit" id="submitButton" class="btn btn-primary">Save</button>

		</form>
	</div>
</div>

<script id="group-value" type="text/x-handlebars-template">
<div class="form-group groupValueGroup">
	<label for="inputGroupValue" class="col-sm-2 control-label">Group Value:</label>
	<div class="col-sm-3">
		<input type="text" name="groupValue[]"  class="inputGroupValue form-control" value="{{groupValue}}">
		<span class="help-block">{{groupName}}</span>
	</div>

	<div class="col-sm-offset-2col-sm-3">
		<button type="button" class="btn btn-default removeValueButton">Remove</button>
	</div>
</div>

</script>