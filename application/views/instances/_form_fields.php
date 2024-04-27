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
	<label for="inputOwnerHomepage" class="col-sm-2 control-label">Owner Contact (http:// or mailto:):</label>
	<div class="col-sm-6">
		<input type="text" name="ownerHomepage" id="inputOwnerHomepage" class="form-control" value="<?= $instance->getOwnerHomepage(); ?>">
	</div>
</div>


<div class="form-group">
	<label for="inputGoogleAnalyticsKey" class="col-sm-2 control-label">Google Analytics Key:</label>
	<div class="col-sm-6">
		<input type="text" name="googleAnalyticsKey" id="inputGoogleAnalyticsKey" class="form-control" value="<?= $instance->getGoogleAnalyticsKey(); ?>">
	</div>
</div>

<div class="assetCompleter">
<div class="form-group">
	<label for="inputFeaturedAsset" class="col-sm-2 control-label">Feature Asset:</label>
	<div class="col-sm-6">
		<input type="text" name="featuredAsset" id="inputFeaturedAsset" class="relatedAssetSelectedItem tryAutocompleteAsset form-control" value="<?= $instance->getFeaturedAsset(); ?>">
	</div>
</div>
<div class="form-group">
	<div class="col-sm-8 col-sm-offset-2 assetPreview">
	</div>
</div>
</div>
<div class="form-group">
	<label for="inputFeaturedAssetText" class="col-sm-2 control-label">Featured Asset Text</label>
	<div class="col-sm-6">
		<textarea name="featuredAssetText" class="form-control"><?= $instance->getFeaturedAssetText(); ?></textarea><br/>
	</div>
</div>

<div class="form-group">
	<label for="inputNotes" class="col-sm-2 control-label">Instance Notes</label>
	<div class="col-sm-6">
		<textarea name="notes" class="form-control"><?= $instance->getNotes(); ?></textarea><br/>
	</div>
</div>


<fieldset class="fieldsetSection">

<legend>Storage Settings</legend>

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
	<div class="col-sm-6 secretToggle hide">
		<input type="text" name="amazonS3Secret" id="inputAmazonS3Secret" class="form-control" value="<?= $instance->getAmazonS3Secret(); ?>">
	</div>
	<div class="col-sm-2">
					<button class="btn btn-primary btn-sm revealButton">Reveal</button>
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

</fieldset>

<fieldset class="fieldsetSection">

<legend>Custom Styling and Display</legend>

<div class="form-group">
	<div class="col-sm-offset-2 col-sm-8">
		<label>
			<input type="checkbox" id="useCustomHeader" name="useCustomHeader" value="On" <?=$instance->getUseCustomHeader()?"checked":null?>>
			Use Custom Header and Footer
		</label>
	</div>
</div>

<div class="form-group">
	<label for="inputCustomHeaderText" class="col-sm-2 control-label">Custom Header Content</label>
	<div class="col-sm-6">
		<textarea name="customHeaderText" class="form-control"><?= $instance->getCustomHeaderText(); ?></textarea><br/>
	</div>
</div>

<div class="form-group">
	<label for="inputCustomFooterText" class="col-sm-2 control-label">Custom Footer Content</label>
	<div class="col-sm-6">
		<textarea name="customFooterText" class="form-control"><?= $instance->getCustomFooterText(); ?></textarea><br/>
	</div>
</div>

<hr>
<div class="form-group">
	<div class="col-sm-offset-2 col-sm-8">
		<label>
			<input type="checkbox" id="useCustomCSS" name="useCustomCSS" value="On" <?=$instance->getUseCustomCSS()?"checked":null?>>
			Use Custom CSS
		</label>
	</div>
</div>


<div class="form-group">
	<label for="inputCustomHeaderCSS" class="col-sm-2 control-label">Custom CSS Content</label>
	<div class="col-sm-6">
		<textarea name="customHeaderCSS" class="form-control"><?= $instance->getCustomHeaderCSS(); ?></textarea><br/>
	</div>
</div>
<hr>
<div class="form-group">
	<div class="col-sm-offset-2 col-sm-8">
		<label>
			<input type="checkbox" id="enableInterstitial" name="enableInterstitial" value="On" <?=$instance->getEnableInterstitial()?"checked":null?>>
			Show Custom Interstitial When Embedding through API
		</label>
	</div>
</div>


<div class="form-group">
	<label for="inputInterstitialText" class="col-sm-2 control-label">Custom Interstitial Text</label>
	<div class="col-sm-6">
		<textarea name="interstitialText" id="inputInterstitialText" class="form-control"><?= $instance->getInterstitialText(); ?></textarea><br/>
	</div>
</div>
<hr>

<div class="form-group">
	<div class="col-sm-offset-2 col-sm-8">
		<label>
			<input type="checkbox" id="useHeaderLogo" name="useHeaderLogo" value="On" <?=$instance->getUseHeaderLogo()?"checked":null?>>
			Use Header Logo
		</label>
	</div>
</div>

<div class="form-group">
	<label for="customHeaderImage" class="col-sm-2 control-label">Header Image (PNG): </label>
	<div class="col-sm-8">
		<input id="customHeaderImage" type="file" name="customHeaderImage" class="file form-control">
	</div>
</div>
</fieldset>

<fieldset class="fieldsetSection">
<legend>Miscellaneous Configuration</legend>

<div class="form-group">
	<div class="col-sm-offset-2 col-sm-2">
		<label class="control-label">
			Interface Version
		</label>
	</div>
	<div class="col-sm-4">
			<select name="interfaceVersion" class="form-control">
				<option value="0" <?=$instance->getInterfaceVersion()==0?'SELECTED':null?>>Classic</option>
				<option value="1" <?=$instance->getInterfaceVersion()==1?'SELECTED':null?>>VueJS</option>
			</selecT>
	</div>
	<div class="col-sm-2">
		<a href="<?=instance_url("instances/previewNewInterface")?>" class="btn btn-primary">Preview VueJS Interface</a>
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
	<div class="col-sm-offset-2 col-sm-8">
		<label>
			<input type="checkbox" id="hideVideoAudio" name="hideVideoAudio" value="On" <?=$instance->getHideVideoAudio()?"checked":null?>>
			Hide video/audio download links from "view" users <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" title="This will not prevent tech-savvy users from downloading files."></span>
		</label>
	</div>
</div>

<div class="form-group">
	<div class="col-sm-offset-2 col-sm-8">
		<label>
			<input type="checkbox" id="enableHLSStreaming" name="enableHLSStreaming" value="On" <?=$instance->getEnableHLSStreaming()?"checked":null?>>
			Enable HLS Streaming <span class="glyphicon glyphicon-question-sign" data-toggle="tooltip" title="This allows for smoother video playback, but takes additional storage space."></span>
		</label>
	</div>
</div>


<div class="form-group">
	<div class="col-sm-offset-2 col-sm-8">
		<label>
			<input type="checkbox" id="allowIndexing" name="allowIndexing" value="On" <?=$instance->getAllowIndexing()?"checked":null?>>
			Allow indexing by search engines (Google)
		</label>
	</div>
</div>

<div class="form-group">
	<div class="col-sm-offset-2 col-sm-8">
		<label>
			<input type="checkbox" id="showCollectionInSearchResults" name="showCollectionInSearchResults" value="On" <?=$instance->getShowCollectionInSearchResults()?"checked":null?>>
			Show Collection In Search Results
		</label>
	</div>
</div>

<div class="form-group">
	<div class="col-sm-offset-2 col-sm-8">
		<label>
			<input type="checkbox" id="showTemplateInSearchResults" name="showTemplateInSearchResults" value="On" <?=$instance->getShowTemplateInSearchResults()?"checked":null?>>
			Show Template In Search Results
		</label>
	</div>
</div>
<div class="form-group">
	<div class="col-sm-offset-2 col-sm-8">
		<label>
			<input type="checkbox" id="showPreviousNextSearchResults" name="showPreviousNextSearchResults" value="On" <?=$instance->getShowPreviousNextSearchResults()?"checked":null?>>
			Show Previous/Next Search Results when Viewing Asset
		</label>
	</div>
</div>


</fieldset>


<fieldset class="fieldsetSection vueInterfaceSection">
<legend>Vue Interface Options</legend>
<div class="form-group">
	<div class="col-sm-offset-2 col-sm-8">
		<label>
			<input type="checkbox" id="enableThemes" name="enableTheming" value="1" <?=$instance->getEnableThemes()?"checked":null?>>
			Enable Theme Selection
		</label>
	</div>
</div>

<div class="form-group">
	<label for="defaultTheme" class="col-sm-2 control-label">Default Theme:</label>
	<div class="col-sm-6">
		<select name="defaultTheme" id="defaultTheme">
			<?foreach($this->config->item("available_themes") as $theme):?>
			<option value="<?=$theme?>" <?=$instance->getDefaultTheme()==$theme?"SELECTED":null?>><?=$theme?></option>
			<?endforeach?>
		</select>
	</div>
</div>
<div class="form-group">
	<label for="availableThemes" class="col-sm-2 control-label">Available Themes</label>
	<div class="col-sm-6">
		<ul style="list-style-type: none; margin-left:0; padding-left:0">
			<?foreach($this->config->item("available_themes") as $theme):?>
				<li><input type="checkbox" name="availableThemes[]" value="<?=$theme?>" id="<?=$theme?>" <?=in_array($theme, $instance->getAvailableThemes()??[])?"CHECKED":null?>> <label for="<?=$theme?>"><?=$theme?></label></li>
			<?endforeach?>
	</ul>

		
	</div>
</div>

<div class="form-group">
	<label for="customHomeRedirect" class="col-sm-2 control-label">
		Custom Home Redirect:
	</label>
	<div class="col-sm-6">
		<input type="text" name="customHomeRedirect" id="customHomeRedirect" class="form-control" value="<?= $instance->getCustomHomeRedirect(); ?>">
	</div>
</div>

<div class="form-group">
	<label for="maximumMoreLikeThis" class="col-sm-2 control-label">More Like This Display Count:</label>
	<div class="col-sm-6">
		<input type="text" name="maximumMoreLikeThis" id="maximumMoreLikeThis" class="form-control" value="<?= $instance->getMaximumMoreLikeThis(); ?>">
	</div>
</div>

<div class="form-group">
	<label for="defaultTextTruncationHeight" class="col-sm-2 control-label">
		Text Area Widget Collapsed Height (px):
	</label>
	<div class="col-sm-6">
		<input type="text" name="defaultTextTruncationHeight" id="defaultTextTruncationHeight" class="form-control" value="<?= $instance->getDefaultTextTruncationHeight(); ?>">
	</div>
</div>
</fieldset>

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
		if($(".relatedAssetSelectedItem").val().length > 0) {
			relatedAssetPreview($(".relatedAssetSelectedItem").val(),$(".relatedAssetSelectedItem"), $(".assetCompleter"));	
		}
		$('[data-toggle="tooltip"]').tooltip();

		buildAssetAutocomplete($(".assetCompleter"));
		
	});


</script>
<?$this->load->view("handlebarsTemplates");