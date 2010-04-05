<?php
/*
	customers/service-delete-process.php

	access: customers_write

	Deletes the selected service from the customer's account.
*/

// includes
require("../include/config.php");
require("../include/amberphplib/main.php");

require("../include/customers/inc_customers.php");
require("../include/services/inc_services.php");



if (user_permissions_get('customers_write'))
{
	/*
		Load Data
	*/
	$obj_customer				= New customer_services;
	$obj_customer->id			= @security_form_input_predefined("int", "id_customer", 1, "");
	$obj_customer->id_service_customer	= @security_form_input_predefined("int", "id_service_customer", 1, "");


	// for error handling purposes
	@security_form_input_predefined("any", "delete_confirm", 1, "You must confirm the deletion");



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
		// make sure the service exists for this customer
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
		$_SESSION["error"]["form"]["service_delete"] = "failed";
		header("Location: ../index.php?page=customers/service-delete.php&customerid=". $obj_customer->id ."&serviceid=". $obj_customer->id_service_customer);
		exit(0);
	}
	else
	{
		/*
			Delete Service
		*/
		$obj_customer->service_delete();

		// return to services page
		header("Location: ../index.php?page=customers/services.php&id=". $obj_customer->id );
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
