<?php if(!class_exists('raintpl')){exit;}?><table class="data" border="1">
	<tr class="header">
		<th class="subject">Subject</th>
		<th class="size">Size</th>
		<th class="completion">Parts</th>
		<th class="addedu">Posted</th>
		<th class="nzb">NZB</th>
	</tr>
	<?php $counter1=-1; if( isset($filearr) && is_array($filearr) && sizeof($filearr) ) foreach( $filearr as $key1 => $value1 ){ $counter1++; ?>

	<tr class="row_<?php echo $counter1%2+1;?>">
		<td class="subject">
			<a class="subject" style="text-decoration: none;" href="browsenzb.php?chash=<?php echo $value1["chash"];?>&group=<?php echo $group["id"];?>&subject=<?php echo urlencode( $value1["subject"] );?>"><?php echo $value1["subject"];?>

			<?php if( $value1["nstatust"] > 0 ){ ?>

				<a class="modal_nfo rndbtn" href="nfo.php?chash=<?php echo $value1["chash"];?>&group=<?php echo $group["id"];?>" title="View Nfo" rel="nfo">Nfo</a>
			<?php } ?>

			</a>
		</td>
		<td class="size">
			<?php if( $value1["size"] > 1099511627776 ){ ?> <?php $size=$this->var['size']=$value1["size"] / 1099511627776;?>

				<?php echo round( $size );?>TiB
			<?php }elseif( $value1["size"] > 1073741824 ){ ?> <?php $size=$this->var['size']=$value1["size"] / 1073741824;?>

				<?php echo round( $size );?>GiB
			<?php }elseif( $value1["size"] > 1048576 ){ ?> <?php $size=$this->var['size']=$value1["size"] / 1048576;?>

				<?php echo round( $size );?>MiB
			<?php }elseif( $value1["size"] > 1024 ){ ?> <?php $size=$this->var['size']=$value1["size"] / 1024;?>

				<?php echo round( $size );?>KiB
			<?php }else{ ?> <?php echo $value1["size"];?>B
			<?php } ?>

		</td>
		<td class="completion">
			<?php $completion=$this->var['completion']=($value1["actualparts"] / $value1["totalparts"]) * 100;?>

			<?php if( $completion > 100 ){ ?> <?php $completion=$this->var['completion']=100;?><?php } ?>

			<?php echo number_format( $completion );?>%
		</td>
		<td class="addedu"><?php echo utsince( $value1["utime"] );?> Ago</td>
		<td class="nzb"><a class="nzb" href="getnzb.php?identifier=<?php echo $value1["chash"];?>&subject=<?php echo urlencode( $value1["subject"] );?>&group=<?php echo $group["id"];?>&type=multi" download><?php echo $nzb_img;?></a></td>
	</tr>
	<?php } ?>

</table>
