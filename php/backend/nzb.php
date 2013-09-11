<?
/* Generate an NZB */
require_once("../config.php");
require_once(PHP_DIR."/backend/db.php");

class nzb
{
	// Create an NZB file.
	public function createNZB($type, $identifier, $groupid, $title='')
	{
		$db = new DB;
		$nzb = false;
		switch ($type)
		{
			case "single":
				$files = $db->query(sprintf("SELECT f.*, g.name AS groupname FROM files_%d f LEFT JOIN groups g ON g.id = f.groupid WHERE f.id = %d ORDER BY f.subject ASC", $groupid, $identifier)); // Single file.
				break;
			case "multi":
				$files = $db->query(sprintf("SELECT f.*, g.name AS groupname FROM files_%d f LEFT JOIN groups g ON g.id = f.groupid WHERE f.chash = %s ORDER BY f.subject ASC", $groupid, $db->escapeString($identifier))); // Collection of files.
				break;
		}

		if (count($files) > 0)
		{
			$nzb = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<!DOCTYPE nzb PUBLIC \"-//newzBin//DTD NZB 1.1//EN\" \"http://www.newzbin.com/DTD/nzb/nzb-1.1.dtd\">\n<!-- NZB Generated by: ".htmlspecialchars(NZB_FOOTER, ENT_QUOTES, 'utf-8')." -->\n<nzb xmlns=\"http://www.newzbin.com/DTD/2003/nzb\">\n";
			if ($title != '')
				$nzb .= " <head>\n  <meta type=\"title\">".htmlspecialchars($title, ENT_QUOTES, 'utf-8')."</meta>\n </head>\n";

			foreach ($files as $file)
			{
				$nzb .= " <file poster=\"".htmlspecialchars($file["poster"], ENT_QUOTES, 'utf-8')."\" date=\"".$file["utime"]."\" subject=\"".htmlspecialchars($file["subject"], ENT_QUOTES, 'utf-8')." (1/".$file["parts"].")\">\n";
				$nzb .= "  <groups>\n   <group>".$file["groupname"]."</group>\n  </groups>\n  <segments>\n";

				$parts = $db->query(sprintf("SELECT * FROM parts_%d WHERE fileid = %d ORDER BY part", $groupid, $file['id']));
				foreach ($parts as $part)
				{
					$nzb .= "   <segment bytes=\"".$part["psize"]."\" number=\"".$part["part"]."\">".htmlspecialchars($part["messid"], ENT_QUOTES, 'utf-8')."</segment>\n";
				}
				$nzb .= " </segments>\n </file>\n";
			}
			$nzb .= "</nzb>";
		}
		return $nzb;
	}
}
?>
