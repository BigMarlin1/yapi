<?php
require_once("config.php");
require_once(PHP_DIR."backend/db.php");
require_once(PHP_DIR."backend/groups.php");
require_once(PHP_DIR."backend/nntp.php");
require_once(PHP_DIR."backend/rarinfo/par2info.php");
require_once(PHP_DIR."backend/rarinfo/rarinfo.php");
require_once(PHP_DIR."backend/rarinfo/sfvinfo.php");
require_once(PHP_DIR."backend/rarinfo/zipinfo.php");

/*
 * Class for fetching NFO files and storing them in the DB.
 */

Class nfo
{
	// File with no NFO.
	const NFO_FALSE = -61;
	// File possibly has an NFO but we couldn't determine.
	const NFO_POSSIBLE = -60;
	// File is unchecked.
	const NFO_UNCHECKED = 0;
	// File has an NFO with .nfo extension.
	const NFO_NORMAL = 1;
	// File has an NFO without .nfo extension.
	const NFO_HIDDEN = 2;
	// File has an NFO from .rar file.
	const NFO_RAR = 3;
	// File has an NFO from .zip file.
	const NFO_ZIP = 4;
	// File has an NFO from predb.
	const NFO_PREDB = 5;

	function nfo($echo = false)
	{
		$this->echov = $echo;
		$this->debug = DEBUG_MESSAGES;
		$this->nfolimit = 100;
		$this->incrementlimit = -5;
	}

	// Go through active groups scanning for NFOs.
	public function scanfornfo()
	{
		$groups = new groups;
		$garr = $groups->getstarted();

		if ($this->echov)
			echo "Starting to look for up to ".$this->nfolimit." new NFOs.\n- = no NFO ;; + = NFO ;; * = Hidden NFO ;; _ no Hidden NFO\n";

		if (count($garr) > 0)
		{
			$db = new DB;
			$limit = $newnfos = 0;
			foreach ($garr as $group)
			{
				if ($limit > $this->nfolimit)
					break;

				// Mark files with no NFO as false.
				$db->queryExec(sprintf("UPDATE files_%d SET nstatus = %d WHERE nstatus = %d AND origsubject NOT REGEXP '[.][nN][fF][oO]\"|[.][0-9]+\".*[(]1[/]1[)]$'", $group["id"], NFO::NFO_FALSE, NFO::NFO_UNCHECKED, '%.nfo"%'));

				// Mark files with incrementlimit as no NFO.
				$db->queryExec(sprintf("UPDATE files_%d SET nstatus = %d WHERE nstatus < %d", $group["id"], NFO::NFO_FALSE, $this->incrementlimit));

				// Find files with nfo or (1/1) and an uncommon extension.
				$farr = $db->query(sprintf("SELECT fhash, origsubject, id FROM files_%d WHERE nstatus BETWEEN (%d AND %d) AND origsubject REGEXP '[.][nN][fF][oO]\"|[.][0-9]+\".*[(]1[/]1[)]$' LIMIT %d", $group["id"], NFO::NFO_UNCHECKED, $this->incrementlimit, $this->nfolimit));
				if (count($farr) > 0)
				{
					if ($this->echov)
						echo count($farr)." files have an NFO or possible NFO out of ".$this->nfolimit." for group ".$group["name"].".\n";

					foreach ($farr as $file)
					{
						$limit++;
						if ($limit > $this->nfolimit)
							break;

						if (preg_match('/\.nfo"/i', $file["origsubject"]))
							$newnfos += $this->getnfo($file, $group);
						else
							$newnfos += $this->getnfo($file, $group, "hidden");
					}
					if ($newnfos > 0)
							echo "\nDownloaded $newnfos new NFOs.\n";
					else
						echo "Downloaded no new NFOs.\n";

					return true;
				}
				// Nothing to do.
				else
					return false;
			}
		}
		else
		{
			if ($this->echov)
				echo "No groups enabled.\n";
			return false;
		}
	}

	// Download NFO, insert it, check if hidden nfo is really an NFO.
	public function getnfo($file, $group, $type='')
	{
		$db = new DB;
		$nntp = new nntp;

		// Connect, increment failed attempts if fails.
		if ($nntp->doconnect() == false)
		{
			if ($this->echov)
				echo "-";
			$this->increment($file["id"], $group["id"]);
			return 0;
		}

		$part = $db->queryOneRow(sprintf("SELECT messid FROM parts_%d WHERE fileid = %d LIMIT 1", $group["id"], $file["id"]));
		if ($part === false)
		{
			if ($this->echov)
				echo "-";
			$this->increment($file["id"], $group["id"]);
			return 0;
		}

		// Download the article.
		$pnfo = $nntp->getMessage($group["name"], $part["messid"]);
		if ($pnfo == false)
		{
			if ($this->echov)
				echo "-";
			$this->increment($file["id"], $group["id"]);
			return 0;
		}

		if ($type == "hidden")
		{
			if ($this->checknfo($pnfo) === true)
			{
				// Insert NFO into DB.
				$db->queryExec(sprintf("INSERT INTO filenfo (nfo, fhash) VALUES (compress(%s), %s)", $db->escapeString($pnfo), $db->escapeString( $file["fhash"])));
				// Update the file.
				$db->queryExec(sprintf("UPDATE files_%d SET nstatus = %d WHERE id = %d", $group["id"], NFO::NFO_HIDDEN, $file["id"]));
				if ($this->echov)
					echo "*";
				return 1;
			}
			else
			{
				if ($this->echov)
					echo "_";

				// Set nstatus to possible nfo.
				$db->queryExec(sprintf("UPDATE files_%d SET nstatus = %d WHERE id = %d", $group["id"], NFO::NFO_POSSIBLE, $file["id"]));
				return 0;
			}
		}
		else
		{
			$db->queryExec(sprintf("INSERT INTO filenfo (nfo, fhash) VALUES (compress(%s), %s)", $db->escapeString($pnfo), $db->escapeString($file["fhash"])));
			$db->queryExec(sprintf("UPDATE files_%d SET nstatus = %d WHERE id = %d", $group["id"], NFO::NFO_NORMAL, $file["id"]));
			if ($this->echov)
				echo "+";
			return 1;
		}
	}

	// Check if it's an NFO file.
	public function checknfo($nfo)
	{
		if (!preg_match('/(<\?xml|;\s*Generated\sby.+SF\w|^[^\w]*PAR|\.[a-z0-9]{2,7}\s[a-z0-9]{8}|^[^\w]*RAR|\A.{0,10}(JFIF|matroska|ftyp|ID3))/i', $nfo)) 
		{
			if (strlen($nfo) < (100 * 1024) && strlen($nfo) > 12)
			{
				if (@exif_imagetype($nfo) == false)
				{
					$par2info = new Par2Info();
					$par2info->setData($nfo);
					if ($par2info->error)
					{
						$rar = new RarInfo;
						$rar->setData($nfo);
						if ($rar->error)
						{
							$zip = new ZipInfo;
							$zip->setData($nfo);
							if ($zip->error)
							{
								$sfv = new SfvInfo;
								$sfv->setData($nfo);
								if ($sfv->error)
									return true;
							}
						}
					}
				}
			}
		}
		return false;
	}

	// Increment failed attempts at getting an nfo.
	public function increment($fileid, $groupid)
	{
		$db = new DB;
		$db->queryExec(sprintf("UPDATE files_%d SET nstatus = nstatus -1 WHERE id = %d", $groupid, $fileid));
	}
}
