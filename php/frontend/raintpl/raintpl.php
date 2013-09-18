<?php
// Include this in your php file, then you just need to set the vars and draw the page.
require_once(dirname(__FILE__).'/../../config.php');
require_once('rain.tpl.class.php');
require_once('functions.php');
$tpl = new RainTPL();
$string = $_SERVER['SERVER_NAME'];
if (isset($_SERVER['SERVER_PORT']))
{
	if ($_SERVER['SERVER_PORT'] != 80)
		$string .= ':'.$_SERVER['SERVER_PORT'];
}
$tpl->assign('serveraddress', $string);
raintpl::$tpl_dir = PHP_DIR.'/frontend/raintemplates/';
raintpl::$cache_dir = PHP_DIR.'/frontend/raintpl/tmp/';
raintpl::configure('php_enabled', true );
?>
