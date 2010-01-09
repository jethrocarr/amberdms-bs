<?php
/*
	projects/phase-edit-process.php

	access: projects_write

	Allows new phases to be added to projects, or existing phases to be modified
*/

// includes
include_once("../include/config.php");
include_once("../include/amberphplib/main.php");


if (user_permissions_get('projects_write'))
{
	/////////////////////////

	$projectid			= @security_form_input_predefined("int", "projectid", 1, "");
	$phaseid			= @security_form_input_predefined("int", "phaseid", 0, "");
	
	$data["name_phase"]		= @security_form_input_predefined("any", "name_phase", 1, "You must set a phase name.");
	$data["description"]		= @security_form_input_predefined("any", "description", 0, "");



	//// VERIFY PROJECT/PHASE IDS /////////////
	

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
		if ($phaseid)
		{
			$mode = "edit";
			
			// are we editing an existing phase? make sure it exists and belongs to this project
			$sql_obj		= New sql_query;
			$sql_obj->string	= "SELECT projectid FROM `project_phases` WHERE id='$phaseid' LIMIT 1";
			$sql_obj->execute();

			if (!$sql_obj->num_rows())
			{
				log_write("error", "process", "The phase you have attempted to edit - $phaseid - does not exist in this system.");
			}
			else
			{
				$sql_obj->fetch_array();

				if ($sql_obj->data[0]["projectid"] != $projectid)
				{
					log_write("error", "process", "The requested phase does not match the provided project ID. Potential application bug?");
				}
			}
		}
		else
		{
			$mode = "add";
		}
	}



		
	//// ERROR CHECKING ///////////////////////


	// make sure we don't choose a phase name that has already been used for this project
	// Note: we don't mind if this phase name is the same as other phases in DIFFERENT projects

	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT id FROM `project_phases` WHERE name_phase='". $data["name_phase"] ."' AND projectid='$projectid' ";
	
	if ($phaseid)
		$sql_obj->string .= " AND id!='$phaseid'";

	$sql_obj->execute();

	if ($sql_obj->num_rows())
	{
		log_write("error", "process", "This phase name is already used in this project - please choose a unique name.");
		$_SESSION["error"]["name_project-error"] = 1;
	}


	/// if there was an error, go back to the entry page
	if ($_SESSION["error"]["message"])
	{	
		$_SESSION["error"]["form"]["phase_view"] = "failed";
		header("Location: ../index.php?page=projects/phase-edit.php&id=$projectid&phaseid=$phaseid");
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
			Create a new phase (if required)
		*/
		if ($mode == "add")
		{
			// create a new entry in the DB
			$sql_obj->string	= "INSERT INTO `project_phases` (projectid) VALUES ('$projectid')";
			$sql_obj->execute();

			$phaseid = $sql_obj->fetch_insert_id();
		}



		/*
			Update Phase Details
		*/
		if ($phaseid)
		{
			$sql_obj->string	= "UPDATE `project_phases` SET "
						."name_phase='". $data["name_phase"] ."', "
						."description='". $data["description"] ."' "
						."WHERE id='$phaseid' LIMIT 1";
		
			$sql_obj->execute();
		}



		/*
			Commit
		*/
		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "process", "A fatal error occuring whilst attempting to update the phase. No changes were made.");
		}
		else
		{
			$sql_obj->trans_commit();

			if ($mode == "add")
			{
				log_write("notification", "project", "Phase successfully created.");
			}
			else
			{
				log_write("notification", "project", "Phase successfully updated.");
			}
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
