<?php
require_once("../config.php");
require_once(PHP_DIR."/backend/headers.php");

if ($argc === 1 || strlen($argv[1]) < 3)
	exit("Downloads new article headers.\n"
		."Valid first argument: alt.binaries.groupname for 1 group or all for all groups.\n"
		."ex. 1: php update_headers.php alt.binaries.teevee\n"
		."ex. 2: php update_headers.php all\n");

$h = new headers(true);
$h->main($argv[1], "forward");

?>
