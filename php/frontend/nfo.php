<?php
if (!isset($_GET['chash']))
	echo 'ERROR: Must supply NFO identifier.';
else
{
	if (!isset($_GET['group']))
		echo 'ERROR: Must supply groupid.';
	else
	{
		require_once('config.php');
		require_once(PHP_DIR.'backend/nfo.php');
		$n = new nfo;
		$nfo = $n->returnNfo($_GET['chash'], $_GET['group'], true);
		if ($nfo != false)
			echo "<pre>$nfo</pre>";
		else
			echo 'ERROR: Problem fetching NFO.';
	}
}
