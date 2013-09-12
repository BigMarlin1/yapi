<?php
require_once("../config.php");
require_once(PHP_DIR."/backend/nfo.php");

if ($argc === 1)
	exit("Downloads NFOs for headers.\n"
		."1 argument, wether to echo or not.\n"
		."ex. 1: php match_nfos.php true\n"
		."ex. 2: php match_nfos.php false\n");

if ($argv[1] == "true")
{
	$n = new nfo(true);
	$n->scanfornfo();
}
else
{
	$n = new nfo();
	$n->scanfornfo();
}

?>
