<?php

class matchfiles
{
	public function main($groupname, $subject)
	{
		switch ($groupname)
		{
			case "alt.binaries.moovee":
				return $this->moovee($subject);
			case "alt.binaries.teevee":
				return $this->teevee($subject);
			default:
				return $this->generic($subject);
		}
	}

	// Rematch subjects if you changed the regex.
	public function rematch($groupname)
	{
		require_once("config.php");
		require_once(PHP_DIR."/backend/groups.php");
		$groups = new groups;
		$group = $groups->getgroupinfo($groupname);
		if ($group["tstatus"] == 0)
			exit ("Unable to rematch regex on this group, it has never been run.\n");

		require_once(PHP_DIR."/backend/db.php");
		$db = new DB;
		$files = $db->query(sprintf("SELECT subject, id, chash FROM files_%d", $group["id"]));
		if (count($files) > 0)
		{
			echo "Trying to rematch ".count($files)." files. File match = +\n";
			$matched = $total = 0;
			foreach ($files as $file)
			{
				$total++;
				$match = $this->main($group["name"], $file["subject"]);
				if ($match != $file["subject"])
				{
					$chash = sha1($match);
					if ($chash != $file["chash"])
					{
						$db->queryExec(sprintf("UPDATE files_%d SET chash = %s WHERE id = %d", $group["id"], $db->escapeString($chash), $file["id"]));
						echo "+";
						$matched++;
					}
				}
				if ($total %100 == 0)
					echo ".";
				if ($total %10000 == 0)
					echo "\n";
			}
			echo "\n$matched files were rematched.\n";
		}
		else
			exit ("No files are in the DB for this group.\n");
	}

	// Generic function.
	public function generic($subject)
	{
		$subject = preg_replace('/[\[( ]\d+\/\d+[\]) ]/', '', $subject);
		$subject = preg_replace('/([-_](proof|sample|thumbs?))*(\.part\d*)?(\.rar|\.r\d+)?(\d{1,3}\.rev"|\.vol.+?"|\.[A-Za-z0-9]{2,4}"|")/', '', $subject);
		return $subject;
	}

	// alt.binaries.moovee
	public function moovee($subject)
	{
		return $this->generic($subject);
	}

	// alt.binaries.teevee
	public function teevee($subject)
	{
		//[152393]-[FULL]-[#a.b.teevee]-[ Do.No.Harm.S01E11.720p.WEB-DL.DD5.1.H.264-pcsyndicate ]-[17/39] - "Do.No.Harm.S01E11.720p.WEB-DL.DD5.1.H.264-pcsyndicate.part16.rar" yEnc
		if (preg_match('/^(\[\d+\]-\[.+?\]-\[.+?\]-\[ .+? \]-\[)\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
			return $match[1];
		//anckfheuwydj502 - [9/9] - "anckfheuwydj548.vol31+16.par2" yEnc
		else if (preg_match('/^([a-z0-9]+ - \[)\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
			return $match[1];
		else
			return $this->generic($subject);
	}
}
