<?php
$e = 	"Downloads new article headers.\n"
		."Valid first argument: alt.binaries.groupname for 1 group or all for all groups.\n"
		."Valid second argument: true / false ; Wether to use the alternate NNTP server or not..\n"
		."ex. 1: php update_headers.php alt.binaries.teevee true : Will get new headers for this group using the second provider.\n"
		."ex. 2: php update_headers.php all false : Will get new headers for all groups using the first provider.\n";

if ($argc === 2 || !isset($argv[1]) || !isset($argv[2]))
	exit($e);

if (strlen($argv[1]) < 3 && ($argv[2] != 'false' || $argv[2] != 'true'))
	exit($e);

require_once(dirname(__FILE__).'/../../config.php');
require_once(PHP_DIR."backend/headers.php");
$a = false;
if ($argv[2] == 'true')
	$a = true;

$h = new headers(true);
$h->main($argv[1], 'forward', '', $a);
?>
