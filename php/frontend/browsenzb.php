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
					<h4><?php echo urldecode($_GET["subject"]); ?></h4>
						<?php $chash = $_GET["chash"]; $group = $_GET["group"]; include('rainscripts/browsenzb.php'); ?>
				</div>
			</div>
			<?php include('rainscripts/footer.php'); ?>
		</div>
	</body>
</html>
