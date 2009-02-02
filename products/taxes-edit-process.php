<?php
/*
	products/taxes-edit-process.php

	access: products_write

	Allows new tax items to be added to a product, or existing ones to be edited.
*/

// includes
require("../include/config.php");
require("../include/amberphplib/main.php");

// custom includes
require("../include/products/inc_products.php");


if (user_permissions_get('products_write'))
{
	$obj_product_tax = New product_tax;


	/*
		Import POST Data
	*/

	$obj_product_tax->id				= security_form_input_predefined("int", "id_product", 1, "");
	$obj_product_tax->itemid			= security_form_input_predefined("int", "id_item", 0, "");

	$obj_product_tax->data["taxid"]			= security_form_input_predefined("int", "taxid", 1, "");
	$obj_product_tax->data["description"]		= security_form_input_predefined("any", "description", 0, "");
	$obj_product_tax->data["manual_option"]		= security_form_input_predefined("any", "manual_option", 0, "");
	$obj_product_tax->data["manual_amount"]		= security_form_input_predefined("money", "manual_amount", 0, "");



	/*
		Error Handling
	*/



	// make sure the product actually exists
	if (!$obj_product_tax->verify_product_id())
	{
		log_write("error", "process", "The product you have attempted to edit - ". $obj_product_tax->id ." - does not exist in this system.");
	}


	// if provided, make sure the item ID is valid
	if ($obj_product_tax->itemid)
	{
		if (!$obj_product_tax->verify_item_id())
		{
			log_write("error", "process", "The tax ID you have attempted to edit - ". $obj_product_tax->itemid ." - does not exist in this system.");
		}
	}
	


	// TODO: check for valid taxid


	// return to input page in event of an error
	if ($_SESSION["error"]["message"])
	{
		$_SESSION["error"]["form"]["product_taxes_edit"] = "failed";
		header("Location: ../index.php?page=products/taxes-edit.php&id=". $obj_product_tax->id ."&itemid=". $obj_product_tax->itemid);
		exit(0);
	}


	/*
		Process Data
	*/

	$obj_product_tax->action_update();

	// display updated details
	header("Location: ../index.php?page=products/taxes.php&id=". $obj_product_tax->id);
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
