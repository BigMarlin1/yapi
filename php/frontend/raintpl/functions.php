<?php

// Extra functions not in php, or not working well with raintpl.

// Convert unixtime to secs/mins/hrs/days since now.
function utsince($time)
{
	$diff = time() - $time;
	if ($diff > 86400)
		return floor($diff/(60*60*24))."d";
	else if ($diff > 3600)
		return round($diff/(60*60), 1)."h";
	else if ($diff > 99)
		return round($diff/60)."m";
	else
		return $diff."s";
}

// Convert unixtime to date: Tue, 10 Sep 2013 14:39:34 -0400
function utdate($time)
{
	return date('D, j M Y G:i:s O', $time);
}

// Replace alt.binaries with a.b
function abreplace($group)
{
	return str_replace('alt.binaries', 'a.b', $group);
}

// Encode a string to be compatible in a URL.
function encodeforurl($str)
{
	return htmlentities($str);
}

// Encode a url to xml compliant.
function xml_entities($url)
{
	return strtr($url, array("<" => "&lt;", ">" => "&gt;", '"' => "&quot;", "'" => "&apos;", "&" => "&amp;", "#" => ""));
}

// Encode a string to xml compliant.
function xml_escape($str)
{
	return strtr($str, array("<" => "_", ">" => "_", '"' => "_", "'" => "_", "&" => "_", "#" => "_"));
}
