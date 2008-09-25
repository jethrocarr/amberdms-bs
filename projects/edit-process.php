<?php
/*
	projects/edit-process.php

	access: projects_write

	Allows existing projects to be adjusted, or new projects to be added.
*/

// includes
include_once("../include/config.php");
include_once("../include/amberphplib/main.php");


if (user_permissions_get('projects_write'))
{
	/////////////////////////

	$id				= security_form_input_predefined("int", "id_project", 0, "");
	
	$data["code_project"]		= security_form_input_predefined("any", "code_project", 0, "");	
	$data["name_project"]		= security_form_input_predefined("any", "name_project", 1, "You must set a project name");
	$data["details"]		= security_form_input_predefined("any", "details", 0, "");
	
	$data["date_start"]		= security_form_input_predefined("date", "date_start", 0, "");
	$data["date_end"]		= security_form_input_predefined("date", "date_end", 0, "");
	
	

	// are we editing an existing project or adding a new one?
	if ($id)
	{
		$mode = "edit";

		// make sure the project actually exists
		$mysql_string		= "SELECT id FROM `projects` WHERE id='$id'";
		$mysql_result		= mysql_query($mysql_string);
		$mysql_num_rows		= mysql_num_rows($mysql_result);

		if (!$mysql_num_rows)
		{
			$_SESSION["error"]["message"][] = "The project you have attempted to edit - $id - does not exist in this system.";
		}
	}
	else
	{
		$mode = "add";
	}


		
	//// ERROR CHECKING ///////////////////////


	// make sure we don't choose a project name that has already been taken
	$mysql_string	= "SELECT id FROM `projects` WHERE name_project='". $data["name_project"] ."'";
	if ($id)
		$mysql_string .= " AND id!='$id'";
	$mysql_result	= mysql_query($mysql_string);
	$mysql_num_rows	= mysql_num_rows($mysql_result);

	if ($mysql_num_rows)
	{
		$_SESSION["error"]["message"][] = "This project name is already used for another project - please choose a unique name.";
		$_SESSION["error"]["name_project-error"] = 1;
	}


	/// if there was an error, go back to the entry page
	if ($_SESSION["error"]["message"])
	{	
		if ($mode == "edit")
		{
			$_SESSION["error"]["form"]["project_view"] = "failed";
			header("Location: ../index.php?page=projects/view.php&id=$id");
			exit(0);
		}
		else
		{
			$_SESSION["error"]["form"]["project_add"] = "failed";
			header("Location: ../index.php?page=projects/add.php");
			exit(0);
		}
	}
	else
	{
		if ($mode == "add")
		{
			// create a new entry in the DB
			$mysql_string = "INSERT INTO `projects` (name_project) VALUES ('".$data["name_project"]."')";
			if (!mysql_query($mysql_string))
			{
				$_SESSION["error"]["message"][] = "A fatal SQL error occured: ". $mysql_error();
			}

			$id = mysql_insert_id();
		}

		if ($id)
		{
			// update project details
			$mysql_string = "UPDATE `projects` SET "
						."name_project='". $data["name_project"] ."', "
						."code_project='". $data["code_project"] ."', "
						."date_start='". $data["date_start"] ."', "
						."date_end='". $data["date_end"] ."', "
						."details='". $data["details"] ."' "
						."WHERE id='$id'";
						
			if (!mysql_query($mysql_string))
			{
				$_SESSION["error"]["message"][] = "A fatal SQL error occured: ". mysql_error();
			}
			else
			{
				if ($mode == "add")
				{
					$_SESSION["notification"]["message"][] = "Project successfully created.";
					journal_quickadd_event("projects", $id, "Project successfully created.");
				}
				else
				{
					$_SESSION["notification"]["message"][] = "Project successfully updated.";
					journal_quickadd_event("projects", $id, "Project successfully created.");
				}
				
			}
		}

		// display updated details
		header("Location: ../index.php?page=projects/view.php&id=$id");
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
