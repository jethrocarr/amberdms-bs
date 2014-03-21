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

	$id				= @security_form_input_predefined("int", "id_project", 0, "");
	
	$data["code_project"]		= @security_form_input_predefined("any", "code_project", 0, "");	
	$data["name_project"]		= @security_form_input_predefined("any", "name_project", 1, "You must set a project name");
	$data["internal_only"]		= @security_form_input_predefined("any", "internal_only", 0, "");
	$data["details"]		= @security_form_input_predefined("any", "details", 0, "");
	
	$data["date_start"]		= @security_form_input_predefined("date", "date_start", 1, "");
	$data["date_end"]		= @security_form_input_predefined("date", "date_end", 0, "");


	// process checkboxes
	if ($data["internal_only"])
	{
		$data["internal_only"] = 1;
	}
	

	// are we editing an existing project or adding a new one?
	if ($id)
	{
		$mode = "edit";

		// make sure the project actually exists
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM `projects` WHERE id='$id' LIMIT 1";
		$sql_obj->execute();

		if (!$sql_obj->num_rows())
		{
			log_write("error", "process", "The project you have attempted to edit - $id - does not exist in this system.");
		}
	}
	else
	{
		$mode = "add";
	}


		
	//// ERROR CHECKING ///////////////////////


	// make sure we don't choose a project name that has already been taken
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT id FROM `projects` WHERE name_project='". $data["name_project"] ."'";

	if ($id)
		$sql_obj->string .= " AND id!='$id'";

	$sql_obj->execute();

	if ($sql_obj->num_rows())
	{
		log_write("error", "process", "This project name is already used for another project - please choose a unique name.");
		$_SESSION["error"]["name_project-error"] = 1;
	}


	// make sure we don't choose a projet code that has already been taken
	if ($data["code_project"])
	{
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT id FROM `projects` WHERE code_project='". $data["code_project"] ."'";

		if ($id)
			$sql_obj->string .= " AND id!='$id'";

		$sql_obj->execute();

		if ($sql_obj->num_rows())
		{
			log_write("error", "process", "This project code is already used for another project - please choose a unique code, or leave it blank to recieve an auto-generated value.");
			$_SESSION["error"]["code_project-error"] = 1;
		}
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
		/*
			Start Transaction
		*/

		$sql_obj = New sql_query;
		$sql_obj->trans_begin();


		// set a default code
		if (!$data["code_project"])
		{
			$data["code_project"] = config_generate_uniqueid("CODE_PROJECT", "SELECT id FROM projects WHERE code_project='VALUE'");
		}
		


		/*
			Create a new project (if required)
		*/
		if ($mode == "add")
		{
			// create a new entry in the DB
			$sql_obj->string	= "INSERT INTO `projects` (name_project) VALUES ('".$data["name_project"]."')";
			$sql_obj->execute();

			$id = $sql_obj->fetch_insert_id();
		}


		/*
			Update project details
		*/
		if ($id)
		{
			// update project details
			$sql_obj->string	= "UPDATE `projects` SET "
							."name_project='". $data["name_project"] ."', "
							."code_project='". $data["code_project"] ."', "
							."date_start='". $data["date_start"] ."', "
							."date_end='". $data["date_end"] ."', "
							."internal_only='". $data["internal_only"] ."', "
							."details='". $data["details"] ."' "
							."WHERE id='$id' LIMIT 1";

			$sql_obj->execute();
		}


		/*
			Update Journal
		*/
		if ($mode == "add")
		{
			journal_quickadd_event("projects", $id, "Project successfully created.");
		}
		else
		{
			journal_quickadd_event("projects", $id, "Project successfully created.");
		}



		/*
			Commit
		*/
		if (error_check())
		{
			$sql_obj->trans_rollback();

			log_write("error", "process", "An error occured whilst attempting to update project. No changes have been made.");

			if ($mode == "add")
			{
				header("Location: ../index.php?page=projects/add.php");
				exit(0);
			}
			else
			{
				header("Location: ../index.php?page=projects/view.php&id=$id");
				exit(0);
			}
		}
		else
		{
			$sql_obj->trans_commit();

			if ($mode == "add")
			{
				log_write("notification", "process", "Project successfully created.");
			}
			else
			{
				log_write("notification", "process", "Project successfully updated.");
			}
		
			header("Location: ../index.php?page=projects/view.php&id=$id");
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
