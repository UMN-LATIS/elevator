<?

// strip leading http/https from a string
function stripHTTP($source) {

	$parsedURL = parse_url($source);
	if(!array_key_exists("scheme", $parsedURL)) {
		return $source;
	}
	return str_ireplace($parsedURL["scheme"] . ":", "", $source);

}

function getClickToSearchLink($widgetModel, $linkText, $displayText = null) {
	if(!$widgetModel->getClickToSearch()) {
		return $linkText;
	}
	$linkText = trim($linkText);
	if(!$displayText) {
		$displayText = $linkText;
	}

	$linkText = str_ireplace("?", "", $linkText);
	$linkText = str_ireplace("...", "", $linkText);

	if($widgetModel->getClickToSearchType() == 0) {
		return "<A href=\"".instance_url("/search/querySearch/". rawurlencode($linkText)) ."\">".$displayText."</a>";
	}

	if($widgetModel->getClickToSearchType() == 1) {
		return "<A href=\"".instance_url("/search/scopedQuerySearch/". $widgetModel->getFieldTitle() . "/". rawurlencode($linkText)) ."\">".$displayText."</a>";
	}


}