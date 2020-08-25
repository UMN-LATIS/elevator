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

	$(".revealButton").click(function(e) {
		e.preventDefault();
		$(".secretToggle").removeClass("hide");
	})

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
				<label for="inputShowInBrowse" class="col-sm-2 control-label">Show in Browse List:</label>
				<div class="col-sm-6">
					<input type="checkbox"  name="showInBrowse" id="inputShowInBrowse" class="form-control" <?=$collection->getShowInBrowse()?"CHECKED":null ?> >
				</div>
			</div>

			<div class="form-group">
				<div class="col-sm-6 col-sm-offset-2">
				<p><a class="btn btn-primary btn-sm" data-toggle="collapse" data-target="#bucketDetails">Show Bucket Options</a></p>
				</div>
			</div>

			<div class="collapse" id="bucketDetails">
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
					<div class="col-sm-6 secretToggle hide">
						<input type="text"  name="S3Secret" id="inputAmazonS3Secret" class="form-control" value="<?=$collection->getS3Secret() ?>"  >
					</div>
					<div class="col-sm-2">
					<button class="btn btn-primary btn-sm revealButton">Reveal</button>
						</div>
				</div>
			</div>

			
			<div class="form-group">
				<label for="inputParent" class="col-sm-2 control-label">Collection Description:</label>
				<div class="col-sm-10">
					<textarea rows=20 class="form-control" id="collectionDescriptionText" name="collectionDescription"><?=$collection->getCollectionDescription()?></textarea>
				</div>
			</div>

				<div class="form-group">
					<label for="inputPreviewImage" class="col-sm-2 control-label">Preview Image (ObjectID):</label>
					<div class="col-sm-6">
						<input type="text" name="previewImage" id="inputPreviewImage" class="form-control" value="<?=$collection->getPreviewImage() ?>" >
					</div>
				</div>

			<input type="submit" name="submit" value="Update Collection" class='btn btn-primary' />

		</form>
	</div>
</div>
<script type="text/javascript">
$(document).ready(function() {
	tinymce.init({
	    selector: "textarea",
	    menubar : false,
	    relative_urls : false,
	    toolbar: "undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | code | image link ",
	    plugins: ["link", "code", "image"]
	 });
});
</script>

<?$this->load->view("modals/bucketCreation_modal");?>