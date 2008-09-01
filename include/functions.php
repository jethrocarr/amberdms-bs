<?php
//
// include/functions.php
//
// various useful functions, used to reduce code duplication.
// 
//
// FUNCTIONS:
//
// DB VALUE FUNCTIONS
//
// db_get_value ( table, name )
//	returns the requested value from the requested table or returns 0 if it was unable to.
//	This works where the table has the structure of name & value (eg: cfg_basic)
//
// db_update_value ( table, name, value )
//	updates the desired value in it's table.
//
//
// CONFIG CHANGE CONTROL
//
// app_trackchanges_flag( scriptname )
//	used to keep track of which config sections have changed, so we know what scripts need to be run.
//
//
// HELP FUNCTIONS
//
// helplink( id )
//	returns an html string, including a help icon, with a hyperlink to the help page specified by id.
//


/* DB VALUE FUNCTIONS */

function db_get_value($table, $name)
{
	$mysql_string	= "SELECT value FROM `$table` WHERE name='$name'";
	$mysql_result	= mysql_query($mysql_string);
	$mysql_num_rows	= mysql_num_rows($mysql_result);

	if ($mysql_num_rows)
	{
		// fetch and return value
		$mysql_data = mysql_fetch_array($mysql_result);
		return $mysql_data['value'];
	}

	// default fail
	return 0;
}

function db_update_value($table, $name, $value)
{
	$mysql_string = "UPDATE `$table` SET value='$value' WHERE name='$name'";
	mysql_query($mysql_string) || die("MySQL Problem: ". mysql_error());

	return 1;
}


/* CONFIG CHANGE CONTROL */

function app_trackchanges_flag($scriptname)
{
	// check if an entry already exists for this script
	$mysql_string	= "SELECT id FROM `app_trackchanges` WHERE script='$scriptname'";
	$mysql_result	= mysql_query($mysql_string);
	$mysql_num_rows	= mysql_num_rows($mysql_result);

	if (!$mysql_num_rows)
	{
		// no entry exists, add a new one.
		$mysql_string = "INSERT INTO `app_trackchanges` (script) VALUES ('$scriptname')";
		mysql_query($mysql_string) || die("MySQL Problem: ". mysql_error());

	}
	
	return 1;
}




/* HELP FUNCTIONS */


function helplink($id)
{
	return "<a href=\"help/viewer.php?id=$id\" target=\"new\" title=\"Click here for a popup help box\"><img src=\"images/icons/help.gif\" alt=\"?\" border=\"0\"></a>";
}



?>
