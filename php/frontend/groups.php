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
			<div id="content" style="margin-bottom:5px;">
				<div id="innercontent">
					<h3>Groups:</h3>
						<?php include('rainscripts/groupslist.php'); ?>
				</div>
			</div>
			<?php include('rainscripts/footer.php'); ?>
		</div>
	</body>
</html>
