<?php
/*
	blacklist-enable-process.php
	
	access: admins only

	Allows the admin to enable/disable blacklisting.
*/

include_once("../include/database.php");
include_once("../include/user.php");
include_once("../include/security.php");
include_once("../include/errors.php");
include_once("../include/functions.php");


if (user_permissions_get('admin'))
{
	/////////////////////////
	
	// convert the data given
	$usr_blacklisting	= security_form_input("/^[\S\s]*$/", "usr_blacklisting", 0, "Invalid form input recieved.");

		
	//// ERROR CHECKING ///////////////////////

	if ($usr_blacklisting != "enabled")
	{
		$usr_blacklisting = "disabled";
	}


	// if there was an error, go back to the previous page
	if ($_SESSION["error"]["message"])
	{	
		header("Location: ../index.php?page=user/blacklist.php");
		exit(0);
	}
	else
	{
		// update configuration
		db_update_value('cfg_basics', 'USR_BLACKLISTING', $usr_blacklisting);

		// no need to flag any config changes, this is a live change since it only
		// affects aoconf.

		// if blacklisting was disabled, we should flush the blacklist table
		if ($usr_blacklisting == "disabled")
		{
			$mysql_string = "DELETE FROM `users_blacklist`";
			mysql_query($mysql_string);
		}
		

		// take the user to the message page
		$_SESSION["notification"]["message"] = "Brute force blacklisting was $usr_blacklisting.";
		header("Location: ../index.php?page=user/blacklist.php");
	
		exit(0);
	}

	/////////////////////////
	
}
else
{
	// user does not have perms to view this page/isn't logged on
	error_render_noperms();
}


?>
