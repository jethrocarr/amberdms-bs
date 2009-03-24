<?php
/*
	projects/delete-process.php

	access: projects_write

	Deletes an unwanted project, provided that there is no time booked to it.
*/

// includes
include_once("../include/config.php");
include_once("../include/amberphplib/main.php");


if (user_permissions_get('projects_write'))
{
	/////////////////////////

	// basic input
	$id				= security_form_input_predefined("int", "id_project", 1, "");
	
	// these exist to make error handling work right
	$data["code_project"]		= security_form_input_predefined("any", "code_project", 0, "");	
	$data["name_project"]		= security_form_input_predefined("any", "name_project", 0, "");
	
	// confirm deletion
	$data["delete_confirm"]		= security_form_input_predefined("any", "delete_confirm", 1, "You must confirm the deletion");
	



	//// VERIFY PROJECT/PHASE IDS /////////////
	
	
	// make sure the project actually exists
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT id FROM `projects` WHERE id='$id' LIMIT 1";
	$sql_obj->execute();

	if (!$sql_obj->num_rows())
	{
		log_write("error", "process", "The project you have attempted to delete - $id - does not exist in this system.");
	}


		
	//// ERROR CHECKING ///////////////////////


	// make sure there is no time booked to this project
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT id FROM project_phases WHERE projectid='$id'";
	$sql_obj->execute();

	if ($sql_obj->num_rows())
	{
		$sql_obj->fetch_array();

		foreach ($sql_obj->data as $phase_data)
		{
			$sql_phase_obj			= New sql_query;
			$sql_phase_obj->string		= "SELECT id FROM timereg WHERE phaseid='". $phase_data["id"] ."' LIMIT 1";
			$sql_phase_obj->execute();

			if ($sql_phase_obj->num_rows())
			{
				$locked = 1;
			}
		}
	}


	if ($locked)
	{
		$_SESSION["error"]["message"][] = "This project has time booked to it, and therefore can not be deleted.";
	}




	/// if there was an error, go back to the entry page
	if ($_SESSION["error"]["message"])
	{	
		$_SESSION["error"]["form"]["project_delete"] = "failed";
		header("Location: ../index.php?page=projects/delete.php&id=$id");
		exit(0);
	}
	else
	{
		/*
			Delete Project
		*/
			
		$sql_obj		= New sql_query;
		$sql_obj->string	= "DELETE FROM projects WHERE id='$id'";
			
		if (!$sql_obj->execute())
		{
			$_SESSION["error"]["message"][] = "A fatal SQL error occured whilst trying to delete the project";
		}
		else
		{		
			$_SESSION["notification"]["message"][] = "Project has been successfully deleted.";
		}



		/*
			Delete Phases
		*/
		
		$sql_obj		= New sql_query;
		$sql_obj->string	= "DELETE FROM project_phases WHERE projectid='$id'";
			
		if (!$sql_obj->execute())
		{
			$_SESSION["error"]["message"][] = "A fatal SQL error occured whilst trying to delete the project phases";
		}



		/*
			Delete Time Groups
		*/
		
		$sql_obj		= New sql_query;
		$sql_obj->string	= "DELETE FROM time_groups WHERE projectid='$id'";
			
		if (!$sql_obj->execute())
		{
			$_SESSION["error"]["message"][] = "A fatal SQL error occured whilst trying to delete time groups belonging to the project";
		}



		/*
			Delete Project Journal
		*/
		journal_delete_entire("projects", $id);

		
		// display updated details
		header("Location: ../index.php?page=projects/projects.php");
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
