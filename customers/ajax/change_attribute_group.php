<?php 
/*
	customers/ajax/change_attribute_group.php
	
	access: customers_write

	Changes the group id associated with an attribute
*/

require("../../include/config.php");
require("../../include/amberphplib/main.php");
	
if (user_permissions_get('customers_write'))
{
	//get data
	$attr_id = @security_script_input_predefined("int",  $_GET['attr_id']);
	$group_id = @security_script_input_predefined("int",  $_GET['group_id']);
	
	//change group id in database
	$sql_obj		= New sql_query;
	$sql_obj->string	= "UPDATE attributes SET id_group = " .$group_id. " WHERE id = " .$attr_id;
	$sql_obj->execute();
}

exit(0);
?>