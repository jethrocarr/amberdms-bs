<?php
/*
	products/delete-process.php

	access: products_write

	Deletes a product, provided that the product has not been added to any invoices.
*/

// includes
include_once("../include/config.php");
include_once("../include/amberphplib/main.php");


if (user_permissions_get('products_write'))
{
	/////////////////////////

	$id				= security_form_input_predefined("int", "id_product", 0, "");

	// these exist to make error handling work right
	$data["code_product"]		= security_form_input_predefined("any", "code_product", 0, "");
	$data["name_product"]		= security_form_input_predefined("any", "name_product", 0, "");

	// confirm deletion
	$data["delete_confirm"]		= security_form_input_predefined("any", "delete_confirm", 1, "You must confirm the deletion");

	
	// make sure the product actually exists
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT id FROM `products` WHERE id='$id'";
	$sql_obj->execute();
	
	if (!$sql_obj->num_rows())
	{
		$_SESSION["error"]["message"][] = "The product you have attempted to edit - $id - does not exist in this system.";
	}


		
	//// ERROR CHECKING ///////////////////////


	// check if the product belongs to any invoices
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT id FROM account_items WHERE (type='product' OR type='time') AND customid='$id'";
	$sql_obj->execute();

	if ($sql_obj->num_rows())
	{
		$_SESSION["error"]["message"][] = "You are not able to delete this product because it has been added to an invoice.";
	}
	

	/// if there was an error, go back to the entry page
	if ($_SESSION["error"]["message"])
	{	
		$_SESSION["error"]["form"]["product_delete"] = "failed";
		header("Location: ../index.php?page=products/delete.php&id=$id");
		exit(0);
		
	}
	else
	{

		/*
			Delete Product
		*/
			
		$sql_obj		= New sql_query;
		$sql_obj->string	= "DELETE FROM products WHERE id='$id'";
			
		if (!$sql_obj->execute())
		{
			$_SESSION["error"]["message"][] = "A fatal SQL error occured whilst trying to delete the product";
		}
		else
		{		
			$_SESSION["notification"]["message"][] = "Product has been successfully deleted.";
		}



		/*
			Delete Product Journal
		*/
		journal_delete_entire("products", $id);



		// return to products list
		header("Location: ../index.php?page=products/products.php");
		exit(0);
	}

	/////////////////////////
	
}
else
{
	// user does not have perms to view this page/isn't logged on
	error_render_noperms();
	header("Location: ../index.php?page=message.php");
	exit(0);
}


?>
