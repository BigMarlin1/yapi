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
					<?php require_once('rainscripts/indexcontent.php'); ?>
				</div>
			</div>
			<?php require_once('rainscripts/footer.php'); ?>
		</div>
	</body>
</html>
