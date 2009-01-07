<?php
/*
	AMBERPHPLIB

	PHP functions + classed developed by Amberdms for use in various products.

	All code is Licensed under the GNU GPL version 2. If you wish to use this
	code in a propietary/commercial product, please contact sales@amberdms.com.
*/




/*
	CORE FUNCTIONS

	These functions are required for basic operation by all major components of
	AMBERPHPLIB, so we define them first.
*/
function log_debug($category, $content)
{
	return log_write("debug", $category, $content);
}

function log_write($type, $category, $content)
{
	if ($_SESSION["user"]["debug"] == "on")
	{
		// write log record
		$log_record = array();

		$log_record["type"]	= $type;
		$log_record["category"]	= $category;
		$log_record["content"]	= $content;
		$log_record["memory"]	= memory_get_usage();
	
		// this provided PHP 4 compadiblity.
		// TODO: when upgrading to PHP 5, replace with microtime(TRUE).
		list($usec, $sec)	= explode(" ", microtime());
		$log_record["time"]	= ((float)$usec + (float)$sec);
		
		$_SESSION["user"]["log_debug"][] = $log_record;
		
		// print log messages when running from CLI
		if ($_SESSION["mode"] == "cli")
			print "Debug: $content\n";
	}

	// also add error messages to the error array
	if ($type == "error")
	{
		$_SESSION["error"]["message"][] = $content;
		
		// print log messages when running from CLI
		if ($_SESSION["mode"] == "cli")
			print "Error: $content\n";
	}

	// also add notification messages to the notification array
	if ($type == "notification")
	{
		$_SESSION["notification"]["message"][] = $content;
		
		// print log messages when running from CLI
		if ($_SESSION["mode"] == "cli")
			print "$content\n";
	}

}




/*
	INCLUDE MAJOR AMBERDPHPLIB COMPONENTS
*/

log_debug("start", "");
log_debug("start", "AMBERPHPLIB STARTED");
log_debug("start", "Debugging for: ". $_SERVER["REQUEST_URI"] ."");
log_debug("start", "");


// Important that we require language first, since other functions
// require it.
require("inc_language.php");

// DB SQL processing and execution
require("inc_sql.php");

// User + Security Functions
require("inc_user.php");
require("inc_security.php");

// Error Handling
require("inc_errors.php");

// Misc Functions
require("inc_misc.php");

// Template processing engines
require("inc_template_engines.php");

// Functions/classes for data entry and processing
require("inc_forms.php");
require("inc_tables.php");
require("inc_file_uploads.php");

// Journal System
require("inc_journal.php");

// Menus
require("inc_menus.php");




/*
	Configure Local Timezone

	Decent timezone handling was only implemented with PHP 5.2.0, so the ability to select the user's localtime zone
	is limited to users running this software on PHPv5 servers.

	Users of earlier versions will be limited to just using the localtime of the server - the effort required
	to try and add timezone for older users (mainly PHPv4) is not worthwhile when everyone should be moving to PHP 5.2.0+
	in the near future.
*/

if (version_compare(PHP_VERSION, '5.2.0') === 1)
{
	log_debug("start", "Setting timezone based on user/system configuration");
	
	// fetch config option
	if ($_SESSION["user"]["timezone"])
	{
		// fetch from user preferences
		$timezone = $_SESSION["user"]["timezone"];
	}
	else
	{
		// user hasn't chosen a default time format yet - use the system default
		$timezone = sql_get_singlevalue("SELECT value FROM config WHERE name='TIMEZONE_DEFAULT' LIMIT 1");
	}

	// if set to SYSTEM just use the default of the server, otherwise
	// we need to set the timezone here.
	if ($timezone != "SYSTEM")
	{
		if (!date_default_timezone_set($timezone))
		{
			log_write("error", "start", "A problem occured trying to set timezone to \"$timezone\"");
		}
		else
		{
			log_debug("start", "Timezone set to \"$timezone\" successfully");
		}
	}

	unset($timezone);
}




?>
