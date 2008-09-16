<?php
/*
	timereg/timereg-day-process.php

	access: timekeeping

	Allows either the addition of a new time entry to the day, or the adjustment of an existing entry.
*/

// includes
include_once("../include/config.php");
include_once("../include/amberphplib/main.php");

// custom includes
include("../include/user/permissions_staff.php");



if (user_permissions_get('timekeeping'))
{
	/////////////////////////

	$id				= security_form_input_predefined("int", "id_timereg", 0, "");
	$employeeid			= security_form_input_predefined("int", "id_employee", 1, "");
	
	$data["date"]			= security_form_input_predefined("date", "date", 1, "You must specify a date for the entry to belong to.");
	$data["phaseid"]		= security_form_input_predefined("int", "phaseid", 1, "You must select a project & phase for the time to be assigned to");
	$data["time_booked"]		= security_form_input_predefined("hourmins", "time_booked", 1, "You must enter some time to book");
	$data["description"]		= security_form_input_predefined("any", "description", 1, "You must enter a description");



	// are we editing an existing time entry or adding a new one?
	if ($id)
	{
		$mode = "edit";

		// make sure the time entry actually exists
		$mysql_string		= "SELECT id FROM `timereg` WHERE id='$id'";
		$mysql_result		= mysql_query($mysql_string);
		$mysql_num_rows		= mysql_num_rows($mysql_result);

		if (!$mysql_num_rows)
		{
			$_SESSION["error"]["message"][] = "The time entry you have attempted to edit - $id - does not exist in this system.";
		}
	}
	else
	{
		$mode = "add";
	}


		
	//// ERROR CHECKING ///////////////////////


	// make sure user has permissions to write for this staff member
	if (!user_permissions_staff_get("timereg_write", $employeeid))
	{
		$_SESSION["error"]["message"][] = "Sorry, you do not have access rights to book time for this employee.";
	}


	// make sure we don't end up with more than 24 hours booked for one day

	// get a total of the time currently booked for this date
	$mysql_string	= "SELECT time_booked FROM `timereg` WHERE date='". $data["date"] ."' AND employeeid='". $data["employeeid"] ."'";
	if ($id)
		$mysql_string .= " AND id!='$id'";
		
	$mysql_result	= mysql_query($mysql_string);
	$mysql_num_rows	= mysql_num_rows($mysql_result);

	$timetotal = 0;
	if ($mysql_num_rows)
	{
		while ($mysql_data = mysql_fetch_array($mysql_result))
		{
			$timetotal += $mysql_data["time_booked"];
		}
	}

	// add new value of the current item
	$timetotal += $data["time_booked"];

	// make sure the totals are less than 24 hours
	if ($timetotal > 86400)
	{
		$_SESSION["error"]["message"][] = "You can not book more than 24 hours of time in one day.";
	}
	



	/// if there was an error, go back to the entry page
	if ($_SESSION["error"]["message"])
	{	
		if ($mode == "edit")
		{
			$_SESSION["error"]["form"]["timereg_day"] = "failed";
			header("Location: ../index.php?page=timekeeping/timereg-day.php&date=". $data["date"] ."&editid=$id");
			exit(0);
		}
		else
		{
			$_SESSION["error"]["form"]["timereg_day"] = "failed";
			header("Location: ../index.php?page=timekeeping/timereg-day.php&date=". $data["date"] . "");
			exit(0);
		}
	}
	else
	{
		if ($mode == "add")
		{
			// create a new entry in the DB
			$mysql_string = "INSERT INTO `timereg` (date) VALUES ('". $data["date"]."')";
			if (!mysql_query($mysql_string))
			{
				$_SESSION["error"]["message"][] = "A fatal SQL error occured: ". mysql_error();
			}

			$id = mysql_insert_id();
		}

		if ($id)
		{
			// update timereg details
			$mysql_string = "UPDATE `timereg` SET "
						."date='". $data["date"] ."', "
						."employeeid='". $employeeid ."', "
						."phaseid='". $data["phaseid"] ."', "
						."time_booked='". $data["time_booked"] ."', "
						."description='". $data["description"] ."' "
						."WHERE id='$id'";
						
			if (!mysql_query($mysql_string))
			{
				$_SESSION["error"]["message"][] = "A fatal SQL error occured: ". mysql_error();
			}
			else
			{
				if ($mode == "add")
				{
					$_SESSION["notification"]["message"][] = "Time successfully booked.";
				}
				else
				{
					$_SESSION["notification"]["message"][] = "Time booked has been updated successfully.";
				}
				
			}
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
