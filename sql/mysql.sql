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

/* This will only be used as a template, when a new group is started the table will be copied to files_groupid */
DROP TABLE IF EXISTS files;

CREATE TABLE files
(
	/* 4 billion files per group should be good enough */
	id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	groupid MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
	subject VARCHAR(255) NOT NULL DEFAULT '',
	origsubject VARCHAR(255) NOT NULL DEFAULT '',
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
	PRIMARY KEY (id),
	KEY poster (poster),
	KEY utime (utime),
	KEY ltime (ltime),
	KEY groupid (groupid),
	KEY chash (chash),
	KEY fhash (fhash)
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
	/* article number */
	anumber BIGINT UNSIGNED NOT NULL DEFAULT 0,
	/* the size in bytes of the part */
	psize MEDIUMINT UNSIGNED NOT NULL DEFAULT 0,
	PRIMARY KEY (id),
	KEY fileid (fileid),
	KEY anumber (anumber)
) ENGINE=INNODB ROW_FORMAT=COMPRESSED KEY_BLOCK_SIZE=8 DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci AUTO_INCREMENT=1;
