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

	if ($_SESSION["user"]["instance"]["id"])
	{
		/*
			Connect to instance DB
		*/

		// login to the database
		$link = mysql_connect($_SESSION["user"]["instance"]["db_hostname"], $config["db_user"], $config["db_pass"]);
		if (!$link)
			die("Unable to connect to DB:" . mysql_error());

		// select the database
		$db_selected = mysql_select_db($config["db_name"] ."_". $_SESSION["user"]["instance"]["id"], $link);
		if (!$db_selected)
			die("Unable to connect to DB:" . mysql_error());
	}
	else
	{
		/*
			Connect to main instances database to allow user to login
		*/

		// login to the database
		$link = mysql_connect($config["db_hostname"], $config["db_user"], $config["db_pass"]);
		if (!$link)
			die("Unable to connect to DB:" . mysql_error());

		// select the database
		$db_selected = mysql_select_db($config["db_name"] ."_instances", $link);
		if (!$db_selected)
			die("Unable to connect to DB:" . mysql_error());
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
	$link = mysql_connect($config["db_hostname"], $config["db_user"], $config["db_pass"]);
	if (!$link)
		die("Unable to connect to DB:" . mysql_error());

	// select the database
	$db_selected = mysql_select_db($config["db_name"], $link);
	if (!$db_selected)
		die("Unable to connect to DB:" . mysql_error());

}



?>
