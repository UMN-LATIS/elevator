<label for='group_label'>Group Label</label>
<input type="input" name="group_label" value="<?= $instanceGroup->getGroupLabel(); ?>" required="required"/><br />

<select name='group_type' required='required'>
	<option value=''></option>
	<option value='JobCode' <?php if ($instanceGroup->getGroupType() == 'job_code') {echo "selected";} ?>>Job Code</option>
	<option value='Course' <?php if ($instanceGroup->getGroupType() == 'enrollment') {echo "selected";} ?>>Enrollment</option>
	<option value='User' <?php if ($instanceGroup->getGroupType() == 'emplid') {echo "selected";} ?>>Emplid</option>
</select>
is
<input type="input" name="group_value" value="<?= $instanceGroup->getGroupValue(); ?>" required="required"/><br />

<input type="submit" name="submit" value="Submit" class='btn btn-primary'/>
