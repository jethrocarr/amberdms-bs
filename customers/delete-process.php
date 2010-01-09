<?php
/*
	customers/delete-process.php

	access: customers_write

	Deletes a customer provided that the customer has not been added to any invoices or time groups.
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

	$obj_customer->id		= @security_form_input_predefined("int", "id_customer", 1, "");


	// these exist to make error handling work right
	$data["name_customer"]		= @security_form_input_predefined("any", "name_customer", 0, "");

	// confirm deletion
	$data["delete_confirm"]		= @security_form_input_predefined("any", "delete_confirm", 1, "You must confirm the deletion");

	

	/*
		Error Handling
	*/


	// make sure the customer actually exists
	if (!$obj_customer->verify_id())
	{
		log_write("error", "process", "The customer you have attempted to edit - ". $obj_customer->id ." - does not exist in this system.");
	}


	// check if the customer can be safely deleted
	if ($obj_customer->check_delete_lock())
	{
		log_write("error", "process", "This customer can not be removed because their account has invoices or time groups belonging to it.");
	}

	

	// return to the input page in the event of an error
	if ($_SESSION["error"]["message"])
	{	
		$_SESSION["error"]["form"]["customer_delete"] = "failed";
		header("Location: ../index.php?page=customers/delete.php&id=". $obj_customer->id);
		exit(0);
	}



	/*
		Delete Customer
	*/

	// delete customer
	$obj_customer->action_delete();


	// return to customers list
	header("Location: ../index.php?page=customers/customers.php");
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
