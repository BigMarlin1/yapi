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
					<h3>Browsing <?php echo $_GET['group']; ?>:</h3>
					<?php
						$grpid = $_GET['groupid'];
						require_once('rainscripts/browsetable.php');
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
