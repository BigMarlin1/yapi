<?php
require_once('config.php');
require_once(PHP_DIR.'backend/nzb.php');
$nzb = new nzb;
$subject = substr($_GET['subject'], 0, 150);
$nzbfile = $nzb->createNZB($_GET['type'], $_GET['identifier'], $_GET['group'], $subject);
if ($nzbfile === false)
	echo "Unable to create NZB file.\n";
else
{
	header("Content-type: text/plain");
	header("Content-Disposition: attachment; filename=".preg_replace('/\.$|[^\w.-\[\]()]/', '', $subject).".nzb");
	print $nzbfile;
}

?>
