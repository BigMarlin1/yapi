<!DOCTYPE html>
<?php include('raintpl/raintpl.php'); ?>
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
					<h3>Browsing <?php echo $_GET["group"]; ?>:</h3>
						<?php $grpid = $_GET["groupid"]; include('rainscripts/browsetable.php'); ?>
				</div>
			</div>
			<?php include('rainscripts/footer.php'); ?>
		</div>
	</body>
</html>
