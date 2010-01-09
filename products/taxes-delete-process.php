<?php
/*
	products/taxes-delete-process.php

	access: products_write

	Deletes the requested tax item
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
		Import GET Data
	*/
	
	$obj_product_tax->id		= @security_script_input("/^[0-9]*$/", $_GET["id"]);
	$obj_product_tax->itemid	= @security_script_input("/^[0-9]*$/", $_GET["itemid"]);


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
			log_write("error", "process", "The tax item you have attempted to edit - ". $obj_product_tax->itemid ." - does not exist in this system.");
		}
	}
	


	// return to input page in event of an error
	if ($_SESSION["error"]["message"])
	{
		header("Location: ../index.php?page=products/taxes.php&id=". $obj_product_tax->id);
		exit(0);
	}


	/*
		Process Data
	*/

	$obj_product_tax->action_delete();

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
