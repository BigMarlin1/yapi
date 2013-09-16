<?php
/*
 * Functions to be used in various scripts.
 */

// Download URL using curl.
function getUrl($url, $method='get', $postdata='')
{
	$ch = curl_init();
	if ($method == 'post')
	{
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
	}
	curl_setopt($ch, CURLOPT_URL, $url);
	$header[] = 'Accept-Language: en-us';
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_TIMEOUT, 15);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

	$buffer = curl_exec($ch);
	$err = curl_errno($ch);
	curl_close($ch);

	if ($err != 0)
		return false;
	else
		return $buffer;
}
?>
