<?php

/* Class for handling connection to SQL database, querying etc using PDO.
 * Exceptions are caught and displayed to the user. */

class DB
{
	private static $initialized = false;
	private static $pdo = null;

	// Start a connection to the DB.
	function DB()
	{
		if (DB::$initialized === false)
		{
			if (defined("DB_PORT"))
				$pdos = 'mysql:host='.DB_HOST.';port='.DB_PORT.';dbname='.DB_NAME.';charset=utf8';
			else
				$pdos = 'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8';

			try {
				DB::$pdo = new PDO($pdos, DB_USER, DB_PASSWORD);
				DB::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			} catch (PDOException $e) {
				exit("Connection to the SQL server failed, error follows: (".$e->getMessage().")");
			}

			DB::$initialized = true;
		}
		$this->memcached = false;
		if (defined("MEMCACHE_ENABLED"))
			$this->memcached = MEMCACHE_ENABLED;
	}

	// Returns a string, escaped with single quotes, false on failure. http://www.php.net/manual/en/pdo.quote.php
	public function escapeString($str)
	{
		if (is_null($str))
			return "NULL";

		return DB::$pdo->quote($str);
	}

	// For inserting a row. Returns last insert ID.
	public function queryInsert($query)
	{
		if ($query=="")
			return false;

		try
		{
			$ins = DB::$pdo->exec($query);
			return DB::$pdo->lastInsertId();
		} catch (PDOException $e) {
			printf($e);
			return false;
		}
	}

	// Used for deleting, updating (and inserting without needing the last insert id). Return the affected row count. http://www.php.net/manual/en/pdo.exec.php
	public function queryExec($query)
	{
		if ($query == "")
			return false;

		try {
			return DB::$pdo->exec($query);
		} catch (PDOException $e) {
			printf($e);
			return false;
		}
	}

	// Return an array of rows, an empty array if no results.
	// Optional: Pass true to cache the result with memcache.
	public function query($query, $memcache=false, $mexpiry=CACHE_LEXPIRY)
	{
		if ($query == "")
			return false;

		if ($memcache === true && $this->memcached === true)
		{
			try {
				$memcached = new Mcached();
				$crows = $memcached->get($query);
				if ($crows !== false)
					return $crows;
			} catch (Exception $er) {
				printf ($er);
			}
		}

		$rows = array();

		try {
			$result = DB::$pdo->query($query);
		} catch (PDOException $e) {
			printf($e);
			return $rows;
		}

		foreach ($result as $row)
		{
			$rows[] = $row;
		}

		if ($memcache === true && $this->memcached === true && $memcached !== false)
			$memcached->add($query, $rows, $mexpiry);

		return $rows;
	}

	// Returns the first row of the query.
	public function queryOneRow($query)
	{
		$rows = $this->query($query);

		if (!$rows || count($rows) == 0)
			return false;

		return ($rows) ? $rows[0] : $rows;
	}

	// Query without returning an empty array like our function query(). http://php.net/manual/en/pdo.query.php
	public function queryDirect($query)
	{
		if ($query == "")
			return false;

		try {
			$result = DB::$pdo->query($query);
		} catch (PDOException $e) {
			printf($e);
			$result = false;
		}
		return $result;
	}

	// Optimises/repairs tables on mysql. Vacuum/analyze on postgresql.
	public function optimise($admin=false)
	{
		$tablecnt = 0;
		$alltables = $this->query("SHOW table status WHERE Data_free > 0");
		$tablecnt = count($alltables);
		foreach ($alltables as $table)
		{
			if ($admin === false)
				echo "Optimizing table: ".$table['Name'].".\n";
			if (strtolower($table['Engine']) == "myisam")
				$this->queryDirect("REPAIR TABLE `".$table['Name']."`");
			$this->queryDirect("OPTIMIZE TABLE `".$table['Name']."`");
		}
		$this->queryDirect("FLUSH TABLES");
		return $tablecnt;
	}

