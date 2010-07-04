<?php 
/*
	projects/ajax/insert_new_phase.php

	Insert a new phase into an existing project.
*/

require("../../include/config.php");
require("../../include/amberphplib/main.php");


if (user_permissions_get('projects_write'))
{
	$name_phase		= @security_script_input_predefined("any", $_GET['name_phase']);
	$projectid		= @security_script_input_predefined("int", $_GET['projectid']);


	// make sure the project actually exists
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT id FROM `projects` WHERE id='$projectid' LIMIT 1";
	$sql_obj->execute();

	if (!$sql_obj->num_rows())
	{
		log_write("error", "process", "The project you have attempted to edit - $id - does not exist in this system.");
		exit(0);
	}


	// insert the new phase
	$sql_obj		= New sql_query;
	$sql_obj->string	= "INSERT INTO project_phases (name_phase, projectid) VALUES (\"" . $name_phase ."\", \"" .$projectid. "\")";
	$sql_obj->execute();

	$phase_id = $sql_obj->fetch_insert_id();

	echo $phaseid;
	
	exit(0);
}
else
{
	error_render_noperms();
}


?>
