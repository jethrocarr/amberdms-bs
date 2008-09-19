<?php
/*
	Configuration file for the Amberdms Billing System

	This file should be read-only by the httpd user. All other users should be denied.
*/

/*
	Database Settings

	Currently we only support MySQL databases but this may be expanded
	to include other SQL databases in the future.
*/
$_GLOBAL["db_host"] = "localhost";			// hostname of the MySQL server
$_GLOBAL["db_name"] = "billing_system";			// database name
$_GLOBAL["db_user"] = "root";				// MySQL user
$_GLOBAL["db_pass"] = "";				// MySQL password (if any)


/*
	Logging
*/

// Debug Settings
$_GLOBAL["debug"] = TRUE;



/*
	File Storage

	Various features of this application allow you to upload file attachments. There are
	two different methods to configuring this.

	1. Upload all files to a directory on the webserver

		Advantages:
			* Keeps the SQL database small, which increases performance and the
			  small size makes backups faster.
			* Work well in a hosting cluster if using a shared storage device.
			* More efficent to backup, due to ability to rsync or transfer increments.
			* No limits to the filesize, except for limits imposed by network connection speed and HTTP timeout.

	2. Upload all files into the MySQL database as binary blobs

		Advantages:
			* All the data is in a single location
			* Single location to backup
			* Easier security controls.
			* If you have a replicating MySQL setup, the files will be replicated
			  as well.

	Because there are valid reasons for choosing either solution, this application allows you
	to select which one to use.

	Note: If you change the method after the program has uploaded files to the disk or the DB,
	you will still be able to access all the files, but data will be spread across both the filesystem
	and the database, which is ugly and is an unsupported configuration.

	SUMMARY: SELECT THE OPTION YOU PREFER AND STICK WITH IT, OTHERWISE YOU ARE SETTING YOURSELF
	UP FOR TROUBLE. AMBERDMS DOES NOT SUPPORT ANY PROBLEMS OCCURING DUE TO A CHANGE OF METHOD.

	TODO: In future, develop tool for moving all data in or out of the DB if user decides to change.

	Syntax:
	$_GLOBAL["data_storage_method"] = "database";
	or
	$_GLOBAL["data_storage_method"] = "filesystem";
	$_GLOBAL["data_storage_location"] = "data/default/";
*/

// data storage method
$_GLOBAL["data_storage_method"] = "database";

// data directory location
$_GLOBAL["data_storage_location"] = "data/default/";




/*
	Fixed options

	Do not touch anything below this line
*/

// Connect to the MySQL database
include("database.php");

// Initate session variables
session_start();

?>
