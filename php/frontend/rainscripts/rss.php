<?php
if (isset($_GET['help']))
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
						<?php $tpl->draw('rsshelp'); ?>
					</div>
				</div>
				<?php include('rainscripts/footer.php'); ?>
			</div>
		</body>
	</html>
	<?php
}
else
{
	require_once(PHP_DIR.'backend/files.php');
	$files = new files;
	if (isset($_GET['group']))
	{
		require_once(PHP_DIR.'backend/groups.php');
		$groups = new groups;
		$gid = $groups->getid(str_replace('a.b', 'alt.binaries.', $_GET['group']));

		$farr = $files->getallforgroup($gid, 0, RSS_LIMIT);
	}
	else
		$farr = $files->getforbrowse(0, RSS_LIMIT);

	$tpl->assign(array('farr' => $farr, 'web_name' => WEB_NAME, 'admin_email' => ADMIN_EMAIL));
	$tpl->draw('rss');
}
?>
