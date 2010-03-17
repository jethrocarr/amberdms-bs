<?php
/*
	This configration file is a place holder.

	Your real configuration file is in config-settings.php
*/

$GLOBALS["config"] = array();




/*
	Define Amberdms Billing System fixed values
*/

// define the application details
$GLOBALS["config"]["app_name"]			= "Amberdms Billing System";
$GLOBALS["config"]["app_version"]		= "2.0.0_alpha_1";

// define the schema version required
$GLOBALS["config"]["schema_version"]		= "20091206";



/*
	Apply required PHP settings
*/
ini_set('memory_limit', '32M');			// note that ABS doesn't need much RAM apart from when
						// doing source diffs or graph generation.



/*
	Inherit User Configuration & Database Connectivity
*/
include("config-settings.php");
include("database.php");

?>
