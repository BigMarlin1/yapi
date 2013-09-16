<?php

class matchfiles
{
	public function matchfiles()
	{
		$this->e0 = '([-_](proof|sample|thumbs?))*(\.part\d*)?(\.r(ar|\d+))?(\d{1,3}\.rev"|\.vol.+?"|\.[A-Za-z0-9]{2,4}"|")';
	}

	public function main($groupname, $subject)
	{
		switch ($groupname)
		{
			case 'alt.binaries.hdtv.x264':
				return $this->hdtvx264($subject);
			case 'alt.binaries.moovee':
				return $this->moovee($subject);
			case 'alt.binaries.teevee':
				return $this->teevee($subject);
			default:
				return $this->generic($subject);
		}
	}

	// Rematch subjects if you changed the regex.
	public function rematch($groupname, $force=false)
	{
		require_once('config.php');
		require_once(PHP_DIR.'/backend/groups.php');
		$groups = new groups;
		$group = $groups->getgroupinfo($groupname);
		if ($group['tstatus'] == 0)
			exit ("Unable to rematch regex on this group, it has never been run. Run update_headers.php on it first.\n");

		require_once(PHP_DIR.'/backend/db.php');
		$db = new DB;
		$files = $db->query('SELECT origsubject, subject, id, chash FROM files_'.$group['id']);
		if (count($files) > 0)
		{
			echo 'Trying to rematch '.count($files)." files. File match = +\n";
			$matched = $unmatched = 0;
			foreach ($files as $file)
			{
				$matches = $this->main($group['name'], preg_replace('/\s*\(\d+\/\d+\)$/', '', $file['origsubject']));
				if ($matches['subject'] != $file['subject'] || $force === true)
				{
					$chash = sha1($matches['hash']);
					if ($chash != $file['chash'] || $force === true)
					{
						$db->queryExec(sprintf('UPDATE files_%d SET chash = %s, subject = %s WHERE id = %d', $group['id'], $db->escapeString($chash), $db->escapeString($matches['subject']), $file['id']));
						$matched++;
						if ($matched %100 == 0)
							echo '+';
					}
					else
						$unmatched++;
				}
				else
					$unmatched++;

				if ($unmatched %100 == 0 && $unmatched != 0)
					echo '.';
			}
			echo "\n$matched files were rematched.\n";
		}
		else
			exit ("No files are in the DB for this group.\n");
	}

	// Generic function.
	public function generic($subject)
	{
		$subject = preg_replace('/[\[( ]\d+(\/| of )\d+[\]) ]/', '', $subject);
		$subject = preg_replace('/'.$this->e0.'/', '', $subject);

		$csub = preg_replace('/^[^\w]*/', '', $subject);
		$csub = trim(utf8_encode(preg_replace('/yEnc$/', '', $csub)));

		return array('hash' => $subject, 'subject' => $csub);
	}

	// alt.binaries.hdtv.x264
	public function hdtvx264($subject)
	{
		//[86/97] - "135631-2.9" yEnc
		if (preg_match('/^\[\d+(\/\d+\] - "(\d+-\d+)\.).+?" yEnc$/', $subject, $match))
			return array('hash' => $match[1], 'subject' => $match[2]);
		//Zeit des Erwachens - mit Robert De Niro - 1990 - (German) - AC3 HD720p Avi by Waldorf - [05/74] - "Zeit des Erwachens.par2" yEnc
		else if (preg_match('/((.+? HD720p.+?by Waldorf)\s+-\s+\[)\d+\/\d+\]/', $subject, $match))
			return array('hash' => $match[1], 'subject' => $match[2]);
		else
			return $this->generic($subject);
	}

	// alt.binaries.moovee
	public function moovee($subject)
	{
		//[135615]-[FULL]-[#a.b.moovee]-[ Prince.Of.Darkness.REMASTERED.1987.BDRiP.x264-LiViDiTY ]-[08/34] - "ly-podarknesssd-sample.vol7+2.par2" yEnc
		if (preg_match('/^(\[\d+\]-\[.+?\]-\[.+?\]-\[ (.+?) \]-\[)\d+\/\d+\] ?(- |")".+?""? yEnc$/', $subject, $match))
			return array('hash' => $match[1], 'subject' => $match[2]);
		//[86/97] - "135631-2.9" yEnc
		else if (preg_match('/^\[\d+(\/\d+\] - "\d+-\d+\.).+?" yEnc$/', $subject, $match))
			return array('hash' => $match[1], 'subject' => $subject);
		else
			return $this->generic($subject);
	}

