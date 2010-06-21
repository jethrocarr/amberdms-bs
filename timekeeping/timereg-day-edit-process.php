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

	$id				= @security_form_input_predefined("int", "id_timereg", 0, "");
	$employeeid			= @security_form_input_predefined("int", "employeeid", 1, "");
	
	$data["date"]			= @security_form_input_predefined("date", "date", 1, "You must specify a date for the entry to belong to.");
	$data["projectid"]		= @security_form_input_predefined("int", "projectid", 1, "You must select a project & phase for the time to be assigned to");
	$data["phaseid"]		= @security_form_input_predefined("int", "phaseid", 1, "You must select a project & phase for the time to be assigned to");
	$data["time_booked"]		= @security_form_input_predefined("hourmins", "time_booked", 1, "You must enter some time to book");
	$data["description"]		= @security_form_input_predefined("any", "description", 1, "You must enter a description");



	// are we editing an existing time entry or adding a new one?
	if ($id)
	{
		$mode = "edit";

		// make sure the time entry actually exists
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id, locked FROM `timereg` WHERE id='$id' LIMIT 1";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			log_write("error", "process", "The time entry you have attempted to edit - $id - does not exist in this system.");
		}
		else
		{
			$sql_obj->fetch_array();

			if ($sql_obj->data[0]["locked"])
			{
				log_write("error", "process", "This time entry has been locked and can not be adjusted.");
			}
		}
	}
	else
	{
		$mode = "add";
	}


		
	//// ERROR CHECKING ///////////////////////


	// make sure user has per
	if (!user_permissions_get("timekeeping_all_write") && !user_permissions_staff_get("timereg_write", $employeeid))
	{
		log_write("error", "process", "Sorry, you do not have access rights to book time for this employee.");
	}


	// make sure we don't end up with more than 24 hours booked for one day

	// get a total of the time currently booked for this date
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT time_booked FROM `timereg` WHERE date='". $data["date"] ."' AND employeeid='$employeeid'";

	if ($id)
		$sql_obj->string .= " AND id!='$id'";
		
	$sql_obj->execute();
	$timetotal = 0;

	if ($sql_obj->num_rows())
	{
		$sql_obj->fetch_array();

		foreach ($sql_obj->data as $data_sql)
		{
			$timetotal += $data_sql["time_booked"];
		}
	}

	// add new value of the current item
	$timetotal += $data["time_booked"];

	// make sure the totals are less than 24 hours
	if ($timetotal > 86400)
	{
		log_write("error", "process", "You can not book more than 24 hours of time in one day.");
	}
	

	// make sure user is not trying to book time in the future if the option isn't enabled
	if (sql_get_singlevalue("SELECT value FROM config WHERE name='TIMESHEET_BOOKTOFUTURE'") == "disabled")
	{
		if (time_date_to_timestamp($data["date"]) > mktime())
		{
			log_write("error", "timereg_day_edit-process", "You are not permitted to book time in the future. If you wish to change this behaviour, adjust the TIMESHEET_BOOKTOFUTURE option in the configuration.");
			$_SESSION["error"]["date-error"] = 1;
		}
	}



	/// if there was an error, go back to the entry page
	if ($_SESSION["error"]["message"])
	{	
		if ($mode == "edit")
		{
			$_SESSION["error"]["form"]["timereg_day"] = "failed";
			header("Location: ../index.php?page=timekeeping/timereg-day-edit.php&date=". $data["date"] ."&editid=$id");
			exit(0);
		}
		else
		{
			$_SESSION["error"]["form"]["timereg_day"] = "failed";
			header("Location: ../index.php?page=timekeeping/timereg-day-edit.php&date=". $data["date"] . "");
			exit(0);
		}
	}
	else
	{

		// start transaction
		$sql_obj = New sql_query;
		$sql_obj->trans_begin();


		if ($mode == "add")
		{
			// create a new entry in the DB
			$sql_obj->string	= "INSERT INTO `timereg` (date) VALUES ('". $data["date"]."')";
			$sql_obj->execute();

			$id = $sql_obj->fetch_insert_id();

			if (!$id)
			{
				log_write("error", "process", "An error occured whilst attempting to create a new time record.");
			}
		}

		if ($id)
		{
			// update timereg details
			$sql_obj->string	= "UPDATE `timereg` SET "
							."date='". $data["date"] ."', "
							."employeeid='". $employeeid ."', "
							."phaseid='". $data["phaseid"] ."', "
							."time_booked='". $data["time_booked"] ."', "
							."description='". $data["description"] ."' "
							."WHERE id='$id' LIMIT 1";

			if (!$sql_obj->execute())
			{
				log_write("error", "process", "A problem occured whilst attempting to update timereg details");
			}
		}




		// commit
		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "process", "No changes have been made.");
		}
		else
		{
			$sql_obj->trans_commit();

			if ($mode == "add")
			{
				log_write("notification", "process", "Time successfully booked.");
			}
			else
			{
				log_write("notification", "process", "Time booked has been updated successfully.");
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
