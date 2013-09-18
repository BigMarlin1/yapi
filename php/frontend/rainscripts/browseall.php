<?php
if (!isset($_GET['page']))
	$_GET['page'] = 1;

// Set the offset for paginator.
$offset = ($_GET['page'] * MAX_PERPAGE) - MAX_PERPAGE;

// Get the releases.
require_once(PHP_DIR.'backend/files.php');
$files = new files;
$farr = $files->getforbrowse($offset);
if (count($farr) === 0)
	exit('No releases in the database. Or an error occured.');

// Total amount of releases - for paginator.
$totalcount = $files->getbrowsecount();
if ($totalcount === false)
	exit('No releases in the database. Or an error occured.');

$type = 'browseall';
$tpl->assign(array('filearr' => $farr, 'nzb_img' => '<img src="raintemplates/images/nzb.png" alt="nzb" width="20" height="25">'));
include('paginator.php');
$tpl->draw('browseall');
include('paginator.php');
?>
