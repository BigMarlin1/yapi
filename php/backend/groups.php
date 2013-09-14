<?php
require_once('config.php');
require_once(PHP_DIR.'/backend/db.php');

Class groups
{
	// Return name for the groupid.
	public function getname($gid)
	{
		$db = new DB;
		$garr = $db->queryOneRow('SELECT name FROM groups WHERE id = '.$gid);
		return $garr['name'];
	}

	// Return groupid for the group name.
	public function getid($gname)
	{
		$db = new DB;
		$garr = $db->queryOneRow('SELECT id FROM groups WHERE name = '.$db->escapeString($gname));
		return $garr['id'];
	}

	// Return row for a group, or all groups if active.
	public function getactive($type, $name='')
	{
		switch ($type)
		{
			case 'backfill':
				$q = 'SELECT * FROM groups WHERE bactive = 1';
				break;
			case 'forward':
				$q = 'SELECT * FROM groups WHERE factive = 1';
				break;
			default:
				return false;
		}
		if ($name != '' && $name != 'all')
			$q .= " AND name = '{$name}'";

		$db = new DB;
		return $db->query($q);
	}

	// Get all groups that have at least 1 file.
	public function getstarted()
	{
		$db = new DB;
		return $db->query('SELECT name, id FROM groups WHERE tstatus = 1 AND lastart > 0 ORDER BY name ASC');
	}

	// Return the amount of files indexed for the group. Cache result with memcache.
	public function getfilecount($gid)
	{
		$db = new DB;
		$cnt = $db->query('SELECT COUNT(*) AS c FROM files_'.$gid, true);
		return $cnt[0]['c'];
	}

	// Return the amount of collections for the group. Cache result with memcache.
	public function getcollectioncount($gid)
	{
		$db = new DB;
		$cnt = $db->query('SELECT COUNT(DISTINCT(chash)) AS c FROM files_'.$gid, true);
		return $cnt[0]['c'];
	}

	// Return everything sorted by name.
	public function getallsortname()
	{
		$db = new DB;
		return $db->query('SELECT * FROM groups ORDER BY name');
	}

	// Returns row(s) for group(s).
	public function getgroupinfo($group='')
	{
		$db = new DB;
		if ($group == '')
			return $db->query('SELECT * FROM groups');
		else if (is_numeric($group))
			return $db->queryOneRow('SELECT * FROM groups WHERE id = '.$group);
		else
			return $db->queryOneRow('SELECT * FROM groups WHERE name = '.$db->escapeString($group));
	}

	// Resets files / parts for the group(s).
	public function tablereset($gname='')
	{
		$db = new DB;
		$done = 0;
		if ($gname == '')
		{
			$groups = $this->getgroupinfo($gname);
			
			foreach ($groups as $group)
			{
				if ($group['tstatus'] == 1)
				{
					$db->queryExec('TRUNCATE TABLE parts_'.$group['id']);
					$db->queryExec('TRUNCATE TABLE files_'.$group['id']);
					$db->queryExec('UPDATE groups SET firstdate = 0, lastdate = 0, firstart = 0, lastart = 0, factive = 0, bactive = 0 WHERE id = '.$group['id']);
					$done++;
				}
			}
		}
		else
		{
			if ($group['tstatus'] == 1)
			{
				$group = $this->getgroupinfo($gname);
				$db->queryExec('TRUNCATE TABLE parts_'.$group['id']);
				$db->queryExec('TRUNCATE TABLE files_'.$group['id']);
				$db->queryExec('UPDATE groups SET firstdate = 0, lastdate = 0, firstart = 0, lastart = 0, factive = 0, bactive = 0 WHERE id = '.$group['id']);
				$done = 1;
			}
		}
		return $done;
	}

	// Enable / disable group backfill or forward.
	public function grouptoggle($status, $type, $gname)
	{
		$db = new DB;
		
		$t = ' bactive';
		if ($type == 'forward')
			$t = ' factive';
		$s = ' = 0 ';
		if ($status == 'enable')
			$s = ' = 1 ';

		$db->queryExec('UPDATE groups SET'.$t.$s.'WHERE name = '.$db->escapeString($gname));

		return $db->queryOneRow('SELECT * FROM groups WHERE name = '.$db->escapeString($gname));
	}
}
