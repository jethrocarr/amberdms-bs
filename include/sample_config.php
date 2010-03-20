<?php
/*
	Sample configuration file for the Amberdms Billing System. This should be installed
	as config-settings.php

	This file should be read-only by the httpd user. All other users should be denied.
*/



/*
	Instance Configuration

	When running the Amberdms Billing System for a single entity/company, set
	this value to "single".

	To support multiple instances, set to "hosted" to enable the hosted mode
	and refer to the product documentation for further details.
*/

$config["instance"]	= "single";



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
	Dangerous Config Options

	Enable this to allow more dangerous program options to be adjusted via the config page
	on the web interface - options such as paths, emailing and more.

	It is recommended to only have this enabled during inital program configuration.
*/

$config["dangerous_conf_options"] = "enabled";




/*
	Force debugging on for all users + scripts
	(note: debugging can be enabled on a per-user basis by an admin via the web interface)
*/
// $_SESSION["user"]["debug"] = "on";



?>
