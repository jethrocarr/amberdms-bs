<?php
/*
	timereg/timereg-day-delete-process.php

	access: timekeeping

	Allows the deletion of an unwanted time entry.
*/

// includes
include_once("../include/config.php");
include_once("../include/amberphplib/main.php");


if (user_permissions_get('timekeeping'))
{
	/////////////////////////

	$id				= security_form_input_predefined("int", "id_timereg", 1, "");
	
	$data["date"]			= security_form_input_predefined("any", "date", 1, "You must specify a date for the entry to belong to.");


	
	// make sure the time entry actually exists
	$mysql_string		= "SELECT id FROM `timereg` WHERE id='$id'";
	$mysql_result		= mysql_query($mysql_string);
	$mysql_num_rows		= mysql_num_rows($mysql_result);
	
	if (!$mysql_num_rows)
	{
		$_SESSION["error"]["message"][] = "The time entry you have attempted to delete - $id - does not exist in this system.";
	}


		
	//// ERROR CHECKING ///////////////////////


	/// if there was an error, go back to the entry page
	if ($_SESSION["error"]["message"])
	{	
		header("Location: ../index.php?page=timekeeping/timereg-day.php&date=". $data["date"] ."&editid=$id");
		exit(0);
	}
	else
	{
		// delete
		$mysql_string = "DELETE FROM `timereg` WHERE id='$id'";
						
		if (!mysql_query($mysql_string))
		{
			$_SESSION["error"]["message"][] = "A fatal SQL error occured: ". mysql_error();
		}
		else
		{
			$_SESSION["notification"]["message"][] = "Time entry successfully removed.";
		}

		// display updated details
		header("Location: ../index.php?page=timekeeping/timereg-day.php&date=". $data["date"] ."");
		exit(0);
	}

	/////////////////////////
	
}
else
{
	// user does not have perms to view this page/isn't logged on
	error_render_noperms();
	header("Location: ../index.php?page=message.php");
	exit(0);
}


?>
