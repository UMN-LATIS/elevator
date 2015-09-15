<script>

var bucketCreationCallback = function(accessKey, secretKey, bucketName, bucketRegion) {
	$("#inputAmazonS3Key").val(accessKey);
	$("#inputAmazonS3Secret").val(secretKey);
	$("#inputDefaultBucket").val(bucketName);
	$("#inputBucketRegion").val(bucketRegion);
}

$(document).ready(function() {
	$(".s3control").on("change", function() {
		var notEmpty = false;
		$(".s3control").each(function() {
			if($(this).val() != "") {
				notEmpty = true;
			}
		});

		if(notEmpty) {
			$(".bucketButton").attr("disabled", true);
		}
		else {
			$(".bucketButton").attr("disabled", false);
		}

	});

	$(".s3control").trigger('change');
});

</script>

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
				<div class="col-sm-6 col-sm-offset-2">
			<!-- Button trigger modal -->
			<button type="button" class="btn btn-primary btn-sm bucketButton" data-toggle="modal" data-target="#bucketCreationModal">
				Create new bucket
			</button>
				</div>
			</div>


			<div class="form-group">
				<label for="inputBucket" class="col-sm-2 control-label">Bucket:</label>
				<div class="col-sm-6">
					<input type="text" name="bucket" id="inputDefaultBucket" class="form-control s3control" value="<?=$collection->getBucket() ?>" >
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
					<input type="text" name="S3Key" id="inputAmazonS3Key" class="form-control s3control" value="<?=$collection->getS3Key() ?>" >
				</div>
			</div>

			<div class="form-group">
				<label for="inputS3Secret" class="col-sm-2 control-label">S3 Secret:</label>
				<div class="col-sm-6">
					<input type="text" name="S3Secret" id="inputAmazonS3Secret" class="form-control s3control" value="<?=$collection->getS3Secret() ?>" >
				</div>
			</div>




			<input type="submit" name="submit" value="Update Collection" class='btn btn-primary' />

		</form>
	</div>
</div>


<?$this->load->view("bucketCreation_modal");?>