<?php
/*
	Configuration file for the Amberdms Billing System

	This file should be read-only by the httpd user. All other users should be denied.
*/

// Database Settings
$_GLOBAL["db_host"] = "localhost";			// hostname of the MySQL server
$_GLOBAL["db_name"] = "billing_system";			// database name
$_GLOBAL["db_user"] = "root";				// MySQL user
$_GLOBAL["db_pass"] = "";				// MySQL password (if any)

// Debug Settings
$_GLOBAL["debug"] = TRUE;

// Connect to the MySQL database
include("database.php");

// Initate session variables
session_start();

?>
