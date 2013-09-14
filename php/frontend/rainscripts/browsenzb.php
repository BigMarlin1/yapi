<?php
require_once(PHP_DIR.'backend/files.php');
$files = new files;
$file = $files->getforbnzb($chash, $group);
require_once(PHP_DIR.'backend/groups.php');
$groups = new groups;
$grp = $groups->getgroupinfo($group);
$tpl->assign(array('file' => $file, 'nzb_img' => '<img src="raintemplates/images/nzb.png" alt="nzb" width="20" height="25">', 'group' => $grp));
$tpl->draw('browsenzb');
?>
