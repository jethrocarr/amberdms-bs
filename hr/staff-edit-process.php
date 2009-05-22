<?php
/*
	staff/edit-process.php

	access: staff_write

	Allows existing staff to be adjusted, or new staff to be added.
*/

// includes
include_once("../include/config.php");
include_once("../include/amberphplib/main.php");

// custom includes
include_once("../include/hr/inc_staff.php");


if (user_permissions_get('staff_write'))
{
	// create object
	$obj_employee	= New hr_staff;


	/*
		Load POST data
	*/

	$obj_employee->id				= security_form_input_predefined("int", "id_staff", 0, "");
	
	$obj_employee->data["name_staff"]		= security_form_input_predefined("any", "name_staff", 1, "");
	$obj_employee->data["staff_code"]		= security_form_input_predefined("any", "staff_code", 0, "");
	$obj_employee->data["staff_position"]		= security_form_input_predefined("any", "staff_position", 0, "");
	
	$obj_employee->data["contact_phone"]		= security_form_input_predefined("any", "contact_phone", 0, "");
	$obj_employee->data["contact_fax"]		= security_form_input_predefined("any", "contact_fax", 0, "");
	$obj_employee->data["contact_email"]		= security_form_input_predefined("email", "contact_email", 0, "");
	
	$obj_employee->data["date_start"]		= security_form_input_predefined("date", "date_start", 1, "");
	$obj_employee->data["date_end"]			= security_form_input_predefined("date", "date_end", 0, "");

	

	/*
		Error Handling
	*/

	// verify employee ID
	if ($obj_employee->id)
	{
		if (!$obj_employee->verify_id())
		{
			log_write("error", "staff-edit-process", "The employee you have attempted to edit - ". $obj_employee->id ." - does not exist in this system.");
		}
	}

	// make sure we don't choose a staff name that has already been taken
	if (!$obj_employee->verify_name_staff())
	{
		log_write("error", "staff-edit-process", "Another staff member already has this name - please choose a unique name.");
		$_SESSION["error"]["name_staff-error"] = 1;
	}

	if ($obj_employee->data["staff_code"])
	{
		if (!$obj_employee->verify_code_staff())
		{
			log_write("error", "staff-edit-process", "Another staff member already has this code - please choose a unique code or leave blank for a default.");
			$_SESSION["error"]["staff_code-error"] = 1;
		}
	}


	// return to input page in event of any errors
	if ($_SESSION["error"]["message"])
	{
		if ($obj_employee->id)
		{
			header("Location: ../index.php?page=hr/staff-view.php&id=". $obj_employee->id);
			exit(0);
		}
		else
		{
			header("Location: ../index.php?page=hr/staff-add.php");
			exit(0);
		}
	}



	/*
		Process Data
	*/

	// create/update employee information
	$obj_employee->action_update();

	// display updated details
	header("Location: ../index.php?page=hr/staff-view.php&id=". $obj_employee->id);
	exit(0);

}
else
{
	// user does not have perms to view this page/isn't logged on
	error_render_noperms();
	header("Location: ../index.php?page=message.php");
	exit(0);
}


?>
