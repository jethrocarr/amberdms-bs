<?php
/*
	services/cdr-rates-items-delete-process.php

	access:	services_write 

	Deletes an unwanted rate item from the rates table.
*/

// includes
require("../include/config.php");
require("../include/amberphplib/main.php");

require("../include/services/inc_services.php");
require("../include/services/inc_services_cdr.php");



if (user_permissions_get('services_write'))
{
	/*
		Load Data
	*/
	$obj_rate_table				= New cdr_rate_table_rates;
	$obj_rate_table->id			= @security_script_input_predefined("int", $_GET["id"]);
	$obj_rate_table->id_rate		= @security_script_input_predefined("int", $_GET["id_rate"]);

	// check for prefix
	if (!$obj_rate_table->id_rate)
	{
		$prefix = @security_script_input('/^[0-9]*$/', $_GET["prefix"]);

		if (!empty($prefix))
		{
			$obj_rate_table->id_rate = sql_get_singlevalue("SELECT id as value FROM cdr_rate_tables_values WHERE id_rate_table='". $obj_rate_table->id ."' AND rate_prefix='". $prefix ."' LIMIT 1");
		}
	}


	/*
		Verify Data
	*/


	// verify that the selected CDR rate table exists if one has been supplied.
	if (!$obj_rate_table->verify_id_rate())
	{
		log_write("error", "process", "The CDR rate value you have attempted to edit - ". $obj_rate_table->id_rate ." - does not exist in this system.");
	}



	/*
		Check for any errors
	*/
	if (error_check())
	{	
		header("Location: ../index.php?page=services/cdr-rates-items.php&id=". $obj_rate_table->id );
		exit(0);
	}
	else
	{
		/*
			Delete Rate Item
		*/
		$obj_rate_table->load_data_rate();

		$obj_rate_table->action_rate_delete();


		/*
			Complete
		*/
		header("Location: ../index.php?page=services/cdr-rates-items.php&id=". $obj_rate_table->id );
		exit(0);
			
	}

	/////////////////////////
	
}
else
{
	// user does not have perms to view this page/isn't logged on
	error_render_noperms();
	header("Location: ../index.php?page=message.php");
	exit(0);
}


?>
