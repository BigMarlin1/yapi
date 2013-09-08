<?php if(!class_exists('raintpl')){exit;}?><ul class="pagination">
	<?php if( $lastpage > 0 ){ ?>

		<li class="pagination">
			<a class="pagination" href="browsegroup.php?groupid=<?php echo $group["id"];?>&group=<?php echo $group["name"];?>&page=<?php echo $lastpage;?>">
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
			<a class="pagination" href="browsegroup.php?groupid=<?php echo $group["id"];?>&group=<?php echo $group["name"];?>&page=<?php echo $nextpage;?>">
				[<?php echo $nextpage;?>] Next »
			</a>
		</li>
	<?php } ?>

	<?php if( $curpage > 1 ){ ?>

		<li class="pagination">
			<a class="pagination" href="browsegroup.php?groupid=<?php echo $group["id"];?>&group=<?php echo $group["name"];?>&page=1">
				[Return to start]
			</a>
		</li>
	<?php } ?>

</ul>
