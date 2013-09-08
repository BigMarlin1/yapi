<?php
require_once(PHP_DIR.'backend/groups.php');
$groups = new groups;
$garr = $groups->getallsortname();
if (count($garr) === 0)
	exit("No groups in the database.");

function getfcount($gid)
{
	$groups = new groups;
	return $groups->getfilecount($gid);
}

function getccount($gid)
{
	$groups = new groups;
	return $groups->getcollectioncount($gid);
}

$tpl->assign("grouparr", $garr);
$tpl->draw("groupslist");
?>
