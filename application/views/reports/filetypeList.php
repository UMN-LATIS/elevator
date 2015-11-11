<?
 function formatSizeUnits($bytes)
    {
        if ($bytes >= 1073741824)
        {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        }
        elseif ($bytes >= 1048576)
        {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        }
        elseif ($bytes >= 1024)
        {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        }
        elseif ($bytes > 1)
        {
            $bytes = $bytes . ' bytes';
        }
        elseif ($bytes == 1)
        {
            $bytes = $bytes . ' byte';
        }
        else
        {
            $bytes = '0 bytes';
        }

        return $bytes;
}?>


<div class="row rowContainer">
	<div class="col-sm-12">

		<table class="table table-striped">
		<thead>
			<tr>
                <td>File Type</td>
				<td>Count</td>
				<td>Size</td>
			</tr>
		</thead>
		<?foreach($results as $result):?>
			<tr>
                <td><?=$result["_id"]?></td>
				<td><?=$result['value']["count"]?></td>
				<td><?=formatSizeUnits($result["value"]['size'])?></td>
			</tr>
		<?endforeach?>
		</table>

	</div>
</div>