<?php
/*
	accounts/ajax/get_time_data.php

	Returns data for the selected time group, provided that the user
	belongs to an accounts or projects group.
*/

require("../../include/config.php");
require("../../include/amberphplib/main.php");
require("../../include/products/inc_products.php");


if (user_permissions_get("timereg_view") || user_permissions_get("accounts_ar_write"))
{
	/*
		TODO: time group items have not yet been moved to a more modern, logical
			object-orientated structure like products.. for now, query directly
			but this will need to be changed in future.
	*/

	// fetch values
	$id			= @security_script_input_predefined("int", $_GET['id']);

	// verify input
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT name_group, description FROM `time_groups` WHERE id='". $id ."'";
	$sql_obj->execute();

	if ($sql_obj->num_rows())
	{
		$sql_obj->fetch_array();

		$data			= array();
		$data["name_group"]	= $sql_obj->data[0]["name_group"];
		$data["description"]	= $sql_obj->data[0]["description"];

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
