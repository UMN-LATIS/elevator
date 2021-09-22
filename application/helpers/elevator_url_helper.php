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
function autolink_elevator($str, $type = 'both', $popup = FALSE)
{
	// Find and replace any URLs.
	if ($type !== 'email' && preg_match_all('#(\w*://|www\.)[^\s()<>;]+\w#i', $str, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER))
		{
			// Set our target HTML if using popup links.
			$target = ($popup) ? ' target="_blank"' : '';
			
			// We process the links in reverse order (last -> first) so that
			// the returned string offsets from preg_match_all() are not
			// moved as we add more HTML.
			foreach (array_reverse($matches) as $match)
			{
				// $match[0] is the matched string/link
				// $match[1] is either a protocol prefix or 'www.'
				//
				// With PREG_OFFSET_CAPTURE, both of the above is an array,
				// where the actual value is held in [0] and its offset at the [1] index.
				$a = '<a href="'.(strpos($match[1][0], '/') ? '' : 'http://').$match[0][0].'"'.$target.'>'.$match[0][0].'</a>';
				$str = substr_replace($str, $a, $match[0][1], strlen($match[0][0]));
			}
		}
		
		// Find and replace any emails.
		if ($type !== 'url' && preg_match_all('#([\w\.\-\+]+@[a-z0-9\-]+\.[a-z0-9\-\.]+[^[:punct:]\s])#i', $str, $matches, PREG_OFFSET_CAPTURE))
		{
			foreach (array_reverse($matches[0]) as $match)
			{
				if (filter_var($match[0], FILTER_VALIDATE_EMAIL) !== FALSE)
				{
					$str = substr_replace($str, mailto($match[0]), $match[1], strlen($match[0]));
				}
			}
		}
		
		return $str;
	}

function getFinalURL($url)
{
    $ch = curl_init();

	// set URL and other appropriate options
	curl_setopt($ch, CURLOPT_URL,$url);
	curl_setopt($ch, CURLOPT_HEADER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_NOBODY, true);
	curl_exec($ch);
	$outputURL = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

	// close cURL resource, and free up system resources
	curl_close($ch);
	if($outputURL) {
		return $outputURL;
	}
	else {
		return $url;
	}
}


function render_json($source, $status = 200) {
	$CI =& get_instance();
	return $CI->output
        ->set_content_type('application/json')
        ->set_status_header($status)
        ->set_output(json_encode($source));

}