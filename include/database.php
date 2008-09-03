<?php
/*
	include/database.php

	Establishes MySQL connection.	
*/



// login to the database
$link = mysql_connect($_GLOBAL["db_hostname"], $_GLOBAL["db_user"], $_GLOBAL["db_pass"]);
if (!$link)
	die("Unable to connect to DB:" . mysql_error());

// select the database
$db_selected = mysql_select_db($_GLOBAL["db_name"], $link);
if (!$db_selected)
	die("Unable to connect to DB:" . mysql_error());



?>
