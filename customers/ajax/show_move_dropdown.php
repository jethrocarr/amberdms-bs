<?php 
	require("../../include/config.php");
	require("../../include/amberphplib/main.php");
	
	if (user_permissions_get('customers_write'))
	{
		$attr_id = @security_script_input_predefined("int",  $_GET['id']);
		$group_id = @security_script_input_predefined("int",  $_GET['group_id']);
		
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT * FROM attributes_group WHERE id != " .$group_id;
		$sql_obj->execute();
		
		$html_string = "";
		
		if ($sql_obj->num_rows())
		{
			$html_string .= "<select id=\"select_group_attr_" .$attr_id. "\"><option value=\"\">-- select --</option>";
			$sql_obj->fetch_array();
			foreach ($sql_obj->data as $data_row)
			{
				$html_string .= "<option value=\"" .$data_row['id']. "\">" .$data_row['group_name']. "</option>";
			}
			$html_string .= "</select>";
			
			echo $html_string;
		}
		else
		{
			echo "no groups";
		}
	}
	
	exit(0);
?>