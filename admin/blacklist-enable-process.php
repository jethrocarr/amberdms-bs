<?php
/*
	admin/blacklist-enable-process.php
	
	access: admins only

	Allows the admin to enable/disable blacklisting.
*/


// includes
include_once("../include/config.php");
include_once("../include/amberphplib/main.php");



if (user_permissions_get('admin'))
{
	/////////////////////////
	
	// convert the data given
	$data["blacklist_enable"]	= @security_form_input_predefined("any", "blacklist_enable", 0, "");
	$data["blacklist_limit"]	= @security_form_input_predefined("int", "blacklist_limit", 1, "");
	
	
		
	//// ERROR CHECKING ///////////////////////

	if ($data["blacklist_enable"] == "on")
	{
		$data["blacklist_enable"] = "enabled";
	}
	else
	{
		$data["blacklist_enable"] = "disabled";
	}


	// if there was an error, go back to the previous page
	if ($_SESSION["error"]["message"])
	{	
		$_SESSION["error"]["form"]["blacklist_control"] = "failed";

		header("Location: ../index.php?page=admin/blacklist.php");
		exit(0);
	}
	else
	{
		// enable/disable blacklisting
		$sql_obj		= New sql_query;
		$sql_obj->string	= "UPDATE config SET value='". $data["blacklist_enable"] ."' WHERE name='BLACKLIST_ENABLE' LIMIT 1";
		$sql_obj->execute();

		// update limit value
		$sql_obj		= New sql_query;
		$sql_obj->string	= "UPDATE config SET value='". $data["blacklist_limit"] ."' WHERE name='BLACKLIST_LIMIT' LIMIT 1";
		$sql_obj->execute();

		// if blacklisting was disabled, we should flush the blacklist table
		if ($data["blacklist_enable"] == "disabled")
		{
			$sql_obj		= New sql_query;
			$sql_obj->string	= "DELETE FROM `users_blacklist`";
			$sql_obj->execute();
		}

		// take the user to the message page
		$_SESSION["notification"]["message"][] = "Blacklisting configuration changes applied.";
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
