<?php
require_once("config.php");
require_once(PHP_DIR."backend/db.php");
require_once(PHP_DIR."backend/groups.php");
require_once(PHP_DIR."backend/nntp.php");

/*
 * Class for fetching NFO files and storing them in the DB.
 */

Class nfo
{
	const NFO_FALSE = -8;
	const NFO_POSSIBLE = -4;
	const NFO_UNCHECKED = 0;
	const NFO_NORMAL = 4;
	const NFO_HIDDEN = 8;

	function nfo($echo = false)
	{
		$this->echov = $echo;
		$this->debug = DEBUG_MESSAGES;
		$this->nfolimit = 100;
	}

	// Go through active groups scanning for NFOs.
	public function scannfo()
	{
		$groups = new groups;
		$garr = $groups->getstarted();

		if (count($garr) > 0)
		{
			$db = new DB;
			foreach ($garr as $group)
			{
				$regex = '[.][nN][fF][oO]"|[[.][0-9]+".*[(]1[/]1[)]$';
				// Mark files with no NFO as false.
				$db->queryExec(sprintf("UPDATE files_%d SET nstatus = %d WHERE nstatus = %d AND origsubject NOT REGEXP '%s'", NFO_FALSE, $group["id"], NFO_UNCHECKED, '%.nfo"%', $regex));

				// Find files with nfo or (1/1) and an uncommon extension.
				$farr = $db->query(sprintf("SELECT fhash FROM files_%d WHERE nstatus = %d AND REGEXP '%s' LIMIT %d", $group["id"], NFO_UNCHECKED, $regex, $this->nfolimit));
			}
		}
		else
			return false;
	}
}
