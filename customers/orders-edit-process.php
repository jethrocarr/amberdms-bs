<?php
/*
	customers/orders-edit-process.php

	access: customers_orders

	Allows new orders to be added to customers or existing ones to be edited.
*/

// includes
require("../include/config.php");
require("../include/amberphplib/main.php");

require("../include/customers/inc_customers.php");



if (user_permissions_get('customers_orders'))
{
	/*
		Load Data
	*/
	$obj_customer				= New customer_orders;
	$obj_customer->id			= @security_form_input_predefined("int", "id_customer", 1, "");
	$obj_customer->id_order			= @security_form_input_predefined("int", "id_order", 0, "");


	if ($obj_customer->id_order)
	{
		// load the order data
		$obj_customer->load_data_order();
	}

	// standard fields
	$data["date_ordered"]			= @security_form_input_predefined("date", "date_ordered", 1, "");
	$data["type"]				= @security_form_input_predefined("any", "type", 0, "");


	// product fields
	switch ($data["type"])
	{
		case "product":
			$data["customid"]		= @security_form_input_predefined("int", "productid", 1, "");
			$data["description"]		= @security_form_input_predefined("any", "description", 0, "");
			$data["price"]			= @security_form_input_predefined("money", "price", 0, "");
			$data["price_setup"]		= @security_form_input_predefined("money", "price_setup", 0, "");
			$data["discount"]		= @security_form_input_predefined("float", "discount", 0, "");

			// options
			$data["quantity"]		= @security_form_input_predefined("int", "quantity", 0, "");

			if (!$data["quantity"])
				$data["quantity"] = 1;	// all services must have at least 1
		break;


		case "service":
			// TODO: stuff here?
		break;


		default:
			// unknown type
			log_write("error", "process", "An unexpected error occured, type value of ". $data["type"] ." is invalid");
			error_flag_field("type");
		break;
	}





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
		if ($obj_customer->id_order)
		{
			// are we editing an existing order? make sure it exists and belongs to this customer
			if (!$obj_customer->verify_id_order())
			{
				log_write("error", "process", "The order you have attempted to edit - ". $obj_customer->id_order ." - does not exist in this system.");
			}
			else
			{
				$obj_customer->load_data();
				$obj_customer->load_data_order();
			}
		}
	}


	// type specific validation
	switch ($data["type"])
	{
		case "product":

			// verify the product ID is valid
			$sql_product_obj		= New sql_query;
			$sql_product_obj->string	= "SELECT id FROM products WHERE id='". $data["customid"] ."' LIMIT 1";
			$sql_product_obj->execute();

			if (!$sql_product_obj->num_rows())
			{
				log_write("error", "process", "Unable to find the product with ID of ". $data["customid"] ."");
			}

			unset($sql_product_obj);

		break;
	}



	/*
		Check for any errors
	*/
	if (error_check())
	{	
		$_SESSION["error"]["form"]["orders_view"] = "failed";
		header("Location: ../index.php?page=customers/orders-view.php&id_customer=". $obj_customer->id ."&id_order=". $obj_customer->id_order);
		exit(0);
	}
	else
	{
		/*
			Update the database
		*/

		// update
		$obj_customer->data_orders = $data;
		$obj_customer->action_update_orders();

		// return to services page
		header("Location: ../index.php?page=customers/orders.php&id_customer=". $obj_customer->id ."");
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
