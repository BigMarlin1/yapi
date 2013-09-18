<!DOCTYPE html>
<?php require_once('raintpl/raintpl.php'); ?>
<html>
	<?php require_once('rainscripts/head.php'); ?>
	<body>
		<div id="wrapper">
		<?php 
			require_once('rainscripts/header.php'); 
			require_once('rainscripts/nav.php');
		?>
			<div id="content">
				<div id="innercontent">
					<h4><?php echo urldecode($_GET['subject']); ?></h4>
					<?php
						$chash = $_GET['chash'];
						$group = $_GET['group'];
						$subject = $_GET['subject'];
						require_once('rainscripts/nzbcontents.php');
					?>
				</div>
			</div>
			<?php require_once('rainscripts/footer.php'); ?>
		</div>
	</body>
</html>
