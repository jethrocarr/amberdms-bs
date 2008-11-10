<?php
/*
	hr/staff-delete-process.php

	access: staff_write

	Deletes a employee provided that the employee has not been added to invoices or time bookings.
*/

// includes
include_once("../include/config.php");
include_once("../include/amberphplib/main.php");


if (user_permissions_get('staff_write'))
{
	/////////////////////////

	$id				= security_form_input_predefined("int", "id_staff", 1, "");

	// these exist to make error handling work right
	$data["name_staff"]		= security_form_input_predefined("any", "name_staff", 0, "");

	// confirm deletion
	$data["delete_confirm"]		= security_form_input_predefined("any", "delete_confirm", 1, "You must confirm the deletion");

	
	// make sure the employee actually exists
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT id FROM `staff` WHERE id='$id'";
	$sql_obj->execute();
	
	if (!$sql_obj->num_rows())
	{
		$_SESSION["error"]["message"][] = "The employee you have attempted to edit - $id - does not exist in this system.";
	}


		
	//// ERROR CHECKING ///////////////////////

	$locked = 0;
	
	// make sure employee does not belong to any AR invoices
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT id FROM account_ar WHERE employeeid='$id'";
	$sql_obj->execute();

	if ($sql_obj->num_rows())
	{
		$locked = 1;
	}

	// make sure employee does not belong to any AP invoices
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT id FROM account_ap WHERE employeeid='$id'";
	$sql_obj->execute();

	if ($sql_obj->num_rows())
	{
		$locked = 1;
	}


	// make sure employee has no time booked
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT id FROM timereg WHERE employeeid='$id'";
	$sql_obj->execute();

	if ($sql_obj->num_rows())
	{
		$locked = 1;
	}


	if ($locked)
	{
		$_SESSION["error"]["message"][] = "You are not able to delete this employee because it has been added to an invoice or has time booked.";
	}


	

	/// if there was an error, go back to the entry page
	if ($_SESSION["error"]["message"])
	{	
		$_SESSION["error"]["form"]["staff_delete"] = "failed";
		header("Location: ../index.php?page=hr/staff-delete.php&id=$id");
		exit(0);
		
	}
	else
	{

		/*
			Delete Employee
		*/
			
		$sql_obj		= New sql_query;
		$sql_obj->string	= "DELETE FROM staff WHERE id='$id'";
			
		if (!$sql_obj->execute())
		{
			$_SESSION["error"]["message"][] = "A fatal SQL error occured whilst trying to delete the employee";
		}
		else
		{		
			$_SESSION["notification"]["message"][] = "Employee has been successfully deleted.";
		}


		/*
			Delete User <-> Employee permissions mappings
		*/

		$sql_obj		= New sql_query;
		$sql_obj->string	= "DELETE FROM users_permissions_staff WHERE staffid='$id'";
			
		if (!$sql_obj->execute())
		{
			$_SESSION["error"]["message"][] = "A fatal SQL error occured whilst trying to delete the user-employee permissions mappings";
		}



		/*
			Delete Journal
		*/
		journal_delete_entire("staff", $id);



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
