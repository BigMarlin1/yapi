<?php
require_once("config.php");
require_once(PHP_DIR."/backend/db.php");

Class groups
{
	// Return name for the groupid.
	public function getname($gid)
	{
		$db = new DB;
		$garr = $db->queryOneRow("SELECT name FROM groups WHERE id = {$gid}");
		return $garr["name"];
	}

	// Return row for a group, or all groups if active.
	public function getactive($type, $name = '')
	{
		switch ($type)
		{
			case "backfill":
				$q = "SELECT * FROM groups WHERE bactive = 1";
				break;
			case "forward":
				$q = "SELECT * FROM groups WHERE factive = 1";
				break;
			default:
				return false;
		}
		if ($name != '' && $name != "all")
			$q .= " AND name = '{$name}'";

		$db = new DB;
		return $db->query($q);
	}

	// Return the amount of files indexed for the group. Cache result with memcache.
	public function getfilecount($gid)
	{
		$db = new DB;
		$cnt = $db->query(sprintf("SELECT COUNT(*) AS c FROM files_%d", $gid), true);
		return $cnt[0]["c"];
	}

	// Return the amount of collections for the group. Cache result with memcache.
	public function getcollectioncount($gid)
	{
		$db = new DB;
		$cnt = $db->query(sprintf("SELECT COUNT(DISTINCT(chash)) AS c FROM files_%d", $gid), true);
		return $cnt[0]["c"];
	}

	// Return everything sorted by name.
	public function getallsortname()
	{
		$db = new DB;
		return $db->query("SELECT * FROM groups ORDER BY name");
	}

	// Returns row(s) for group(s).
	public function getgroupinfo($group = '')
	{
		$db = new DB;
		if ($group == '')
			return $db->query("SELECT * FROM groups");
		else if (is_numeric($group))
			return $db->queryOneRow(sprintf("SELECT * FROM groups WHERE id = %d", $group));
		else
			return $db->queryOneRow(sprintf("SELECT * FROM groups WHERE name = %s", $db->escapeString($group)));
	}

	// Resets files / parts for the group(s).
	public function tablereset($gname = '')
	{
		$db = new DB;
		$groups = $this->getgroupinfo($gname);
		$done = 0;
		foreach ($groups as $group)
		{
			if ($group["tablestatus"] == 1)
			{
				$db->queryExec(sprintf("TRUNCATE TABLE parts_%d", $group["id"]));
				$db->queryExec(sprintf("TRUNCATE TABLE files_%d", $group["id"]));
				$db->queryExec(sprintf("UPDATE groups SET firstdate = 0, lastdate = 0, firstart = 0, lastart = 0, factive = 0, bactive = 0 WHERE id = %d", $group["id"]));
				$done++;
			}
		}
		return $done;
	}

	// Enable / disable group backfill or forward.
	public function grouptoggle($status, $type, $gname)
	{
		$db = new DB;
		$query = "UPDATE groups SET";
		
		$t = " bactive";
		if ($type == "forward")
			$t = " factive";
		$s = " = 0 ";
		if ($status == "enable")
			$s = " = 1 ";

		$query .= $t.$s.sprintf("WHERE name = %s", $db->escapeString($gname));
		$db->queryExec($query);

		return $db->queryOneRow(sprintf("SELECT * FROM groups WHERE name = %s", $db->escapeString($gname)));
	}
}
