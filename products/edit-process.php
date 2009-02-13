<?php
/*
	products/edit-process.php

	access: products_write

	Allows existing products to be adjusted, or new products to be added.
*/

// includes
require("../include/config.php");
require("../include/amberphplib/main.php");

// custom includes
require("../include/products/inc_products.php");


if (user_permissions_get('products_write'))
{
	$obj_product = New product;


	/*
		Import POST Data
	*/

	$obj_product->id				= security_form_input_predefined("int", "id_product", 0, "");
	
	$obj_product->data["code_product"]		= security_form_input_predefined("any", "code_product", 0, "");
	$obj_product->data["name_product"]		= security_form_input_predefined("any", "name_product", 1, "");
	$obj_product->data["units"]			= security_form_input_predefined("any", "units", 1, "");
	$obj_product->data["account_sales"]		= security_form_input_predefined("int", "account_sales", 1, "");
	$obj_product->data["account_purchase"]		= security_form_input_predefined("int", "account_purchase", 1, "");

	$obj_product->data["date_start"]		= security_form_input_predefined("date", "date_start", 1, "");
	$obj_product->data["date_end"]			= security_form_input_predefined("date", "date_end", 0, "");
	$obj_product->data["date_current"]		= security_form_input_predefined("date", "date_current", 0, "");
	$obj_product->data["details"]			= security_form_input_predefined("any", "details", 0, "");
	
	$obj_product->data["price_cost"]		= security_form_input_predefined("money", "price_cost", 0, "");
	$obj_product->data["price_sale"]		= security_form_input_predefined("money", "price_sale", 0, "");
	
	$obj_product->data["quantity_instock"]		= security_form_input_predefined("int", "quantity_instock", 0, "");
	$obj_product->data["quantity_vendor"]		= security_form_input_predefined("int", "quantity_vendor", 0, "");

	// only get vendor ID if vendors exist, otherwise will trigger an error
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT id FROM vendors LIMIT 1";
	$sql_obj->execute();

	if ($sql_obj->num_rows())
	{
		$obj_product->data["vendorid"]		= security_form_input_predefined("int", "vendorid", 0, "");
	}

	$obj_product->data["code_product_vendor"]	= security_form_input_predefined("any", "code_product_vendor", 0, "");



	/*
		Error Handling
	*/



	// make sure the product actually exists
	if ($obj_product->id)
	{
		if (!$obj_product->verify_id())
		{
			log_write("error", "process", "The product you have attempted to edit - ". $obj_product->id ." - does not exist in this system.");
		}
	}


	// make sure we don't choose a product code that has already been choosen
	if (!$obj_product->verify_code_product())
	{
		log_write("error", "process", "This product code is already used for another product - please choose a unique identifier.");
		$_SESSION["error"]["code_product-error"] = 1;
	}


	// make sure we don't choose a product name that has already been taken
	if (!$obj_product->verify_name_product())
	{
		log_write("error", "process", "This product name is already used for another product - please choose a unique name.");
		$_SESSION["error"]["name_product-error"] = 1;
	}


	// return to input page in event of an error
	if ($_SESSION["error"]["message"])
	{
		if ($obj_product->id)
		{
			$_SESSION["error"]["form"]["product_view"] = "failed";
			header("Location: ../index.php?page=products/view.php&id=". $obj_product->id);
			exit(0);
		}
		else
		{
			$_SESSION["error"]["form"]["product_add"] = "failed";
			header("Location: ../index.php?page=products/add.php");
			exit(0);
		}
	}


	/*
		Process Data
	*/

	$obj_product->action_update();

	// display updated details
	header("Location: ../index.php?page=products/view.php&id=". $obj_product->id);
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
