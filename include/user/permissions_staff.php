<?php
/*
	includes/user/permissions_staff.php

	This file provides various functions for verifying access permissions for user <-> staff access rights.
*/



/*
	user_permissions_staff_get($type, $staffid)

	This function looks up the database to see if the user has the specified permission
	in their access rights configuration for the requested employee.

	If the user has the correct permissions for this employee, the function will return 1,
	otherwise the function will return 0.
*/
function user_permissions_staff_get($type, $staffid)
{
	log_debug("user/permissions_staff", "Executing user_permissions_staff_get($type, $staffid)");

	// get ID of permissions record
	$sql_query		= New sql_query;
	$sql_query->string	= "SELECT id FROM permissions_staff WHERE value='$type'";
	$sql_query->execute();
	
	if ($sql_query->num_rows())
	{
		$sql_query->fetch_array();
		$permid	= $sql_query->data[0]["id"];

		// check if the user has this permission for this staff member
		$user_perms		= New sql_query;
		$user_perms->string	= "SELECT id FROM users_permissions_staff WHERE userid='". $_SESSION["user"]["id"] ."' AND staffid='$staffid' AND permid='$permid'";
		$user_perms->execute();

		if ($user_perms->num_rows())
		{
			// user has permissions
			return 1;
		}
	}
	
	return 0;
}



