<?php 
/*
	projects/ajax/insert_new_project.php

	Inserts a new project.
*/

require("../../include/config.php");
require("../../include/amberphplib/main.php");


if (user_permissions_get('projects_write'))
{
	$name_project		= @security_script_input_predefined("any", $_GET['name_project']);
	
	$code_project		= config_generate_uniqueid("code_project", "SELECT id FROM projects WHERE code_project='VALUE'");
	
	$sql_obj		= New sql_query;
	$sql_obj->string	= "INSERT INTO projects (name_project, code_project) VALUES (\"" . $name_project ."\", \"" .$code_project. "\")";
	$sql_obj->execute();


	$projectid = $sql_obj->fetch_insert_id();

	echo $projectid;
	
	exit(0);
}
?>
