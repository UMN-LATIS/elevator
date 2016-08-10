<?

// strip leading http/https from a string
function stripHTTP($source) {

	$parsedURL = parse_url($source);
	if(!array_key_exists("scheme", $parsedURL)) {
		return $source;
	}
	return str_ireplace($parsedURL["scheme"] . ":", "", $source);

}

function getClickToSearchLink($widgetModel, $linkText) {
	if(!$widgetModel->getClickToSearch()) {
		return $linkText;
	}

	if($widgetModel->getClickToSearchType() == 0) {
		return "<A href=\"".instance_url("/search/querySearch/". rawurlencode($linkText)) ."\">".$linkText."</a>";
	}

	if($widgetModel->getClickToSearchType() == 1) {
		return "<A href=\"".instance_url("/search/scopedQuerySearch/". $widgetModel->getFieldTitle() . "/". rawurlencode($linkText)) ."\">".$linkText."</a>";
	}


}