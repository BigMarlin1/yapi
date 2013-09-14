<?php if(!class_exists('raintpl')){exit;}?><h3>API Info:</h3>
	<p>The API is based on the nzedb/newznab API, it's similar but there are some differences.</p>
<h3>Things not included (because they are incompatible with our site):</h3>
	<p><u><b>Registration:</b></u> ?t=register
		<br /><u><b>Api key:</b></u> ?apikey=
		<br /><u><b>User:</b></u> ?t=user
		<br /><u><b>Cart:</b></u> ?t=cartadd / ?t=cartdelete
		<br /><u><b>Searching TV, Movies, Music, Books:</b></u> ?t=tvsearch / ?t=movie / etc..
		<br /><u><b>Comments:</b></u> ?t=comments
		<br /><u><b>Extended attributes:</b></u> ?t=attrs / ?extended=1
		<br /><u><b>Categories:</b></u> Anything relating to categories. (sorting by category for example)
	</p>
<h3>Things that are the same.</h3>
	<p><u><b>Capabilities:</b></u> <a href="api.php?t=caps">http://<?php echo $serveraddress;?>/api.php?t=caps</a> | Displays the capabilities of the API.
		<br /><u><b>Get:</b></u> <a href="api.php?t=get&id=c990511d2b41d7c0c48996da7dabd179582e9d45">http://<?php echo $serveraddress;?>/api.php?t=get&id=SHA1HASH</a> | Gets an NZB file for the collection using its hash, optionally pass the groupid to speed it up (&gid=2)
		<br /><u><b>Details:</b></u> <a href="api.php?t=details&id=c990511d2b41d7c0c48996da7dabd179582e9d45">http://<?php echo $serveraddress;?>/api.php?t=details&id=SHA1HASH</a> | Loads the page for the collection,  optionally pass the groupid to speed it up (&gid=2)
		<br /><u><b>Output format:</b></u> &o=xml | &o=json returns XML or JSON, if you don't specify it defaults to xml.
		<br /><u><b>Search filters:</b></u> <a href="api.php?t=search&q=linux&group=a.b.multimedia&minsize=0&maxsize=734003200">http://<?php echo $serveraddress;?>/api.php?t=search&q=linux&group=a.b.multimedia&minsize=0&maxsize=734003200</a>  Filters by min/max size and group (not required to use them all). minsize|maxsize|group
	</p>
<h3>Things that are changed.</h3>
	<p><u><b>Search:</b></u> <a href="api.php?t=search&q=linux">http://<?php echo $serveraddress;?>/api.php?t=search&q=linux</a> | Search collections.
		<br /><u><b>Sorting:</b></u> <a href="api.php?t=search&q=linux&sort=size_desc">http://<?php echo $serveraddress;?>/api.php?t=search&q=linu&sort=size_desc</a> | Sort the searches.
		<br /><u><b>Sorting options:</b></u> first arg: size | name | files | date | poster | (cat is removed) || second arg: asc | desc
	</p>
