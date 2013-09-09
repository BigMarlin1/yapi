#!/bin/sh

# Change this path to the php/cli folder.
export cli_dir="/var/www/yapi/php/cli"
export php="$(which php5)"
# Amount of time to sleep in between.
export sleep="60"

while :
do
	cd $cli_dir
	$php $cli_dir/update_headers.php all

echo "Sleeping for $sleep seconds."
sleep  $sleep
done
