<?php if(!class_exists('raintpl')){exit;}?><ul class="pagination">
	<?php if( $lastpage > 0 ){ ?>

		<li class="pagination">
			<a class="pagination" href="nzbcontents.php?page=<?php echo $lastpage;?>&chash=<?php echo $chash;?>&subject=<?php echo urlencode( $subject );?>&group=<?php echo $group;?>">
				« Previous [<?php echo $lastpage;?>]
			</a>
		</li>
	<?php } ?>

	<?php if( $maxpages > 1 ){ ?>

		<li class="pagination">
			<a class="pagination"><?php echo $curpage;?></a>
		</li>
	<?php } ?>

	<?php if( $nextpage <= $maxpages ){ ?>

		<li class="pagination">
			<a class="pagination" href="nzbcontents.php?page=<?php echo $nextpage;?>&chash=<?php echo $chash;?>&subject=<?php echo urlencode( $subject );?>&group=<?php echo $group;?>">
				[<?php echo $nextpage;?>] Next »
			</a>
		</li>
	<?php } ?>

	<?php if( $curpage > 1 ){ ?>

		<li class="pagination">
			<a class="pagination" href="nzbcontents.php?page=1&chash=<?php echo $chash;?>&subject=<?php echo urlencode( $subject );?>&group=<?php echo $group;?>">
				[Return to start]
			</a>
		</li>
	<?php } ?>

</ul>
