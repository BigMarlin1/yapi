<?php
require_once(PHP_DIR.'backend/files.php');
$files = new files;
require_once(PHP_DIR.'backend/groups.php');
$groups = new groups;
$file = $files->getforbnzb($chash, $group);
$grp = $groups->getgroupinfo($group);
$tpl->assign("file", $file);
$tpl->assign("group", $grp);
$tpl->draw("browsenzb");
?>
