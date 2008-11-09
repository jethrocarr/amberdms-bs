<?php
/*
	projects/timebilled-delete-process.php

	access: projects_write

	Deletes an unwanted time group. Note that the locked field will prevent a user
	from deleting a time group that belongs to an invoice.
*/

// includes
include_once("../include/config.php");
include_once("../include/amberphplib/main.php");


if (user_permissions_get('projects_write'))
{
	/////////////////////////

	// basic input
	$projectid			= security_form_input_predefined("int", "projectid", 1, "");
	$groupid			= security_form_input_predefined("int", "groupid", 1, "");
	
	// these exist to make error handling work right
	$data["name_group"]		= security_form_input_predefined("any", "name_group", 0, "");
	$data["name_customer"]		= security_form_input_predefined("any", "name_customer", 0, "");
	$data["code_invoice"]		= security_form_input_predefined("any", "code_invoice", 0, "");
	$data["description"]		= security_form_input_predefined("any", "description", 0, "");

	// confirm deletion
	$data["delete_confirm"]		= security_form_input_predefined("any", "delete_confirm", 1, "You must confirm the deletion");
	



	//// VERIFY PROJECT/TIME GROUP IDS /////////////
	

	// check that the specified project actually exists
	$mysql_string	= "SELECT id FROM `projects` WHERE id='$projectid'";
	$mysql_result	= mysql_query($mysql_string);
	$mysql_num_rows	= mysql_num_rows($mysql_result);

	if (!$mysql_num_rows)
	{
		$_SESSION["error"]["message"][] = "The project you have attempted to edit - $projectid - does not exist in this system.";
	}
	else
	{
		// make sure the time_group exists
		// and check if it's locked or not

		$mysql_string	= "SELECT projectid, locked FROM time_groups WHERE id='$groupid'";
		$mysql_result	= mysql_query($mysql_string);
		$mysql_num_rows	= mysql_num_rows($mysql_result);

		if (!$mysql_num_rows)
		{
			$_SESSION["error"]["message"][] = "The time group you have attempted to edit - $groupid - does not exist in this system.";
		}
		else
		{
			$mysql_data = mysql_fetch_array($mysql_result);

			if ($mysql_data["projectid"] != $projectid)
			{
				$_SESSION["error"]["message"][] = "The requested time group does not match the provided project ID. Potential application bug?";
			}

			if ($mysql_data["locked"])
			{
				$_SESSION["error"]["message"][] = "This time group can not be deleted since it has now been locked.";
			}
		}
	}

		
	//// ERROR CHECKING ///////////////////////


	/// if there was an error, go back to the entry page
	if ($_SESSION["error"]["message"])
	{	
		$_SESSION["error"]["form"]["timebilled_delete"] = "failed";
		header("Location: ../index.php?page=projects/timebilled-delete.php&projectid=$projectid&groupid=$groupid");
		exit(0);
	}
	else
	{
		/*
			Delete Time Group
		*/
			
		$sql_obj		= New sql_query;
		$sql_obj->string	= "DELETE FROM time_groups WHERE id='$groupid'";
			
		if (!$sql_obj->execute())
		{
			$_SESSION["error"]["message"][] = "A fatal SQL error occured whilst trying to delete the time group";
		}
	


		/*
			Unassign time entries from the deleted time_group
		*/

		$sql_obj		= New sql_query;
		$sql_obj->string	= "UPDATE timereg SET billable='0', groupid='0', locked='0' WHERE groupid='$groupid'";
	
		if (!$sql_obj->execute())
		{
			$_SESSION["error"]["message"][] = "A fatal SQL error occured whilst trying to unassign the unwanted time entries.";
		}
	
		
		$_SESSION["notification"]["message"][] = "Time billing group has been removed.";

		// display updated details
		header("Location: ../index.php?page=projects/timebilled.php&id=$projectid");
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
