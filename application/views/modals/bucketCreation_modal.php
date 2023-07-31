
<div class="modal fade" id="bucketCreationModal">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title">Create an S3 Bucket</h4>
      </div>
      <div class="modal-body">
        <form action="" method="POST" id="bucketCreationForm" class="form-horizontal" role="form">
          <p>
            This process will create a new IAM user with a single bucket and permissions for that bucket.  This user will not have the permissions necessary to delete originals from this bucket.  An IAM user with an associated multifactor device will be necessary for that.
          </p>
            <div class="form-group">
              <label for="inputS3 Key" class="col-sm-2 control-label">S3 Key:</label>
              <div class="col-sm-10">
                <input type="text" name="s3Key" id="inputS3Key" class="form-control" value="" required="required" pattern="" title="" autocomplete="off">
              </div>
            </div>
            <div class="form-group">
              <label for="inputS3 Secret" class="col-sm-2 control-label">S3 Secret:</label>
              <div class="col-sm-10">
                <input type="text" name="s3Secret" id="inputS3Secret" class="form-control" value="" required="required" pattern="" title="" autocomplete="off">
              </div>
            </div>

            <div class="form-group">
              <label for="inputBucket Name" class="col-sm-2 control-label">Bucket Name:</label>
              <div class="col-sm-10">
                <input type="text" name="name" id="inputBucketName" class="form-control" value="" required="required" pattern="" title="" autocomplete="off">
                <span class="help-block">'elevator' will automatically be added as a prefix to the bucket name.</span>
              </div>

            </div>

            <div class="form-group">
              <label for="inputRegion" class="col-sm-2 control-label">Region:</label>
              <div class="col-sm-10">
                <input type="text" name="region" id="inputRegion" class="form-control" value="us-east-1" required="required" pattern="" title="" autocomplete="off">
              </div>
            </div>
             <div class="form-group">
              <div class="col-sm-offset-2 col-sm-10">
                <div class="checkbox">
                  <label>
                    <input name="useStandardIA" type="checkbox" CHECKED>Enable Standard IA
                  </label>
                </div>
              </div>
            </div>
            <div class="form-group">
              <div class="col-sm-offset-2 col-sm-10">
                <div class="checkbox">
                  <label>
                    <input name="useLifecycle" type="checkbox">Enable Glacier
                  </label>
                </div>
              </div>
            </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary createBucket">Create Bucket</button>
      </div>
    </div>
  </div>
</div>


<script>


$(".createBucket").on("click", function() {
  $.post( basePath +"instances/buildIAMandCreateBucket", $( "#bucketCreationForm" ).serialize(), function(data) {

    decoded = JSON.parse(data);
    if(decoded.error) {
      alert(decoded.error);
    }
    else {
      bucketCreationCallback(decoded.accessKey, decoded.secretKey, decoded.bucketName, decoded.bucketRegion);
      $('#bucketCreationModal').modal('hide')

    }


  });
});

</script>