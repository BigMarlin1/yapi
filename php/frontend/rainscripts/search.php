<?php
// Get groups with files.
require_once(PHP_DIR.'backend/groups.php');
$groups = new groups;
$grouparr = $groups->getstarted();
if (count($grouparr) == 0)
	exit("No groups have files.");

// Set the page on first page load.
if (!isset($_GET["page"]))
	$_GET["page"] = 1;

$rcheck = false;
// Get releases.
if (count($_GET) > 1)
{
	if (isset($_GET["subject"]) && isset($_GET["retention"]))
	{
		require_once(PHP_DIR.'backend/files.php');
		$files = new files;
		$farr = $files->getforsearch($_GET["subject"], $_GET["retention"], $_GET["group"], ($_GET["page"] * MAX_PERPAGE) - MAX_PERPAGE);
		if (count($farr) > 0)
		{
			$rcheck = true;
			$totalcount = $files->getsearchcount($_GET["subject"], $_GET["retention"], $_GET["group"]);
		}
	}
}

$type = "search";

$tpl->assign("rcheck", $rcheck);
$tpl->assign("grouparr", $grouparr);
if ($rcheck === true)
{
	$tpl->assign("subject", $_GET["subject"]);
	$tpl->assign("retention", $_GET["retention"]);
	$tpl->assign("group", $_GET["group"]);
	$tpl->assign("filearr", $farr);
	$tpl->assign("nzb_img", '<img src="raintemplates/images/nzb.png" alt="nzb" width="20" height="25">');
	include("paginator.php");
}
$tpl->draw("search");
if ($rcheck === true)
	include("paginator.php");
?>
