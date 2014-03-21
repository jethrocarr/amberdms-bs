<?php
/*
	include/database.php

	Establishes connection to the MySQL database.
*/



if ($config["instance"] == "hosted")
{
	/*
		HOSTED INSTANCE CONFIGURATION

		In a hosted configuration, there are multiple billing systems and users need to
		select which instance to log into.
		
		Before login, the billing system connects to an instances database which lists
		all the instances available.

		When the user athenticates, the relevent database is selected and then saved
		to the session variables, which is then used here to connect directly to the
		relevant database server
	*/

	if (isset($_SESSION["user"]["instance"]["id"]))
	{
		/*
			Connect to instance DB
		*/

		// login to the database
		$link = ($GLOBALS["___mysqli_ston"] = mysqli_connect($_SESSION["user"]["instance"]["db_hostname"],  $config["db_user"],  $config["db_pass"]));
		if (!$link)
			die("Unable to connect to DB:" . ((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)));

		// select the database
		$db_selected = ((bool)mysqli_query( $link, "USE ". $config["db_name"] ."_". $_SESSION["user"]["instance"]["id"]));
		if (!$db_selected)
			die("Unable to connect to DB:" . ((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)));
	}
	else
	{
		/*
			Connect to main instances database to allow user to login
		*/

		// login to the database
		$link = ($GLOBALS["___mysqli_ston"] = mysqli_connect($config["db_host"],  $config["db_user"],  $config["db_pass"]));
		if (!$link)
			die("Unable to connect to DB:" . ((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)));

		// select the database
		$db_selected = ((bool)mysqli_query( $link, "USE ". $config["db_name"] ."_instances"));
		if (!$db_selected)
			die("Unable to connect to DB:" . ((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)));
	}

}
else
{
	/*
		SINGLE INSTANCE CONFIGURATION

		There is only 1 billing system database, simply connect to it
		using the supplied information.
	*/

		
	// login to the database
	$link = ($GLOBALS["___mysqli_ston"] = mysqli_connect($config["db_host"],  $config["db_user"],  $config["db_pass"]));
	if (!$link)
		die("Unable to connect to DB:" . ((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)));

	// select the database
	$db_selected = ((bool)mysqli_query( $link, "USE ". $config["db_name"]));
	if (!$db_selected)
		die("Unable to connect to DB:" . ((is_object($GLOBALS["___mysqli_ston"])) ? mysqli_error($GLOBALS["___mysqli_ston"]) : (($___mysqli_res = mysqli_connect_error()) ? $___mysqli_res : false)));

}


// Disable SQL modes for this session to ensure backwards compat with
// newer MySQL version (> 5.6) using STRICT modes.

mysqli_query( $link, "SET SESSION sql_mode=''");


/*
	Bootstrap Framework

	We couldn't use the Amberphplib framework to connect to the database, however
	now that we have connected, we can force set the default values and it will
	use the connection we have established as the default for all queries.
*/

$GLOBALS["cache"]["database_default_link"]	= $link;
$GLOBALS["cache"]["database_default_type"]	= "mysql";



?>
