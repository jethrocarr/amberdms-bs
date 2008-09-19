<?php
/*
	include/database.php

	Establishes MySQL connection.	
*/



// login to the database
$link = mysql_connect($config["db_hostname"], $config["db_user"], $config["db_pass"]);
if (!$link)
	die("Unable to connect to DB:" . mysql_error());

// select the database
$db_selected = mysql_select_db($config["db_name"], $link);
if (!$db_selected)
	die("Unable to connect to DB:" . mysql_error());



?>
