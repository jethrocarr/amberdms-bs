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
			// translate the column labels
			$product_list->render_column_names();
		
			// display header row
			print "<table class=\"table_content\" width=\"100%\">";
			print "<tr>";
			
				foreach ($product_list->render_columns as $columns)
				{
					print "<td class=\"header\"><b>". $columns ."</b></td>";
				}
				
				print "<td class=\"header\"></td>";	// filler for link column
				
			print "</tr>";
		
			// display data
			for ($i=0; $i < $product_list->data_num_rows; $i++)
			{
				print "<tr>";

				foreach ($product_list->columns as $columns)
				{
					print "<td>". $product_list->data[$i]["$columns"] ."</td>";
				}
				print "<td><a href=\"index.php?page=products/view.php&id=". $product_list->data[$i]["id"] ."\">view</td>";
				
				print "</tr>";
			}

			print "</table>";

			// TODO: display CSV download link

		}

		
	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
