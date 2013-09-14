<?php if(!class_exists('raintpl')){exit;}?><?php echo '<?xml  version="1.0" encoding="UTF-8"  ?>'; ?>

<caps>
	<server appversion="0.1" version="0.1" title="<?php echo $web_name;?>" strapline="A basic usenet indexer" email="<?php echo $admin_email;?>" url="http://<?php echo $serveraddress;?>/" image="http://<?php echo $logopath;?>/"/>
	<limits max="1000000" default="1000000"/>

	<registration available="no" open="yes" />
	<searching>
		<search available="yes"/>
		<tv-search available="no"/>
		<movie-search available="no"/>
		<audio-search available="no"/>
	</searching>
	<categories></categories>
	<groups>
	<?php $counter1=-1; if( isset($groups) && is_array($groups) && sizeof($groups) ) foreach( $groups as $key1 => $value1 ){ $counter1++; ?>

		<group id="<?php echo $value1["id"];?>" name="<?php echo $value1["name"];?>" description="" lastupdate="<?php echo utdate( $value1["lastdate"] );?>"></group>
	<?php } ?>

	</groups>
	<genres></genres>
</caps>
