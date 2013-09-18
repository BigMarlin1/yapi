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
						require_once('rainscripts/browsenzb.php');
					?>
				</div>
			</div>
			<?php require_once('rainscripts/footer.php'); ?>
		</div>
		<script src="/scripts/jquery-1.9.1.js"></script>
		<script src="/scripts/jquery.colorbox-min.js"></script>
		<script src="/scripts/jquery.qtip.js"></script>
		<script src="/scripts/utils.js"></script>
	</body>
</html>
