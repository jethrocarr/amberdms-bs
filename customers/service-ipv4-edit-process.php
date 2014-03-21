<?php
/*
	customers/service-ipv4-edit-process.php

	access:	customers_write

	Apply changed to an IPv4 address
*/

// includes
require("../include/config.php");
require("../include/amberphplib/main.php");

require("../include/customers/inc_customers.php");
require("../include/services/inc_services.php");
require("../include/services/inc_services_traffic.php");



if (user_permissions_get('customers_write'))
{
	/*
		Init
	*/

	$obj_customer						= New customer_services;
	$obj_ipv4						= New traffic_customer_service_ipv4;



	/*
		Load Data
	*/

	$obj_customer->id					= @security_form_input_predefined("int", "id_customer", 1, "");
	$obj_customer->id_service_customer			= @security_form_input_predefined("int", "id_service_customer", 0, "");

	$obj_ipv4->id						= @security_form_input_predefined("int", "id_ipv4", 0, "");
	$obj_ipv4->id_customer					= $obj_customer->id;
	$obj_ipv4->id_service_customer				= $obj_customer->id_service_customer;

	$obj_ipv4->data["ipv4_address"]				= @security_form_input_predefined("ipv4", "ipv4_address", 1, "");
	$obj_ipv4->data["ipv4_cidr"]				= @security_form_input_predefined("int", "ipv4_cidr", 1, "");
	$obj_ipv4->data["description"]				= @security_form_input_predefined("any", "description", 0, "");



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



	// verify that this is a data_traffic server
	if ($obj_customer->obj_service->data["typeid_string"] != "data_traffic")
	{
		log_write("error", "page_output", "The requested service is not a data_traffic service.");
		return 0;
	}


	// verify that the ID of the address is valid (if supplied)
	if ($obj_ipv4->id)
	{
		if (!$obj_ipv4->verify_id())
		{
			log_write("error", "page_output", "The supplied IPv4 address ID is not valid");
			return 0;
		}
	}


	// verify valid IP subnet
	// TODO: write me


	/*
		Check for any errors
	*/
	if (error_check())
	{	
		$_SESSION["error"]["form"]["service_ipv4_edit"] = "failed";

		header("Location: ../index.php?page=customers/service-ipv4-edit.php&id_customer=". $obj_customer->id ."&id_service_customer=". $obj_customer->id_service_customer ."&id_ipv4=". $obj_ipv4->id);
		exit(0);
	}
	else
	{
		/*
			Update/Create IPv4 address
		*/
		$obj_ipv4->action_update();


		/*
			Complete
		*/
		header("Location: ../index.php?page=customers/service-ipv4.php&id_customer=". $obj_customer->id ."&id_service_customer=". $obj_customer->id_service_customer ."");
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
