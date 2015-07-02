<div class="row rowContainer">
	<div class="col-md-9">
		<form method="post" class="form-horizontal" accept-charset="utf-8" action="<?= instance_url("collectionManager/save/"); ?>" />
			<input type="hidden" name="collectionId"  value="<?= isset($collection)?$collection->getId():null; ?>" /><br />
			<div class="form-group">
				<label for="inputTitle" class="col-sm-2 control-label">Title:</label>
				<div class="col-sm-6">
					<input type="text" name="title" id="inputTitle" class="form-control" value="<?= $collection->getTitle() ?>" >
				</div>
			</div>

			<div class="form-group">
				<label for="inputParent" class="col-sm-2 control-label">Parent:</label>
				<div class="col-sm-6">
					<select name="parent" id="inputParent" class="form-control" required="required">
						<option value=0>None</option>
						<?foreach($this->instance->getCollections() as $collectionItem):?>
						<option value=<?=$collectionItem->getId()?> <?=($collection->getParent()&&$collectionItem->getId()==$collection->getParent()->getId())?"SELECTED":null?> ><?=$collectionItem->getTitle()?></option>
						<?endforeach?>
					</select>
				</div>
			</div>


			<div class="form-group">
				<label for="inputBucket" class="col-sm-2 control-label">Bucket:</label>
				<div class="col-sm-6">
					<input type="text" name="bucket" id="inputBucket" class="form-control" value="<?=$collection->getBucket() ?>" >
				</div>
			</div>

			<div class="form-group">
				<label for="inputBucketRegion" class="col-sm-2 control-label">Bucket Region:</label>
				<div class="col-sm-6">
					<input type="text" name="bucketRegion" id="inputBucketRegion" class="form-control" value="<?=$collection->getBucketRegion() ?>" >
				</div>
			</div>

			<div class="form-group">
				<label for="inputS3Key" class="col-sm-2 control-label">S3 Key:</label>
				<div class="col-sm-6">
					<input type="text" name="S3Key" id="inputS3Key" class="form-control" value="<?=$collection->getS3Key() ?>" >
				</div>
			</div>

			<div class="form-group">
				<label for="inputS3Secret" class="col-sm-2 control-label">S3 Secret:</label>
				<div class="col-sm-6">
					<input type="text" name="S3Secret" id="inputS3Secret" class="form-control" value="<?=$collection->getS3Secret() ?>" >
				</div>
			</div>




			<input type="submit" name="submit" value="Update Collection" class='btn btn-primary' />

		</form>
	</div>
</div>