<?php
require_once('config.php');
require_once(PHP_DIR.'/backend/db.php');
require_once(PHP_DIR.'/backend/groups.php');
require_once(PHP_DIR.'/backend/matchfiles.php');
require_once(PHP_DIR.'/backend/nntp.php');

Class headers
{
	function headers($echo=false)
	{
		$this->vecho = $echo;
		$this->debug = DEBUG_MESSAGES;
		$this->newgroupfetch = NEW_HEADERS;
		$this->loopsize = QTY_HEADERS;
		$this->message = array();
		$this->alternate = false;
	}

	public function main($group, $type, $headers='', $alternate=false)
	{
		if (NNTP_ALTERNATE === true)
			$this->alternate = $alternate;

		$g = new groups;
		switch($type)
		{
			case 'backfill':
				$this->start($g->getactive('backfill', $group), $type, $headers);
				break;
			case 'forward':
				$this->start($g->getactive('forward', $group), $type, $headers);
				break;
		}
	}

	// Check if tables exist, start backfilling/updating functions  depending on args.
	public function start($groups, $type, $headers)
	{
		if (count($groups) > 0)
		{
			foreach ($groups as $group)
			{
				if ($group['tstatus'] == 0)
				{
					$db = new DB;
					if ($db->newtables($group['id']) === false)
						exit("There is a problem creating new parts/files tables for this group.\n");
					else
						echo 'This is the first time running group '.$group['name'].", we created the required SQL tables for it.\nThe next time you run this script the group will start working.\n";
				}
				else
				{
					$group['ftname'] = 'files_'.$group['id'];
					$group['ptname'] = 'parts_'.$group['id'];
					if ($type == 'backfill')
					{
						if ($this->backfill($group, $headers) === false)
							continue;
					}
					else if ($type == 'forward')
					{
						if ($this->forward($group) === false)
							continue;
					}
				}
			}
		}
		else
		{
			if ($type == 'backfill')
				exit ("Make sure you have enabled backfilling for the group(s) (php group_toggle.php enable backfill alt.binaries.teevee)\n");
			else if ($type == 'forward')
				exit ("Make sure you have enabled forward updating of group(s) (php group_toggle.php enable forward alt.binaries.teevee)\n");
		}
	}

	// Check if the group needs updating, send newest/oldest article numbers to loop.
	public function forward($group)
	{
		$nntp = new Nntp;
		if ($nntp->doConnect(true, $this->alternate) === false)
			return false;

		$gover = $nntp->selectGroup($group['name']);
		if(PEAR::isError($gover))
		{
			$gover = $nntp->dataError($nntp, $group['name'], false, $this->alternate);
			if ($gover === false)
				return false;
		}
		$nntp->doQuit();

		// For alternate provider.
		$lastart = $group['lastart'];
		if ($this->alternate === true)
			$lastart = $group['lastarta'];

		$new = false;
		// New group.
		if($lastart == 0)
		{
			$new = true;
			$oldest = $gover['last'] - $this->newgroupfetch;
			if ($oldest <= $gover['first'])
				$oldest = $gover['first'];
			$newest = $gover['last'];
		}
		else if ($lastart < $gover['last'])
		{
			$oldest = $lastart + 1;
			$newest = $gover['last'];
		}
		// Nothing to do we already have the article.
		else if ($lastart >= $gover['last'])
		{
			if ($this->vecho)
				echo 'No new articles for group '.$group['name'].".\n";
			return false;
		}
		else
			return false;

		// Not enough articles, skip.
		if (($newest - $oldest) < 2)
		{
			if ($this->vecho)
				echo 'No new articles for group '.$group['name'].".\n";
			return false;
		}

		$ret = $this->loopforward($oldest, $newest, $newest-$oldest, $group);

		$firstart = $group['firstart'];
		$provider = 0;
		$cols = '';
		if ($this->alternate == true)
		{
			$firstart = $group['firstarta'];
			$provider = 1;
			$cols = 'a';
		}

		// Update oldest time/article#.
		if ($new === true || $firstart == 0)
		{
			$db = new DB;
			$row = $db->queryOneRow(sprintf('SELECT f.utime, p.anumber FROM files_%d f INNER JOIN parts_%d p ON p.fileid = f.id WHERE p.provider = %d ORDER BY f.utime ASC LIMIT 1', $group['id'], $group['id'], $provider));
			if ($row != false)
				$db->queryExec(sprintf('UPDATE groups SET firstdate%s = %d, firstart%s = %d WHERE id = %d', $cols, $row['utime'], $cols, $row['anumber'], $group['id']));
		}
		return $ret;
	}

	// Go over the total wanted articles, split them up in smaller chunks to download at a time. Do oldest to newest.
	public function loopforward($oldest, $newest, $total, $group)
	{
		$under = $first = $done = false;
		$newart = $oldart = 0;
		$max = $this->loopsize;
		while ($done === false)
		{
			// First run over $max.											ex.: (75000 - 10000) = 65000 ;; 65000 > 20000 ;; true
			if ($first === false && ($newest - $oldest) > $max)
			{
				// The newest article we want.								ex.: (10000 + 20000) = 30000
				$newart = $oldest + $max;
				// The oldest article we want.								ex.: 10000
				$oldart = $oldest;
				// First run done, so don't rerun this if, also don't run the next if.
				$first = true;
			}
			// First run but under $max.									ex.: (25000 - 10000) = 15000 ;; 15000 <= 20000 ;; true
			else if ($first === false && ($newest - $oldest) <= $max)
			{
				$newart = $newest;										//	ex.: 25000
				$oldart = $oldest;										//	ex.: 10000
				// Marks that we are done.
				$under = true;
			}
			// Subsequent runs smaller than $max.
			else if (($newart + 1) < ($newest - $max))					//	ex.: (30000 + 1) = 30001 ;; (75000 - 20000) = 50000 ;; 30001 < 50000 ;; true
			{
				$oldart = $newart + 1;									//	ex.: (30000 + 1) = 30001
				$newart = $oldart + $max;								//	ex.: (35001 + 20000) = 55001
			}
			// Subsequent runs bigger than $max.
			else if (($newart + 1) >= ($newest - $max))					//	ex.: (50001 + 1) = 50002 ;; (75000 - 20000) = 50000  ;; 50002 >= 50000 ;; true
			{
				$oldart = $newart + 1;									//	ex.: (55001 + 1) = 55002
				$newart = $newest;										//	ex.: 75000
				$under = true;
			}

			// Start downloading headers.
			$this->fetchheaders($oldart, $total, ($newest - $newart), round((($oldart - $oldest) / $total) * 100), $newart, $group, 'forward');

			// Done.
			if ($under === true || $newart >= $newest)
				$done = true;
		}
		return true;
	}

	// Check if we can backfill send newest/oldest article numbers to loop.
	public function backfill($group, $headers)
	{
		$nntp = new Nntp;
		if ($nntp->doConnect(true, $this->alternate) === false)
			return false;

		$gover = $nntp->selectGroup($group['name']);
		if(PEAR::isError($gover))
		{
			$gover = $nntp->dataError($nntp, $group['name'], false, $this->alternate);
			if ($gover === false)
				return false;
		}

		$nntp->doQuit();

		$firstart = $group['firstart'];
		if ($this->alternate === true)
			$firstart = $group['firstarta'];

		if ($firstart == 0)
		{
			if ($this->vecho)
				echo 'You can not backfill group '.$group['name']." until you run update_headers.php\n";
			return false;
		}
		else if ($firstart >= $gover['first'])
		{
			$newest = $firstart - 1;
			$oldest = $newest - $headers;
			// If it's older than the servers oldest, set to the servers oldest.
			if ($oldest <= $gover['first'])
				$oldest = $gover['first'];
		}
		else
		{
			if ($this->vecho)
				echo 'Unable to backfill further on group '.$group['name'].", our oldest stored article is older or as old than the servers oldest article.\n";
			return false;
		}

		if (($newest - $oldest) < 2)
		{
			if ($this->vecho)
				echo 'Unable to backfill further on group '.$group['name'].", our oldest stored article is older or as old than the servers oldest article.\n";
			return false;
		}

		$ret = $this->loopbackwards($oldest, $newest, $newest-$oldest, $group);

		$provider = 0;
		$cols = '';
		if ($this->alternate == true)
		{
			$provider = 1;
			$cols = 'a';
		}

		// Update oldest time/article# in the groups table.
		$db = new DB;
		$row = $db->queryOneRow(sprintf('SELECT f.utime, p.anumber FROM files_%d f INNER JOIN parts_%d p ON p.fileid = f.id WHERE p.provider = %d ORDER BY f.utime ASC LIMIT 1', $group['id'], $group['id'], $provider));
		if ($row != false)
			$db->queryExec(sprintf('UPDATE groups SET firstdate%s = %d, firstart%s = %d WHERE id = %d', $cols, $row['utime'], $cols, $row['anumber'], $group['id']));

		return $ret;
	}

	// Go over the total wanted articles, split them up in smaller chunks to download at a time. Do newest to oldest when backfilling.
	public function loopbackwards($oldest, $newest, $total, $group)
	{
		$under = $first = $done = false;
		$newart = $oldart = 0;
		$max = $this->loopsize;
		while ($done === false)
		{
			// First run over $max											ex.: (65000 - 10000) = 55000 ;; 55000 > 20000 ;; true
			if ($first === false && ($newest - $oldest) > $max)
			{
				// The newest article we want.								ex.: 65000;
				$newart = $newest;
				// The oldest article we want.								ex.: (65000 - 20000) = 45000
				$oldart = $newart - $max;
				// First run done, so don't rerun this if, also don't run the next if.
				$first = true;
			}
			// First run under $max.										ex.: (25000 - 10000) = 15000 ;; 15000 <= 20000 ;; true
			else if ($first === false && ($newest - $oldest) <= $max)
			{
				$newart = $newest;										//	ex.: 25000
				$oldart = $oldest;										//	ex.: 10000
				// Marks that we are done.
				$under = true;
			}
			// Subsequent runs over $oldest.
			else if ((($oldart - 1) - $max) > $oldest)					//	ex.: (45000 - 1) = 44999 ;; (44999 - 20000) = 24999 ;; 24999 > 10000 ;; true
			{
				$newart = $oldart - 1;									//	ex.: (45000 - 1) = 44999
				$oldart = $newart - $max;								//	ex.: (44999 - 20000) = 24999
			}
			// Subsequent runs under $oldest.
			else if ((($oldart - 1) - $max)  <= ($oldest))				//	ex.: ((24999 - 1) - 20000) = 4998 ;; 4998 <= 10000 ;; true
			{
				$newart = $oldart -1;									//	ex.: (24999 - 1) = 24998
				$oldart = $oldest;										//	ex.: 10000
				$under = true;
			}

			// Start downloading headers.
			$this->fetchheaders($oldart, $total, ($oldart - $oldest), round((($newest - $newart) / $total) * 100), $newart, $group, 'backfill');

			// Done.
			if ($under === true || $oldart <= $oldest)
				$done = true;
		}
		return true;
	}

	// Download headers, insert into DB.
	public function fetchheaders($oldest, $total, $left, $percent, $newest, $group, $type)
	{
		if ($this->vecho)
		{
			$pserver = NNTP_SERVER;
			if ($this->alternate === true)
				$pserver = NNTPA_SERVER;

			echo 'Fetching '.number_format($newest-$oldest).' article headers from '.$pserver.' for group '.$group['name'].', articles '.number_format($oldest).' to '.number_format($newest).".\n";
			if ($left > $this->loopsize)
				echo 'This group is '.$percent.'% done with '.number_format($left)." headers left in queue.\n";
		}

		$ustart = microtime(true);
		// Connect to usenet.
		$nntp = new Nntp;
		if ($nntp->doConnect(true, $this->alternate) === false)
			return false;

		// Select the group, reconnect if there's an error.
		$gc = $nntp->selectGroup($group['name']);
		if(PEAR::isError($gc))
		{
			$gover = $nntp->dataError($nntp, $group['name'], false, $this->alternate);
			if ($gover === false)
				return false;
		}

		// Download the messages, reconnect without compression if there's an error.
		$msgs = $nntp->getOverview($oldest."-".$newest, true, false);
		if(PEAR::isError($msgs))
		{
			$nntp->doQuit();
			if ($nntp->doConnect(false, $this->alternate) === false)
				return false;

			$gc = $nntp->selectGroup($group['name']);
			if(PEAR::isError($gc))
			{
				$gover = $nntp->dataError($nntp, $group['name'], false, $this->alternate);
				if ($gover === false)
					return false;
			}

			$msgs = $nntp->getOverview($oldest.'-'.$newest, true, false);
			if(PEAR::isError($msgs))
			{
				$nntp->doQuit();
				if ($this->echooutput)
					echo "Error downloading headers in function fetchheaders.\nError follows: {$msgs->code}: {$msgs->message}\n";
				return false;
			}
		}
		$nntp->doQuit();
		$uend = microtime(true);

		// Make sure we got more than 0 headers.
		if (is_array($msgs) && count($msgs) > 0)
		{
			$db = new DB;
			$matching = new matchfiles;
			$msgsreceived = $msgsignored = $this->message = array();

			$provider = 0;
			if ($this->alternate == true)
				$provider = 1;

			// Loop over the headers, insert into DB.
			foreach ($msgs as $msg)
			{
				if (!isset($msg['Number']))
					continue;
				else
					$mnumber = $msg['Number'];

				// Gt the newest date each time for going forward.
				if ($type == 'forward')
					$newdate = $msg['Date'];

				if (isset($msg['Bytes']))
					$bytes = $msg['Bytes'];
				else
					$bytes = $msg[':bytes'];

				$msgsreceived[] = $mnumber;

				// Only keep yEnc messages with part numbers.
				if (!isset($msg['Subject']) || !preg_match('/(.+yEnc) \((\d+)\/(\d+)\)$/', $msg['Subject'], $matches))
				{
					$msgsignored[] = $mnumber;
					continue;
				}

				$subject = utf8_encode(trim($matches[1]));

				// Set up the info for inserting into files_groupid table.
				if(!isset($this->message[$subject]))
				{
					$mfarr = $matching->main($group['name'], $subject);
					$this->message[$subject]['subject'] = $db->escapeString(substr($mfarr['subject'], 0, 500));
					$this->message[$subject]['origsubject'] = $db->escapeString(substr($msg['Subject'], 0, 500));
					$this->message[$subject]['parts'] = (int)$matches[3];
					$this->message[$subject]['utime'] = strtotime($msg['Date']);
					$this->message[$subject]['ltime'] = time();
					$this->message[$subject]['poster'] = $db->escapeString($msg['From']);
					$this->message[$subject]['fhash'] = $db->escapeString(sha1($msg['From'].$subject));
					$this->message[$subject]['chash'] = $db->escapeString(sha1($mfarr['hash']));
				}

				// Set up info for the parts_groupid table.
				$this->message[$subject]['part'][(int)$matches[2]] = array('messid' => substr($msg['Message-ID'],1,-1), 'anumber' => $msg['Number'], 'psize' => $bytes, 'part' => (int)$matches[2]);
			}

			if (count($msgsreceived) > 0)
			{
				$istart = microtime(true);
				$done = 0;

				// Loop messages, insert files, use fileid when inserting parts.
				foreach ($this->message as $subject => $file)
				{
					$fchk = $db->queryOneRow('SELECT id FROM '.$group['ftname'].' WHERE fhash = '.$file['fhash']);
					if ($fchk === false)
					{
						$fileid = $db->queryInsert(sprintf('INSERT INTO %s (subject, origsubject, parts, utime, ltime, poster, fhash, chash, groupid, fsize, partsa) VALUES (%s, %s, %d, %d, %d, %s, %s, %s, %d, 0, 0)', $group['ftname'], $file['subject'], $file['origsubject'], $file['parts'], $file['utime'], $file['ltime'], $file['poster'], $file['fhash'], $file['chash'], $group['id']));
						if ($fileid === false)
							continue;
					}
					else
						$fileid = $fchk["id"];

					// Loop parts.
					$db->beginTransaction();
					$pquery = 'INSERT IGNORE INTO '.$group['ptname'].' (part, fileid, messid, anumber, psize, provider) ';
					$first = false;
					$pvalues = array();
					foreach ($file['part'] as $insprep)
					{
						if ($first === false)
						{
							$pquery .= ' VALUES(?,?,?,?,?,?)';
							$first = true;
						}
						else
							$pquery .= ',(?,?,?,?,?,?)';

						$pvalues = array_merge($pvalues, array_values(array($insprep['part'], $fileid, $insprep['messid'], $insprep['anumber'], $insprep['psize'], $provider)));
					}

					$ipstmt = $db->Prepare($pquery);
					try {
						$ipstmt->execute($pvalues);
					} catch (PDOException $e) {
						echo $e->getMessage()."\n";
					}

					$curdone = $ipstmt->rowCount();
					$done += $curdone;
					$db->Commit();

					// Update filesize / parts.
					if ($curdone > 0)
					{
						// Not the best way to get, but it's good enough I guess.
						$parr = $db->queryOneRow(sprintf('SELECT SUM(psize) AS size, COUNT(c) AS parts FROM (SELECT psize, id AS c FROM %s WHERE fileid = %d AND provider = %d ORDER BY id DESC LIMIT %d) AS sub', $group['ptname'], $fileid, $provider, $curdone));
						if ($parr !== false)
							$db->queryExec(sprintf('UPDATE %s SET fsize = fsize + %d, partsa = partsa + %d WHERE id = %d', $group['ftname'], $parr['size'], $parr['parts'], $fileid));
					}

				}

				// Update group's last article and date when going forward.
				if ($type == 'forward')
				{
					$cols = '';
					if ($this->alternate == true)
						$cols = 'a';

					if (isset($newdate) && isset($newest))
						$db->queryExec(sprintf('UPDATE groups SET lastart%s = %d, lastdate%s = %d WHERE id = %d', $cols, $newest, $cols, strtotime($newdate), $group['id']));
				}

				if ($done > 0)
				{
					if ($this->vecho)
						echo 'Received (in '.substr($uend - $ustart, 0, 4).'s) '.(count($msgsreceived) - 1).' headers of '.($newest-$oldest).', '.count($msgsignored).' were not yEnc so ignored, '.number_format(($done -1)).' were inserted (in '.substr(microtime(true) - $istart, 0, 4)." secs).\n";
					return true;
				}
				else
				{
					if ($this->vecho)
						echo 'Received (in '.substr($uend - $ustart, 0, 4).'s) '.(count($msgsreceived) - 1).' headers of '.($newest-$oldest).', '.count($msgsignored)." were not yEnc so ignored, no articles were inserted (if you have another NNTP provider, it might have inserted them before).\n";
					return false;
				}
			}
			// No messages to process.
			else
				return false;
		}
		else
			return false;
	}
}
