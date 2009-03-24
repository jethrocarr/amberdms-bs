<?php
/*
	admin/blacklist-delete-process.php
	
	access: admins only

	Deletes the specified entry from the blacklist.
*/

// includes
include_once("../include/config.php");
include_once("../include/amberphplib/main.php");


if (user_permissions_get('admin'))
{
	/////////////////////////
	
	// convert the data given
	$id = security_script_input("/^[0-9]*$/", $_GET["id"]);
	

	//// ERROR CHECKING ///////////////////////

	if ($id == "error" || !$id)
	{
		$_SESSION["error"]["message"] = array("Invalid blacklist ID input");
	}

	// if there was an error, go back to the previous page
	if ($_SESSION["error"]["message"])
	{	
		header("Location: ../index.php?page=admin/blacklist.php");
		exit(0);
	}
	else
	{
		// remove entry
		$sql_obj		= New sql_query;
		$sql_obj->string	= "DELETE FROM `users_blacklist` WHERE id='$id' LIMIT 1";

		if ($sql_obj->execute())
		{
			$_SESSION["notification"]["message"][] = "Blacklist entry removed successfully.";
		}

		// take the user to the message page
		header("Location: ../index.php?page=admin/blacklist.php");
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
