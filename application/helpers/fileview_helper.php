	<?php


function renderFileMenu($menuArray) {
	$outputText = '';
	$outputText .= '<div class="row infoRow"><div class="col-md-12">';

	if(isset($menuArray['fileInfo'])) {
		$outputText .= '<span class="glyphicon glyphicon-info-sign infoPopover" aria-haspopup="true" aria-expanded="false" role="button" data-placement="bottom" data-toggle="popover" title="File Info" data-html=true data-content=\'<ul class="list-group">';
		foreach($menuArray['fileInfo'] as $entryKey=>$fileInfoEntry) {
			if($entryKey == "Location") {
				$outputText .= '<li class="list-group-item assetDetails"><strong>Location: </strong><A href="#mapModal"  data-toggle="modal" data-latitude="' . $fileInfoEntry['latitude'] . '" data-longitude="' . $fileInfoEntry['longitude'] . '">View Location</a></li>';
			}
			else if($entryKey == "Exif") {
				$outputText .= '<li class="list-group-item assetDetails"><strong>' . $entryKey . ':</strong> <a href="" class="exifToggle" data-fileobject="' . $fileInfoEntry . '">View Exif</a></li>';
			}
			else {
				$outputText .= '<li class="list-group-item assetDetails"><strong>' . $entryKey . ':</strong> ' . htmlentities($fileInfoEntry, ENT_QUOTES) . ' </li>';	
			}
		}
		$outputText .= '</ul>\'></span>';
	}
	
	if(isset($menuArray['download'])) {

		$outputText .= ' <span class="glyphicon glyphicon-download infoPopover" aria-haspopup="true" role="button" aria-expanded="false" data-placement="bottom" data-toggle="popover" title="Download" data-html="true" data-content=\'<ul>';
		foreach($menuArray['download'] as $downloadKey=>$downloadEntry) {
			$outputText .= '<li class="list-group-item assetDetails"><a href="' . $downloadEntry . '">' . $downloadKey . '</a></li>';
		}
		$outputText .= '</ul>\'></span>';

	}
	if(isset($menuArray['embed'])) {
		$outputText .= '  <span class="glyphicon glyphicon-share infoPopover" aria-haspopup="true" aria-expanded="false" role="button" data-placement="bottom" data-toggle="popover" title="Share" data-html="true" data-content=\'<ul>';
		$outputText .= '<li class="list-group-item assetDetails"><strong>Embed: </strong><input class="form-control embedControl frameEmbed" value="' . htmlspecialchars($menuArray['embed'], ENT_QUOTES) . '"></li>';
		$outputText .= '<li class="list-group-item assetDetails"><strong>Link: </strong><input class="form-control embedControl linkEmbed" value="' . htmlspecialchars("http:".$menuArray['embedLink'], ENT_QUOTES) . '"></li>';
		$outputText .= '<li class="list-group-item assetDetails"><strong>Open in Window: </strong><a onclick="' . htmlspecialchars('window.open(\'http:'.$menuArray['embedLink'] . '\', \'_blank\', \'location=yes,height=600,width=800,scrollbars=yes,status=yes\')', ENT_QUOTES) . '">Open</a></li>';
		$outputText .= '</ul>\'></span>';
	}

	if(isset($menuArray['excerpt'])) {
		$outputText .= ' <span data-toggle="collapse" data-target="#excerptGroup" class="glyphicon glyphicon-time excerptTooltip" data-toggle="tooltip" title="Create Excerpt"></span>';
	}

	if(isset($menuArray['zoom'])) {
		$outputText .= '<span></span><input type="range" class="zoom-range" min=20 value=20>
			<span class="canFullscreen glyphicon glyphicon-resize-full" data-toggle="tooltip" title="Fullscreen"></span>';
	}
	
	$outputText .= "</div></div>";

	return $outputText;

}


?>
