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
	$data["blacklist_enable"]	= security_form_input_predefined("any", "blacklist_enable", 0, "");
	$data["blacklist_limit"]	= security_form_input_predefined("int", "blacklist_limit", 1, "");
	
	
		
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
		$mysql_string = "UPDATE config SET value='". $data["blacklist_enable"] ."' WHERE name='BLACKLIST_ENABLE'";
		if (!mysql_query($mysql_string))
		{
			$_SESSION["error"]["message"][] = "A fatal SQL error occured: ". mysql_error();
		}

		// update limit value
		$mysql_string = "UPDATE config SET value='". $data["blacklist_limit"] ."' WHERE name='BLACKLIST_LIMIT'";
		if (!mysql_query($mysql_string))
		{
			$_SESSION["error"]["message"][] = "A fatal SQL error occured: ". mysql_error();
		}


		// if blacklisting was disabled, we should flush the blacklist table
		if ($data["blacklist_enable"] == "disabled")
		{
			$mysql_string = "DELETE FROM `users_blacklist`";
			mysql_query($mysql_string);
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
