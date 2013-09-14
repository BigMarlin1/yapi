<?php
require_once('../config.php');
require_once(PHP_DIR.'/backend/headers.php');

$e = "Downloads old article headers.\n"
		."Valid first argument: alt.binaries.groupname for 1 group or all for all groups\n"
		."Valid second argument: the amount of articles to download.\n"
		."Valid third argument: false: use first nntp provider true: use second nntp provider.\n"
		."ex. 1: php backfill_headers.php alt.binaries.teevee 1000 true\n"
		."ex. 2: php backfill_headers.php all 1000 false\n";

if (!isset($argv[1]) || !isset($argv[2]) || !isset($argv[3]))
	exit($e);
if(strlen($argv[1]) < 2 || !is_numeric($argv[2]))
	exit($e);
if (strlen($argv[3]) < 4)
	exit($e);

$a = false;
if ($argv[3] == 'true')
	$a = true;

$h = new headers(true);
$h->main($argv[1], 'backfill', $argv[2], $a);
?>
