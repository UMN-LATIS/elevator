
<div class="row rowContainer">
	<div class="col-md-12">
		<form action="<?=instance_url("permissions/instanceHandlerGroups")?>" method="POST" role="form">

		<?foreach($handlerPermissions as $handlerName=>$permission):?>
		<div class="row rowPadding">
			<div class="col-md-4">
				<?=$handlerName?>
			</div>
			<div class="col-md-4">
				<select name="<?=$handlerName?>" id="input<?=$handlerName?>" class="form-control" required="required">
					<option value="<?=PERM_DERIVATIVES_GROUP_1?>" <?=($permission==PERM_DERIVATIVES_GROUP_1)?"SELECTED":null?>>Group 1</option>
					<option value="<?=PERM_DERIVATIVES_GROUP_2?>" <?=($permission==PERM_DERIVATIVES_GROUP_2)?"SELECTED":null?>>Group 2</option>
				</select>
			</div>
		</div>
		<?endforeach?>


			<button type="submit" name="submit" class="btn btn-primary">Save</button>
		</form>


	</div>
</div>