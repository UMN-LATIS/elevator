<div class="panel panel-info">
	<div class="panel-heading">
		<h3 class="panel-title">No Instance Set</h3>
	</div>
	<div class="panel-body">
		To use Elevator via an LMS integration, you must connect a target instance..
	</div>
</div>

<div class="row loginBox">
	<div class="col-md-9">

		<form action="<?=instance_url("api/v1/lti13/updateLTIinstance")?>" method="POST" class="form-horizontal" role="form">
		<div class="form-group">
			<label for="inputEmail" class="col-sm-2 control-label">Target Instance:</label>
			<div class="col-sm-5">
				<select name="apiInstance" class="form-control">
				<option value=0 >None</option>
				<?foreach($this->doctrine->em->getRepository("Entity\Instance")->findAll() as $instance):?>
					<option value=<?=$instance->getId()?>><?=$instance->getName()?></option>
				<?endforeach?>
				</select>
			</div>
		</div>
			<input type="hidden" name="user" value="<?=$userId?>" >
			<input type="hidden" name="courseID" value="<?=$courseId?>" >
			<input type="hidden" name="returnURL" value="<?=$returnURL?>" >
			<input type="hidden" name="launchId" value="<?=$launchId?>" >

			<div class="form-group">
				<div class="col-sm-6 col-sm-offset-2">
					<button type="submit" class="btn btn-primary">Save</button>
				</div>

			</div>
		</form>


	</div>
</div>
