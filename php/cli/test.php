<?php
require_once('../config.php');
require_once(PHP_DIR.'/backend/db.php');
require_once(PHP_DIR.'/backend/nntp.php');

// Test the DB connection.
echo 'Testing the database connection:\n';
$db = new DB;
var_dump($db->query('SELECT 1 = 1'));
sleep(3);

// Test the NNTP connection.
echo "\nTesting the nntp connection:\n";
$nntp = new NNTP;
var_dump($nntp->doConnect());
sleep(3);

echo "\nSelecting group alt.binaries.teevee:\n";
var_dump($groupinfo = $nntp->selectGroup('alt.binaries.teevee'));
sleep(3);

if (isset($groupinfo['last']) && strlen($groupinfo['last']) > 0)
{
	echo "\nDownloading last article from the group:\n";
	// 2nd arg true uses the field names from the header as keys in the array.
	var_dump($nntp->getOverview($groupinfo['last'].'-'.$groupinfo['last'], true, false));
	sleep(1);
}

echo "\nDisconnecting from usenet.\n";
$nntp->doQuit();
sleep(1);

exit("Finished all tests.\n");
