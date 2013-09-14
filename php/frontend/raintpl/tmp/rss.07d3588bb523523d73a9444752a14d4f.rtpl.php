<?php if(!class_exists('raintpl')){exit;}?><?php echo '<?xml  version="1.0" encoding="UTF-8"  ?>'; ?>

<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
	<atom:link href="http://<?php echo $serveraddress;?>/rss.php" rel="self" type="application/rss+xml" />
	<title><?php echo $web_name;?></title>
	<description>RSS results</description>
	<link>http://<?php echo $serveraddress;?>/rss.php</link>
	<webMaster><?php echo $admin_email;?></webMaster>
	<?php $counter1=-1; if( isset($farr) && is_array($farr) && sizeof($farr) ) foreach( $farr as $key1 => $value1 ){ $counter1++; ?>

	<item>
		<title><?php echo xml_escape( $value1["origsubject"] );?></title>
		<description><?php echo xml_escape( $value1["name"] );?></description>
		<link>http://<?php echo $serveraddress;?>/browsenzb.php?chash=<?php echo $value1["chash"];?>&amp;group=<?php echo $value1["groupid"];?>&amp;subject=<?php echo xml_entities( $value1["subject"] );?></link>
		<guid><?php echo $value1["chash"];?></guid>
		<pubDate><?php echo utdate( $value1["utime"] );?></pubDate>
		<enclosure url="http://<?php echo $serveraddress;?>/getnzb.php?identifier=<?php echo $value1["chash"];?>&amp;subject=<?php echo xml_entities( $value1["subject"] );?>&amp;group=<?php echo $value1["groupid"];?>&amp;type=multi" length="<?php echo $value1["size"];?>" type="application/x-nzb" />
	</item>
	<?php } ?>

</channel>
</rss>