	// alt.binaries.teevee
	public function teevee($subject)
	{
		//[152393]-[FULL]-[#a.b.teevee]-[ Do.No.Harm.S01E11.720p.WEB-DL.DD5.1.H.264-pcsyndicate ]-[17/39] - "Do.No.Harm.S01E11.720p.WEB-DL.DD5.1.H.264-pcsyndicate.part16.rar" yEnc
		//[152426]-[FULL]-[#a.b.teevee@EFNet]-[ Greys.Anatomy.S06E15.DVDRip.XviD-REWARD ]-[09/35] ""greys.anatomy.s06e15.dvdrip.xvid-reward.nfo"" yEnc
		//[153409]-[FULL]-[#a.b.teevee@EFNet]-[The.Mentalist.S02E06.DVDRip.XviD-NODLABS] - [31/38] - "the.mentalist.s02e06.dvdrip.xvid-nodlabs.r21" yEnc
		if (preg_match('/^(\[\d+\]-\[.+?\]-\[.+?\]-\[ ?(.+?) ?\] ?- ?\[)\d+\/\d+\] ?(- |")".+?""? yEnc$/', $subject, $match))
			return array('hash' => $match[1], 'subject' => $match[2]);
		//[#a.b.teevee] Mythbusters.S08E22.Arrow.Machine.Gun.1080p.WEB-DL.AAC2.0.H.264-XEON - [13/46] - "Mythbusters.S08E22.Arrow.Machine.Gun.1080p.WEB-DL.AAC2.0.H.264-XEON.part11.rar" yEnc 
		else if (preg_match('/^(\[#a\.b\.teevee\] (.+?) - \[)\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
			return array('hash' => $match[1], 'subject' => $match[2]);
		//Aqua.Teen.Hunger.Force.S10E04.Banana.Planet.1080p.WEB-DL.DD5.1.H264-iT00NZ [01/15] - "Aqua.Teen.Hunger.Force.S10E04.Banana.Planet.1080p.WEB-DL.DD5.1.H264-iT00NZ.mkv.001" yEnc
		//House.Hunters.International.S57E05.720p.hdtv.x264 [01/21] - "House.Hunters.International.S57E05.720p.hdtv.x264.nfo" yEnc
		//The.Real.Housewives.Of.New.Jersey.S05E15.Zen.Things.I.Hate.About.You.WEB-DL.x264-RKSTR - [01/32] - "The.Real.Housewives.Of.New.Jersey.S05E15.Zen.Things.I.Hate.About.You.WEB-DL.x264-RKSTR.par2" yEnc
		else if (preg_match('/^(([A-Z0-9].{4,}?S\d+E\d+.+?[-.][A-Za-z0-9]+) (- )?\[)\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
			return array('hash' => $match[1], 'subject' => $match[2]);
		//Jeopardy.2013.09.13.Tournament.Of.Champions.Finale.PDTV.x264-TM - [00/13] - "Jeopardy.2013.09.13.Tournament.Of.Champions.Finale.PDTV.x264-TM.nzb" yEnc
		else if (preg_match('/^(([A-Z0-9][a-z0-9A-Z.-]{4,}?-[A-Za-z0-9]+) (- )?\[)\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
			return array('hash' => $match[1], 'subject' => $match[2]);
		//The Bachelor AU H.264 S01E01 [6 of 68] "The Bachelor AU S01E01.mp4.006" yEnc
		else if (preg_match('/^(([A-Z0-9].{4,}?S\d+E\d+) \[)\d+ of \d+\] ".+?" yEnc$/', $subject, $match))
			return array('hash' => $match[1], 'subject' => $match[2]);
		//(Dgpc) [00/36] - "The.X.Factor.AU.S05E19.x264-NoGRP.nzb" yEnc
		else if (preg_match('/^\(Dgpc\) \[\d+(\/\d+\] - "(.+?))'.$this->e0.' yEnc$/', $subject, $match))
			return array('hash' => $match[1], 'subject' => $match[2]);
		//anckfheuwydj502 - [9/9] - "anckfheuwydj548.vol31+16.par2" yEnc
		else if (preg_match('/^([a-z0-9]+ - \[)\d+\/\d+\] - ".+?" yEnc$/', $subject, $match))
			return array('hash' => $match[1], 'subject' => $subject);
		else
			return $this->generic($subject);
	}
}
