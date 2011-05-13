#!/usr/bin/php
<?php
/*
	include/cron/index.php

	This script is used to execute all the other cron scripts and will correctly
	handle multi-instance "hosted" configurations.
*/

// includes
require("../config.php");
require("../amberphplib/main.php");



/*
	Get the name of the script to run
*/

// verify
if (!isset($argv[1]))
{
	die("You must supply the name of the script to execute as a command line argument\n");
}

// include the page
if (!@include($argv[1]))
{
	die("Unable to load script \"". $argv[1] ."\"\n");
}

// set our user account
$_SESSION["user"]["id"] = 0;



/*
	If we are running in hosted mode, we need to fetch an array
	of all the active instances, and then execute the cronjob for all those
	instances.
*/
$instances = array();

if ($GLOBALS["config"]["instance"] == "hosted")
{
	// backup the original globals
	$GLOBALS["config_orig"]		= $GLOBALS["config"];

	// query the hosted DB
	$sql_instance_obj		= New sql_query;
	$sql_instance_obj->string	= "SELECT instanceid, db_hostname FROM instances WHERE active=1";
	$sql_instance_obj->execute();

	$sql_instance_obj->fetch_array();

	foreach ($sql_instance_obj->data as $data)
	{
		print "\n----\n";
		print "Processing instance ". $data["instanceid"] ."\n";
		print "----\n";


		/*
			Need to erase global cache here, otherwise stuff can linger on between instances which
			is really, really, nasty.

			This is not a problem with the web-interface since the cache only lasts for the processing
			of each page load and is then cleared, however since this one PHP script executes code for
			all instances the cache can survive.
		*/

		$GLOBALS["cache"] 		= array();
		$GLOBALS["config"]		= $GLOBALS["config_orig"];	// restore original globals


		// if the hostname is blank, default to the current
		if ($data["db_hostname"] == "")
		{
			$data["db_hostname"] = $GLOBALS["config"]["db_host"];
		}

		// if the instance database is on a different server, initate a connection
		// to the new server.
		if ($data["db_hostname"] != $GLOBALS["config"]["db_host"])
		{
			$link = mysql_connect($data["db_hostname"], $config["db_user"], $config["db_pass"]);

			if (!$link)
			{
				die("Unable to connect to database server for instance ". $data["instanceid"] ." - error: " . mysql_error() ."\n");
			}
		}

		// select the instance database
		$dbaccess = mysql_select_db($GLOBALS["config"]["db_name"] ."_". $data["instanceid"]);
	
		if (!$dbaccess)
		{
			// invalid instance ID
			// ID has a record in the instance table, but does not have a valid database
			die("Instance ID has record but no database accessible - error: ". mysql_error() ."\n");
		}


		/*
			Bootstrap Framework
	
			We couldn't use the Amberphplib framework to connect to the database, however
			now that we have connected, we can force set the default values and it will
			use the connection we have established as the default for all queries.
		*/

		$GLOBALS["cache"]["database_default_link"]	= $link;
		$GLOBALS["cache"]["database_default_type"]	= "mysql";


		/*
			Load correct global settings
		*/

		log_debug("start", "Loading configuration from database for hosted DB");

		$sql_config_obj			= New sql_query;
		$sql_config_obj->string		= "SELECT name, value FROM config ORDER BY name";
		$sql_config_obj->execute();
		$sql_config_obj->fetch_array();

		foreach ($sql_config_obj->data as $data_config)
		{
			$GLOBALS["config"][ $data_config["name"] ] = $data_config["value"];
		}

		unset($sql_config_obj);



		// execute the page functions
		page_execute();
	}
}
else
{
	/*
		Running in single-instance mode - no need to connect to the database.

		Just execute the functions in the script once.
	*/

	page_execute();
}


?>
