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

<?if($instance->getId()):?>
<input type=hidden name="instanceId" value="<?=$instance->getId()?>">
<?endif?>

<div class="form-group">
	<label for="inputName" class="col-sm-2 control-label">Instance Name:</label>
	<div class="col-sm-6">
		<input type="text" name="name" id="inputName" class="form-control" value="<?= $instance->getName(); ?>">
	</div>
</div>

<div class="form-group">
	<label for="inputDomain" class="col-sm-2 control-label">Domain:</label>
	<div class="col-sm-6">
		<input type="text" name="domain" id="inputDomain" class="form-control" value="<?= $instance->getDomain(); ?>">
	</div>
</div>

<div class="form-group">
	<label for="inputOwnerHomepage" class="col-sm-2 control-label">Owner Homepage:</label>
	<div class="col-sm-6">
		<input type="text" name="ownerHomepage" id="inputOwnerHomepage" class="form-control" value="<?= $instance->getOwnerHomepage(); ?>">
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
	<label for="inputAmazonS3Key" class="col-sm-2 control-label">Amazon S3 Key:</label>
	<div class="col-sm-6">
		<input type="text" name="amazonS3Key" id="inputAmazonS3Key" class="form-control s3control" value="<?= $instance->getAmazonS3Key(); ?>">
	</div>
</div>

<div class="form-group">
	<label for="inputAmazonS3Secret" class="col-sm-2 control-label">Amazon S3 Secret:</label>
	<div class="col-sm-6">
		<input type="password" data-toggle="password" name="amazonS3Secret" id="inputAmazonS3Secret" class="form-control" value="<?= $instance->getAmazonS3Secret(); ?>">
	</div>
</div>

<div class="form-group">
	<label class="col-sm-2 control-label">S3 Storage Type</label>
	<div class="col-sm-6">
		<div class="radio">
			<label>
				<input type="radio" name="s3StorageType" id="inputS3StorageType" value="<?=AWS_REDUCED?>" <?=($instance->getS3StorageType()== AWS_REDUCED || $instance->getS3StorageType()==null)?"checked=\"checked\"":null?>>
				Reduced Redundancy Storage
			</label>
		</div>
		<div class="radio">
			<label>
				<input type="radio" name="s3StorageType" id="inputS3StorageType" value="<?=AWS_STANDARD?>" <?=($instance->getS3StorageType()== AWS_STANDARD)?"checked=\"checked\"":null?>>
				Standard Storage
			</label>
		</div>
	</div>


</div>


<div class="form-group">
	<label for="inputDefaultBucket" class="col-sm-2 control-label">Default Bucket:</label>
	<div class="col-sm-6">
		<input type="text" name="defaultBucket" id="inputDefaultBucket" class="form-control s3control" value="<?= $instance->getDefaultBucket(); ?>">
	</div>
</div>

<div class="form-group">
	<label for="inputBucketRegion" class="col-sm-2 control-label">Bucket Region:</label>
	<div class="col-sm-6">
		<input type="text" name="bucketRegion" id="inputBucketRegion" class="form-control" value="<?= $instance->getBucketRegion(); ?>">
	</div>
</div>

<div class="form-group">
	<label for="inputGoogleAnalyticsKey" class="col-sm-2 control-label">Google Analytics Key:</label>
	<div class="col-sm-6">
		<input type="password" data-toggle="password" name="googleAnalyticsKey" id="inputGoogleAnalyticsKey" class="form-control" value="<?= $instance->getGoogleAnalyticsKey(); ?>">
	</div>
</div>
<!--
<div class="form-group">
	<label for="inputClarifaiId" class="col-sm-2 control-label">Clarifai Id:</label>
	<div class="col-sm-6">
		<input type="text" name="clarifaiId" id="inputClarifaiId" class="form-control" value="<?= $instance->getClarifaiId(); ?>">
	</div>
</div>

<div class="form-group">
	<label for="inputClarifaiSecret" class="col-sm-2 control-label">Clarifai Secret:</label>
	<div class="col-sm-6">
		<input type="password" data-toggle="password" name="clarifaiSecret" id="inputClarifaiSecret" class="form-control" value="<?= $instance->getClarifaiSecret(); ?>">
	</div>
</div>
-->
<div class="form-group">
	<label for="inputBoxKey" class="col-sm-2 control-label">Box API Key:</label>
	<div class="col-sm-6">
		<input type="password" data-toggle="password" name="boxKey" id="inputBoxKey" class="form-control" value="<?= $instance->getBoxKey(); ?>">
	</div>
</div>

<div class="form-group">
	<label for="inputFeaturedAsset" class="col-sm-2 control-label">Feature Asset:</label>
	<div class="col-sm-6">
		<input type="text" name="featuredAsset" id="inputFeaturedAsset" class="form-control" value="<?= $instance->getFeaturedAsset(); ?>">
	</div>
</div>

<div class="form-group">
	<label for="inputFeaturedAssetText" class="col-sm-2 control-label">Featured Asset Text</label>
	<div class="col-sm-6">
		<textarea name="featuredAssetText" class="form-control"><?= $instance->getFeaturedAssetText(); ?></textarea><br/>
	</div>
</div>




<div class="form-group">
	<label for="introText" class="col-sm-2 control-label">Intro Text</label>
	<div class="col-sm-6">
		<textarea name="introText" class="form-control introText"><?= $instance->getIntroText(); ?></textarea><br/>
	</div>
</div>


	<div class="form-group">
			<div class="col-sm-offset-2 col-sm-8">
				<label>
					<input type="checkbox" id="useCustomHeader" name="useCustomHeader" value="On" <?=$instance->getUseCustomHeader()?"checked":null?>>
					Use Custom Header
				</label>
			</div>
		</div>

	<div class="form-group">
			<div class="col-sm-offset-2 col-sm-8">
				<label>
					<input type="checkbox" id="useCustomCSS" name="useCustomCSS" value="On" <?=$instance->getUseCustomCSS()?"checked":null?>>
					Use Custom CSS
				</label>
			</div>
		</div>
	<div class="form-group">
			<div class="col-sm-offset-2 col-sm-8">
				<label>
					<input type="checkbox" id="useHeaderLogo" name="useHeaderLogo" value="On" <?=$instance->getUseHeaderLogo()?"checked":null?>>
					Use Header Logo
				</label>
			</div>
		</div>
	<div class="form-group">
			<div class="col-sm-offset-2 col-sm-8">
				<label>
					<input type="checkbox" id="useCentralAuth" name="useCentralAuth" value="On" <?=$instance->getUseCentralAuth()?"checked":null?>>
					Use Central Auth
				</label>
			</div>
		</div>


<div class="form-group">
	<div class="col-sm-6 col-offset-2">
		<button type="submit" class="btn btn-primary">Submit</button>
	</div>
</div>


<script type="text/javascript">
$(document).ready(function() {
	tinymce.init({
	    mode: "specific_textareas",
	    editor_selector: "introText",
	    menubar : false,
	    plugins: "link",
	    setup: function(editor) {
 			editor.on('change', function () {
            	tinymce.triggerSave();
        	});
	    }
	 });

});


</script>
