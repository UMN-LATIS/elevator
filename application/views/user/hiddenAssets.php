<?if(!$isOffset):?>
<script>
var offset = 0;
</script>


<div class="row rowContainer">
	<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
		<table id="resultsTable" class="table table-hover">
			<thead>
					<th>Id</th>
					<th>Title</th>
					<th>Template</th>
					<th>Modified</th>
					<th>Ready for Display</th>
			</thead>
			<tbody>
				<?endif?>
				<?foreach($hiddenAssets as $asset):?>
				<tr>
					<?if(isset($asset["deleted"])):?>
					<td><A href="<?=instance_url("/assetManager/restoreAsset/".$asset['objectId'])?>"><?=$asset['objectId']?></a></td>
					<?else:?>
					<td><A href="<?=instance_url("/assetManager/editAsset/".$asset['objectId'])?>"><?=$asset['objectId']?></a></td>
					<?endif?>
					<td><?=$asset['title']?></td>
					<td><?=($this->asset_template->getTemplate($asset['templateId'])!==null)?$this->asset_template->getTemplate($asset['templateId'])->getName():null?></td>
					<td><?=$asset["modifiedDate"]->setTimezone(new DateTimeZone('America/Chicago'))->format("m/d/y H:i:s")?></td>
					<td><?=$asset["readyForDisplay"]?"yes":"no"?></td>
				</tr>
				<?endforeach?>
				<?if(!$isOffset):?>
			</tbody>
		</table>
	</div>
</div>

<script>
$(document).ready(function() {
    $('#resultsTable').DataTable( {
    	"paging":   false,
    	"ordering": true,
    	"info": false,
        "order": [[ 3, "desc" ]],
        "columns":[
            {
                "sortable": false
            },
            {
                "sortable": true
            },
            {
                "sortable": true
            },
            {
                "sortable": true
            },
            {
                "sortable": true
            }
        ]
    } );
} );	
</script>

<?endif?>