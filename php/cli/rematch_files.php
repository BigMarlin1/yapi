<?php
require_once("../config.php");
require_once(PHP_DIR."/backend/matchfiles.php");

if (!isset($argv[1]) && !preg_match('/\.bina(er|ries)\./', $argv[1]))
	exit("Used for rematching files using regex (if the regex change).\nex.: php rematch_files.php alt.binaries.teevee\n");

$mf = new matchfiles;
$mf->rematch($argv[1]);
