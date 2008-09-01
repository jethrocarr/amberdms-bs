<?php
//
// user/user-permissions-process.php
//
// change the permissions of an existing user.
//

// includes
include_once("../include/database.php");
include_once("../include/user.php");
include_once("../include/security.php");


if (user_permissions_get("admin"))
{
	// check input
	$userid			= security_form_input("/^[0-9]*$/", "userid", 1, "No user ID supplied.");

	// convert all the permissions input
	$permissions = array();
	$mysql_perms_string	= "SELECT * FROM `permissions` ORDER BY value";
	$mysql_perms_result	= mysql_query($mysql_perms_string);

	while ($mysql_perms_data = mysql_fetch_array($mysql_perms_result))
	{
		$permissions[ $mysql_perms_data["value"] ] = security_form_input("/^on$/", $mysql_perms_data["value"], 0, "Form provided invalid input!");
	}

	// reset the request, so we can re-use it.
	mysql_data_seek($mysql_perms_result,0);


	if ($_SESSION["error"]["message"])
	{
		// errors occured
		$_SESSION["error"]["form"] = "permissions";
		header("Location: ../index.php?page=user/user-details.php&id=$userid");
		exit(0);
	}
	else
	{
		$_SESSION["error"] = array();


		// UPDATE THE PERMISSIONS
		// this takes quite a few mysql calls, as we need to remove old permissions and add new ones on a one-by-one basis.

		while ($mysql_perms_data = mysql_fetch_array($mysql_perms_result))
		{
			// check if any current settings exist
			$mysql_user_string	= "SELECT id FROM `users_permissions` WHERE userid='$userid' AND permid='" . $mysql_perms_data["id"] . "'";
			$mysql_user_result	= mysql_query($mysql_user_string);
			$mysql_user_num_rows	= mysql_num_rows($mysql_user_result);

			if ($mysql_user_num_rows)
			{
				// user has this particular permission set

				// if the new setting is "off", delete the current setting.
				if ($permissions[ $mysql_perms_data["value"] ] != "on")
				{
					$mysql_string	= "DELETE FROM `users_permissions` WHERE userid='$userid' AND permid='" . $mysql_perms_data["id"] . "'";
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
					$mysql_string	= "INSERT INTO `users_permissions` (userid, permid) VALUES ('$userid', '" . $mysql_perms_data["id"] . "')";
					$mysql_result = mysql_query($mysql_string);
					if (!$mysql_result)
					{
						die('MySQL Error: ' . mysql_error());
					}
				}

				// if new setting is "off", we don't need todo anything.

			}
			
		} // end of while
		
		
		// goto preferences page
		$_SESSION["error"]["form"]		= "permissions";
		$_SESSION["notification"]["message"]	= "The user's preferences have been updated successfully.";
		header("Location: ../index.php?page=user/user-details.php&id=$userid");
		exit(0);

	} // if valid data input

	
	
} // end of "is user logged in?"
else
{
	// user does not have permissions to access this page.
	error_render_noperms();
}


?>
