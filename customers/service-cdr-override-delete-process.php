<?php
/*
	customers/service-cdr-override-delete-process.php

	access:	customers_write

	Deletes an unwanted CDR override for the selected customer
*/

// includes
require("../include/config.php");
require("../include/amberphplib/main.php");

require("../include/customers/inc_customers.php");
require("../include/services/inc_services.php");
require("../include/services/inc_services_cdr.php");



if (user_permissions_get('customers_write'))
{
	/*
		Init
	*/
	$obj_rate_table						= New cdr_rate_table_rates_override;
	$obj_customer						= New customer_services;



	/*
		Load Data
	*/

	$obj_customer->id					= @security_script_input_predefined("int", $_GET["id_customer"]);
	$obj_customer->id_service_customer			= @security_script_input_predefined("int", $_GET["id_service_customer"]);

	$obj_rate_table->id_rate_override			= @security_script_input_predefined("int", $_GET["id_rate_override"]);

	$obj_rate_table->option_type				= "customer";
	$obj_rate_table->option_type_id				= $obj_customer->id_service_customer;



	/*
		Verify Data
	*/

	// check that the specified customer actually exists
	if (!$obj_customer->verify_id())
	{
		log_write("error", "process", "The customer you have attempted to edit - ". $obj_customer->id ." - does not exist in this system.");
	}
	else
	{
		// make sure the service exists and is assigned to the customer
		if (!$obj_customer->verify_id_service_customer())
		{
			log_write("error", "process", "The service you have attempted to edit - ". $obj_customer->id_service_customer ." - does not exist in this system.");
		}
		else
		{
			$obj_customer->load_data();
			$obj_customer->load_data_service();
		}
	}


	/*
		Check for any errors
	*/
	if (error_check())
	{	
		header("Location: ../index.php?page=customers/service-cdr-override.php&id_customer=". $obj_customer->id ."&id_service_customer=". $obj_customer->id_service_customer );
		exit(0);
	}
	else
	{
		/*
			Delete Rate Override
		*/
		$obj_rate_table->action_rate_delete_override();


		/*
			Complete
		*/
		header("Location: ../index.php?page=customers/service-cdr-override.php&id_customer=". $obj_customer->id ."&id_service_customer=". $obj_customer->id_service_customer );
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
