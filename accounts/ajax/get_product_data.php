<?php
/*
	accounts/ajax/get_product_data.php

	Returns the details for the requested product ID, provided
	that the user belongs to accounts_ar, accounts_ap or products_view
	group.
*/

require("../../include/config.php");
require("../../include/amberphplib/main.php");
require("../../include/products/inc_products.php");


if (user_permissions_get("products_view") || user_permissions_get("accounts_ar_write") || user_permissions_get("accounts_ap_write") || user_permissions_get("accounts_quotes_write"))
{
	$obj_product		= New product;

	$obj_product->id	= @security_script_input_predefined("int", $_GET['id']);


	if ($obj_product->verify_id())
	{
		$obj_product->load_data();
	
		echo json_encode($obj_product->data);
	}
	else
	{
		log_write("error", "message", "(AJAX) Invalid product requested");
		die("fatal error");
	}

	exit(0);
}

?>
