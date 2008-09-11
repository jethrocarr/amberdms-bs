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

	$projectid			= security_form_input_predefined("int", "projectid", 1, "");
	$phaseid			= security_form_input_predefined("int", "phaseid", 0, "");
	
	$data["name_phase"]		= security_form_input_predefined("any", "name_phase", 1, "You must set a phase name.");
	$data["description"]		= security_form_input_predefined("any", "description", 0, "");



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
			$mysql_string	= "SELECT projectid FROM `project-phases` WHERE id='$phaseid'";
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


	// make sure we don't choose a phase name that has already been used for this project
	// Note: we don't mind if this phase name is the same as other phases in DIFFERENT projects
	$mysql_string	= "SELECT id FROM `project-phases` WHERE name_phase='". $data["name_phase"] ."' AND projectid='$projectid'";
	if ($phaseid)
		$mysql_string .= " AND id!='$phaseid'";
	$mysql_result	= mysql_query($mysql_string);
	$mysql_num_rows	= mysql_num_rows($mysql_result);

	if ($mysql_num_rows)
	{
		$_SESSION["error"]["message"][] = "This phase name is already used in this project - please choose a unique name.";
		$_SESSION["error"]["name_project-error"] = 1;
	}


	/// if there was an error, go back to the entry page
	if ($_SESSION["error"]["message"])
	{	
		header("Location: ../index.php?page=projects/phase-edit.php&projectid=$projectid&phaseid=$phaseid");
		exit(0);
	}
	else
	{
		if ($mode == "add")
		{
			// create a new entry in the DB
			$mysql_string = "INSERT INTO `project-phases` (projectid) VALUES ('$projectid')";
			if (!mysql_query($mysql_string))
			{
				$_SESSION["error"]["message"][] = "A fatal SQL error occured: ". $mysql_error();
			}

			$phaseid = mysql_insert_id();
		}

		if ($phaseid)
		{
			// update phase details
			$mysql_string = "UPDATE `project-phases` SET "
						."name_phase='". $data["name_phase"] ."', "
						."description='". $data["description"] ."' "
						."WHERE id='$phaseid'";
			
			if (!mysql_query($mysql_string))
			{
				$_SESSION["error"]["message"][] = "A fatal SQL error occured: ". mysql_error();
			}
			else
			{
				if ($mode == "add")
				{
					$_SESSION["notification"]["message"][] = "Phase successfully created.";
				}
				else
				{
					$_SESSION["notification"]["message"][] = "Phase successfully updated.";
				}
				
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
