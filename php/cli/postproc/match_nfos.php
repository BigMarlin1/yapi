<?php
if ($argc != 3)
	exit("Downloads NFOs for headers. 2 Arguments, both true or false.\n"
		."Argument 1, wether to echo or not.\n"
		."Argument 2, try to download previously failed NFO's with the second NNTP provider.\n"
		."ex. 1: php match_nfos.php true false\n"
		."ex. 2: php match_nfos.php false false\n");

require_once(dirname(__FILE__).'/../../config.php');
require_once(PHP_DIR.'backend/nfo.php');

$echo = true;
if ($argv[1] == 'false')
	$echo = false;

$alt = false;
if ($argv[2] == 'true')
	$alt = true;

$n = new nfo($echo);
$n->scanfornfo($alt);
?>
