<?php
require_once('../config.php');
require_once(PHP_DIR.'/backend/groups.php');

$e = "Deletes all headers, resets the group status for the group.\nUsage:\nphp reset_group.php alt.binaries.teevee  => Resets a specific group.\nphp reset_group.php all                  => Resets all groups.\n";
if (!isset($argv[1]))
	exit($e);

$groups = new groups;
if ($argv[1] == 'all')
{
	$gs = $groups->tablereset();
	if ($gs > 0)
		echo $gs." group(s) sucesfully reset.\n";
	else
		echo "There was a problem resetting the grou(p), please do it manually through mysql.\n";
}
else if (preg_match('/\.bina(er|ries)\./', $argv[1]))
{
	$gs = $groups->tablereset($argv[1]);
	if ($gs > 0)
		echo 'Group '.$argv[1]." sucesfully reset.\n";
	else
		echo "There was a problem resetting the group, please do it manually through mysql.\n";
}
else
	exit($e);
?>
