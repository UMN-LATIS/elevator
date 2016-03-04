<div class="row rowContainer">
	<div class="col-md-9">
		<form method="post" class="form-horizontal" accept-charset="utf-8" action="<?= instance_url("instances/savePage/"); ?>" />
			<input type="hidden" name="pageId"  value="<?= isset($page)?$page->getId():null; ?>" /><br />
			<div class="form-group">
				<label for="inputTitle" class="col-sm-2 control-label">Title:</label>
				<div class="col-sm-6">
					<input type="text" name="title" id="inputTitle" class="form-control" value="<?= $page->getTitle() ?>" >
				</div>
			</div>

			<div class="form-group">
				<label for="inputParent" class="col-sm-2 control-label">Body:</label>
				<div class="col-sm-10">
					<textarea rows=20 class="form-control" id="bodyText" name="body"><?=$page->getBody()?></textarea>
				</div>
			</div>


	<div class="form-group">
			<div class="col-sm-offset-2 col-sm-8">
				<label>
					<input type="checkbox" id="includeInHeader" name="includeInHeader" value="On" <?=$page->getIncludeInHeader()?"checked":null?>>
					Include In Header
				</label>
			</div>
		</div>


			<input type="submit" name="submit" value="Update Page" class='btn btn-primary' />

		</form>
	</div>
</div>

<script type="text/javascript">
$(document).ready(function() {
	tinymce.init({
	    selector: "textarea",
	    menubar : false,
	    toolbar: "undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | code | link",
	    plugins: ["link", "code"]
	 });
});
</script>