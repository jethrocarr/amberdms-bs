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
$config["db_host"] = "localhost";			// hostname of the MySQL server
$config["db_name"] = "billing_system";			// database name
$config["db_user"] = "root";				// MySQL user
$config["db_pass"] = "";				// MySQL password (if any)



/*
	Fixed options

	Do not touch anything below this line
*/

// Connect to the MySQL database
include("database.php");

// Initate session variables
session_start();

?>
