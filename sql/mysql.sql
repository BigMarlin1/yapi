DROP TABLE IF EXISTS groups;

CREATE TABLE groups
(
	/* 16 million groups max */
	id MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT,
	/* group name */
	name VARCHAR(255) NOT NULL DEFAULT '',
	/* unixdate of the oldest article */
	firstdate INT(8) UNSIGNED NOT NULL DEFAULT 0,
	/* unixdate of the newest article */
	lastdate INT(8) UNSIGNED NOT NULL DEFAULT 0,
	/* article number for the oldest article */
	firstart BIGINT UNSIGNED NOT NULL DEFAULT 0,
	/* article number for the newest article */
	lastart BIGINT UNSIGNED NOT NULL DEFAULT 0,
	/* unixdate of the oldest article 2nd provider */
	firstdatea INT(8) UNSIGNED NOT NULL DEFAULT 0,
	/* unixdate of the newest article 2nd provider */
	lastdatea INT(8) UNSIGNED NOT NULL DEFAULT 0,
	/* article number for the oldest article 2nd provider */
	firstarta BIGINT UNSIGNED NOT NULL DEFAULT 0,
	/* article number for the newest article 2nd provider */
	lastarta BIGINT UNSIGNED NOT NULL DEFAULT 0,
	/* if the group is active for downloading new headers */
	factive TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
	/* if the group is active for backfilling old headers */
	bactive TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
	/* to see if the group has its own parts/files tables */
	tstatus TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
	PRIMARY KEY (id),
	KEY factive (factive),
	KEY bactive (bactive),
	KEY tstatus (tstatus),
	UNIQUE KEY name (name)
) ENGINE=MYISAM DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci AUTO_INCREMENT=1;

INSERT INTO groups (name) VALUES ('alt.binaries.moovee');
INSERT INTO groups (name) VALUES ('alt.binaries.teevee');
INSERT INTO groups (name) VALUES ('alt.binaries.tvseries');

/* This will only be used as a template, when a new group is started the table will be copied to files_groupid */
DROP TABLE IF EXISTS files;

CREATE TABLE files
(
	/* 4 billion files per group should be good enough */
	id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	/* id of the group */
	groupid MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
	/* subject modified by regex */
	subject VARCHAR(500) NOT NULL DEFAULT '',
	/* original subject */
	origsubject VARCHAR(500) NOT NULL DEFAULT '',
	/* poster of the article */
	poster VARCHAR(255) NOT NULL DEFAULT '',
	/* the size of every part  combined in bytes */
	fsize BIGINT UNSIGNED NOT NULL DEFAULT 0,
	/* the real amount of parts this file was split into (by using (#/#) at the end of a subject)  */
	parts MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
	/* the actual amount of parts we have for this file */
	partsa MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
	/* unixtime the article was posted on usenet */
	utime INT(8) UNSIGNED NOT NULL DEFAULT 0,
	/* unixtime the header was locally inserted */
	ltime INT(8) UNSIGNED NOT NULL DEFAULT 0,
	/* the hash to tie the files together */
	chash VARCHAR(64) NOT NULL DEFAULT '0',
	/* the hash to determine if we already inserted the subject or not */
	fhash VARCHAR(64) NOT NULL DEFAULT '0',
	/* wether we have an NFO or not for this file */
	nstatus TINYINT(1) SIGNED NOT NULL DEFAULT 0,
	/* wether this file is passworded or not */
	pstatus TINYINT(1) SIGNED NOT NULL DEFAULT 0,
	/* how many files inside the rar/zip files we added to the innerfiles table */
	innerfiles TINYINT UNSIGNED NOT NULL DEFAULT 0,
	PRIMARY KEY (id),
	KEY poster (poster),
	KEY utime (utime),
	KEY ltime (ltime),
	KEY groupid (groupid),
	KEY chash (chash),
	KEY fhash (fhash),
	KEY nstatus (nstatus),
	KEY pstatus (pstatus)
) ENGINE=INNODB ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8 DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci AUTO_INCREMENT=1;

/* This will only be used as a template, when a new group is started the table will be copied to parts_groupid */
DROP TABLE IF EXISTS parts;

CREATE TABLE parts
(
	id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	/* part number ex.: yEnc (1/29) ;; the number 1 */
	part MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
	/* file id from files table */
	fileid INT UNSIGNED NOT NULL DEFAULT 0,
	/* the message-id */
	messid VARCHAR(255) NOT NULL DEFAULT '',
	/* the size in bytes of the part */
	psize MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
	/* the nntp provider */
	provider TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
	PRIMARY KEY (id),
	KEY fileid (fileid),
	UNIQUE messid (messid)
) ENGINE=INNODB ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8 DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci AUTO_INCREMENT=1;

/* NFO files */
DROP TABLE IF EXISTS filenfo;

CREATE TABLE filenfo
(
	id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	/* hash from the files table */
	fhash VARCHAR(64) NOT NULL DEFAULT '0',
	/* nfo file, compressed */
	nfo BLOB NULL DEFAULT NULL,
	PRIMARY KEY (id),
	KEY fhash (fhash)
) ENGINE=INNODB ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8 DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci AUTO_INCREMENT=1;

/* Files inside of rars */
DROP TABLE IF EXISTS innerfiles;

CREATE TABLE innerfiles
(
	id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	/* hash from the files table */
	fhash VARCHAR(64) NOT NULL DEFAULT '0',
	/* file name */
	ifname VARCHAR(255) NOT NULL DEFAULT '',
	/* unixtime the file was compressed */
	iftime INT(8) UNSIGNED NOT NULL DEFAULT 0,
	/* the size of the file in bytes */
	ifsize BIGINT UNSIGNED NOT NULL DEFAULT 0,
	PRIMARY KEY (id),
	KEY fhash (fhash),
	KEY ifname (ifname),
	KEY iftime (iftime),
	KEY ifsize (ifsize),
	UNIQUE uniquefile (ifname, fhash)
) ENGINE=INNODB ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8 DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci AUTO_INCREMENT=1;
