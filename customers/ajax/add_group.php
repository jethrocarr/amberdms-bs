<?php 
/*
	customers/ajax/add_group.php
	
	access: customers_write

	Creates a new group entry in the database
*/
require("../../include/config.php");
require("../../include/amberphplib/main.php");
	
if (user_permissions_get('customers_write'))
{
	//get data
	$name = @security_script_input_predefined("any",  $_GET['name']);
	
	//add group to database
	$sql_obj		= New sql_query;
	$sql_obj->string	= "INSERT INTO attributes_group(group_name) VALUES(\"" .$name. "\")";
	$sql_obj->execute();
	
	//retrieve ID of new group
	$id = $sql_obj->fetch_insert_id();
	
	echo $id;
}
exit(0);
?>