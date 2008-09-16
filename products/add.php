<?php
/*
	products/add.php
	
	access: products_write

	Form to add a new product to the database.
*/

if (user_permissions_get('products_write'))
{
	function page_render()
	{
		$id = security_script_input('/^[0-9]*$/', $_GET["id"]);

		/*
			Title + Summary
		*/
		print "<h3>ADD PRODUCT</h3><br>";
		print "<p>This page allows you to add a new product.</p>";

		/*
			Define form structure
		*/
		$form = New form_input;
		$form->formname = "product_add";
		$form->language = $_SESSION["user"]["lang"];

		$form->action = "products/edit-process.php";
		$form->method = "post";

		// general
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


		// submit button
		$structure = NULL;
		$structure["fieldname"] 	= "submit";
		$structure["type"]		= "submit";
		$structure["defaultvalue"]	= "Create Product";
		$form->add_input($structure);
		

		// define subforms
		$form->subforms["product_view"]		= array("code_product", "name_product", "date_current", "details");
		$form->subforms["product_pricing"]	= array("price_cost", "price_sale");
		$form->subforms["product_quantity"]	= array("quantity_instock", "quantity_vendor");
		$form->subforms["submit"]		= array("submit");

		// load any data returned due to errors
		$form->load_data_error();

		// display the form
		$form->render_form();

	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
