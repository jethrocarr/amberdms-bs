<?php
/*
	customers/edit-process.php

	access: customers_write

	Allows existing customers to be adjusted, or new customers to be added.
*/

// includes
require("../include/config.php");
require("../include/amberphplib/main.php");

// custom includes
require("../include/customers/inc_customers.php");


if (user_permissions_get('customers_write'))
{
	$obj_customer = New customer;


	/*
		Load POST data
	*/

	$obj_customer->id				= security_form_input_predefined("int", "id_customer", 0, "");
	
	$obj_customer->data["code_customer"]		= security_form_input_predefined("any", "code_customer", 0, "");
	$obj_customer->data["name_customer"]		= security_form_input_predefined("any", "name_customer", 1, "");
	$obj_customer->data["name_contact"]		= security_form_input_predefined("any", "name_contact", 0, "");
	
	$obj_customer->data["contact_phone"]		= security_form_input_predefined("any", "contact_phone", 0, "");
	$obj_customer->data["contact_fax"]		= security_form_input_predefined("any", "contact_fax", 0, "");
	$obj_customer->data["contact_email"]		= security_form_input_predefined("email", "contact_email", 0, "");
	$obj_customer->data["date_start"]		= security_form_input_predefined("date", "date_start", 1, "");
	$obj_customer->data["date_end"]			= security_form_input_predefined("date", "date_end", 0, "");

	$obj_customer->data["address1_street"]		= security_form_input_predefined("any", "address1_street", 0, "");
	$obj_customer->data["address1_city"]		= security_form_input_predefined("any", "address1_city", 0, "");
	$obj_customer->data["address1_state"]		= security_form_input_predefined("any", "address1_state", 0, "");
	$obj_customer->data["address1_country"]		= security_form_input_predefined("any", "address1_country", 0, "");
	$obj_customer->data["address1_zipcode"]		= security_form_input_predefined("any", "address1_zipcode", 0, "");
	
	$obj_customer->data["address2_street"]		= security_form_input_predefined("any", "address2_street", 0, "");
	$obj_customer->data["address2_city"]		= security_form_input_predefined("any", "address2_city", 0, "");
	$obj_customer->data["address2_state"]		= security_form_input_predefined("any", "address2_state", 0, "");
	$obj_customer->data["address2_country"]		= security_form_input_predefined("any", "address2_country", 0, "");
	$obj_customer->data["address2_zipcode"]		= security_form_input_predefined("any", "address2_zipcode", 0, "");
	
	$obj_customer->data["tax_number"]		= security_form_input_predefined("any", "tax_number", 0, "");
	$obj_customer->data["tax_default"]		= security_form_input_predefined("int", "tax_default", 0, "");

	// get tax selection options
	$sql_taxes_obj		= New sql_query;
	$sql_taxes_obj->string	= "SELECT id FROM account_taxes";
	$sql_taxes_obj->execute();

	if ($sql_taxes_obj->num_rows())
	{
		$sql_taxes_obj->fetch_array();

		foreach ($sql_taxes_obj->data as $data_tax)
		{
			$obj_customer->data["tax_". $data_tax["id"] ] = security_form_input_predefined("any", "tax_". $data_tax["id"], 0, "");
		}
	}


	/*
		Error Handling
	*/


	// verify valid ID (if performing update)
	if ($obj_customer->id)
	{
		if (!$obj_customer->verify_id())
		{
			log_write("error", "process", "The customer you have attempted to edit - ". $obj_customer->id ." - does not exist in this system.");
		}
	}


	// make sure we don't choose a customer name that has already been taken
	if (!$obj_customer->verify_name_customer())
	{
		log_write("error", "process", "This customer name is already used for another customer - please choose a unique name.");
		$_SESSION["error"]["name_customer-error"] = 1;
	}


	// make sure we don't choose a customer code that has already been taken
	if (!$obj_customer->verify_code_customer())
	{
		log_write("error", "process", "This customer code is already used for another customer - please choose a unique code or leave blank for an automatically generated value.");
		$_SESSION["error"]["name_customer-error"] = 1;
	}


	// don't allow a date closed to be set if there are active services belonging to this customer
	if (!$obj_customer->verify_date_end())
	{
		log_write("error", "process", "You can not close this customer, as there are still active services on this account");
		$_SESSION["error"]["date_end-error"] = 1;
	}


	// return to input page if any errors occured
	if ($_SESSION["error"]["message"])
	{
		if ($obj_customer->id)
		{
			$_SESSION["error"]["form"]["customer_view"] = "failed";
			header("Location: ../index.php?page=customers/view.php&id=". $obj_customer->id ."");
			exit(0);
		}
		else
		{
			$_SESSION["error"]["form"]["customer_add"] = "failed";
			header("Location: ../index.php?page=customers/add.php");
			exit(0);
		}
	}


	/*
		Process Data
	*/

	$obj_customer->action_update();

	$obj_customer->action_update_taxes();


	// display updated details
	header("Location: ../index.php?page=customers/view.php&id=". $obj_customer->id);
	exit(0);
	
}
else
{
	// user does not have perms to view this page/isn't logged on
	error_render_noperms();
	header("Location: ../index.php?page=message.php");
	exit(0);
}


?>
