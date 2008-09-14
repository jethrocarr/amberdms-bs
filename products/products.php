<?php
/*
	products.php
	
	access: "products_view" group members

	Displays a list of all the products on the system.
*/

if (user_permissions_get('products_view'))
{
	function page_render()
	{
		// establish a new table object
		$product_list = New table;

		$product_list->language	= $_SESSION["user"]["lang"];
		$product_list->tablename	= "product_list";
		$product_list->sql_table	= "products";


		// define all the columns and structure
		$product_list->add_column("standard", "id_product", "id");
		$product_list->add_column("standard", "code_product", "");
		$product_list->add_column("standard", "name_product", "");
		$product_list->add_column("price", "price_cost", "");
		$product_list->add_column("price", "price_sale", "");
		$product_list->add_column("date", "date_current", "");
		$product_list->add_column("standard", "quantity_instock", "");
		$product_list->add_column("standard", "quantity_vendor", "");

		// defaults
		$product_list->columns		= array("code_product", "name_product", "price_cost", "price_sale", "quantity_instock");
		$product_list->columns_order	= array("code_product");

		// custom SQL stuff
		$product_list->prepare_sql_addfield("id", "");



		// heading
		print "<h3>PRODUCTS LIST</h3><br><br>";


		// options form
		$product_list->load_options_form();
		$product_list->render_options_form();


		// fetch all the product information
		$product_list->generate_sql();
		$product_list->load_data_sql();

		if (!count($product_list->columns))
		{
			print "<p><b>Please select some valid options to display.</b></p>";
		}
		elseif (!$product_list->data_num_rows)
		{
			print "<p><b>You currently have no products in your database.</b></p>";
		}
		else
		{

			// view link
			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$product_list->add_link("view", "products/view.php", $structure);

			// display the table
			$product_list->render_table();

			// TODO: display CSV download link

		}

		
	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
