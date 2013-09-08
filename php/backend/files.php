<?php
require_once("config.php");
require_once(PHP_DIR."/backend/db.php");

Class files
{
	// Return rows up to MAX_PERPAGE. Cached with memcache medium expiry.
	public function getallforgroup($groupid, $offset)
	{
		$db = new DB;
		return $db->query(sprintf("SELECT *, SUM(fsize) AS size, SUM(parts) AS totalparts, SUM(partsa) AS actualparts FROM files_%d GROUP BY chash ORDER BY utime DESC LIMIT %d OFFSET %d", $groupid, MAX_PERPAGE, $offset), true, CACHE_MEXPIRY);
	}

	// Get count of all files. Cached with memcache long expiry.
	public function getcount($groupid)
	{
		$db = new DB;
		$cnt = $db->query(sprintf("SELECT COUNT(DISTINCT(chash)) AS c FROM files_%d", $groupid), true);
		return $cnt[0]["c"];
	}

	// Get the sum of a file for browsenzb.
	public function getforbnzb($chash, $groupid)
	{
		$db = new DB;
		return $db->queryOneRow(sprintf("SELECT *, SUM(fsize) AS size, SUM(parts) AS totalparts, SUM(partsa) AS actualparts FROM files_%d WHERE chash = %s GROUP BY chash", $groupid, $db->escapeString($chash)));
	}

	// Get all files for nzbcontents.
	public function getforbnzbcontents($chash, $groupid, $offset)
	{
		$db = new DB;
		return $db->query(sprintf("SELECT * FROM files_%d WHERE chash = %s ORDER BY subject ASC LIMIT %d OFFSET %d", $groupid, $db->escapeString($chash), MAX_PERPAGE, $offset));
	}

	// Count for nzbcontents pagination.
	public function countforbnzbcontents($chash, $groupid)
	{
		$db = new DB;
		$c = $db->queryOneRow(sprintf("SELECT COUNT(*) AS cnt FROM files_%d WHERE chash = %s", $groupid, $db->escapeString($chash)));
		return $c["cnt"];
	}
}

?>
