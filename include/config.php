<?php
/*
	ABS MASTER CONFIGURATION FILE

	This file contains key application configuration options and values for
	developers rather than users/admins.

	DO NOT MAKE ANY CHANGES TO THIS FILE, INSTEAD PLEASE MAKE ANY ADJUSTMENTS
	TO "config-settings.php" TO ENSURE CORRECT APPLICATION OPERATION.

	If config-settings.php does not exist, then you need to copy sample_config.php
	into it's place.
*/

$GLOBALS["config"] = array();




/*
	Define Amberdms Billing System fixed values
*/

// define the application details
$GLOBALS["config"]["app_name"]			= "Amberdms Billing System";
$GLOBALS["config"]["app_version"]		= "2.0.4";

// define the schema version required
$GLOBALS["config"]["schema_version"]		= "20180424";



/*
	Session Management
*/

// Initate session variables
if (isset($_SERVER['SERVER_NAME']))
{
	// proper session variables
	session_name("amberdms_billing_system");
	session_start();
}
else
{
	// trick to make logging and error system work correctly for scripts.
	$GLOBALS["_SESSION"]		= array();
	$_SESSION["mode"]		= "cli";
	$_SESSION["user"]["debug"]	= "on";
}



/*
	Inherit User Configuration
*/
include("config-settings.php");

/*
	Silence warnings to avoid unexpected errors appearing on newer PHP versions
	than what the developers tested with - but turn on for devs
*/
if (empty($_SESSION["user"]["debug"]))
{
	ini_set("display_errors", 0);
}


/*
	Connect to Databases
*/
include("database.php");


?>
