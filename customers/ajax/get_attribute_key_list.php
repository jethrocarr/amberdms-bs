<?php 

require("../../include/config.php");
require("../../include/amberphplib/main.php");
	
	if (user_permissions_get('customers_write'))
	{
		$sql_obj		= New sql_query;
		$sql_obj->string	= "SELECT DISTINCT `key` FROM attributes";
		$sql_obj->execute();
	
		if ($sql_obj->num_rows())
		{
			$sql_obj->fetch_array();
			
			foreach ($sql_obj->data as $data_row)
			{
				$values[]	= $data_row["key"];
			}
		}
		
		echo format_arraytocommastring($values, '"');
	}
	exit(0);
?>