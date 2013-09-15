<?php
require_once('config.php');
require_once(PHP_DIR.'/backend/db.php');

Class files
{
	// For browsegroup and RSS. Cached with memcache medium expiry.
	public function getallforgroup($groupid, $offset, $limit='')
	{
		$db = new DB;

		$maxperpage = MAX_PERPAGE;
		// For RSS.
		if ($limit != '')
			$maxperpage = $limit;

		return $db->query(sprintf('SELECT *, SUM(fsize) AS size, SUM(parts) AS totalparts, SUM(partsa) AS actualparts, groups.name, groups.id AS groupid, MAX(nstatus) AS nstatust FROM files_%d INNER JOIN groups ON groups.id = groupid GROUP BY chash ORDER BY utime DESC LIMIT %d OFFSET %d', $groupid, $maxperpage, $offset), true, CACHE_MEXPIRY);
	}

	// Get count of all files for browsegroup and RSS. Cached with memcache long expiry.
	public function getcount($groupid, $limit='')
	{
		$db = new DB;
		$cnt = $db->query('SELECT COUNT(DISTINCT(chash)) AS c FROM files_'.$groupid, true);
		return $cnt[0]['c'];
	}

	// Get the sum of a file for browsenzb.
	public function getforbnzb($chash, $groupid)
	{
		$db = new DB;
		return $db->queryOneRow(sprintf('SELECT *, SUM(fsize) AS size, SUM(parts) AS totalparts, SUM(partsa) AS actualparts, MAX(nstatus) AS nstatust FROM files_%d WHERE chash = %s GROUP BY chash', $groupid, $db->escapeString($chash)));
	}

	// Get for browse page, need to select from all groups. Also used for RSS. Cache with memcache long.
	public function getforbrowse($offset, $limit='')
	{
		$db = new DB;
		$tids = $db->query('SELECT id FROM groups WHERE tstatus = 1 AND lastdate > 0 ORDER BY lastdate DESC LIMIT '.MAX_PERPAGE);
		$count = count($tids);
		if ($count > 0)
		{
			$fstr = '';
			$i = 1;

			$maxperpage = MAX_PERPAGE;
			// For RSS.
			if ($limit != '')
				$maxperpage = $limit;

			$max = $maxperpage;
			if ($count > 25)
				$max = ($maxperpage / 2);

			foreach ($tids as $tid)
			{
				$id = $tid['id'];
				if ($i++ == $count)
					$fstr .= '(SELECT *, SUM(fsize) AS size, SUM(parts) AS totalparts, SUM(partsa) AS actualparts, MAX(nstatus) AS nstatust FROM files_'.$id.' GROUP BY chash ORDER BY utime DESC LIMIT '.$max.' OFFSET '.$offset.')';
				else
					$fstr .= '(SELECT *, SUM(fsize) AS size, SUM(parts) AS totalparts, SUM(partsa) AS actualparts, MAX(nstatus) AS nstatust FROM files_'.$id.' GROUP BY chash ORDER BY utime DESC LIMIT '.$max.' OFFSET '.$offset.') UNION ';
			}
			return $db->query('SELECT files.*, groups.name, groups.id AS groupid FROM ('.$fstr.') AS files INNER JOIN groups ON groups.id = files.groupid ORDER BY utime DESC LIMIT '.$maxperpage, true);
		}
		else
			return false;
	}

	// Count for browseall paginator. Cache with memcache long.
	public function getbrowsecount()
	{
		$db = new DB;
		$tids = $db->query('SELECT id FROM groups WHERE tstatus = 1 AND lastdate > 0 ORDER BY lastdate DESC LIMIT '.MAX_PERPAGE);
		$count = count($tids);
		if ($count === 1)
		{
			$c = $db->queryOneRow('SELECT COUNT(DISTINCT(chash)) AS cnt FROM files_'.$tids[0]['id']);
			return $c['cnt'];
		}
		else if ($count > 1)
		{
			$fstr = '';
			$i = 1;

			foreach ($tids as $tid)
			{
				$id = $tid['id'];
				if ($i == 1)
					$fstr .= '(SELECT COUNT(DISTINCT(chash)) FROM files_'.$id.') +';
				else if ($i == $count)
					$fstr .= ' (SELECT COUNT(DISTINCT(chash)) FROM files_'.$id.')';
				else
					$fstr .= ' (SELECT COUNT(DISTINCT(chash)) FROM files_'.$id.') +';
				$i++;
			}
			$c = $db->queryOneRow('SELECT '.$fstr.' AS cnt');
			return $c['cnt'];
		}
		else
			return false;
	}

	// Get for search page, need to select from all groups. Cache with memcache long.
	public function getforsearch($subject, $age, $group, $offset)
	{
		$db = new DB;
		// Convert age to seconds.
		if ($age == 0 || !is_numeric($age))
			$age = '';
		else
			$age = 'AND utime > '.(time() - ($age * 86400));

		// Split multi search terms.
		$words = explode(' ', $subject);
		$subq = '';
		$i = 0;
		if (count($words) > 0)
		{
			foreach ($words as $word)
			{
				if ($word != "")
				{
					if ($i++ == 0 && (strpos($word, '^') === 0))
						$subq .= sprintf(' AND subject LIKE %s', $db->escapeString(substr($word, 1).'%'));
					elseif (substr($word, 0, 2) == '--')
						$subq .= sprintf(' AND subject NOT LIKE %s', $db->escapeString('%'.substr($word, 2).'%'));
					else
						$subq .= sprintf(' AND subject LIKE %s', $db->escapeString('%'.$word.'%'));
				}
			}
		}

		if ($group == 'all')
		{
			$tids = $db->query('SELECT id FROM groups WHERE tstatus = 1 AND lastdate > 0 ORDER BY lastdate DESC LIMIT '.MAX_PERPAGE);
			$count = count($tids);
			$fstr = '';
			$i = 1;
			$max = MAX_PERPAGE;
			if ($count > 25)
				$max = (MAX_PERPAGE / 2);

			foreach ($tids as $tid)
			{
				$id = $tid['id'];
				if ($i++ == $count)
					$fstr .= sprintf('(SELECT *, SUM(fsize) AS size, SUM(parts) AS totalparts, SUM(partsa) AS actualparts, MAX(nstatus) AS nstatust FROM files_%d WHERE 1=1 %s %s GROUP BY chash ORDER BY utime DESC LIMIT %d OFFSET %d)', $id, $subq, $age, $max, $offset);
				else
					$fstr .= sprintf('(SELECT *, SUM(fsize) AS size, SUM(parts) AS totalparts, SUM(partsa) AS actualparts, MAX(nstatus) AS nstatust FROM files_%d WHERE 1=1 %s %s GROUP BY chash ORDER BY utime DESC LIMIT %d OFFSET %d) UNION ', $id, $subq, $age, $max, $offset);
			}
			return $db->query('SELECT files.*, groups.name, groups.id AS groupid FROM ('.$fstr.') AS files INNER JOIN groups ON groups.id = files.groupid ORDER BY utime DESC LIMIT '.MAX_PERPAGE, true);
		}
		else
			return $db->query(sprintf('SELECT *, groups.name, groups.id AS groupid, SUM(fsize) AS size, SUM(parts) AS totalparts, SUM(partsa) AS actualparts, MAX(nstatus) AS nstatust FROM files_%d INNER JOIN groups ON groups.id = groupid WHERE 1=1 %s %s GROUP BY chash ORDER BY utime DESC LIMIT %d OFFSET %d', $group, $subq, $age, MAX_PERPAGE, $offset));
	}

	// Count for search paginator. Cache with memcache long.
	public function getsearchcount($subject, $age, $group)
	{
		$db = new DB;
		if ($age == 0 || !is_numeric($age))
			$age = '';
		else
			$age = 'AND utime > '.(time() - ($age * 86400));

		// Split multi search terms.
		$words = explode(' ', $subject);
		$subq = '';
		$i = 0;
		if (count($words) > 0)
		{
			foreach ($words as $word)
			{
				if ($word != '')
				{
					if ($i++ == 0 && (strpos($word, '^') === 0))
						$subq .= sprintf(' AND subject LIKE %s', $db->escapeString(substr($word, 1).'%'));
					elseif (substr($word, 0, 2) == '--')
						$subq .= sprintf(' AND subject NOT LIKE %s', $db->escapeString('%'.substr($word, 2).'%'));
					else
						$subq .= sprintf(' AND subject LIKE %s', $db->escapeString('%'.$word.'%'));
				}
			}
		}

		if ($group == 'all')
		{
			$tids = $db->query('SELECT id FROM groups WHERE tstatus = 1 AND lastdate > 0 ORDER BY lastdate DESC LIMIT '.MAX_PERPAGE);
			$count = count($tids);
			if ($count === 1)
			{
				$c = $db->queryOneRow(sprintf('SELECT COUNT(DISTINCT(chash)) AS cnt FROM files_%d WHERE 1=1 %s %s', $tids[0]["id"], $subq, $age));
				return $c['cnt'];
			}
			else if ($count > 0)
			{
				$fstr = '';
				$i = 1;

				foreach ($tids as $tid)
				{
					$id = $tid['id'];
					if ($i == 1)
						$fstr .= sprintf('(SELECT COUNT(DISTINCT(chash)) FROM files_%d WHERE 1=1 %s %s) +', $id, $subq, $age);
					else if ($i == $count)
						$fstr .= sprintf(' (SELECT COUNT(DISTINCT(chash)) FROM files_%d WHERE 1=1 %s %s)', $id, $subq, $age);
					else
						$fstr .= sprintf(' (SELECT COUNT(DISTINCT(chash)) FROM files_%d WHERE 1=1 %s %s) +', $id, $subq, $age);
					$i++;
				}
				$c = $db->queryOneRow('SELECT '.$fstr.' AS cnt');
				return $c['cnt'];
			}
			else
				return false;
		}
		else
		{
			$c = $db->queryOneRow(sprintf('SELECT COUNT(DISTINCT(chash)) AS cnt FROM files_%d WHERE 1=1 %s %s', $group, $subq, $age));
			return $c['cnt'];
		}
	}

	// Search, for API.
	public function apisearch($subject, $age, $group, $offset, $limit, $minsize, $maxsize, $sargs)
	{
		$db = new DB;
		$ages = $minsizes = $maxsizes = '';
		// Convert age to seconds.
		if ($age > 0 && is_numeric($age))
			$ages = 'AND utime > '.(time() - ($age * 86400));

		// Sorting.
		$sort = 'ORDER BY utime DESC';
		if ($sargs != 0)
		{
			if (count($sargs == 2))
			{
				switch($sargs[0])
				{
					case 'name':
						$farg = 'subject';
						break;
					case 'size':
						$farg = 'tsize';
						break;
					case 'files':
						$farg = 'tfiles';
						break;
					case 'date':
						$farg = 'utime';
						break;
					case 'poster':
						$farg = 'poster';
						break;
					default:
						$farg = 'utime';
				}
				$sort = 'ORDER BY '.$farg.' '.$sargs[1];
			}
		}

		if (is_numeric($minsize))
		{
			$minsizes = 'HAVING SUM(fsize) > '.$minsize;
			if ($maxsize > 0 && is_numeric($maxsize))
				$maxsizes = 'AND SUM(fsize) < '.$maxsize;
		}

		// Split multi search terms.
		$words = explode('.', $subject);
		$subq = '';
		$i = 0;
		if (count($words) > 0)
		{
			foreach ($words as $word)
			{
				if ($word != '')
				{
					if ($i++ == 0 && (strpos($word, '^') === 0))
						$subq .= sprintf(' AND subject LIKE %s', $db->escapeString(substr($word, 1).'%'));
					elseif (substr($word, 0, 2) == '--')
						$subq .= sprintf(' AND subject NOT LIKE %s', $db->escapeString('%'.substr($word, 2).'%'));
					else
						$subq .= sprintf(' AND subject LIKE %s', $db->escapeString('%'.$word.'%'));
				}
			}
		}

		if (is_numeric($group) && $group == 0)
		{
			$tids = $db->query('SELECT id FROM groups WHERE tstatus = 1 AND lastdate > 0 ORDER BY lastdate DESC LIMIT '.$limit);
			$count = count($tids);
			$fstr = '';
			$i = 1;
			$max = $limit;
			if ($count > 25)
				$max = ($limit / 2);

			foreach ($tids as $tid)
			{
				$id = $tid['id'];
				if ($i++ == $count)
					$fstr .= sprintf('(SELECT *, SUM(fsize) AS tsize, SUM(partsa) AS tfiles, MAX(nstatus) AS nstatust FROM files_%d WHERE 1=1 %s %s GROUP BY chash %s %s %s LIMIT %d OFFSET %d)', $id, $subq, $ages, $minsizes, $maxsizes, $sort, $max, $offset);
				else
					$fstr .= sprintf('(SELECT *, SUM(fsize) AS tsize, SUM(partsa) AS tfiles, MAX(nstatus) AS nstatust FROM files_%d WHERE 1=1 %s %s GROUP BY chash %s %s %s LIMIT %d OFFSET %d) UNION ', $id, $subq, $ages, $minsizes, $maxsizes, $sort, $max, $offset);
			}
			return $db->query('SELECT files.*, groups.name, groups.id AS groupid, tsize, tfiles FROM ('.$fstr.') AS files INNER JOIN groups ON groups.id = files.groupid '.$sort.' LIMIT '.$limit);
		}
		else
		{
			$gq = $db->queryOneRow('SELECT id FROM groups WHERE name = '.$db->escapeString($group).' AND tstatus = 1');
			if ($gq === false)
				return array();

			return $db->query(sprintf('SELECT *, groups.name, groups.id AS groupid, SUM(fsize) AS tsize, MAX(nstatus) AS nstatust FROM files_%d INNER JOIN groups ON groups.id = groupid WHERE 1=1 %s %s GROUP BY chash %s %s %s LIMIT %d OFFSET %d', $gq['id'], $subq, $ages, $minsizes, $maxsizes, $sort, $limit, $offset));
		}
	}

	// Get all files for nzbcontents.
	public function getforbnzbcontents($chash, $groupid, $offset)
	{
		$db = new DB;
		return $db->query(sprintf('SELECT * FROM files_%d WHERE chash = %s ORDER BY origsubject ASC LIMIT %d OFFSET %d', $groupid, $db->escapeString($chash), MAX_PERPAGE, $offset), true);
	}

	// Count for nzbcontents pagination.
	public function countforbnzbcontents($chash, $groupid)
	{
		$db = new DB;
		$c = $db->queryOneRow(sprintf('SELECT COUNT(*) AS cnt FROM files_%d WHERE chash = %s', $groupid, $db->escapeString($chash)));
		return $c['cnt'];
	}

	// Get by chash for api, when no groupid look through all groups.
	public function getbychash($chash, $groupname)
	{
		$db = new DB;
		if ($groupname == 'all')
		{
			$tids = $db->query('SELECT id FROM groups WHERE tstatus = 1 AND lastdate > 0 ORDER BY lastdate DESC LIMIT '.MAX_PERPAGE);
			$count = count($tids);
			if ($count == 0)
				return false;
			else if ($count === 1)
				return $db->queryOneRow(sprintf('SELECT subject, origsubject, chash, groupid, utime, SUM(fsize) AS size, MAX(nstatus) AS nstatust FROM files_%d WHERE chash = %s GROUP BY chash', $tids[0]['id'], $db->escapeString($chash)));
			else
			{
				$fstr = '';
				$i = 1;
				foreach ($tids as $tid)
				{
					$id = $tid['id'];
					if ($i++ == $count)
						$fstr .= '(SELECT subject, origsubject, chash, groupid, utime, SUM(fsize) AS size, MAX(nstatus) AS nstatust FROM files_'.$id.' WHERE chash = '.$db->escapeString($chash).' GROUP BY chash)';
					else
						$fstr .= '(SELECT subject, origsubject, chash, groupid, utime, SUM(fsize) AS size, MAX(nstatus) AS nstatust FROM files_'.$id.' WHERE chash = '.$db->escapeString($chash).' GROUP BY chash) UNION ';
				}
				return $db->queryOneRow('SELECT subject, origsubject, chash, groupid, utime, size, nstatust FROM ('.$fstr.') AS files LIMIT 1');
			}
		}
		else
		{
			$gq = $db->queryOneRow('SELECT id FROM groups WHERE name = '.$db->escapeString($groupname));
			if ($gq == false)
				return false;

			return $db->queryOneRow(sprintf('SELECT subject, origsubject, chash, groupid, utime, SUM(fsize) AS size, MAX(nstatus) AS nstatust FROM files_%d WHERE chash = %s GROUP BY chash', $gq['id'], $db->escapeString($chash)));
		}
	}
}

?>
