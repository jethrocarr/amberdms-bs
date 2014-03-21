<?php
/*
	projects/timebilled-delete-process.php

	access: projects_timegroup

	Deletes an unwanted time group. Note that the locked field will prevent a user
	from deleting a time group that belongs to an invoice.
*/

// includes
include_once("../include/config.php");
include_once("../include/amberphplib/main.php");


if (user_permissions_get('projects_timegroup'))
{
	/////////////////////////

	// basic input
	$projectid			= @security_form_input_predefined("int", "projectid", 1, "");
	$groupid			= @security_form_input_predefined("int", "groupid", 1, "");
	
	// these exist to make error handling work right
	$data["name_group"]		= @security_form_input_predefined("any", "name_group", 0, "");
	$data["name_customer"]		= @security_form_input_predefined("any", "name_customer", 0, "");
	$data["code_invoice"]		= @security_form_input_predefined("any", "code_invoice", 0, "");
	$data["description"]		= @security_form_input_predefined("any", "description", 0, "");

	// confirm deletion
	$data["delete_confirm"]		= @security_form_input_predefined("any", "delete_confirm", 1, "You must confirm the deletion");
	



	//// VERIFY PROJECT/TIME GROUP IDS /////////////
	

	// check that the specified project actually exists
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT id FROM `projects` WHERE id='$projectid' LIMIT 1";
	$sql_obj->execute();

	if (!$sql_obj->num_rows())
	{
		log_write("error", "process", "The project you have attempted to edit - $projectid - does not exist in this system.");
	}
	else
	{
		// make sure the time_group exists
		// and check if it's locked or not

		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT projectid, locked FROM time_groups WHERE id='$groupid' LIMIT 1";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			log_write("error", "process", "The time group you have attempted to edit - $groupid - does not exist in this system.");
		}
		else
		{
			$sql_obj->fetch_array();

			if ($sql_obj->data[0]["projectid"] != $projectid)
			{
				log_write("error", "process", "The requested time group does not match the provided project ID. Potential application bug?");
			}

			if ($sql_obj->data[0]["locked"])
			{
				log_write("error", "process", "This time group can not be deleted since it has now been locked.");
			}
		}
	}


		
	//// ERROR CHECKING ///////////////////////


	/// if there was an error, go back to the entry page
	if (error_check())
	{	
		$_SESSION["error"]["form"]["timebilled_delete"] = "failed";
		header("Location: ../index.php?page=projects/timebilled-delete.php&id=$projectid&groupid=$groupid");
		exit(0);
	}
	else
	{
		/*
			Start Transaction
		*/

		$sql_obj = New sql_query;
		$sql_obj->trans_begin();


		/*
			Delete Time Group
		*/
			
		$sql_obj->string	= "DELETE FROM time_groups WHERE id='$groupid' LIMIT 1";
		$sql_obj->execute();


		/*
			Unassign time entries from the deleted time_group
		*/

		$sql_obj->string	= "UPDATE timereg SET billable='0', groupid='0', locked='0' WHERE groupid='$groupid'";
		$sql_obj->execute();



		/*
			Commit
		*/

		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "process", "An error occured whilst attempting to delete the time group. No changes were made.");
		
			$_SESSION["error"]["form"]["timebilled_delete"] = "failed";
			header("Location: ../index.php?page=projects/timebilled-delete.php&id=$projectid&groupid=$groupid");
			exit(0);
		}
		else
		{
			$sql_obj->trans_commit();
		
			log_write("notification", "process", "Time billing group has been removed.");

			header("Location: ../index.php?page=projects/timebilled.php&id=$projectid");
			exit(0);
		}
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
