<?php
require_once('../config.php');
require_once(PHP_DIR.'/backend/passwordchecking.php');

if ($argc != 3)
	exit("Check passwords/encryption on files. 2 Arguments, both true or false.\n"
		."Argument 1, wether to echo or not.\n"
		."Argument 2, try to download previously failed files with the second NNTP provider.\n"
		."ex. 1: php check_passwords.php true false\n"
		."ex. 2: php check_passwords.php false false\n");

$echo = true;
if ($argv[1] == 'false')
	$echo = false;

$alt = false;
if ($argv[2] == 'true')
	$alt = true;

$n = new PChecking($echo);
$n->startChecking($alt);
?>
