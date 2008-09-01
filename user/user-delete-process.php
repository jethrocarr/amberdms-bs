<?php
//
// user/user-delete-process.php
//
// delete an administrator user account
//

// includes
include_once("../include/database.php");
include_once("../include/user.php");
include_once("../include/security.php");


if (user_permissions_get("admin"))
{
	// check input
	$userid			= security_form_input("/^[0-9]*$/", "userid", 1, "No user ID supplied.");
	$confirm		= security_form_input("/^on$/", "confirm", 1, "Please confirm the deletion");

	if ($_SESSION["error"]["message"])
	{
		// errors occured
		$_SESSION["error"]["form"] = "delete";
		header("Location: ../index.php?page=user/user-details.php&id=$userid");
		exit(0);
	}
	else
	{
		$_SESSION["error"] = array();

		// blow away the user.
		$mysql_string = "DELETE FROM `users` WHERE id='$userid'";
		$mysql_result = mysql_query($mysql_string);
		if (!$mysql_result)
		{
			die('MySQL Error: ' . mysql_error());
		}

		// delete the user's permissions
		$mysql_string = "DELETE FROM `users_permissions` WHERE userid='$userid'";
		$mysql_result = mysql_query($mysql_string);
		if (!$mysql_result)
		{
			die('MySQL Error: ' . mysql_error());
		}

		
		
		// goto user admin page
		$_SESSION["notification"]["message"] = "The administrator has been successfully deleted.";
		header("Location: ../index.php?page=user/users.php");
		exit(0);

	} // if valid data input

	
	
} // end of "is user logged in?"
else
{
	// user does not have permissions to access this page.
	error_render_noperms();
}


?>
