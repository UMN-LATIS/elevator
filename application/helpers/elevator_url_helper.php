<?

// strip leading http/https from a string
function stripHTTP($source) {
	$parsedURL = parse_url($source);
	if(!array_key_exists("scheme", $parsedURL)) {
		return $source;
	}
	return str_ireplace($parsedURL["scheme"] . ":", "", $source);
}

// strip leading http/https:// from a string
function matchScheme($source) {
	$parsedURL = parse_url($source);
	if(!array_key_exists("scheme", $parsedURL)) {
		return $source;
	}
	$stripped = str_ireplace($parsedURL["scheme"] . ":", "", $source);
	$scheme = empty($_SERVER['HTTPS'])?"http":"https";
	return $scheme . ":" . $stripped;
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

function autolink_elevator($value) {
	$protocols = array('http', 'mail');
	$attributes = ["target"=>"_blank"];
	// Link attributes
	$attr = '';
	foreach ($attributes as $key => $val) {
		$attr = ' ' . $key . '="' . htmlentities($val) . '"';
	}
	
	$links = array();
	
	// Extract existing links and tags
	$value = preg_replace_callback('~(<a .*?>.*?</a>|<.*?>)~i', function ($match) use (&$links) { return '<' . array_push($links, $match[1]) . '>'; }, $value);
	
	// Extract text links for each protocol
	foreach ((array)$protocols as $protocol) {
		switch ($protocol) {
			case 'http':
			case 'https':   $value = preg_replace_callback('~(?:(https?)://([^\s<]+)|(www\.[^\s<]+?\.[^\s<]+))(?<![\.,:])~i', function ($match) use ($protocol, &$links, $attr) { if ($match[1]) $protocol = $match[1]; $link = $match[2] ?: $match[3]; return '<' . array_push($links, "<a $attr href=\"$protocol://$link\">$link</a>") . '>'; }, $value); break;
				case 'mail':    $value = preg_replace_callback('~([^\s<]+?@[^\s<]+?\.[^\s<]+)(?<![\.,:])~', function ($match) use (&$links, $attr) { return '<' . array_push($links, "<a $attr href=\"mailto:{$match[1]}\">{$match[1]}</a>") . '>'; }, $value); break;
				case 'twitter': $value = preg_replace_callback('~(?<!\w)[@#](\w++)~', function ($match) use (&$links, $attr) { return '<' . array_push($links, "<a $attr href=\"https://twitter.com/" . ($match[0][0] == '@' ? '' : 'search/%23') . $match[1]  . "\">{$match[0]}</a>") . '>'; }, $value); break;
				default:        $value = preg_replace_callback('~' . preg_quote($protocol, '~') . '://([^\s<]+?)(?<![\.,:])~i', function ($match) use ($protocol, &$links, $attr) { return '<' . array_push($links, "<a $attr href=\"$protocol://{$match[1]}\">{$match[1]}</a>") . '>'; }, $value); break;
		}
	}
		
		// Insert all link
		return preg_replace_callback('/<(\d+)>/', function ($match) use (&$links) { return $links[$match[1] - 1]; }, $value);
}