	// Check if the tables exists for the groupid, make new tables and set status to 1 in groups table for the id.
	public function newtables($grpid)
	{
		$files = $parts = false;
		try {
			DB::$pdo->query(sprintf("SELECT * FROM files_%d LIMIT 1", $grpid));
			$files = true;
		} catch (PDOException $e) {
			if ($this->queryExec(sprintf("CREATE TABLE files_%d LIKE files", $grpid)) !== false)
				$files = true;
		}

		if ($files === true)
		{
			try {
				DB::$pdo->query(sprintf("SELECT * FROM parts_%d LIMIT 1", $grpid));
				$parts = true;
			} catch (PDOException $e) {
				if ($this->queryExec(sprintf("CREATE TABLE parts_%d LIKE parts", $grpid)) !== false)
					$parts = true;
			}
			if ($parts === true)
				if ($this->queryExec(sprintf("UPDATE groups SET tstatus = 1 WHERE id = %d", $grpid)) !== false)
					return true;
		}
		return false;
	}

	// Prepares a statement, to run use exexute(). http://www.php.net/manual/en/pdo.prepare.php
	public function Prepare($query)
	{
		try {
			$stat = DB::$pdo->prepare($query);
		} catch (PDOException $e) {
			printf($e);
			$stat = false;
		}
		return $stat;
	}

	// Turns off autocommit until commit() is ran. http://www.php.net/manual/en/pdo.begintransaction.php
	public function beginTransaction()
	{
		return DB::$pdo->beginTransaction();
	}

	// Commits a transaction. http://www.php.net/manual/en/pdo.commit.php
	public function Commit()
	{
		return DB::$pdo->commit();
	}

	// Rollback transcations. http://www.php.net/manual/en/pdo.rollback.php
	public function Rollback()
	{
		return DB::$pdo->rollBack();
	}

	// Convert unixtime to sql compatible timestamp : 1969-12-31 07:00:00, also escapes it, pass false as 2nd arg to not escape.
	// (substitute for mysql FROM_UNIXTIME function)
	public function from_unixtime($utime, $escape=true)
	{
		return ($escape) ? $this->escapeString(date('Y-m-d h:i:s', $utime)) : date('Y-m-d h:i:s', $utime);
	}

	// Date to unix time.
	// (substitute for mysql's UNIX_TIMESTAMP() function)
	public function unix_timestamp($date)
	{
		return strtotime($date);
	}

	// Return uuid v4 string. http://www.php.net/manual/en/function.uniqid.php#94959
	// (substitute for mysql's UUID() function)
	public function uuid()
	{
		return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
	}

	// Checks whether the connection to the server is working. Optionally start a new connection.
	public function ping($restart = false)
	{
		try {
			return (bool) DB::$pdo->query('SELECT 1+1');
		} catch (PDOException $e) {
			if ($restart = true)
			{
				DB::$initialized = false;
				$this->DB();
			}
			return false;
		}
	}
}

// Class for caching queries into RAM using memcached using php5-memcache.
class Mcached
{
	// Make a connection to memcached server.
	function Mcached()
	{
		// Memcache, not memcached. (php-memcache vs php-memcached)
		if (extension_loaded('memcache'))
		{
			$this->m = new Memcache();
			if ($this->m->connect(MEMCACHE_HOST, MEMCACHE_PORT) == false)
				throw new Exception('Unable to connect to the memcached server.');
		}
		else
			throw new Exception('Extension "memcache" not found.');

		$this->compression = MEMCACHE_COMPRESSED;
		if (defined("MEMCACHE_COMPRESSION"))
		{
			if (MEMCACHE_COMPRESSION === false)
				$this->compression = false;
		}
	}

	// Return a SHA1 hash of the query, used for the key.
	function key($query)
	{
		return sha1($query);
	}

	// Return some stats on the server.
	public function Server_Stats()
	{
		return $this->m->getExtendedStats();
	}

	// Flush all the data on the server.
	public function Flush()
	{
		return $this->m->flush();
	}

	// Add a query to memcached server.
	public function add($query, $result, $expiry)
	{
		return $this->m->add($this->key($query), $result, $this->compression, $expiry);
	}

	// Delete a query on the memcached server.
	public function delete($query)
	{
		return $this->m->delete($this->key($query));
	}

	// Retrieve a query from the memcached server. Stores the query if not found.
	public function get($query)
	{
		return $this->m->get($this->key($query));
	}
}
