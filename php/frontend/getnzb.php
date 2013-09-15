<?php
if (isset($_GET['type']) && isset($_GET['subject']) && isset($_GET['identifier']) && isset($_GET['group']))
{
	require_once('config.php');
	require_once(PHP_DIR.'backend/nzb.php');
	$nzb = new nzb;
	$subject = substr($_GET['subject'], 0, 150);
	$nzbfile = $nzb->createNZB($_GET['type'], $_GET['identifier'], $_GET['group'], $subject);
	if ($nzbfile === false)
		print "Not Found, data doesn't exist?.";
	else
	{
		header('Content-type: application/x-nzb');
		header('Content-Disposition: attachment; filename="'.preg_replace('/\.$|[^\w.\[\]()-]/', '', $subject).'.nzb"');
		print $nzbfile;
	}
}
else
	print 'Invalid request, make sure you sent all the variables.';
?>
