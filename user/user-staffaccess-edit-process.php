<?php
/*
	user/user-staffaccess-edit-process.php

	Access: admin users only

	Updates access permissions to an employee for a specific user.

	TODO: This code is very simular to the code in the user/user-permissions-process function. It might
	be worthwhile looking at creating functions/classes to generalise handling of these sorts of permissions
	DB structures.
*/


// includes
include_once("../include/config.php");
include_once("../include/amberphplib/main.php");


if (user_permissions_get('admin'))
{
	////// INPUT PROCESSING ////////////////////////

	$id		= security_form_input_predefined("int", "id_user", 1, "");
	$staffid	= security_form_input_predefined("int", "id_staff", 1, "");
	
	
	// convert all the permissions input
	$permissions = array();

	$sql_perms_obj		= New sql_query;
	$sql_perms_obj->string	= "SELECT * FROM `permissions_staff` ORDER BY value";
	$sql_perms_obj->execute();
	$sql_perms_obj->fetch_array();

	for ($sql_perms_obj->data as $data_perms)
	{
		$permissions[ $data_perms["value"] ] = security_form_input_predefined("any", $data_perms["value"], 0, "Form provided invalid input!");
	}

	

	///// ERROR CHECKING ///////////////////////
	
	// make sure the user actually exists
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT id FROM `users` WHERE id='$id' LIMIT 1";
	$sql_obj->execute();
	
	if (!$sql_obj->num_rows())
	{
		log_write("error", "process", "The user you have attempted to edit - $id - does not exist in this system.");
	}
	

	// make sure the staff member exists
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT id FROM `staff` WHERE id='$staffid' LIMIT 1";
	$sql_obj->execute();

	if (!$sql_obj->num_rows())
	{
		log_write("error", "process", "The staff member you have attempted to set permission for - $id - does not exist in this system.");
	}



	//// PROCESS DATA ////////////////////////////


	if (error_check())
	{
		$_SESSION["error"]["form"]["user_permissions_staff"] = "failed";
		header("Location: ../index.php?page=user/user-staffaccess-edit.php&id=$id&staffid=$staffid");
		exit(0);
	}
	else
	{
		error_clear();

		/*
			UPDATE THE PERMISSIONS
		
			This takes quite a few SQL calls, as we need to remove old permissions
			and add new ones on a one-by-one basis.

			TODO: This code could be optimised to be a bit more efficent with it's SQL queries.
		*/

		$sql_obj = New sql_query;
		$sql_obj->trans_begin();

		foreach ($sql_perms_obj->data as $data_perms)
		{
			// check if any current settings exist
			$sql_user_obj		= New sql_query;
			$sql_user_obj->string	= "SELECT id FROM `users_permissions_staff` WHERE userid='$id' AND staffid='$staffid' AND permid='" . $data_perms["id"] . "' LIMIT 1";
			$sql_user_obj->execute();

			if ($sql_user_obj->num_rows())
			{
				// user has this particular permission set

				// if the new setting is "off", delete the current setting.
				if ($permissions[ $data_perms["value"] ] != "on")
				{
					$sql_obj->string	= "DELETE FROM `users_permissions_staff` WHERE userid='$id' AND staffid='$staffid' AND permid='" . $data_perms["id"] . "' LIMIT 1";
					$sql_obj->execute();
				}

				// if new setting is "on", we don't need todo anything.

			}
			else
			{	// no current setting exists

				// if the new setting is "on", insert a new setting
				if ($permissions[ $data_perms["value"] ] == "on")
				{
					$sql_obj->string	= "INSERT INTO `users_permissions_staff` (userid, staffid, permid) VALUES ('$id', '$staffid', '" . $data_perms["id"] . "')";
					$sql_obj->execute();
				}

				// if new setting is "off", we don't need todo anything.

			}
			
		} // end of while


		// update journal
		journal_quickadd_event("users", $id, "Adjusted user's staffaccess rights.");


		// commit
		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "process", "An error occured attempting to update permissions, no changes have been made");
		}
		else
		{
			$sql_obj->trans_commit();

			log_write("notification", "process", "User staff access permissions have been updated, and are active immediately.");
		}

		// goto view page
		header("Location: ../index.php?page=user/user-staffaccess.php&id=$id");
		exit(0);


	} // if valid data input
	
	
} // end of "is user logged in?"
else
{
	// user does not have permissions to access this page.
	error_render_noperms();
	header("Location: ../index.php?page=message.php");
	exit(0);
}


?>
