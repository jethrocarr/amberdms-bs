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


?>
