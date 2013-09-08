<?php
require_once(PHP_DIR.'backend/files.php');
$files = new files;
require_once(PHP_DIR.'backend/groups.php');
$groups = new groups;
$file = $files->getforbnzb($chash, $group);
$grp = $groups->getgroupinfo($group);
$tpl->assign("file", $file);
$tpl->assign("nzb_img", '<img src="raintemplates/images/nzb.png" alt="nzb" width="20" height="25">');
$tpl->assign("group", $grp);
$tpl->draw("browsenzb");
?>
