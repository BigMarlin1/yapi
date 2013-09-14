<?php
require_once('../config.php');
require_once(PHP_DIR.'/backend/groups.php');

$e = "Enable or disable a groups for backfill or forward.\nUsage: php group_toggle.php enable backfill alt.binaries.teevee\n";
if($argc !== 4)
	exit($e);

if ($argv[1] != ('enable' || 'disable'))
	exit($e);

if ($argv[2] != ('forward' || 'backfill'))
	exit($e);

if (!preg_match('/\.bina(er|ries)\./', $argv[3]))
	exit($e);

$groups = new groups;

$grow = $groups->grouptoggle($argv[1], $argv[2], $argv[3]);

$f = $b = 'off';
if ($grow['factive'] == 1)
	$f = 'on';

if ($grow['bactive'] == 1)
	$b = 'on';

echo "The new status of the group follow:\nBackfill: {$b}\nForward: {$f}\n";
?>
