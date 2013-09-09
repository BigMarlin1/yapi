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

	// Get for browse page, need to select from all groups. Cache with memcache long.
	public function getforbrowse($offset)
	{
		$db = new DB;
		$tids = $db->query("SELECT id FROM groups WHERE tstatus = 1 AND lastdate > 0 ORDER BY lastdate DESC LIMIT ".MAX_PERPAGE);
		$count = count($tids);
		if ($count > 0)
		{
			$fstr = '';
			$i = 1;
			$max = MAX_PERPAGE;
			if ($count > 25)
				$max = (MAX_PERPAGE / 2);

			foreach ($tids as $tid)
			{
				$id = $tid["id"];
				if ($i++ == $count)
					$fstr .= "(SELECT *, SUM(fsize) AS size, SUM(parts) AS totalparts, SUM(partsa) AS actualparts FROM files_$id GROUP BY chash LIMIT $max OFFSET $offset)";
				else
					$fstr .= "(SELECT *, SUM(fsize) AS size, SUM(parts) AS totalparts, SUM(partsa) AS actualparts FROM files_$id GROUP BY chash LIMIT $max OFFSET $offset) UNION ";
			}
			return $db->query(sprintf("SELECT files.*, groups.name, groups.id AS groupid FROM ($fstr) AS files INNER JOIN groups ON groups.id = files.groupid ORDER BY utime DESC LIMIT ".MAX_PERPAGE), true);
		}
		else
			return false;
	}

	// Count for browseall paginator. Cache with memcache long.
	public function getbrowsecount()
	{
		$db = new DB;
		$tids = $db->query("SELECT id FROM groups WHERE tstatus = 1 AND lastdate > 0 ORDER BY lastdate DESC LIMIT ".MAX_PERPAGE);
		$count = count($tids);
		if ($count > 0)
		{
			$fstr = '';
			$i = 1;

			foreach ($tids as $tid)
			{
				$id = $tid["id"];
				if ($i == 1)
					$fstr .= "(SELECT COUNT(DISTINCT(chash)) FROM files_$id) +";
				else if ($i == $count)
					$fstr .= " (SELECT COUNT(DISTINCT(chash)) FROM files_$id)";
				else
					$fstr .= " (SELECT COUNT(DISTINCT(chash)) FROM files_$id) +";
				$i++;
			}
			$c = $db->queryOneRow("SELECT $fstr AS cnt");
			return $c["cnt"];
		}
		else
			return false;
	}

	// Get all files for nzbcontents.
	public function getforbnzbcontents($chash, $groupid, $offset)
	{
		$db = new DB;
		return $db->query(sprintf("SELECT * FROM files_%d WHERE chash = %s ORDER BY subject ASC LIMIT %d OFFSET %d", $groupid, $db->escapeString($chash), MAX_PERPAGE, $offset), true);
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
