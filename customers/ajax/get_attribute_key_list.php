<?php 
/*
	customers/ajax/get_attribute_key_list.php
	
	access: customers_write

	Get list of key names from database for autocomplete
*/

require("../../include/config.php");
require("../../include/amberphplib/main.php");
	
if (user_permissions_get('customers_write'))
{
	//get keys from database
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT DISTINCT `key` FROM attributes";
	$sql_obj->execute();

	//if any keys are returned, add them to an array
	if ($sql_obj->num_rows())
	{
		$sql_obj->fetch_array();
		
		foreach ($sql_obj->data as $data_row)
		{
			$values[]	= $data_row["key"];
		}
	}
	
	//change array to a string
	echo format_arraytocommastring($values, '"');
}
exit(0);
?>