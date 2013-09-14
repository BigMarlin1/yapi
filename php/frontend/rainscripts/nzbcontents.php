<?php
// Set the offset for paginator.
$offset = ($_GET['page'] * MAX_PERPAGE) - MAX_PERPAGE;

require_once(PHP_DIR.'backend/files.php');
$files = new files;
$totalcount = $files->countforbnzbcontents($chash, $group);
$contents = $files->getforbnzbcontents($chash, $group, $offset);

$type = 'nzbcontents';
$tpl->assign(array('contents' => $contents, 'group' => $group, 'chash' => $chash, 'subject' => $subject, 'nzb_img' => '<img src="raintemplates/images/nzb.png" alt="nzb" width="20" height="25">'));
include('paginator.php');
$tpl->draw('nzbcontents');
include('paginator.php');
?>
