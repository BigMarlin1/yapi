<?php
//=========================
// Configuration of most settings.
//=========================

// Mysql settings:
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_USER', '');
define('DB_PASSWORD', '');
define('DB_NAME', '');

// Usenet settings:
define('NNTP_USERNAME', '');
define('NNTP_PASSWORD', '');
define('NNTP_SERVER', '');
define('NNTP_PORT', '');
define('NNTP_SSLENABLED', false);
define('NNTP_TIMEOUT', 15); // Seconds before giving up when trying to connect.
define('NNTP_COMPRESSION', false); // XFeature Gzip compression.

// Second provider, for filling in missed headers. Optional.
define('NNTP_ALTERNATE', false); // Force turn on or off alternate provider.
define('NNTPA_USERNAME', '');
define('NNTPA_PASSWORD', '');
define('NNTPA_SERVER', '');
define('NNTPA_PORT', '');
define('NNTPA_SSLENABLED', false);
define('NNTPA_TIMEOUT', 15);
define('NNTPA_COMPRESSION', false);

// CLI settings:
define('NEW_HEADERS', 1000000); // How many headers to fetch on a new group.
define('QTY_HEADERS', 20000); // How many headers to fetch per loop.
define('DEBUG_MESSAGES', false); // Turn on debug messages.
define('UNRAR_PATH', ''); // Path to the non-free unrar binary. /usr/bin/unrar in ubuntu
define('SEVENZIP_PATH', ''); // Path to the 7zip CLI binary. /usr/bin/7za in ubuntu
define('STORE_FILES', false); // Store file names from inside of zip/rar into the DB.
define('MAX_DOWNLOAD', 3); // Maximum amount of times to download an yEnc file (if it fails to download) when downloading nfos or checking for passwords.

// Website settings.
define('WEB_NAME', 'Yet Another PHP Indexer'); // Name of the website.
define('WEB_FOOTER', 'Copyright &copy; 2013 '.WEB_NAME); // Text at the bottom of the page.
define('MAX_PERPAGE', 50); // Maximum amount of releases per page.
define('ADMIN_EMAIL', 'example@example.com'); // Email address for people to contact you.
define('RSS_LIMIT', 100); // How many results to limit the RSS.
define('HIDE_PASSWORDED', true); // Hide passworded results from the site.

// Memcache settings.
define('MEMCACHE_ENABLED', false); // Wether to use memcached or not. Memcache keeps a MySQL query result in ram.
define('MEMCACHE_HOST', '127.0.0.1');
define('MEMCACHE_PORT', '11211');
define('MEMCACHE_COMPRESSION', true); // To compress the queries using zlib or not (more cpu usage and less ram usage if set to true, inverse for false);

// Cache time settings. For memcache / raintpl. Amount of time in seconds to keep cache results.
define('CACHE_LEXPIRY', '900'); // Results we want to keep a longer time
define('CACHE_MEXPIRY', '600'); // Results we want to keep normal time.
define('CACHE_SEXPIRY', '300'); // Resutls we want to refresh often.

//=========================
// Stuff you don't have to change.
//=========================

// The current path.
define('PHP_DIR', realpath(dirname(__FILE__)).'/');

// Web path.
$www_top = str_replace('\\','/',dirname( $_SERVER['PHP_SELF'] ));
if(strlen($www_top) == 1)
	$www_top = '';
define('WWW_TOP', $www_top);
