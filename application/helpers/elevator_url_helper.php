<?

// strip leading http/https from a string
function stripHTTP($source) {

	$parsedURL = parse_url($source);
	if(!array_key_exists("scheme", $parsedURL)) {
		return $source;
	}
	return str_ireplace($parsedURL["scheme"] . ":", "", $source);

}