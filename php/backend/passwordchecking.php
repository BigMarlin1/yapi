<?php
require_once('../config.php');
require_once(PHP_DIR.'backend/db.php');
require_once(PHP_DIR.'backend/groups.php');
require_once(PHP_DIR.'backend/nfo.php');
require_once(PHP_DIR.'backend/nntp.php');
require_once(PHP_DIR.'backend/rarinfo/archiveinfo.php');

/*
 * Looks through subjects for compressed/encrypted zips/rars.
 */
Class PChecking
{
	const PC_ENCRYPTED = -60; // Encrypted file.
	const PC_PASSWORDED = -50; // Passworded file.
	const PC_UNCHECKED = 0; // File has never been checked.
	const PC_FALSE = 1; // No passwords/encryption.
	const PC_UNKOWN = 2; // Probably no password/encryption.
	const PC_POSSIBLE = 10; // Possibly passworded or encrypted.
	const PC_FAILED = 20; // Failed to download yEnc article. Retry later with alternate provider.
	const PC_DISABLED = 30; // Failed to download yEnc article with the alternate provider.

	function PChecking($echo=false)
	{
		$this->echov = $echo;
		$this->debug = DEBUG_MESSAGES;
		$this->pchecklimit = 100;
		$this->incrementlimit = -5;
		$this->alternate = false;
		$this->unrar = UNRAR_PATH;
		$this->sevenzip = SEVENZIP_PATH;
	}

	// Go through $this->pchecklimit files, look for passwords/encryption.
	public function startChecking($alternate=false)
	{
		if ($alternate === true)
		{
			if (NNTP_ALTERNATE === true)
				$this->alternate = true;
			else
				exit("ERROR: You must set NNTP_ALTERNATE to true in config.php to fetch NFO's using an alternate NNTP provider.\n");

			if ($this->echov)
				echo 'Starting to look up '.$this->pchecklimit." previous files that failed with primary NNTP provider.\nf = error ;; p = passworded ;; e = encrypted ;; - no pass/encrypt ;; n found NFO\n";
		}
		else
		{
			if ($this->echov)
				echo 'Starting to look up '.$this->pchecklimit." files for passwords/encryption.\nf = error ;; p = passworded ;; e = encrypted ;; - no pass/encrypt ;; n found NFO\n";
		}

		$groups = new groups;
		$garr = $groups->getstarted();
		if (count($garr) > 0)
		{
			$i = $this->incrementlimit;
			$inq = '(';
			while ($i < 0)
			{
				$inq .= $i++.', ';
			}
			$inq .= ' 0)';

			$db = new DB;
			$limit = $passworded = 0;
			foreach ($garr as $group)
			{
				if ($limit > $this->pchecklimit)
					break;

				// Find files that have previously failed downloading using the alternate NNTP provider.
				if ($alternate === true)
					$farr = $db->query(sprintf('SELECT chash, fhash, origsubject, id, nstatus, groupid FROM files_%d WHERE pstatus = %d ORDER BY utime DESC LIMIT %d', $group['id'], PChecking::PC_FAILED, $this->pchecklimit));
				else
				{
					// Mark files with no candidates as false.
					$db->queryExec(sprintf("UPDATE files_%d SET pstatus = %d WHERE pstatus = %d AND origsubject NOT REGEXP '[.]((r(ar|0[01])|z(ip|0[01]))[^a-zA-Z0-9.]|([01][01]|00[12])\")'", $group['id'], PChecking::PC_UNKOWN, PChecking::PC_UNCHECKED));

					// Mark files with incrementlimit as failed.
					$db->queryExec(sprintf('UPDATE files_%d SET pstatus = %d WHERE pstatus BETWEEN %d AND %d', $group['id'], PChecking::PC_FAILED, ($this->incrementlimit - 5), $this->incrementlimit));
					
					// Find files with rar or zip or .00" etc.
					$farr = $db->query(sprintf("SELECT chash, fhash, origsubject, id, nstatus, groupid FROM files_%d WHERE origsubject REGEXP '[.]((r(ar|0[01])|z(ip|0[01]))[^a-zA-Z0-9]|([01][01]|00[12])\")' AND pstatus IN %s GROUP BY chash ORDER BY utime DESC LIMIT %d", $group['id'], $inq, $this->pchecklimit));
				}
				if (count($farr) > 0)
				{
					if ($this->echov)
						echo 'Checking '.count($farr).' files for passwords for '.$group['name'].".\n";

					foreach ($farr as $file)
					{
						if ($limit++ > $this->pchecklimit)
							break;

						$passworded += $this->getFile($file, $group);
					}
				}
				else
				{
					if ($this->echov)
						echo 'No new files to check passwords for '.$group['name'].".\n";
				}
			}
		}
		else
		{
			if ($this->echov)
				echo "No groups enabled.\n";
			return false;
		}
	}

	// Download the file, check if it is encrypted or passworded.
	public function getFile($file, $group)
	{
		$nntp = new nntp;
		// Connect, increment failed attempts if fails.
		if ($nntp->doConnect(true, $this->alternate) == false)
			return $this->failed($file['fhash'], $group['id']);

		$db = new DB;
		$part = $db->queryOneRow(sprintf('SELECT messid FROM parts_%d WHERE fileid = %d ORDER BY part ASC LIMIT 1', $group['id'], $file['id']));
		if ($part === false)
			return $this->failed($file['fhash'], $group['id']);

		// Download the article.
		$pfile = $nntp->getMessage($group['name'], $part['messid']);
		if ($pfile == false)
			return $this->failed($file['fhash'], $group['id']);

		return $this->checkfile($pfile, $file);
	}

	public function checkfile($pfile, $filearr)
	{
		$archive = new ArchiveInfo;
		$archive->setData($pfile);
		if ($archive->error)
		{
			if ($this->debug && $this->echov)
				 echo 'DEBUG: File '.$filearr['id'].'|Group '.$filearr['groupid'].": {$archive->error}\n";

			$this->setdone($filearr['chash'], $filearr['groupid'], PChecking::PC_UNKOWN);
			return 0;
		}

		$type = '';
		if (ArchiveInfo::TYPE_RAR == $archive->type)
			$type = 'rar';
		else if (ArchiveInfo::TYPE_SZIP == $archive->type)
			$type  = 'szip';
		else if (ArchiveInfo::TYPE_ZIP == $archive->type)
			$type  = 'zip';
		else
		{
			if ($this->debug && $this->echov)
				 echo 'DEBUG: File '.$filearr['id'].'|Group '.$filearr['groupid'].": is not rar/zip/szip.\n";

			if ($this->echov)
				echo '-';

			$this->setdone($filearr['chash'], $filearr['groupid'], PChecking::PC_UNKOWN);
			return 0;
		}

		if (!empty($archive->isEncrypted))
		{
			if ($this->debug && $this->echov)
				echo 'DEBUG: File '.$filearr['id'].'|Group '.$filearr['groupid'].": is encrypted.\n";

			if ($this->echov)
				echo 'e';

			$this->setdone($filearr['chash'], $filearr['groupid'], PChecking::PC_ENCRYPTED);
			return 1;
		}
		else
		{
			foreach ($archive->getArchiveFileList() as $file)
			{
				if (isset($file['error']))
					continue;

				if (!empty($file['pass']))
				{
					if ($this->debug && $this->echov)
						echo 'DEBUG: File '.$filearr['id'].'|Group '.$filearr['groupid'].": contains a password.\n";

					if ($this->echov)
						echo 'p';

					$this->setdone($filearr['chash'], $filearr['groupid'], PChecking::PC_PASSWORDED);
					return 1;
				}

				if (STORE_FILES === true)
					$this->addfile($filearr['fhash'], $filearr['groupid'], $file['name'], $file['size'], $file['date']);

				if (empty($file['compressed']))
				{
					if ($filearr['nstatus'] == 1)
						continue;
					else
					{
						$data = $archive->getFileData($file['name'], $file['source']);
						if (preg_match('/\.nfo/i', $data))
						{
							if ($this->debug && $this->echov)
								echo 'DEBUG: File '.$filearr['id'].'|Group '.$filearr['groupid'].": Added an uncompressed NFO for this file.\n";

							if ($this->echov)
								echo 'n';

							$nfo = new nfo;
							$nfo->insertnfo($data, $filearr, NFO::NFO_RARINFO);
						}
					}
				}
				else
				{
					if ($filearr['nstatus'] == 1)
						continue;
					else
					{
						// Use unrar or 7zip or both.
						$unrar = $sevenzip = false;
						if ($this->unrar != '')
						{
							$unrar = true;
							$pa = array(ArchiveInfo::TYPE_RAR => $this->unrar);
						}
						if ($this->sevenzip != '')
						{
							$sevenzip = true;
							if ($unrar === false)
								$pa = array(ArchiveInfo::TYPE_ZIP => $this->sevenzip);
							else
								$pa = array(ArchiveInfo::TYPE_RAR => $this->unrar, ArchiveInfo::TYPE_ZIP => $this->sevenzip);
						}

						if ($unrar === false && $sevenzip === false)
							continue;

						$archive->setExternalClients($pa);
						$data = $archive->extractFile($file["name"]);
						if ($data !== false && strlen($data) > 5);
						{
							if ($this->debug && $this->echov)
								echo 'DEBUG: File '.$filearr['id'].'|Group '.$filearr['groupid'].": Added an compressed NFO for this file.\n";

							if ($this->echov)
								echo 'n';

							$nfo = new nfo;
							$nfo->insertnfo($data, $filearr, NFO::NFO_RARINFO);
						}
					}
				}
			}
			if ($this->echov)
				echo '-';

			$this->setdone($filearr['chash'], $filearr['groupid'], PChecking::PC_FALSE);
			return 0;
		}
	}

	// Store file names in the DB from inside the rar / zip.
	public function addfile($fhash, $groupid, $name, $size, $date)
	{
		if ($this->debug && $this->echov)
			echo 'DEBUG: Added file '.$name." to the DB.\n";
		$db = new DB;
		$db->queryExec(sprintf('INSERT IGNORE INTO innerfiles (ifname, ifsize, iftime, fhash) VALUES (%s, %d, %d, %s)', $db->escapeString($name), $size, $date, $db->escapeString($fhash)));
		$db->queryExec(sprintf('UPDATE files_%d SET innerfiles = innerfiles + 1 WHERE fhash = %s', $groupid, $db->escapeString($fhash)));
	}

	// Send to increment or setstatus.
	public function failed($fhash, $groupid)
	{
		if ($this->echov)
			echo 'f';
		if ($this->alternate === true)
			$this->setsetstatus($fhash, $groupid, PChecking::PC_UNKOWN);
		else
			$this->increment($fhash, $groupid);
		return 0;
	}

	// Increment failed attempts at getting a file.
	public function increment($fhash, $groupid)
	{
		$db = new DB;
		$db->queryExec(sprintf('UPDATE files_%d SET pstatus = pstatus -1 WHERE fhash = %s', $groupid, $db->escapeString($fhash)));
	}

	// Set pass status.
	public function setstatus($fhash, $groupid, $type)
	{
		$db = new DB;
		$db->queryExec(sprintf('UPDATE files_%d SET pstatus = %d WHERE fhash = %s', $groupid, $type, $db->escapeString($fhash)));
	}

	// Set the collection status so we dont run into this collection again.
	public function setdone($chash, $groupid, $type)
	{
		$db = new DB;
		$db->queryExec(sprintf('UPDATE files_%d SET pstatus = %d WHERE chash = %s', $groupid, $type, $db->escapeString($chash)));
	}
}
