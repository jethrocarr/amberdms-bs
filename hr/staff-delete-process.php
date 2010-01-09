<?php
/*
	hr/staff-delete-process.php

	access: staff_write

	Deletes a employee provided that the employee has not been added to invoices or time bookings.
*/

// includes
include_once("../include/config.php");
include_once("../include/amberphplib/main.php");

// custom includes
include_once("../include/hr/inc_staff.php");


if (user_permissions_get('staff_write'))
{
	// prepare object
	$obj_employee		= New hr_staff;


	/*
		Load POST data
	*/

	$obj_employee->id		= @security_form_input_predefined("int", "id_staff", 1, "");


	// these exist to make error handling work right
	$data["name_staff"]		= @security_form_input_predefined("any", "name_staff", 0, "");

	// confirm deletion
	$data["delete_confirm"]		= @security_form_input_predefined("any", "delete_confirm", 1, "You must confirm the deletion");

	

	/*
		Error Handling
	*/

	// make sure the employee actually exists
	if (!$obj_employee->verify_id())
	{
		log_write("error", "staff-edit-process", "The employee you have attempted to delete - ". $obj_employee->id ." - does not exist in this system.");
	}



	// make sure employee is not locked
	if ($obj_employee->check_lock())
	{
		log_write("error", "staff-delete-process", "You are not able to delete this employee because they have made postings to the billing system.");
	}


	// return to entry page in event of an error
	if ($_SESSION["error"]["message"])
	{	
		$_SESSION["error"]["form"]["staff_delete"] = "failed";
		header("Location: ../index.php?page=hr/staff-delete.php&id=". $obj_employee->id ."");
		exit(0);
	}
	else
	{

		/*
			Delete Employee
		*/

		$obj_employee->action_delete();


		// return to products list
		header("Location: ../index.php?page=hr/staff.php");
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
