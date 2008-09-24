<?php
/*
	products/view.php

	access: products_view (read-only)
		products_write (write access)

	Displays the selected product and if the user has correct permissions
	allows the product to be updated.
*/

if (user_permissions_get('products_view'))
{
	$id = $_GET["id"];
	
	// nav bar options.
	$_SESSION["nav"]["active"]	= 1;
	
	$_SESSION["nav"]["title"][]	= "Product Details";
	$_SESSION["nav"]["query"][]	= "page=products/view.php&id=$id";
	$_SESSION["nav"]["current"]	= "page=products/view.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Product Journal";
	$_SESSION["nav"]["query"][]	= "page=products/journal.php&id=$id";

	$_SESSION["nav"]["title"][]	= "Delete Product";
	$_SESSION["nav"]["query"][]	= "page=products/delete.php&id=$id";



	function page_render()
	{
		$id = security_script_input('/^[0-9]*$/', $_GET["id"]);

		/*
			Title + Summary
		*/
		print "<h3>PRODUCT DETAILS</h3><br>";
		print "<p>This page allows you to view and adjust the product's records.</p>";

		$mysql_string	= "SELECT id FROM `products` WHERE id='$id'";
		$mysql_result	= mysql_query($mysql_string);
		$mysql_num_rows	= mysql_num_rows($mysql_result);

		if (!$mysql_num_rows)
		{
			print "<p><b>Error: The requested product does not exist. <a href=\"index.php?page=products/products.php\">Try looking for your product on the product list page.</a></b></p>";
		}
		else
		{
			/*
				Define form structure
			*/
			$form = New form_input;
			$form->formname = "product_view";
			$form->language = $_SESSION["user"]["lang"];

			$form->action = "products/edit-process.php";
			$form->method = "post";
			

			// general
			$structure = NULL;
			$structure["fieldname"] 	= "id_product";
			$structure["type"]		= "text";
			$structure["defaultvalue"]	= "$id";
			$form->add_input($structure);
			
			$structure = NULL;
			$structure["fieldname"] 	= "code_product";
			$structure["type"]		= "input";
			$structure["options"]["req"]	= "yes";
			$form->add_input($structure);
			
			$structure = NULL;
			$structure["fieldname"] 	= "name_product";
			$structure["type"]		= "input";
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




			
			// submit section
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
			
			
			// define subforms
			$form->subforms["product_view"]		= array("id_product", "code_product", "name_product", "date_current", "details");
			$form->subforms["product_pricing"]	= array("price_cost", "price_sale");
			$form->subforms["product_quantity"]	= array("quantity_instock", "quantity_vendor");
			$form->subforms["submit"]		= array("submit");

			
			// fetch the form data
			$form->sql_query = "SELECT * FROM `products` WHERE id='$id' LIMIT 1";		
			$form->load_data();

			// display the form
			$form->render_form();

		}

	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
