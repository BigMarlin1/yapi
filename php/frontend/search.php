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
					<?php include('rainscripts/search.php'); ?>
				</div>
			</div>
			<?php include('rainscripts/footer.php'); ?>
		</div>
		<script src="/scripts/jquery-1.9.1.js"></script>
		<script src="/scripts/jquery.colorbox-min.js"></script>
		<script src="/scripts/utils.js"></script>
	</body>
</html>
