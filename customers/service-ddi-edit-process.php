<?php
/*
	customers/service-ddi-edit-process.php

	access:	customers_write

	Apply changes to DDI values.
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

	$obj_customer						= New customer_services;
	$obj_ddi						= New cdr_customer_service_ddi;



	/*
		Load Data
	*/

	$obj_customer->id					= @security_form_input_predefined("int", "id_customer", 1, "");
	$obj_customer->id_service_customer			= @security_form_input_predefined("int", "id_service_customer", 0, "");

	$obj_ddi->id						= @security_form_input_predefined("int", "id_ddi", 0, "");
	$obj_ddi->id_customer					= $obj_customer->id;
	$obj_ddi->id_service_customer				= $obj_customer->id_service_customer;

	$obj_ddi->data["ddi_start"]				= @security_form_input_predefined("any", "ddi_start", 1, "");
	$obj_ddi->data["ddi_finish"]				= @security_form_input_predefined("any", "ddi_finish", 1, "");
	$obj_ddi->data["description"]				= @security_form_input_predefined("money", "description", 0, "");



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



	// verify that this is a phone trunk service
	if ($obj_customer->obj_service->data["typeid_string"] != "phone_trunk")
	{
		log_write("error", "page_output", "The requested service is not a phone_trunk service.");
		return 0;
	}


	// verify that the DDI value is correct (if one has been supplied)
	if ($obj_ddi->id)
	{
		if (!$obj_ddi->verify_id())
		{
			log_write("error", "page_output", "The supplied DDI ID is not valid");
			return 0;
		}
	}




	/*
		Check for any errors
	*/
	if (error_check())
	{	
		$_SESSION["error"]["form"]["cdr_override_edit"] = "failed";
		header("Location: ../index.php?page=customers/service-ddi-edit.php&id_customer=". $obj_customer->id ."&id_service_customer=". $obj_customer->id_service_customer ."&id_ddi=". $obj_ddi->id);
		exit(0);
	}
	else
	{
		/*
			Update/Create DDI entry
		*/
		$obj_ddi->action_update();


		/*
			Complete
		*/
		header("Location: ../index.php?page=customers/service-ddi.php&id_customer=". $obj_customer->id ."&id_service_customer=". $obj_customer->id_service_customer ."");
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
