<?php

// Extra functions not in php, or not working well with raintpl.

// Convert unixtime to secs/mins/hrs/days since now.
function utsince($time)
{
	$diff = time() - $time;
	if ($diff > 172800)
		return floor($diff/(60*60*24))." Days";
	else if ($diff > 86400)
		return floor($diff/(60*60*24))." Day";
	else if ($diff > 3600)
		return round($diff/(60*60), 1)." Hours";
	else if ($diff > 99)
		return round($diff/60)." Mins";
	else
		return $diff." Secs";
}

// Convert unixtime to date.
function utdate($time)
{
	return date('Y-m-d H:i:s', $time);
}
