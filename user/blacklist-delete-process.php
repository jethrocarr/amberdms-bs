<?php
/*
	blacklist-delete-process.php
	
	access: admins only

	Deletes the specified entry from the blacklist.
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
	$id	= security_script_input("/^[0-9]*$/", $_GET["id"]);
		
	//// ERROR CHECKING ///////////////////////

	if ($id == "error")
	{
		$_SESSION["error"]["message"] = "Invalid blacklist ID provided.";
	}

	// if there was an error, go back to the previous page
	if ($_SESSION["error"]["message"])
	{	
		header("Location: ../index.php?page=user/blacklist.php");
		exit(0);
	}
	else
	{
		// remove entry
		$mysql_string = "DELETE FROM `users_blacklist` WHERE id='$id'";
		if (!mysql_query($mysql_string))
		{
			die('MySQL Error: ' . mysql_error());
		}
		
		
		// take the user to the message page
		$_SESSION["notification"]["message"] = "Blacklist entry removed successfully.";
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
