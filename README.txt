yapi
====

Yet another php indexer (usenet).

Work in progress, learning how to make a web interface, goal is to get a binsearch type site with a api and password checking, nothing more than that.
No password checking yet. Basic regex, so matching won't be too good, will add more with time.
Some of the mysql tables are innodb compressed, some are myisam.
API and search are basic right now, will improve with time.



How it works:
Download headers, store them in mysql innodb compressed tables by group (every group has its own tables for headers).
Use regex to extract non unique stuff (file extensions, file/part numbers) from the headers subject and hash it using sha1.
On the website in real time "merge" the files using the hash (so many files will show as 1 collection).
Also in real time calculate the size and completion % of the collection.
If you download more headers and reload the page, the existing collections will update in real time with the new completion and size.
If you change the regex you can run a script to redo the hashes and when you refresh the page, the collections/completion/size will update automatically.
NZB files are generated when you click the button (so they update in real time as well).




Requirements:
PHP 5.4+
MySQL 5+ (or one of the many forks : mariadb, percona, etc..)

apache2 or nginx for web server.

(optional): memcached (some of the query results are cached with it).

PHP extensions/functions:

PDO to connect to mysql.
PEAR for nntp.
SHA1 for hashing.
openssl for ssl connections.
memcache for caching query results.
apc for speeding up execution of php scripts.




General installation:
Install the above requirements.
Create a database in mysql, import the schema in the sql folder.
Git clone into a folder and set up your web server to point to the php/frontend folder
Edit config.php
YourInstallDir/php/frontend/raintpl/tmp must have read access



General usage:
After installing/importing the schema/configuring config.php
Run group_toggle.php to enable a group (ie: php group_toggle.php enable forward alt.binaries.teevee)
Run update_headers.php to fetch new headers, you'll have to run it twice the first time to create the mysql tables (ie: php update_headers.php alt.binaries.teevee)
To get more headers, run group_toggle.php again and enable backfill for the group, then run backfill_headers.php
You can run run.sh with screen to run update_headers.php in a loop ( screen sh run.sh ), edit the script and set the path.



Basic ubuntu 13.04 guide:

Install php and extensions.
sudo apt-get install -y php5 php5-dev php-pear php5-mysql php5-memcache php-apc

Install mysql.
sudo apt-get install mysql-server mysql-client libmysqlclient-dev

Install apache2.
sudo apt-get install apache2

Install memcached
sudo apt-get install memcached

Edit the php.ini files for cli and apache2 (edit both).
/etc/php5/apache2/php.ini
/etc/php5/cli/php.ini
Change max_execution_time to 120
Change memory_limit to 1024M or more
Change date.timezone (see php.net)

Create an apache2 config file.
Create this file: /etc/apache2/sites-available/yapi
Paste the following in it (change the settings for your system):

<VirtualHost *:80>
	ServerAdmin webmaster@localhost
	ServerName localhost

	DocumentRoot /var/www/yapi/php/frontend
	ErrorLog /var/log/apache2/error.log
	LogLevel warn
</VirtualHost>

Disable the default site:
sudo a2dissite default
Enable our site:
sudo a2ensite yapi
Restart apache2:
sudo service apache2 restart

Clone the git to /var/www/

Login to mysql and create a DB.
(you must make a mysql account first, if you installed mysql as root, then you can login to mysql as root using the password you provided during installation : sudo mysql --password=YourPassword)
mysql --password=YourPassword --user=YourUserName
create database yapi;
control+c to get out of mysql

Import the mysql schema. (For root : sudo mysql -p yapi < /var/www/yapi/sql/mysql.sql)
mysql -p --user=YourUserName yapi < /var/www/yapi/sql/mysql.sql

Give write access to YourInstallDir/php/frontend/raintpl/tmp

Edit config.php with your DB, nntp, etc settings.
