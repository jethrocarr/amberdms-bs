<?php
/*
	user/user-delete-process.php

	access: admin

	Deletes a user account
*/

// includes
include_once("../include/config.php");
include_once("../include/amberphplib/main.php");


if (user_permissions_get('admin'))
{
	/////////////////////////

	$id				= security_form_input_predefined("int", "id_user", 1, "");

	// these exist to make error handling work right
	$data["username"]		= security_form_input_predefined("any", "username", 0, "");

	// confirm deletion
	$data["delete_confirm"]		= security_form_input_predefined("any", "delete_confirm", 1, "You must confirm the deletion");

	
	// make sure the user actually exists
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT id FROM `users` WHERE id='$id'";
	$sql_obj->execute();
	
	if (!$sql_obj->num_rows())
	{
		$_SESSION["error"]["message"][] = "The user you have attempted to edit - $id - does not exist in this system.";
	}


		
	//// ERROR CHECKING ///////////////////////
			

	/// if there was an error, go back to the entry page
	if ($_SESSION["error"]["message"])
	{	
		$_SESSION["error"]["form"]["user_delete"] = "failed";
		header("Location: ../index.php?page=user/user-delete.php&id=$id");
		exit(0);
	}
	else
	{

		/*
			Delete User
		*/
			
		$sql_obj		= New sql_query;
		$sql_obj->string	= "DELETE FROM users WHERE id='$id'";
			
		if (!$sql_obj->execute())
		{
			$_SESSION["error"]["message"][] = "A fatal SQL error occured whilst trying to delete the user";
		}
		else
		{		
			$_SESSION["notification"]["message"][] = "User has been successfully deleted.";
		}


		/*
			Delete user permissions
			(both access and staff permissions)
		*/
		
		$sql_obj		= New sql_query;
		$sql_obj->string	= "DELETE FROM users_permissions WHERE userid='$id'";
			
		if (!$sql_obj->execute())
		{
			$_SESSION["error"]["message"][] = "A fatal SQL error occured whilst trying to delete the user permissions";
		}
		
		
		$sql_obj		= New sql_query;
		$sql_obj->string	= "DELETE FROM users_permissions_staff WHERE userid='$id'";
			
		if (!$sql_obj->execute())
		{
			$_SESSION["error"]["message"][] = "A fatal SQL error occured whilst trying to delete the user-staff permissions";
		}



		/*
			Delete Journal
		*/
		journal_delete_entire("users", $id);



		// return to user list
		header("Location: ../index.php?page=user/users.php");
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
