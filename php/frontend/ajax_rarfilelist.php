<?php
if (!isset($_REQUEST['chash']) || !isset($_REQUEST['group']))
	print 'ERROR: Identifier and group is required.';
else
{
	require_once('config.php');
	require_once(PHP_DIR.'/backend/files.php');
	$f = new files;
	$files = $f->getrarfiles($_REQUEST['chash'], $_REQUEST['group']);

	if (count($files) == 0)
		print "No files";
	else
	{
		print "<ul>\n";
		foreach ($files as $f)
			print "<li>".htmlentities($f['ifname'], ENT_QUOTES)."&nbsp;</li>\n";
		print "</ul>";
	}
}
?>
