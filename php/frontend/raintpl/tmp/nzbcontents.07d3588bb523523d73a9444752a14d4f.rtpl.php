<?php if(!class_exists('raintpl')){exit;}?><table class="data" border="1">
	<tr class="header">
		<th class="subject">Subject</th>
		<th class="size">Size</th>
		<th class="completion">Parts</th>
		<th class="addedu">Posted</th>
		<th class="nzb">NZB</th>
	</tr>
	<?php $counter1=-1; if( isset($contents) && is_array($contents) && sizeof($contents) ) foreach( $contents as $key1 => $value1 ){ $counter1++; ?>

	<tr class="row_<?php echo $counter1%2+1;?>">
		<td class="subject"><?php echo $value1["origsubject"];?></td>
		<td class="size">
			<?php if( $value1["fsize"] > 1099511627776 ){ ?> <?php $size=$this->var['size']=$value1["fsize"] / 1099511627776;?>

				<?php echo round( $size );?>TiB
			<?php }elseif( $value1["fsize"] > 1073741824 ){ ?> <?php $size=$this->var['size']=$value1["fsize"] / 1073741824;?>

				<?php echo round( $size );?>GiB
			<?php }elseif( $value1["fsize"] > 1048576 ){ ?> <?php $size=$this->var['size']=$value1["fsize"] / 1048576;?>

				<?php echo round( $size );?>MiB
			<?php }elseif( $value1["fsize"] > 1024 ){ ?> <?php $size=$this->var['size']=$value1["fsize"] / 1024;?>

				<?php echo round( $size );?>KiB
			<?php }else{ ?> <?php echo $value1["fsize"];?>B
			<?php } ?>

		</td>
		<td class="completion">
			<?php $completion=$this->var['completion']=($value1["partsa"] / $value1["parts"]) * 100;?>

			<?php if( $completion > 100 ){ ?> <?php $completion=$this->var['completion']=100;?><?php } ?>

			<?php echo number_format( $completion );?>%
		</td>
		<td class="addedu"><?php echo utsince( $value1["utime"] );?> Ago</td>
		<td class="nzb"><a class="nzb" href="getnzb.php?identifier=<?php echo $value1["id"];?>&subject=<?php echo urlencode( $value1["subject"] );?>&group=<?php echo $group;?>&type=single" download><?php echo $nzb_img;?></a></td>
	</tr>
	<?php } ?>

</table>
