<?php if(!class_exists('raintpl')){exit;}?><table class="data" border="1">
	<tr class="row_2">
			<th>Original subject:</th>
			<td><?php echo $file["origsubject"];?></td>
	<tr class="row_1">
		<th>Added locally:</th>
		<td><?php echo utsince( $file["ltime"] );?> Ago</td>
	</tr>
	<tr class="row_2">
		<th>Added to usenet:</th>
		<td><?php echo utsince( $file["utime"] );?> Ago</td>
	</tr>
	<tr class="row_1">
		<th>Posted by:</th>
		<td><?php echo $file["poster"];?></td>
	</tr>
	<tr class="row_2">
		<th>Group:</th>
		<td><?php echo $group["name"];?></td>
	</tr>
	<tr class="row_1">
		<th>Size:</th>
		<td>
			<?php if( $file["size"] > 1099511627776 ){ ?> <?php $size=$this->var['size']=$file["size"] / 1099511627776;?>

				<?php echo round( $size );?>TiB
			<?php }elseif( $file["size"] > 1073741824 ){ ?> <?php $size=$this->var['size']=$file["size"] / 1073741824;?>

				<?php echo round( $size );?>GiB
			<?php }elseif( $file["size"] > 1048576 ){ ?> <?php $size=$this->var['size']=$file["size"] / 1048576;?>

				<?php echo round( $size );?>MiB
			<?php }elseif( $file["size"] > 1024 ){ ?> <?php $size=$this->var['size']=$file["size"] / 1024;?>

				<?php echo round( $size );?>KiB
			<?php }else{ ?> <?php echo $file["size"];?>B
			<?php } ?>

		</td>
	</tr>
	<tr class="row_1">
		<th>Parts we have:</th>
		<td><a style="text-decoration: none;" href="nzbcontents.php?page=1&chash=<?php echo $file["chash"];?>&subject=<?php echo urlencode( $file["subject"] );?>&group=<?php echo $group["id"];?>"><?php echo $file["actualparts"];?></td>
	</tr>
	<tr class="row_2">
		<th>Max parts possible:</th>
		<td><a style="text-decoration: none;" href="nzbcontents.php?page=1&chash=<?php echo $file["chash"];?>&subject=<?php echo urlencode( $file["subject"] );?>&group=<?php echo $group["id"];?>"><?php echo $file["totalparts"];?></a></td>
	</tr>
	<tr class="row_1">
		<th>Completion:</th>
		<td>
			<?php $completion=$this->var['completion']=($file["actualparts"] / $file["totalparts"]) * 100;?>

			<?php if( $completion > 100 ){ ?> <?php $completion=$this->var['completion']=100;?><?php } ?>

			<?php echo number_format( $completion );?>%
		</td>
	</tr>
	<tr class="row_2">
		<th>NZB:</th>
		<td><a href="getnzb.php?identifier=<?php echo $file["chash"];?>&subject=<?php echo urlencode( $file["subject"] );?>&group=<?php echo $group["id"];?>&type=multi" download><?php echo $nzb_img;?></a></td>
	</tr>
	<?php if( $file["nstatust"] > 0 ){ ?>

	<tr class="row_1">
		<th>NFO:</th>
		<td><a style="float:left;" class="modal_nfo rndbtn" href="nfo.php?chash=<?php echo $file["chash"];?>&group=<?php echo $group["id"];?>" title="View Nfo" rel="nfo">Nfo</a></td>
	</tr>
	<?php } ?>

</table>
