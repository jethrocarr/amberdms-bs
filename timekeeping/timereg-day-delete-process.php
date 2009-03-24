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

	$id				= security_script_input("/^[0-9]*$/", $_GET["id"]);
	$date				= security_script_input("/^\S*$/", $_GET["date"]);
	

	
	// make sure the time entry actually exists
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT id, locked FROM `timereg` WHERE id='$id' LIMIT 1";
	$sql_obj->execute();

	if (!$sql_obj->num_rows())
	{
		log_write("error", "process", "The time entry you have attempted to delete - $id - does not exist in this system.");
	}
	else
	{
		$sql_obj->fetch_array();

		if ($sql_obj->data[0]["locked"])
		{
			log_write("error", "process", "This time entry has been locked and can not be adjusted.");
		}
	}


		
	//// ERROR CHECKING ///////////////////////


	/// if there was an error, go back to the entry page
	if ($_SESSION["error"]["message"])
	{	
		$_SESSION["error"]["form"]["timereg_delete"] = "failed";
		header("Location: ../index.php?page=timekeeping/timereg-day.php&date=". $date ."&editid=$id");
		exit(0);
	}
	else
	{
		// delete
		$sql_obj		= New sql_query;
		$sql_obj->string	= "DELETE FROM `timereg` WHERE id='$id' LIMIT 1";
		$sql_obj->execute();
						
		if (!$sql_obj->num_rows())
		{
			log_write("error", "process", "An error occured whilst deleting entry");
		}
		else
		{
			$_SESSION["notification"]["message"][] = "Time entry successfully removed.";
		}

		// display updated details
		header("Location: ../index.php?page=timekeeping/timereg-day.php&date=". $date ."");
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
