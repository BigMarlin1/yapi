<?php
require_once(PHP_DIR.'backend/files.php');
$files = new files;

// Set the offset for paginator.
if ($_GET["page"] == 1)
	$offset = 0;
else
	$offset = ($_GET["page"] * MAX_PERPAGE) - MAX_PERPAGE;

$totalcount = $files->countforbnzbcontents($chash, $group);

$contents = $files->getforbnzbcontents($chash, $group, $offset);
$type = "nzbcontents";
$tpl->assign("contents", $contents);
$tpl->assign("group", $group);
$tpl->assign("chash", $chash);
$tpl->assign("subject", $subject);
$tpl->assign("nzb_img", '<img src="raintemplates/images/nzb.png" alt="nzb" width="20" height="25">');
include("paginator.php");
$tpl->draw("nzbcontents");
include("paginator.php");
?>
