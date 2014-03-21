<?php
/*
	customers/orders-delete-process.php

	access: customers_order

	Deletes the selected order from the customer.
*/

// includes
require("../include/config.php");
require("../include/amberphplib/main.php");

require("../include/customers/inc_customers.php");



if (user_permissions_get('customers_write'))
{
	/*
		Load Data
	*/

	$obj_customer				= New customer_orders;
	$obj_customer->id			= @security_script_input("/[0-9]*/", $_GET["id_customer"]);
	$obj_customer->id_order			= @security_script_input("/[0-9]*/", $_GET["id_order"]);


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
		// make sure the order exists for this customer
		if (!$obj_customer->verify_id_order())
		{
			log_write("error", "process", "The order item you have attempted to edit - ". $obj_customer->id_order ." - does not exist in this system.");
		}
		else
		{
			$obj_customer->load_data();
			$obj_customer->load_data_order();
		}
	}


	/*
		Check for any errors
	*/
	if (error_check())
	{	
		header("Location: ../index.php?page=customers/orders.php&id_customer=". $obj_customer->id ."&id_order=". $obj_customer->id_order);
		exit(0);
	}
	else
	{
		/*
			Delete Order Item
		*/

		$obj_customer->action_delete_orders();


		/*
			Return
		*/
		header("Location: ../index.php?page=customers/orders.php&id_customer=". $obj_customer->id ."&id_order=". $obj_customer->id_order);
		exit(0);
			
	}
}
else
{
	// user does not have perms to view this page/isn't logged on
	error_render_noperms();
	header("Location: ../index.php?page=message.php");
	exit(0);
}

?>
