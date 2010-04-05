<?php
/*
	customers/service-cdr-override-edit-process.php

	access:	customers_write

	Add or edit call rate overrides for customers.
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

	$obj_customer->id					= @security_form_input_predefined("int", "id_customer", 1, "");
	$obj_customer->id_service_customer			= @security_form_input_predefined("int", "id_service_customer", 0, "");

	$obj_rate_table->id_rate_override			= @security_form_input_predefined("int", "id_rate_override", 0, "");

	$obj_rate_table->option_type				= "customer";
	$obj_rate_table->option_type_id				= $obj_customer->id_service_customer;

	$obj_rate_table->data_rate["rate_prefix"]		= @security_form_input_predefined("any", "rate_prefix", 1, "");
	$obj_rate_table->data_rate["rate_description"]		= @security_form_input_predefined("any", "rate_description", 1, "");
	$obj_rate_table->data_rate["rate_price_sale"]		= @security_form_input_predefined("money", "rate_price_sale", 0, "");



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



	// verify the service ID is valid
	if (!$obj_customer->id_service_customer)
	{
		$obj_customer->obj_service->id	= $data["serviceid"];

		if (!$obj_customer->obj_service->verify_id())
		{
			log_write("error", "process", "Unable to find service ". $obj_customer->obj_service->id ."");
		}
		else
		{
			$obj_customer->obj_service->load_data();
		}
	}


	// check the option id values
	if (!$obj_rate_table->verify_id_override())
	{
		// TODO: seriously need a better error message here, this means almost nothing to me and I wrote it....
		log_write("error", "process", "The service and rate ids do not correct match any known override");
	}

	// verify that the prefix is unique
	if (!$obj_rate_table->verify_rate_prefix_override())
	{
		log_write("error", "process", "Another rate override already exists with the supplied prefix - unable to add another one with the same prefix");
		error_flag_field("rate_prefix");
	}



	/*
		Check for any errors
	*/
	if (error_check())
	{	
		$_SESSION["error"]["form"]["cdr_override_edit"] = "failed";
		header("Location: ../index.php?page=customers/service-cdr-override-edit.php&id_customer=". $obj_customer->id ."&id_service_customer=". $obj_customer->id_service_customer ."&id_rate_override=". $obj_rate_table->id_rate_override);
		exit(0);
	}
	else
	{
		/*
			Update/Create Rate Table
		*/
		$obj_rate_table->action_rate_update_override();


		/*
			Complete
		*/
		header("Location: ../index.php?page=customers/service-cdr-override.php&id_customer=". $obj_customer->id ."&id_service_customer=". $obj_customer->id_service_customer ."");
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
