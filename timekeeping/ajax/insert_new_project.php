<?php 

require("../../config.php");
require("../../amberphplib/main.php");

if (user_permissions_get('timekeeping'))
{
	$name_project		= @security_script_input_predefined("any", $_GET['name_project']);
	
	$code_project		= config_generate_uniqueid("code_project", "SELECT id FROM projects WHERE code_project='VALUE'");
	
	$sql_obj		= New sql_query;
	$sql_obj->string	= "INSERT INTO projects (name_project, code_project) VALUES (\"" . $name_project ."\", \"" .$code_project. "\")";
	$sql_obj->execute();

	$projectid		= sql_get_singlevalue("SELECT id AS value FROM projects WHERE name_project='". $name_project ."'");
	
	unset($sql_obj);

	echo $projectid;
	
	exit(0);
}
?>