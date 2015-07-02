<div class="row rowContainer">
	<div class="col-md-9">
		<p>Currently Shared With:</p>
		<ul>
			<?foreach($sourceCollection->getInstances() as $instance):?>
			<li><?=$instance->getName()?></li>
			<?endforeach?>
		</ul>
	</div>
</div>
<hr>
<div class="row rowContainer">
	<div class="col-md-9">
		<form method="post" class="form-horizontal" accept-charset="utf-8" action="" />
		<input type="hidden" name="sourceCollection" id="inputSourceCollection" class="form-control" value="<?=$sourceCollection->getId()?>" required="required" pattern="" title="">
			<p>When sharing this collection, you will be granting the administrators of the instance full control over the collection.</p>

			<div class="form-group">
				<label for="inputTargetInstance" class="col-sm-2 control-label">Target Instance:</label>
				<div class="col-sm-10">

					<select name="targetInstance">
						<?foreach($instanceList as $instance):?>
							<?if(in_array($instance, $sourceCollection->getInstances()->toArray())) { $disabled = "DISABLED";} else { $disabled = "";}?>
							<option value="<?=$instance->getId()?>" <?=$disabled?>><?=$instance->getName()?></option>
						<?endforeach?>
					</select>
				</div>
			</div>

			<input type="submit" name="submit" value="Update Collection" class='btn btn-primary' />

		</form>
	</div>
</div>