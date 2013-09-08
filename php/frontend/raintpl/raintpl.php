<?php
// Include this in your php file, then you just need to set the vars and draw the page.
require_once("../config.php");
require_once("rain.tpl.class.php");
require_once("functions.php");
$tpl = new RainTPL();
raintpl::$tpl_dir = PHP_DIR."/frontend/raintemplates/";
raintpl::$cache_dir = PHP_DIR."/frontend/raintpl/tmp/";
raintpl::configure("php_enabled", true );
?>
