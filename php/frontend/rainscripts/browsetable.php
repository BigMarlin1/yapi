<?php
// Check if the group has the table created.
require_once(PHP_DIR.'backend/groups.php');
$groups = new groups;
$grp = $groups->getgroupinfo($grpid);
if ($grp['tstatus'] == 0)
	exit('No releases in the database for group '.$grp['name']);

// Set the offset for paginator.
$offset = ($_GET['page'] * MAX_PERPAGE) - MAX_PERPAGE;

// Get the releases.
require_once(PHP_DIR.'backend/files.php');
$files = new files;
$farr = $files->getallforgroup($grpid, $offset);
if (count($farr) === 0)
	exit('No releases in the database for group '.$grp['name']);

// Total amount of releases - for paginator.
$totalcount = $files->getcount($grpid);

$type = 'browse';
$tpl->assign(array('group' => $grp, 'filearr' => $farr,'nzb_img' => '<img src="raintemplates/images/nzb.png" alt="nzb" width="20" height="25">'));
include('paginator.php');
$tpl->draw('browsetable');
include('paginator.php');
?>
