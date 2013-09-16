#!/bin/sh

# Change this path to the php/cli folder.
export cli_dir="/var/www/yapi/php/cli"
export php="$(which php5)"
# Amount of time to sleep in between loops.
export sleep="60"
# Amount of headers to backfill per run.
export backfill="60000"

while :
do
	cd $cli_dir
	$php $cli_dir/update_headers.php all false
	$php $cli_dir/match_nfos.php true false
	$php $cli_dir/check_passwords.php true false

	# If you have a second nntp provider, uncomment the following lines.
	#$php $cli_dir/update_headers.php all true
	#$php $cli_dir/match_nfos.php true true
	#$php $cli_dir/check_passwords.php true true

	# If you want to backfill, uncomment the following lines.
	#$php $cli_dir/backfill_headers.php all $backfill false
	#$php $cli_dir/match_nfos.php true false
	#$php $cli_dir/check_passwords.php true false

	# If you have a second nntp provider and want to backfill, uncomment the following lines.
	#$php $cli_dir/backfill_headers.php all $backfill true
	#$php $cli_dir/match_nfos.php true true
	#$php $cli_dir/check_passwords.php true true

echo "Sleeping for $sleep seconds."
sleep $sleep
done
