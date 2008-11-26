<?php
/*
	staff/edit-process.php

	access: staff_write

	Allows existing staff to be adjusted, or new staff to be added.
*/

// includes
include_once("../include/config.php");
include_once("../include/amberphplib/main.php");


if (user_permissions_get('staff_write'))
{
	/////////////////////////

	$id				= security_form_input_predefined("int", "id_staff", 0, "");
	
	$data["name_staff"]		= security_form_input_predefined("any", "name_staff", 1, "You must set a staff name");
	$data["staff_code"]		= security_form_input_predefined("any", "staff_code", 0, "");
	$data["staff_position"]		= security_form_input_predefined("any", "staff_position", 0, "");
	
	$data["contact_phone"]		= security_form_input_predefined("any", "contact_phone", 0, "");
	$data["contact_fax"]		= security_form_input_predefined("any", "contact_fax", 0, "");
	$data["contact_email"]		= security_form_input_predefined("email", "contact_email", 0, "There is a mistake in the supplied email address, please correct.");
	
	$data["date_start"]		= security_form_input_predefined("date", "date_start", 1, "");
	$data["date_end"]		= security_form_input_predefined("date", "date_end", 0, "");


	// are we editing an existing staff or adding a new one?
	if ($id)
	{
		$mode = "edit";

		// make sure the staff actually exists
		$mysql_string		= "SELECT id FROM `staff` WHERE id='$id'";
		$mysql_result		= mysql_query($mysql_string);
		$mysql_num_rows		= mysql_num_rows($mysql_result);

		if (!$mysql_num_rows)
		{
			$_SESSION["error"]["message"][] = "The staff you have attempted to edit - $id - does not exist in this system.";
		}
	}
	else
	{
		$mode = "add";
	}


		
	//// ERROR CHECKING ///////////////////////


	// make sure we don't choose a staff name that has already been taken
	$mysql_string	= "SELECT id FROM `staff` WHERE name_staff='". $data["name_staff"] ."'";
	if ($id)
		$mysql_string .= " AND id!='$id'";
	$mysql_result	= mysql_query($mysql_string);
	$mysql_num_rows	= mysql_num_rows($mysql_result);

	if ($mysql_num_rows)
	{
		$_SESSION["error"]["message"][] = "Another staff member already has this name - please choose a unique name.";
		$_SESSION["error"]["name_staff-error"] = 1;
	}


	/// if there was an error, go back to the entry page
	if ($_SESSION["error"]["message"])
	{	
		if ($mode == "edit")
		{
			header("Location: ../index.php?page=hr/staff-view.php&id=$id");
			exit(0);
		}
		else
		{
			header("Location: ../index.php?page=hr/staff-add.php");
			exit(0);
		}
	}
	else
	{
		if ($mode == "add")
		{
			// create a new entry in the DB
			$mysql_string = "INSERT INTO `staff` (name_staff) VALUES ('".$data["name_staff"]."')";
			if (!mysql_query($mysql_string))
			{
				$_SESSION["error"]["message"][] = "A fatal SQL error occured: ". $mysql_error();
			}

			$id = mysql_insert_id();
		}

		if ($id)
		{
			// update staff details
			$mysql_string = "UPDATE `staff` SET "
						."name_staff='". $data["name_staff"] ."', "
						."staff_code='". $data["staff_code"] ."', "
						."staff_position='". $data["staff_position"] ."', "
						."contact_phone='". $data["contact_phone"] ."', "
						."contact_email='". $data["contact_email"] ."', "
						."contact_fax='". $data["contact_fax"] ."', "
						."date_start='". $data["date_start"] ."', "
						."date_end='". $data["date_end"] ."' "
						."WHERE id='$id'";
						
			if (!mysql_query($mysql_string))
			{
				$_SESSION["error"]["message"][] = "A fatal SQL error occured: ". mysql_error();
			}
			else
			{
				if ($mode == "add")
				{
					$_SESSION["notification"]["message"][] = "Employee successfully created.";
					journal_quickadd_event("staff", $id, "Employee successfully created.");
				}
				else
				{
					$_SESSION["notification"]["message"][] = "Employee successfully adjusted.";
					journal_quickadd_event("staff", $id, "Employee successfully adjusted.");
				}
			}
		}

		// display updated details
		header("Location: ../index.php?page=hr/staff-view.php&id=$id");
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
