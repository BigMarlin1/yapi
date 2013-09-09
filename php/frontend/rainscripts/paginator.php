<?php
// On your file including the paginator, make a variable $totalcount with the count of the items you want.
if ($totalcount > MAX_PERPAGE)
	$maxpages = ceil($totalcount / MAX_PERPAGE);
else
	$maxpages = 1;

// Set up the page numbers for the first run.
if (!isset($lastpage))
	$lastpage = 0;
if (!isset($curpage))
	$curpage = 1;
if (!isset($nextpage))
	$nextpage = 2;

// If the page was changed, change the page numbers.
$thispage = $_GET['page'];
if ($thispage != $curpage)
{
	$curpage = $thispage;
	$lastpage = $curpage - 1;
	$nextpage = $curpage + 1;
}

$tpl->assign("curpage", $curpage);
$tpl->assign("lastpage", $lastpage);
$tpl->assign("nextpage", $nextpage);
$tpl->assign("maxpages", $maxpages);
switch($type)
{
	case "browseall":
		$tpl->draw("brallpaginator");
		break;
	case "browse":
		$tpl->draw("brzpaginator");
		break;
	case "nzbcontents":
		$tpl->draw("nzbcpaginator");
		break;
}
?>
