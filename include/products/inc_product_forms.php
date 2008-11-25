<?php
/*
	include/products/inc_products_forms.php

	Provides various forms used for managing product entries.
*/

require("include/accounts/inc_charts.php");



/*
	FUNCTIONS
*/



/*
	products_form_details_render

	Values
	productid	ID of the product entry (none for an add form)
	mode		Either "add" or "edit"

	Return Codes
	0		failure
	1		success
*/
function products_form_details_render($productid, $mode)
{
	log_debug("inc_products_forms", "Executing products_forms_details_render($productid)");


	/*
		Define form structure
	*/
	$form = New form_input;
	$form->formname = "product_$mode";
	$form->language = $_SESSION["user"]["lang"];

	$form->action = "products/edit-process.php";
	$form->method = "post";

	// general
	$structure = NULL;
	$structure["fieldname"] 	= "code_product";
	$structure["type"]		= "input";
	$form->add_input($structure);
	
	$structure = NULL;
	$structure["fieldname"] 	= "name_product";
	$structure["type"]		= "input";
	$structure["options"]["req"]	= "yes";
	$form->add_input($structure);

	$structure = charts_form_prepare_acccountdropdown("account_sales", 2);
	$structure["options"]["req"]	= "yes";
	$form->add_input($structure);


	$structure = NULL;
	$structure["fieldname"] 	= "details";
	$structure["type"]		= "textarea";
	$form->add_input($structure);

	$structure = NULL;
	$structure["fieldname"]		= "date_current";
	$structure["type"]		= "date";
	$form->add_input($structure);


	
	// pricing			
	$structure = NULL;
	$structure["fieldname"]		= "price_cost";
	$structure["type"]		= "input";
	$form->add_input($structure);

	$structure = NULL;
	$structure["fieldname"]		= "price_sale";
	$structure["type"]		= "input";
	$form->add_input($structure);

	// quantity
	$structure = NULL;
	$structure["fieldname"]		= "quantity_instock";
	$structure["type"]		= "input";
	$form->add_input($structure);

	$structure = NULL;
	$structure["fieldname"]		= "quantity_vendor";
	$structure["type"]		= "input";
	$form->add_input($structure);


	// supplier details
	$structure = form_helper_prepare_dropdownfromdb("vendorid", "SELECT id, name_vendor as label FROM vendors");
	$form->add_input($structure);
	
	$structure = NULL;
	$structure["fieldname"] 	= "code_product_vendor";
	$structure["type"]		= "input";
	$form->add_input($structure);
		

	// define subforms
	$form->subforms["product_view"]		= array("code_product", "name_product", "account_sales", "date_current", "details");
	$form->subforms["product_pricing"]	= array("price_cost", "price_sale");
	$form->subforms["product_quantity"]	= array("quantity_instock", "quantity_vendor");
	$form->subforms["product_supplier"]	= array("vendorid", "code_product_vendor");
	$form->subforms["submit"]		= array("submit");


	/*
		Mode dependent options
	*/
	
	if ($mode == "add")
	{
		// submit button
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Create Product";
		$form->add_input($structure);
	}
	else
	{
		// submit button
		if (user_permissions_get("products_write"))
		{
			$structure = NULL;
			$structure["fieldname"] 	= "submit";
			$structure["type"]		= "submit";
			$structure["defaultvalue"]	= "Save Changes";
			$form->add_input($structure);
		}
		else
		{
			$structure = NULL;
			$structure["fieldname"] 	= "submit";
			$structure["type"]		= "message";
			$structure["defaultvalue"]	= "<p><i>Sorry, you don't have permissions to make changes to product records.</i></p>";
			$form->add_input($structure);
		}


		// hidden data
		$structure = NULL;
		$structure["fieldname"] 	= "id_product";
		$structure["type"]		= "hidden";
		$structure["defaultvalue"]	= "$productid";
		$form->add_input($structure);
			

		$form->subforms["hidden"]	= array("id_product");
	}


	/*
		Load Data
	*/
	if ($mode == "add")
	{
		$form->load_data_error();
	}
	else
	{
		$form->sql_query = "SELECT * FROM `products` WHERE id='$productid' LIMIT 1";		
		$form->load_data();
	}


	/*
		Display Form Information
	*/
	$form->render_form();


	return 1;
	
} // end of products_forms_details_render




/*
	products_form_delete_render

	Values
	productid	ID of the product entry

	Return Codes
	0		failure
	1		success
*/
function products_form_delete_render($productid)
{
	log_debug("inc_products_forms", "Executing products_forms_delete_render($productid)");


	/*
		Define form structure
	*/
	$form = New form_input;
	$form->formname = "product_delete";
	$form->language = $_SESSION["user"]["lang"];

	$form->action = "products/delete-process.php";
	$form->method = "post";

	// general
	$structure = NULL;
	$structure["fieldname"] 	= "code_product";
	$structure["type"]		= "text";
	$form->add_input($structure);
	
	$structure = NULL;
	$structure["fieldname"] 	= "name_product";
	$structure["type"]		= "text";
	$form->add_input($structure);


	// hidden data
	$structure = NULL;
	$structure["fieldname"] 	= "id_product";
	$structure["type"]		= "hidden";
	$structure["defaultvalue"]	= "$productid";
	$form->add_input($structure);


	// confirm delete
	$structure = NULL;
	$structure["fieldname"] 	= "delete_confirm";
	$structure["type"]		= "checkbox";
	$structure["options"]["label"]	= "Yes, I wish to delete this product and realise that once deleted the data can not be recovered.";
	$form->add_input($structure);


	// submit button
	//
	// We check if the product has been added to any invoices, and then either define
	// a delete button or a message
	
	$structure = NULL;
	$structure["fieldname"] = "submit";
	
	$sql_obj		= New sql_query;
	$sql_obj->string	= "SELECT id FROM account_items WHERE (type='product' OR type='time') AND customid='$id'";
	$sql_obj->execute();

	if ($sql_obj->num_rows())
	{
		$structure["type"]		= "message";
		$structure["defaultvalue"]	= "<i>This project can not be deleted because time entries have been assigned to it.</i>";
	}
	else
	{
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "delete";
	}
			
	$form->add_input($structure);

		


	// define subforms
	$form->subforms["product_delete"]	= array("code_product", "name_product");
	$form->subforms["hidden"]		= array("id_product");
	$form->subforms["submit"]		= array("delete_confirm", "submit");


	/*
		Load Data
	*/
	$form->sql_query = "SELECT * FROM `products` WHERE id='$productid' LIMIT 1";		
	$form->load_data();


	/*
		Display Form Information
	*/
	$form->render_form();


	return 1;
	
} // end of products_forms_delete_render




