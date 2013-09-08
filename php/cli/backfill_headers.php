<?php
require_once("../config.php");
require_once(PHP_DIR."/backend/headers.php");

$e = "Downloads old article headers.\n"
		."Valid first argument: alt.binaries.groupname for 1 group or all for all groups\nValid second argument: the amount of articles to download.\n"
		."ex. 1: php backfill_headers.php alt.binaries.teevee 1000\n"
		."ex. 2: php backfill_headers.php all 1000\n";

if (!isset($argv[1]) || !isset($argv[2]))
	exit($e);
if(strlen($argv[1]) < 2 || !is_numeric($argv[2]))
	exit($e);

$h = new headers(true);
$h->main($argv[1], "backfill", $argv[2]);

?>
