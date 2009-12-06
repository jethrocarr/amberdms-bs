<?php
/*
	This configration file is a place holder.

	Your real configuration file is in config-settings.php
*/



/*
	Define Amberdms Billing System fixed values
*/

// define the application details
$GLOBALS["config"]["app_name"]			= "Amberdms Billing System";
$GLOBALS["config"]["app_version"]		= "1.4.0";

// define the schema version required
$GLOBALS["config"]["schema_version"]		= "20091206";



/*
	Inherit User Configuration & Database Connectivity
*/
include("config-settings.php");
include("database.php");

?>
