<?php if(!class_exists('raintpl')){exit;}?><table border="1">
	<tr>
		<th>Group</th>
		<th>Group ID</th>
		<th>Files</th>
		<th>Collections</th>
		<th>Forward status</th>
		<th>Backfill status</th>
		<th>Newest article #</th>
		<th>Oldest article #</th>
		<th>Newest article Date</th>
		<th>Oldest article Date</th>
	</tr>
	<?php $counter1=-1; if( isset($grouparr) && is_array($grouparr) && sizeof($grouparr) ) foreach( $grouparr as $key1 => $value1 ){ $counter1++; ?>

	<tr>
		<td><a href="browsegroup.php?groupid=<?php echo $value1["id"];?>&group=<?php echo $value1["name"];?>&page=1"><?php echo $value1["name"];?></a></td>
		<td><?php echo $value1["id"];?></td>
		<td><?php if( $value1["tstatus"] == 1 ){ ?> <?php echo getfcount($value1["id"]); ?><?php }else{ ?> 0<?php } ?></td>
		<td><?php if( $value1["tstatus"] == 1 ){ ?> <?php echo getccount($value1["id"]); ?><?php }else{ ?> 0<?php } ?></td>
		<td><?php if( $value1["factive"] == 1 ){ ?> Yes<?php }else{ ?> No<?php } ?></td>
		<td><?php if( $value1["bactive"] == 1 ){ ?> Yes<?php }else{ ?> No<?php } ?></td>
		<td><?php if( $value1["lastart"] > 0 ){ ?> <?php echo $value1["lastart"];?><?php }else{ ?> N/A<?php } ?></td>
		<td><?php if( $value1["firstart"] > 0 ){ ?> <?php echo $value1["firstart"];?><?php }else{ ?> N/A<?php } ?></td>
		<td><?php if( $value1["lastdate"] != 0 ){ ?> <?php echo utdate( $value1["lastdate"] );?><?php }else{ ?> N/A<?php } ?></td>
		<td><?php if( $value1["firstdate"] !=0 ){ ?> <?php echo utdate( $value1["firstdate"] );?><?php }else{ ?> N/A<?php } ?></td>
	</tr>
	<?php } ?>

</table> 
