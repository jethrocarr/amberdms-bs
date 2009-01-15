<?php
/*
	products/delete-process.php

	access: products_write

	Deletes a product, provided that the product has not been added to any invoices.
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

	// these exist to make error handling work right
	$obj_product->data["code_product"]		= security_form_input_predefined("any", "code_product", 0, "");
	$obj_product->data["name_product"]		= security_form_input_predefined("any", "name_product", 0, "");

	// confirm deletion
	$obj_product->data["delete_confirm"]		= security_form_input_predefined("any", "delete_confirm", 1, "You must confirm the deletion");



	/*
		Error Handling
	*/


	// check that the product exists
	if (!$obj_product->verify_id())
	{
		log_write("error", "process", "The product you have attempted to edit - ". $obj_product->id ." - does not exist in this system.");
	}


	// check that the product is safe to delete
	if ($obj_product->check_delete_lock())
	{
		log_write("error", "process", "This product is locked and can not be deleted.");
	}
	

	// if there was an error, go back to the entry page
	if ($_SESSION["error"]["message"])
	{	
		$_SESSION["error"]["form"]["product_delete"] = "failed";
		header("Location: ../index.php?page=products/delete.php&id=". $obj_product->id);
		exit(0);
		
	}



	/*
		Delete Product
	*/
	
	$obj_product->action_delete();


	// return to products list
	header("Location: ../index.php?page=products/products.php");
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
