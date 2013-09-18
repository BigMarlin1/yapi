<?php
require_once(PHP_DIR.'backend/db.php');

Class files
{
	// For browsegroup and RSS. Cached with memcache medium expiry.
	public function getallforgroup($groupid, $offset, $limit='')
	{
		$maxperpage = MAX_PERPAGE;
		// For RSS.
		if ($limit != '')
			$maxperpage = $limit;

		$ps = '';
		if (HIDE_PASSWORDED === true)
			$ps = 'WHERE pstatus > -50';

		$db = new DB;
		$startq = $db->query(sprintf('SELECT DISTINCT(chash) AS c FROM files_%d %s ORDER BY utime DESC LIMIT %d OFFSET %d', $groupid, $ps, $maxperpage, $offset));
		$scount = count($startq);
		if ($scount == 0)
		{
			$db = null;
			return false;
		}
		else
		{
			$fstr = 'SELECT *, SUM(fsize) AS size, SUM(parts) AS totalparts, SUM(partsa) AS actualparts, groups.name, groups.id AS groupid, MAX(nstatus) AS nstatust FROM files_'.$groupid.' INNER JOIN groups ON groups.id = groupid WHERE chash IN (';
			$now = 1;
			foreach ($startq as $c)
			{
				if ($now++ < $scount)
					$fstr .= "'".$c['c']."',";
				else
					$fstr .= "'".$c['c']."'";
			}
			$fstr .= ') GROUP BY chash ORDER BY utime DESC LIMIT '.$maxperpage;
			$result = $db->query($fstr, true, CACHE_MEXPIRY);
			$db = null;
			return $result;
		}
	}

	// Get count of all files for browsegroup and RSS. Cached with memcache long expiry.
	public function getcount($groupid, $limit='')
	{
		$db = new DB;
		$ps = '';
		if (HIDE_PASSWORDED === true)
			$ps = 'WHERE pstatus > -50';

		$cnt = $db->query(sprintf('SELECT COUNT(DISTINCT(chash)) AS c FROM files_%d %s', $groupid, $ps), true);
		$db = null;
		return $cnt[0]['c'];
	}

	// Get the sum of a file for browsenzb.
	public function getforbnzb($chash, $groupid)
	{
		$db = new DB;
		$result = $db->queryOneRow(sprintf('SELECT *, SUM(fsize) AS size, SUM(parts) AS totalparts, SUM(partsa) AS actualparts, MAX(nstatus) AS nstatust FROM files_%d WHERE chash = %s GROUP BY chash', $groupid, $db->escapeString($chash)));
		$db = null;
		return $result;
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

			$ps = '';
			if (HIDE_PASSWORDED === true)
				$ps = 'WHERE pstatus > -50';

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
				$startq = $db->query(sprintf('SELECT DISTINCT(chash) AS c FROM files_%d %s ORDER BY utime DESC LIMIT %d OFFSET %d', $id, $ps, $max, $offset));
				$scount = count($startq);

				if ($i++ == $count)
				{
					if ($scount == 0)
						$fstr = str_replace('UNION', '', $fstr);
					else
					{
						$fstr .= '(SELECT *, SUM(fsize) AS size, SUM(parts) AS totalparts, SUM(partsa) AS actualparts, MAX(nstatus) AS nstatust FROM files_'.$id.' WHERE chash IN (';
						$now = 1;
						foreach ($startq as $c)
						{
							if ($now++ < $scount)
								$fstr .= "'".$c['c']."',";
							else
								$fstr .= "'".$c['c']."'";
						}
						$fstr .= ') GROUP BY chash ORDER BY utime DESC LIMIT '.$max.')';
					}
				}
				else
				{
					if ($scount == 0)
						continue;
					else
					{
						$fstr .= '(SELECT *, SUM(fsize) AS size, SUM(parts) AS totalparts, SUM(partsa) AS actualparts, MAX(nstatus) AS nstatust FROM files_'.$id.' WHERE chash IN (';
						$now = 1;
						foreach ($startq as $c)
						{
							if ($now++ < $scount)
								$fstr .= "'".$c['c']."',";
							else
								$fstr .= "'".$c['c']."'";
						}
						$fstr .= ') GROUP BY chash ORDER BY utime DESC LIMIT '.$max.') UNION';
					}
				}
			}
			$result = $db->query('SELECT files.*, groups.name, groups.id AS groupid FROM ('.$fstr.') AS files INNER JOIN groups ON groups.id = groupid ORDER BY utime DESC LIMIT '.$maxperpage, true);
			$db = null;
			return $result;
		}
		else
		{
			$db = null;
			return false;
		}
	}

	// Count for browseall paginator. Cache with memcache long.
	public function getbrowsecount()
	{
		$db = new DB;
		$ps = '';
		if (HIDE_PASSWORDED === true)
			$ps = 'WHERE pstatus > -50';

		$tids = $db->query('SELECT id FROM groups WHERE tstatus = 1 AND lastdate > 0 ORDER BY lastdate DESC LIMIT '.MAX_PERPAGE);
		$count = count($tids);
		if ($count === 1)
		{
			$c = $db->queryOneRow(sprintf('SELECT COUNT(DISTINCT(chash)) AS cnt FROM files_%d %s', $tids[0]['id'], $ps));
			$db = null;
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
					$fstr .= '(SELECT COUNT(DISTINCT(chash)) FROM files_'.$id.' '.$ps.') +';
				else if ($i == $count)
					$fstr .= ' (SELECT COUNT(DISTINCT(chash)) FROM files_'.$id.' '.$ps.')';
				else
					$fstr .= ' (SELECT COUNT(DISTINCT(chash)) FROM files_'.$id.' '.$ps.') +';
				$i++;
			}
			$c = $db->queryOneRow('SELECT '.$fstr.' AS cnt');
			$db = null;
			return $c['cnt'];
		}
		else
		{
			$db = null;
			return false;
		}
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

		$ps = '';
		if (HIDE_PASSWORDED === true)
			$ps = 'AND pstatus > -50';

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
				$startq = $db->query(sprintf('SELECT DISTINCT(chash) AS c FROM files_%d WHERE 1=1 %s %s %s ORDER BY utime DESC LIMIT %d OFFSET %d', $id, $subq, $age, $ps, $max, $offset));
				$scount = count($startq);

				if ($i++ == $count)
				{
					if ($scount == 0)
						$fstr = str_replace('UNION', '', $fstr);
					else
					{
						$fstr .= '(SELECT *, SUM(fsize) AS size, SUM(parts) AS totalparts, SUM(partsa) AS actualparts, MAX(nstatus) AS nstatust FROM files_'.$id.' WHERE chash IN (';
						$now = 1;
						foreach ($startq as $c)
						{
							if ($now++ < $scount)
								$fstr .= "'".$c['c']."',";
							else
								$fstr .= "'".$c['c']."'";
						}
						$fstr .= ') GROUP BY chash ORDER BY utime DESC LIMIT '.$max.')';
					}
				}
				else
				{
					if ($scount == 0)
						continue;
					else
					{
						$fstr .= '(SELECT *, SUM(fsize) AS size, SUM(parts) AS totalparts, SUM(partsa) AS actualparts, MAX(nstatus) AS nstatust FROM files_'.$id.' WHERE chash IN (';
						$now = 1;
						foreach ($startq as $c)
						{
							if ($now++ < $scount)
								$fstr .= "'".$c['c']."',";
							else
								$fstr .= "'".$c['c']."'";
						}
						$fstr .= ') GROUP BY chash ORDER BY utime DESC LIMIT '.$max.') UNION';
					}
				}
			}
			$result = $db->query('SELECT files.*, groups.name, groups.id AS groupid FROM ('.$fstr.') AS files INNER JOIN groups ON groups.id = files.groupid ORDER BY utime DESC LIMIT '.MAX_PERPAGE, true);
			$db = null;
			return $result;
		}
		else
		{
			$result = $db->query(sprintf('SELECT *, groups.name, groups.id AS groupid, SUM(fsize) AS size, SUM(parts) AS totalparts, SUM(partsa) AS actualparts, MAX(nstatus) AS nstatust FROM files_%d INNER JOIN groups ON groups.id = groupid WHERE 1=1 %s %s %s GROUP BY chash ORDER BY utime DESC LIMIT %d OFFSET %d', $group, $subq, $age, $ps, MAX_PERPAGE, $offset));
			$db = null;
			return $result;
		}
	}

	// Count for search paginator. Cache with memcache long.
	public function getsearchcount($subject, $age, $group)
	{
		$db = new DB;
		if ($age == 0 || !is_numeric($age))
			$age = '';
		else
			$age = 'AND utime > '.(time() - ($age * 86400));

		$ps = '';
		if (HIDE_PASSWORDED === true)
			$ps = 'AND pstatus > -50';

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
				$c = $db->queryOneRow(sprintf('SELECT COUNT(DISTINCT(chash)) AS cnt FROM files_%d WHERE 1=1 %s %s %s', $tids[0]["id"], $subq, $age, $ps));
				$db = null;
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
						$fstr .= sprintf('(SELECT COUNT(DISTINCT(chash)) FROM files_%d WHERE 1=1 %s %s %s) +', $id, $subq, $age, $ps);
					else if ($i == $count)
						$fstr .= sprintf(' (SELECT COUNT(DISTINCT(chash)) FROM files_%d WHERE 1=1 %s %s %s)', $id, $subq, $age, $ps);
					else
						$fstr .= sprintf(' (SELECT COUNT(DISTINCT(chash)) FROM files_%d WHERE 1=1 %s %s %s) +', $id, $subq, $age, $ps);
					$i++;
				}
				$c = $db->queryOneRow('SELECT '.$fstr.' AS cnt');
				$db = null;
				return $c['cnt'];
			}
			else
			{
				$db = null;
				return false;
			}
		}
		else
		{
			$c = $db->queryOneRow(sprintf('SELECT COUNT(DISTINCT(chash)) AS cnt FROM files_%d WHERE 1=1 %s %s %s', $group, $subq, $age, $ps));
			$db = null;
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

		$ps = '';
		if (HIDE_PASSWORDED === true)
			$ps = 'AND pstatus > -50';

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
					$fstr .= sprintf('(SELECT *, SUM(fsize) AS tsize, SUM(partsa) AS tfiles, MAX(nstatus) AS nstatust FROM files_%d WHERE 1=1 %s %s %s GROUP BY chash %s %s %s LIMIT %d OFFSET %d)', $id, $subq, $ages, $ps, $minsizes, $maxsizes, $sort, $max, $offset);
				else
					$fstr .= sprintf('(SELECT *, SUM(fsize) AS tsize, SUM(partsa) AS tfiles, MAX(nstatus) AS nstatust FROM files_%d WHERE 1=1 %s %s %s GROUP BY chash %s %s %s LIMIT %d OFFSET %d) UNION ', $id, $subq, $ages, $ps, $minsizes, $maxsizes, $sort, $max, $offset);
			}
			$result = $db->query('SELECT files.*, groups.name, groups.id AS groupid, tsize, tfiles FROM ('.$fstr.') AS files INNER JOIN groups ON groups.id = files.groupid '.$sort.' LIMIT '.$limit);
			$db = null;
			return $result;
		}
		else
		{
			$gq = $db->queryOneRow('SELECT id FROM groups WHERE name = '.$db->escapeString($group).' AND tstatus = 1');
			if ($gq === false)
			{
				$db = null;
				return array();
			}

			$result = $db->query(sprintf('SELECT *, groups.name, groups.id AS groupid, SUM(fsize) AS tsize, MAX(nstatus) AS nstatust FROM files_%d INNER JOIN groups ON groups.id = groupid WHERE 1=1 %s %s %s GROUP BY chash %s %s %s LIMIT %d OFFSET %d', $gq['id'], $subq, $ages, $ps, $minsizes, $maxsizes, $sort, $limit, $offset));
			$db = null;
			return $result;
		}
	}

	// Get all files for nzbcontents.
	public function getforbnzbcontents($chash, $groupid, $offset)
	{
		$db = new DB;
		$result = $db->query(sprintf('SELECT * FROM files_%d WHERE chash = %s ORDER BY origsubject ASC LIMIT %d OFFSET %d', $groupid, $db->escapeString($chash), MAX_PERPAGE, $offset), true);
		$db = null;
		return $result;
	}

	// Count for nzbcontents pagination.
	public function countforbnzbcontents($chash, $groupid)
	{
		$db = new DB;
		$c = $db->queryOneRow(sprintf('SELECT COUNT(*) AS cnt FROM files_%d WHERE chash = %s', $groupid, $db->escapeString($chash)));
		$db = null;
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
			{
				$db = null;
				return false;
			}
			else if ($count === 1)
			{
				$result = $db->queryOneRow(sprintf('SELECT subject, origsubject, chash, groupid, utime, SUM(fsize) AS size, MAX(nstatus) AS nstatust FROM files_%d WHERE chash = %s GROUP BY chash', $tids[0]['id'], $db->escapeString($chash)));
				$db = null;
				return $result;
			}
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
				$result = $db->queryOneRow('SELECT subject, origsubject, chash, groupid, utime, size, nstatust FROM ('.$fstr.') AS files LIMIT 1');
				$db = null;
				return $result;
			}
		}
		else
		{
			$gq = $db->queryOneRow('SELECT id FROM groups WHERE name = '.$db->escapeString($groupname));
			if ($gq == false)
			{
				$db = null;
				return false;
			}
			$result = $db->queryOneRow(sprintf('SELECT subject, origsubject, chash, groupid, utime, SUM(fsize) AS size, MAX(nstatus) AS nstatust FROM files_%d WHERE chash = %s GROUP BY chash', $gq['id'], $db->escapeString($chash)));
			$db = null;
			return $result;
		}
	}

	// Return innerfiles by chash.
	public function getrarfiles($chash, $group)
	{
		$db = new DB;
		$result = $db->query(sprintf('SELECT i.ifname, i.ifsize, i.iftime FROM innerfiles i INNER JOIN files_%d f ON f.fhash = i.fhash WHERE f.chash = %s', $group, $db->escapeString($chash)));
		$db = null;
		return $result;
	}
}
?>
