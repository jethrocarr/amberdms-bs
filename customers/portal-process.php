<?php
/*
	customers/portal-process.php

	access: customers_write

	Allows adjustments of the customer's portal interface.
*/


// includes
require("../include/config.php");
require("../include/amberphplib/main.php");

// custom includes
require("../include/customers/inc_customers.php");


if (user_permissions_get('customers_write'))
{
	$obj_customer = New customer_portal;


	/*
		Load POST data
	*/

	$obj_customer->id				= @security_form_input_predefined("int", "id_customer", 1, "");

	// check password (if the user has requested to change it)
	if ($_POST["password"] || $_POST["password_confirm"])
	{
		$data["password"]			= @security_form_input_predefined("any", "password", 4, "");
		$data["password_confirm"]		= @security_form_input_predefined("any", "password_confirm", 4, "");

		if ($data["password"] != $data["password_confirm"])
		{
			$_SESSION["error"]["message"][]			= "Customer passwords do not match.";
			$_SESSION["error"]["password-error"]		= 1;
			$_SESSION["error"]["password_confirm-error"]	= 1;
		}
	}




	/*
		Error Handling
	*/


	// verify valid customer ID
	if (!$obj_customer->verify_id())
	{
		log_write("error", "process", "The customer you have attempted to edit - ". $obj_customer->id ." - does not exist in this system.");
	}
	

	// make sure the module is enabled
	if (sql_get_singlevalue("SELECT value FROM config WHERE name='MODULE_CUSTOMER_PORTAL' LIMIT 1") != "enabled")
	{
		log_write("error", "page_output", "MODULE_CUSTOMER_PORTAL is disabled, enable it if you wish to adjust customer portal configuration options.");
	}



	if (error_check())
	{
		$_SESSION["error"]["form"]["customer_portal"] = "failed";
		header("Location: ../index.php?page=customers/portal.php");
		exit(0);
	}


	/*
		Process Data
	*/

	// update portal
	$obj_customer->auth_changepwd($data["password"]);


	// display updated details
	header("Location: ../index.php?page=customers/portal.php&id=". $obj_customer->id);
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
