<?php
/* API based on nzedb/newznab (to make it compatible with sickbeard/couchpotato without too many mods), with some features removed.
 * No categories/genres/registration for example.
 */

include('raintpl/raintpl.php');

// API functions.
if (isset($_GET["t"]))
{
	if ($_GET["t"] == "search" || $_GET["t"] == "s" )
		$function = "s";
	elseif ( $_GET["t"] == "details" || $_GET["t"] == "d")
		$function = "d";
	elseif ( $_GET["t"] == "get" || $_GET["t"] == "g")
		$function = "g";
	elseif ($_GET["t"] == "caps" || $_GET["t"] == "c")
		$function = "c";
	else
		showApiError(202);
}
elseif (isset($_GET["h"]))
{
	?>
	<html>
	<?php include('rainscripts/head.php'); ?>
	<body>
		<div id="wrapper">
			<?php 
				include('rainscripts/header.php'); 
				include('rainscripts/nav.php');
			?>
			<div id="content">
				<div id="innercontent">
					<?php include('rainscripts/apihelp.php'); ?>
				</div>
			</div>
			<?php include('rainscripts/footer.php'); ?>
		</div>
	</body>
	</html>
	<?php
	exit();
}
else
	showApiError(200);

// Output is either json or xml.
$outputtype = "xml";
if (isset($_GET["o"]))
{
	if ($_GET["o"] == "json")
		$outputtype = "json";
}

switch ($function)
{
	case "s":
		if (!isset($_GET["q"]))
			showApiError(302);
		if ($_GET["q"] == "")
			showApiError(200);

		$limit = 100;
		if (isset($_GET["limit"]) && is_numeric($_GET["limit"]) && $_GET["limit"] < 100)
			$limit = $_GET["limit"];

		$group = $minsize = $maxsize = $maxage = $offset = 0;
		if (isset($_GET["maxage"]) && $_GET["maxage"] != "" && is_numeric($_GET["maxage"]))
			$maxage = $_GET["maxage"];

		if (isset($_GET["offset"]) && is_numeric($_GET["offset"]))
			$offset = $_GET["offset"];

		if (isset($_GET["group"]))
			$group = $_GET["group"];

		if (isset($_GET["minsize"]) && is_numeric($_GET["minsize"]))
			$minsize = $_GET["minsize"];

		if (isset($_GET["maxsize"]) && is_numeric($_GET["maxsize"]))
			$maxsize = $_GET["maxsize"];

		require_once(PHP_DIR."backend/files.php");
		$files = new files;
		$farr = $files->apisearch($_GET["q"], $maxage, $group, $offset, $limit, $minsize, $maxsize);
		$fcount = count($farr);
		if ($fcount === 0)
			showApiError(301);

		if ($outputtype == "xml")
		{
			$tpl->assign(array('farr' => $farr, "offset" => $offset, "fcount" => $fcount, "web_name" => WEB_NAME, "admin_email" => ADMIN_EMAIL, "query" => $_GET["q"]));
			header("Content-type: text/xml");
			$tpl->draw('apiresult');
		}
		// TODO: Make that a more specific array of data to return rather than resultset.
		else
			echo json_encode($farr);
		break;

	// Get NZB. Optional search by group.
	case "g":
		if (!isset($_GET["id"]))
			showApiError(200);

		$gid = "all";
		if (isset($_GET["gid"]))
			$gid = $_GET["gid"];

		require_once(PHP_DIR."backend/files.php");
		$files = new files;
		$farr = $files->getbychash($_GET["id"], $gid);
		if ($farr !== false)
			header("Location:".WWW_TOP."/getnzb.php?identifier=".$farr["chash"]."&subject=".urlencode($farr["subject"])."&group=".$farr["groupid"]."&type=multi");
		else
			showApiError(300);
		break;

	// Get individual nzb details. Optional search by group.
	case "d":
		if (!isset($_GET["id"]))
			showApiError(200);

		$gid = "all";
		if (isset($_GET["gid"]))
			$gid = $_GET["gid"];

		require_once(PHP_DIR."backend/files.php");
		$files = new files;
		$farr = $files->getbychash($_GET["id"], $gid);
		if ($farr)
			$reldata[] = $data;
		else
			showApiError(300);

		if ($outputtype == "xml")
		{
			$tpl->assign('farr', $farr);
			header("Content-type: text/xml");
			$tpl->draw('apidetail');
		}
		// TODO: Make that a more specific array of data to return rather than resultset.
		else
			echo json_encode($data);

		break;

	// Capabilities request.
	case "c":
		require_once(PHP_DIR."backend/groups.php");
		$groups = new groups;
		$tpl->assign(array("groups" => $groups->getallsortname(), "web_name" => WEB_NAME, "admin_email" => ADMIN_EMAIL, "logopath" => $string.'/raintemplates/images/logo.png'));
		header("Content-type: text/xml");
		$tpl->draw('apicaps');
		break;

	default:
		showApiError(202);
		break;
}

function showApiError($errcode=900, $errtext="")
{
	switch ($errcode)
	{
		case 200:
			$errtext = "Missing base parameter for the API (h or t is valid)";
			break;
		case 202:
			$errtext = "No such function, please see the API Help page";
			break;
		case 300:
			$errtext = "No collection exists with this hash";
			break;
		case 301:
			$errtext = "No collections found";
			break;
		case 302:
			$errtext = "You must supply a query for your search";
			break;
		default:
			$errtext = "Unknown error";
			break;
	}

	header("Content-type: text/xml");
	echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
	echo "<error code=\"$errcode\" description=\"$errtext\"/>\n";
	die();
}

?>
