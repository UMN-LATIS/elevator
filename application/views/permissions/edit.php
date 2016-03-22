<h2>Editing Permissions For <?=$objectTitle?></h2>

<form method="post" accept-charset="utf-8" action="<?= instance_url("permissions/update/"); ?>" />

	<input type="hidden" name="permissionType" id="inputPermissionType" class="form-control" value="<?= $permissionType; ?>">
	<?php if($permissionTypeId != null): ?>
		<input type="hidden" name="permissionTypeId" id="inputPermissionTypeId" class="form-control" value="<?= $permissionTypeId; ?>">
	<?php endif;?>

	<?php foreach ($permissions as $permission) : ?>

	<div class="row rowPadding rowContainer">

			<? // this shouldn't happen in the view.  It really shouldn't happen in the view.
			$tempUser = new User_model();
			if($permission->getGroup()->getGroupType() == "User") {
				$tempUser->loadUser($permission->getGroup()->getGroupValue());
			}
			if($permission->getGroup()->getGroupType() == "JobCode") {
				$tempUser->jobCodes[] = $permission->getGroup()->getGroupValue();
				$tempUser->resolvePermissions();
			}
			if($permission->getGroup()->getGroupType() == "Course") {
				$tempUser->courses[] = $permission->getGroup()->getGroupValue();
				$tempUser->resolvePermissions();
			}
			if($permission->getGroup()->getGroupType() == UNIT_TYPE) {
				$tempUser->units[] = $permission->getGroup()->getGroupValue();
				$tempUser->resolvePermissions();
			}
			$minimumAccessLevel = $tempUser->getAccessLevel("instance",$this->instance);
			?>

			<input type="hidden" name="instancePermissionId[<?= $permission->getId(); ?>]" value="<?= $permission->getId(); ?>" />


			<div class="col-md-2">
				<label class="pull-right" for='permission_<?= $permission->getId(); ?>'><?= $permission->getGroup()->getGroupLabel(); ?> (<?=$permission->getGroup()->getGroupType()?>) can:</label>
			</div>
			<div class="col-md-6">
				<select class="form-control input-large input-sm pull-left" id='permission_<?= $permission->getId(); ?>' name="permission[<?= $permission->getId(); ?>]">
					<?php foreach ($permissionList as $permissionItem) : ?>

					<?
					$disabled=null;
				  			// you can't grant someone better than "originals" on a drawer
					if($permissionItem->getLevel()>PERM_ORIGINALS && $permissionType=="drawer") {
						$disabled = "disabled";
					}

				  	// you can't give someone a lower perm level than what they have on the instance
					if($permissionType != "instance" && $permissionItem->getLevel() <= $minimumAccessLevel && isset($tempUser->user) && !$tempUser->getIsSuperAdmin()) {
						$disabled = "disabled";
					}
					?>
					<option value="<?= $permissionItem->getId(); ?>" <?=($permission->getPermission()->getId() == $permissionItem->getId())?"selected":null?> <?=$disabled?>><?= $permissionItem->getLabel(); ?></option>
					<?php endforeach; ?>
			</select>
			<a class="btn btn-danger btn-sm buttonWithPadding" href="<?= instance_url("permissions/delete/{$permissionType}/{$permission->getId()}/{$permissionTypeId}"); ?>" role="button"><span class="glyphicon glyphicon-remove"></span></a>
		</div>
		<div class="col-md-1">

		</div>

	</div>
	<?php	endforeach; ?>
	<div class="row rowPadding rowContainer">
		<div class="col-md-3 col-md-offset-2">
			<?if($permissions->count()>0):?>
				<input type="submit" name="submit" value="Save Permissions" class='btn btn-primary'/>
			<?endif?>
		</div>
	</div>
</form>
<hr>
<div class="row rowContainer">
	<div class="col-md-2">
		<a href="<?= instance_url("permissions/newGroup/" . $permissionType . "/" . (($permissionTypeId == null)?null:$permissionTypeId)); ?>" class='btn btn-primary btn-info btn-sm'>Create a new group</a>
	</div>
</div>

<hr>

<?if($permissionType !== DRAWER_PERMISSION):?>
<div class="row rowContainer">
	<div class="col-md-2">
		<a href="<?= instance_url("permissions/instanceHandlerGroups")?>" class='btn btn-primary btn-info btn-sm'>Edit File Handler Groups</a>
	</div>
</div>
<?endif?>

<hr>
<div class="row rowPadding rowContainer">
	<form method="post" action="<?=instance_url("permissions/modifyGroup")?>" />
	<div class="col-md-2">
		Existing Groups:<input type="hidden" name="permissionType" id="inputPermissionType" class="form-control" value="<?= $permissionType; ?>">
		<?php if($permissionTypeId != null): ?>
			<input type="hidden" name="permissionTypeId" id="inputPermissionTypeId" class="form-control" value="<?= $permissionTypeId; ?>">
		<?php endif;?>
	</div>
	<div class="col-md-3">
		<select name="groupId" class="form-control input-sm">
		<?foreach($groupList as $group):?>
			<option value="<?=$group->getId()?>"><?=$group->getGroupLabel()?>  (<?=$group->getGroupType()?>)</option>
			<?endforeach;?>
		</select>
	</div>
	<div class="col-md-2">
		<button type="submit" name="submit" value="edit" class="form-control btn btn-sm btn-info">Edit Group</button>
	</div>
	<div class="col-md-3">
		<button type="submit" name="submit"  value="add" class="form-control btn btn-sm btn-primary">Add Group to Permissions List</button>
	</div>
	<div class="col-md-2">
		<button type="submit" name="submit" value="delete" class="form-control btn btn-sm btn-danger">Delete Group</button>
	</div>
</div>

<hr>


<div class="row rowPadding rowContainer">
<div class="col-md-9">
<h2>My Users:</h2>
<ul class="list-group">
	<?foreach($myUsers as $user):?>
	<li class="list-group-item"><a href="<?=instance_url("permissions/editUser/".$user->getId())?>"><?=$user->getDisplayName()?></a></li>
	<?endforeach?>
</ul>
</div>
</div>

<div class="row rowPadding">
<div class="col-md-9">
<a href="<?= instance_url("permissions/addUser")?>" class="btn btn-primary">Create a local user</a>
</div>
</div>
