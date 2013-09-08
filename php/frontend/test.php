<?php
require_once("/var/www/yapi/php/backend/nzb.php");
$nzb = new nzb;
$subject = "test";
$nzbfile = $nzb->createNZB("multi", "644b81b64d12346d2bacb7e4b2f3fb2a6d2b2570", "2", substr($subject, 0, 100));
if ($nzbfile === false)
	echo "Unable to create NZB file.\n";
else
{
	header("Content-type: text/plain");
	header("Content-Disposition: attachment; filename=".uniqid().".nzb");
	print $nzbfile;
}

?>
