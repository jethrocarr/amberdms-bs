<?php 
/*
	customers/ajax/change_group_name.php
	
	access: customers_write

	Changes the name of a group in the database
*/

require("../../include/config.php");
require("../../include/amberphplib/main.php");

if (user_permissions_get('customers_write'))
{
	//get data
	$id = @security_script_input_predefined("any",  $_GET['id']);
	$name = @security_script_input_predefined("any",  $_GET['name']);
	
	//Update name
	$sql_obj		= New sql_query;
	$sql_obj->string	= "UPDATE attributes_group SET group_name = \"" .$name. "\" WHERE id =" .$id;
	$sql_obj->execute();
}

exit(0);
?>