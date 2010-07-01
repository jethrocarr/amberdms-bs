<?php 

require("../../config.php");
require("../../amberphplib/main.php");

if (user_permissions_get('timekeeping'))
{
	$name_phase		= @security_script_input_predefined("any", $_GET['name_phase']);
	$projectid		= @security_script_input_predefined("any", $_GET['projectid']);
	
//	$code_project		= config_generate_uniqueid("code_project", "SELECT id FROM projects WHERE code_project='VALUE'");
	
	$sql_obj		= New sql_query;
	$sql_obj->string	= "INSERT INTO project_phases (name_phase, projectid) VALUES (\"" . $name_phase ."\", \"" .$projectid. "\")";
	$sql_obj->execute();

	$phaseid		= sql_get_singlevalue("SELECT id AS value FROM project_phases WHERE name_phase='". $name_phase ."'");
	
	unset($sql_obj);

	echo $phaseid;
	
	exit(0);
}
?>