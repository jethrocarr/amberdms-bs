<?php 
/*
	timekeeping/ajax/populate_projects_dropdown.php

	Updates the projects dropdown - called after we AJAX add projects.
*/

require("../../include/config.php");
require("../../include/amberphplib/main.php");


if (user_permissions_get('timekeeping'))
{
	$selected_project	= @security_script_input_predefined("int", $_GET['selected_project']);

	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT id, code_project, name_project FROM projects ORDER BY name_project";
	$sql_obj->execute();
	
	if ($sql_obj->num_rows())
	{
		$sql_obj->fetch_array();

		foreach ($sql_obj->data as $data_row)
		{
			$option_string	.= "<option value=\"" .$data_row['id']. "\"";
				if ($data_row['id'] == $selected_project)
				{	
					$option_string	.= " selected=\"selected\"";
				}
			$option_string	.= ">" .$data_row['code_project']. " -- " .$data_row['name_project']. "</option>";
		}
	}
	else
	{
		$option_string .= "<option value=\"\"> -- no projects found -- </option>";
	}
	
	unset($sql_obj);

	echo $option_string . $selected_project;
	
	exit(0);
}

?>
