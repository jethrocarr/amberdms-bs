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
	$mysql_perms_string	= "SELECT * FROM `permissions_staff` ORDER BY value";
	$mysql_perms_result	= mysql_query($mysql_perms_string);

	while ($mysql_perms_data = mysql_fetch_array($mysql_perms_result))
	{
		$permissions[ $mysql_perms_data["value"] ] = security_form_input_predefined("any", $mysql_perms_data["value"], 0, "Form provided invalid input!");
	}

	// reset the request, so we can re-use it.
	mysql_data_seek($mysql_perms_result,0);

	

	///// ERROR CHECKING ///////////////////////
	
	// make sure the user actually exists
	$mysql_string		= "SELECT id FROM `users` WHERE id='$id'";
	$mysql_result		= mysql_query($mysql_string);
	$mysql_num_rows		= mysql_num_rows($mysql_result);
	if (!$mysql_num_rows)
	{
		$_SESSION["error"]["message"][] = "The user you have attempted to edit - $id - does not exist in this system.";
	}
	
	// make sure the staff member exists
	$mysql_string		= "SELECT id FROM `staff` WHERE id='$staffid'";
	$mysql_result		= mysql_query($mysql_string);
	$mysql_num_rows		= mysql_num_rows($mysql_result);
	if (!$mysql_num_rows)
	{
		$_SESSION["error"]["message"][] = "The staff member you have attempted to set permission for - $id - does not exist in this system.";
	}



	//// PROCESS DATA ////////////////////////////


	if ($_SESSION["error"]["message"])
	{
		$_SESSION["error"]["form"]["user_permissions_staff"] = "failed";
		header("Location: ../index.php?page=user/user-staffaccess-edit.php&id=$id&staffid=$staffid");
		exit(0);
	}
	else
	{
		$_SESSION["error"] = array();

		/*
			UPDATE THE PERMISSIONS
		
			This takes quite a few mysql calls, as we need to remove old permissions
			and add new ones on a one-by-one basis.

			TODO: This code could be optimised to be a bit more efficent with it's MySQL queries.
		*/

		while ($mysql_perms_data = mysql_fetch_array($mysql_perms_result))
		{
			// check if any current settings exist
			$mysql_user_string	= "SELECT id FROM `users_permissions_staff` WHERE userid='$id' AND staffid='$staffid' AND permid='" . $mysql_perms_data["id"] . "'";
			$mysql_user_result	= mysql_query($mysql_user_string);
			$mysql_user_num_rows	= mysql_num_rows($mysql_user_result);

			if ($mysql_user_num_rows)
			{
				// user has this particular permission set

				// if the new setting is "off", delete the current setting.
				if ($permissions[ $mysql_perms_data["value"] ] != "on")
				{
					$mysql_string	= "DELETE FROM `users_permissions_staff` WHERE userid='$id' AND staffid='$staffid' AND permid='" . $mysql_perms_data["id"] . "'";
					$mysql_result = mysql_query($mysql_string);
					if (!$mysql_result)
					{
						die('MySQL Error: ' . mysql_error());
					}

				}

				// if new setting is "on", we don't need todo anything.

			}
			else
			{	// no current setting exists

				// if the new setting is "on", insert a new setting
				if ($permissions[ $mysql_perms_data["value"] ] == "on")
				{
					$mysql_string	= "INSERT INTO `users_permissions_staff` (userid, staffid, permid) VALUES ('$id', '$staffid', '" . $mysql_perms_data["id"] . "')";
					$mysql_result = mysql_query($mysql_string);
					if (!$mysql_result)
					{
						die('MySQL Error: ' . mysql_error());
					}
				}

				// if new setting is "off", we don't need todo anything.

			}
			
		} // end of while

		// done
		$_SESSION["notification"]["message"][] = "User staff access permissions have been updated, and are active immediately.";

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
