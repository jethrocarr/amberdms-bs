<?php
/*
	accounts/ajax/get_time_data.php

	Returns data for the selected time group, provided that the user
	belongs to an accounts or projects group.
*/

require("../../include/config.php");
require("../../include/amberphplib/main.php");
require("../../include/products/inc_products.php");


if (user_permissions_get("accounts_import_statement"))
{
	/*
		TODO: time group items have not yet been moved to a more modern, logical
			object-orientated structure like products.. for now, query directly
			but this will need to be changed in future.
	*/

	// fetch values
	$action	= @security_script_input_predefined("any", $_GET['action']);
	$id	= @security_script_input_predefined("int", $_GET['id']);

	
	// verify input
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT id, name, description FROM `input_structures` WHERE id='". $id ."' AND type_input IN ('bank_statement') LIMIT 1";
	$sql_obj->execute();
	$sql_obj->fetch_array();
	$input_structure_data = $sql_obj->data[0];
	
	
	if ($sql_obj->num_rows())
	{
		$data			= array();
		$data["id"]	= $input_structure_data["id"];
		$data["name"]	= $input_structure_data["name"];
		$data["description"]	= $input_structure_data["description"];
		
		switch($action)
		{
			case 'delete':
				
				$sql_obj->trans_begin();
				$sql_obj->string	= "DELETE FROM `input_structures` WHERE `id` = '$id' LIMIT 1";
				$sql_obj->execute();
				
				$sql_obj->string	= "DELETE FROM `input_structure_items` WHERE `id_structure` = '$id'";
				$sql_obj->execute();
				
				if (error_check())
				{
					$sql_obj->trans_rollback();
					$data["success-state"]	= false;
				}
				else
				{
					$sql_obj->trans_commit();
					$data["success-state"]	= true;
				}
			break;
			
			case 'get-data':
			default:
				
				$sql_obj->string = "SELECT field_src, field_dest FROM `input_structure_items` WHERE id_structure='". $id ."'";
				$sql_obj->execute();
				$sql_obj->fetch_array();
				
				$input_structure_items = $sql_obj->data;
		
				$data['items'] = $input_structure_items;
			break;
		}

		echo json_encode($data);
	}
	else
	{
		log_write("error", "message", "Unable to load time group data for id $id");
		die("fatal error");
	}

	exit(0);
}

?>
