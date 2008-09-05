<?php
/*
	products/edit-process.php

	access: products_write

	Allows existing products to be adjusted, or new products to be added.
*/

// includes
include_once("../include/config.php");
include_once("../include/amberphplib/main.php");


if (user_permissions_get('products_write'))
{
	/////////////////////////

	$id				= security_form_input_predefined("int", "id_product", 0, "");
	
	$data["code_product"]		= security_form_input_predefined("any", "code_product", 1, "You must specify a product code");
	$data["name_product"]		= security_form_input_predefined("any", "name_product", 1, "You must specify a product name");

	$data["date_current"]		= security_form_input_predefined("date", "date_current", 0, "");
	$data["details"]		= security_form_input_predefined("any", "details", 0, "");
	
	$data["price_cost"]		= security_form_input_predefined("float", "price_cost", 0, "");
	$data["price_sale"]		= security_form_input_predefined("float", "price_sale", 0, "");
	
	$data["quantity_instock"]	= security_form_input_predefined("int", "quantity_instock", 0, "");
	$data["quantity_vendor"]	= security_form_input_predefined("int", "quantity_vendor", 0, "");


	// are we editing an existing product or adding a new one?
	if ($id)
	{
		$mode = "edit";

		// make sure the product actually exists
		$mysql_string		= "SELECT id FROM `products` WHERE id='$id'";
		$mysql_result		= mysql_query($mysql_string);
		$mysql_num_rows		= mysql_num_rows($mysql_result);

		if (!$mysql_num_rows)
		{
			$_SESSION["error"]["message"][] = "The product you have attempted to edit - $id - does not exist in this system.";
		}
	}
	else
	{
		$mode = "add";
	}


		
	//// ERROR CHECKING ///////////////////////

	// make sure we don't choose a product code that has already been choosen
	$mysql_string	= "SELECT id FROM `products` WHERE code_product='". $data["code_product"] ."'";
	if ($id)
		$mysql_string .= " AND id!='$id'";
	$mysql_result	= mysql_query($mysql_string);
	$mysql_num_rows	= mysql_num_rows($mysql_result);

	if ($mysql_num_rows)
	{
		$_SESSION["error"]["message"][] = "This product code is already used for another product - please choose a unique name.";
		$_SESSION["error"]["code_product-error"] = 1;
	}


	// make sure we don't choose a product name that has already been taken
	$mysql_string	= "SELECT id FROM `products` WHERE name_product='". $data["name_product"] ."'";
	if ($id)
		$mysql_string .= " AND id!='$id'";
	$mysql_result	= mysql_query($mysql_string);
	$mysql_num_rows	= mysql_num_rows($mysql_result);

	if ($mysql_num_rows)
	{
		$_SESSION["error"]["message"][] = "This product name is already used for another product - please choose a unique name.";
		$_SESSION["error"]["name_product-error"] = 1;
	}


	/// if there was an error, go back to the entry page
	if ($_SESSION["error"]["message"])
	{	
		if ($mode == "edit")
		{
			header("Location: ../index.php?page=products/view.php&id=$id");
			exit(0);
		}
		else
		{
			header("Location: ../index.php?page=products/add.php");
			exit(0);
		}
	}
	else
	{
		if ($mode == "add")
		{
			// create a new entry in the DB
			$mysql_string = "INSERT INTO `products` (name_product) VALUES ('".$data["name_product"]."')";
			if (!mysql_query($mysql_string))
			{
				$_SESSION["error"]["message"][] = "A fatal SQL error occured: ". $mysql_error();
			}

			$id = mysql_insert_id();
		}

		if ($id)
		{
			// update product details
			$mysql_string = "UPDATE `products` SET "
						."name_product='". $data["name_product"] ."', "
						."code_product='". $data["code_product"] ."', "
						."date_current='". $data["date_current"] ."', "
						."details='". $data["details"] ."', "
						."price_cost='". $data["price_cost"] ."', "
						."price_sale='". $data["price_sale"] ."', "
						."quantity_instock='". $data["quantity_instock"] ."', "
						."quantity_vendor='". $data["quantity_vendor"] ."' "
						."WHERE id='$id'";
						
			if (!mysql_query($mysql_string))
			{
				$_SESSION["error"]["message"][] = "A fatal SQL error occured: ". $mysql_error();
			}
			else
			{
				if ($mode == "add")
				{
					$_SESSION["notification"]["message"][] = "Product successfully created.";
				}
				else
				{
					$_SESSION["notification"]["message"][] = "Product successfully updated.";
				}
			}
		}

		// display updated details
		header("Location: ../index.php?page=products/view.php&id=$id");
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
