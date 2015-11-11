<?
$assetTitle = reset($assetModel->getAssetTitle($collapse=false));
?>
<script>
var objectId = "<?=$assetModel->getObjectId()?>";
</script>

<div class="row">

	<div class="col-md-7" id="embedView" data-objectid="<?=$firstAsset?>">
	</div>

	<div class="col-md-5 rightColumn objectIdHost" data-objectid="<?=$assetModel->getObjectId()?>">

		<div class="row">
			<div class="col-md-12">
				<h3><?=$assetTitle?></h3>
			</div>
		</div>


		<?foreach($assetModel->assetObjects as $widget):?>
		<?if($widget->getDisplay() && $widget->hasContents() && implode("", $widget->getAsText(false)) != $assetTitle):?>
		<div class="row">
			<div class="col-md-12 assetWidget">
				<?=$widget->getView()?>
			</div>
		</div>
		<?endif?>
		<?endforeach?>

	</div>

</div>

<div class="row rowPadding">

			<div class="panel-group" id="relatedAccordian">
				<div class="panel panel-default">
					<div class="panel-heading">
						<h4 class="panel-title">
							<a data-toggle="collapse" data-parent="#relatedAccordian" href="#collapseRelated">
								More Like This <span class="glyphicon glyphicon-chevron-down expandRelated pull-right"></span>
							</a>
						</h4>
					</div>
					<div id="collapseRelated" class="panel-collapse collapse">
						<div class="panel-body sideScrollAssets"  >
							<div class="innerDiv" id="relatedAssets">

							</div>

						</div>
					</div>
				</div>
			</div>
		</div>


<div class="modal fade" id="mapModal" tabindex="-1" role="dialog" aria-labelledby="mapModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title" id="mapModalLabel">.</h4>
      </div>
      <div class="modal-body">
      <div id="mapFrame">
				<div id="mapModalContainer">


				</div>
			</div>
      	<a id="mapNearby" href="#">Nearby Assets</a>
      </div>
      <div class="modal-footer">
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->




<?=$this->load->view("handlebarsTemplates");