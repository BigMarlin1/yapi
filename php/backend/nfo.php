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

	// Decompresses and returns the NFO as a string, optional encode to cp437toUTF.
	public function returnNfo($chash, $groupid, $encode=false)
	{
		$db = new DB;
		$nfo = $db->queryOneRow(sprintf("SELECT UNCOMPRESS(nfo) AS n FROM filenfo INNER JOIN files_%d as f ON f.fhash = filenfo.fhash WHERE f.chash = %s LIMIT 1", $groupid, $db->escapeString($chash)));
		if ($nfo == false)
			return false;

		if ($encode == true)
			return $this->cp437toUTF($nfo["n"]);
		else
			return $nfo["n"];
	}

	// Convert cp347 chars in a string to UTF.
	public function cp437toUTF($str)
	{
		$out = '';
		for ($i = 0; $i < strlen($str); $i++)
		{
			$ch = ord($str{$i});
			switch($ch)
			{
				case 128: $out .= 'Ç';break;
				case 129: $out .= 'ü';break;
				case 130: $out .= 'é';break;
				case 131: $out .= 'â';break;
				case 132: $out .= 'ä';break;
				case 133: $out .= 'à';break;
				case 134: $out .= 'å';break;
				case 135: $out .= 'ç';break;
				case 136: $out .= 'ê';break;
				case 137: $out .= 'ë';break;
				case 138: $out .= 'è';break;
				case 139: $out .= 'ï';break;
				case 140: $out .= 'î';break;
				case 141: $out .= 'ì';break;
				case 142: $out .= 'Ä';break;
				case 143: $out .= 'Å';break;
				case 144: $out .= 'É';break;
				case 145: $out .= 'æ';break;
				case 146: $out .= 'Æ';break;
				case 147: $out .= 'ô';break;
				case 148: $out .= 'ö';break;
				case 149: $out .= 'ò';break;
				case 150: $out .= 'û';break;
				case 151: $out .= 'ù';break;
				case 152: $out .= 'ÿ';break;
				case 153: $out .= 'Ö';break;
				case 154: $out .= 'Ü';break;
				case 155: $out .= '¢';break;
				case 156: $out .= '£';break;
				case 157: $out .= '¥';break;
				case 158: $out .= '₧';break;
				case 159: $out .= 'ƒ';break;
				case 160: $out .= 'á';break;
				case 161: $out .= 'í';break;
				case 162: $out .= 'ó';break;
				case 163: $out .= 'ú';break;
				case 164: $out .= 'ñ';break;
				case 165: $out .= 'Ñ';break;
				case 166: $out .= 'ª';break;
				case 167: $out .= 'º';break;
				case 168: $out .= '¿';break;
				case 169: $out .= '⌐';break;
				case 170: $out .= '¬';break;
				case 171: $out .= '½';break;
				case 172: $out .= '¼';break;
				case 173: $out .= '¡';break;
				case 174: $out .= '«';break;
				case 175: $out .= '»';break;
				case 176: $out .= '░';break;
				case 177: $out .= '▒';break;
				case 178: $out .= '▓';break;
				case 179: $out .= '│';break;
				case 180: $out .= '┤';break;
				case 181: $out .= '╡';break;
				case 182: $out .= '╢';break;
				case 183: $out .= '╖';break;
				case 184: $out .= '╕';break;
				case 185: $out .= '╣';break;
				case 186: $out .= '║';break;
				case 187: $out .= '╗';break;
				case 188: $out .= '╝';break;
				case 189: $out .= '╜';break;
				case 190: $out .= '╛';break;
				case 191: $out .= '┐';break;
				case 192: $out .= '└';break;
				case 193: $out .= '┴';break;
				case 194: $out .= '┬';break;
				case 195: $out .= '├';break;
				case 196: $out .= '─';break;
				case 197: $out .= '┼';break;
				case 198: $out .= '╞';break;
				case 199: $out .= '╟';break;
				case 200: $out .= '╚';break;
				case 201: $out .= '╔';break;
				case 202: $out .= '╩';break;
				case 203: $out .= '╦';break;
				case 204: $out .= '╠';break;
				case 205: $out .= '═';break;
				case 206: $out .= '╬';break;
				case 207: $out .= '╧';break;
				case 208: $out .= '╨';break;
				case 209: $out .= '╤';break;
				case 210: $out .= '╥';break;
				case 211: $out .= '╙';break;
				case 212: $out .= '╘';break;
				case 213: $out .= '╒';break;
				case 214: $out .= '╓';break;
				case 215: $out .= '╫';break;
				case 216: $out .= '╪';break;
				case 217: $out .= '┘';break;
				case 218: $out .= '┌';break;
				case 219: $out .= '█';break;
				case 220: $out .= '▄';break;
				case 221: $out .= '▌';break;
				case 222: $out .= '▐';break;
				case 223: $out .= '▀';break;
				case 224: $out .= 'α';break;
				case 225: $out .= 'ß';break;
				case 226: $out .= 'Γ';break;
				case 227: $out .= 'π';break;
				case 228: $out .= 'Σ';break;
				case 229: $out .= 'σ';break;
				case 230: $out .= 'µ';break;
				case 231: $out .= 'τ';break;
				case 232: $out .= 'Φ';break;
				case 233: $out .= 'Θ';break;
				case 234: $out .= 'Ω';break;
				case 235: $out .= 'δ';break;
				case 236: $out .= '∞';break;
				case 237: $out .= 'φ';break;
				case 238: $out .= 'ε';break;
				case 239: $out .= '∩';break;
				case 240: $out .= '≡';break;
				case 241: $out .= '±';break;
				case 242: $out .= '≥';break;
				case 243: $out .= '≤';break;
				case 244: $out .= '⌠';break;
				case 245: $out .= '⌡';break;
				case 246: $out .= '÷';break;
				case 247: $out .= '≈';break;
				case 248: $out .= '°';break;
				case 249: $out .= '∙';break;
				case 250: $out .= '·';break;
				case 251: $out .= '√';break;
				case 252: $out .= 'ⁿ';break;
				case 253: $out .= '²';break;
				case 254: $out .= '■';break;
				case 255: $out .= ' ';break;
				default : $out .= chr($ch);
			}
		}
		return $out;
	}

	// Increment failed attempts at getting an nfo.
	public function increment($fileid, $groupid)
	{
		$db = new DB;
		$db->queryExec(sprintf("UPDATE files_%d SET nstatus = nstatus -1 WHERE id = %d", $groupid, $fileid));
	}
}
