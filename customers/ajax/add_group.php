<?php 
require("../../include/config.php");
require("../../include/amberphplib/main.php");
	
	if (user_permissions_get('customers_write'))
	{
		$name = @security_script_input_predefined("any",  $_GET['name']);
		
		$sql_obj		= New sql_query;
		$sql_obj->string	= "INSERT INTO attributes_group(group_name) VALUES(\"" .$name. "\")";
		$sql_obj->execute();
		
		$id = $sql_obj->fetch_insert_id();
		
		echo $id;
	}
exit(0);
?>