<?php
require_once(dirname(__FILE__).'/../../config.php');
require_once(PHP_DIR.'backend/db.php');
$db = new DB();
echo "Optimizing MySQL tables, this can take a while...\n";
$tablecnt = $db->optimise();
if ($tablecnt > 0)
	exit ("Optimized {$tablecnt} MySQL tables succesfuly.\n");
else
	exit ("No MySQL tables to optimize.\n");
?>
