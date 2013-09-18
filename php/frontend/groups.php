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
			<div id="content" style="margin-bottom:5px;">
				<div id="innercontent">
					<h3>Groups:</h3>
						<?php require_once('rainscripts/groupslist.php'); ?>
				</div>
			</div>
			<?php require_once('rainscripts/footer.php'); ?>
		</div>
	</body>
</html>
