<?php
/*
	projects/phase-delete-process.php

	access: projects_write

	Deletes an unwanted phase, provided that there is no time booked to it.
*/

// includes
include_once("../include/config.php");
include_once("../include/amberphplib/main.php");


if (user_permissions_get('projects_write'))
{
	/////////////////////////

	// basic input
	$projectid			= security_form_input_predefined("int", "projectid", 1, "");
	$phaseid			= security_form_input_predefined("int", "phaseid", 1, "");
	
	// these exist to make error handling work right
	$data["name_phase"]		= security_form_input_predefined("any", "name_phase", 0, "");
	$data["description"]		= security_form_input_predefined("any", "description", 0, "");

	// confirm deletion
	$data["delete_confirm"]		= security_form_input_predefined("any", "delete_confirm", 1, "You must confirm the deletion");
	



	//// VERIFY PROJECT/PHASE IDS /////////////
	

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
		if ($phaseid)
		{
			$mode = "edit";
			
			// are we editing an existing phase? make sure it exists and belongs to this project
			$mysql_string	= "SELECT projectid FROM `project_phases` WHERE id='$phaseid'";
			$mysql_result	= mysql_query($mysql_string);
			$mysql_num_rows	= mysql_num_rows($mysql_result);

			if (!$mysql_num_rows)
			{
				$_SESSION["error"]["message"][] = "The phase you have attempted to edit - $phaseid - does not exist in this system.";
			}
			else
			{
				$mysql_data = mysql_fetch_array($mysql_result);

				if ($mysql_data["projectid"] != $projectid)
				{
					$_SESSION["error"]["message"][] = "The requested phase does not match the provided project ID. Potential application bug?";
				}
				
			}
		}
		else
		{
			$mode = "add";
		}
	}


		
	//// ERROR CHECKING ///////////////////////


	// make sure there is no time booked to this phase
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT id FROM timereg WHERE phaseid='$phaseid'";
	$sql_obj->execute();

	if ($sql_obj->num_rows())
	{
		$_SESSION["error"]["message"][] = "There has been time booked to this phase, therefore this phase can not be deleted.";
	}




	/// if there was an error, go back to the entry page
	if ($_SESSION["error"]["message"])
	{	
		$_SESSION["error"]["form"]["phase_delete"] = "failed";
		header("Location: ../index.php?page=projects/phase-delete.php&projectid=$projectid&phaseid=$phaseid");
		exit(0);
	}
	else
	{
		/*
			Delete Phase
		*/
			
		$sql_obj		= New sql_query;
		$sql_obj->string	= "DELETE FROM project_phases WHERE id='$phaseid'";
			
		if (!$sql_obj->execute())
		{
			$_SESSION["error"]["message"][] = "A fatal SQL error occured whilst trying to delete the phase";
		}
		else
		{		
			$_SESSION["notification"]["message"][] = "Phase has been successfully deleted.";
		}

		// display updated details
		header("Location: ../index.php?page=projects/phases.php&id=$projectid");
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
