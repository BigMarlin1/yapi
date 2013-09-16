#!/bin/sh

# You can run this script like this : sh run.sh, or using screen (screen is recommended) : screen sh run.sh

##############################################
## Change parameters between line 9 and 31. ##
##############################################

# Change this path to the php/cli folder.
export cli_dir="/var/www/yapi/php/cli"

# Amount of time to sleep in between loops.
export sleep="60"

# If you want to check for passwords, set this to true.
export password=false

# If you want to check for NFOs, set this to true.
export nfo=false

# If you want to backfill, set this to true.
export backfill=false

# Amount of headers to backfill per run.
export bqty="40000"

# If you have a second NNTP provider, set this to true.
export alternate=false

# Path to php, you shouldn't have to change this.
export php="$(which php5)"

################################
## End of user configuration. ##
################################

while :
do

	cd $cli_dir
	echo "\033[31mGoing to download headers for the primary NNTP server.\n\033[33m"
	$php update_headers.php all false
	if [ $alternate = true ]
	then
		echo "\033[31m\nGoing to download headers for the secondary NNTP server.\n\033[33m"
		$php update_headers.php all true
	fi
	if [ $nfo = true ]
	then
		echo "\033[31m\nGoing to match NFOs for the primary NNTP server.\n\033[33m"
		$php match_nfos.php true false
	fi
	if [ $password = true ]
	then
		echo "\033[31m\nGoing to check for passwords for the primary NNTP server.\n\033[33m"
		$php check_passwords.php true false
	fi

	if [ $backfill = true ]
	then
		echo "\033[31m\nGoing to backfill headers using the primary NNTP server.\n\033[33m"
		$php backfill_headers.php all $bqty false
		if [ $alternate = true ]
		then
			echo "\033[31m\nGoing to backfill headers using the secondary NNTP server.\n\033[33m"
			$php backfill_headers.php all $bqty true
		fi
		if [ $nfo = true ]
		then
			echo "\033[31m\nGoing to match NFOs for the primary NNTP server.\n\033[33m"
			$php match_nfos.php true false
		fi
		if [ $password = true ]
		then
			echo "\033[31m\nGoing to check for passwords for the primary NNTP server.\n\033[33m"
			$php check_passwords.php true false
		fi
	fi

	if [ $alternate = true ]
	then
		if [ $nfo = true ]
		then
			echo "\033[31m\nGoing to try to fetch missed NFOs using the secondady NNTP server.\n\033[33m"
			$php match_nfos.php true true
			fi
		if [ $password = true ]
		then
			echo "\033[31m\nGoing to try to check missed passworded files using the secondary NNTP server.\n\033[33m"
			$php check_passwords.php true true
		fi
	fi

echo "\033[31m\nSleeping for $sleep seconds.\n\033[33m"
sleep $sleep
done
