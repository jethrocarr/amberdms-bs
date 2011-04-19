<?php
/*
	customers/orders-invoicegen-process.php

	access: customers_orders

	Manually called to generate an invoice for all the customer orders.
*/

// includes
require("../include/config.php");
require("../include/amberphplib/main.php");

// custom includes
require("../include/accounts/inc_invoices.php");
require("../include/accounts/inc_invoices_items.php");
require("../include/customers/inc_customers.php");



if (user_permissions_get('customers_orders'))
{
	/*
		Validate Customer
	*/

	$obj_customer		= New customer_orders;
	$obj_customer->id	= @security_script_input('/^[0-9]*$/', $_GET["id_customer"]);


	if (!$obj_customer->verify_id())
	{
		log_write("error", "process", "The customer you have attempted to edit - ". $obj_customer->id ." - does not exist in this system.");
	}




	/*
		Error Handling
	*/

	if (error_check())
	{	
		header("Location: ../index.php?page=customers/orders.php&id_customer='". $obj_customer->id ."'");
		exit(0);
	}



	/*
		Generate Orders Invoice
	*/

	// generate invoice
	$obj_customer->invoice_generate();

	
	// display updated details
	header("Location: ../index.php?page=customers/invoices.php&id=". $obj_customer->id ."");
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
