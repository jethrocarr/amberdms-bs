<?php
/*
	services/services.php

	access: "services_view" group members

	Lists all the configured services and permits adjustments.
*/

if (user_permissions_get('services_view'))
{
	function page_render()
	{
		// establish a new table object
		$product_list = New table;

		$product_list->language		= $_SESSION["user"]["lang"];
		$product_list->tablename	= "services_list";


		// define all the columns and structure
		$product_list->add_column("standard", "name_service", "");
		$product_list->add_column("standard", "chartid", "CONCAT_WS(' -- ',account_charts.code_chart,account_charts.description)");
		$product_list->add_column("standard", "typeid", "service_types.name");
		$product_list->add_column("standard", "units", "");
		$product_list->add_column("money", "price", "");
		$product_list->add_column("standard", "included_units", "");
		$product_list->add_column("standard", "billing_cycle", "");

		// defaults
		$product_list->columns		= array("name_service", "typeid", "units", "price", "included_units", "billing_cycle");
		$product_list->columns_order	= array("name_service");

		// define SQL structure
		$product_list->sql_obj->prepare_sql_settable("services");
		$product_list->sql_obj->prepare_sql_addfield("id", "services.id");
		$product_list->sql_obj->prepare_sql_addjoin("LEFT JOIN account_charts ON account_charts.id = services.chartid");
		$product_list->sql_obj->prepare_sql_addjoin("LEFT JOIN service_types ON service_types.id = services.typeid");

		// acceptable filter options
		$structure["fieldname"] = "searchbox";
		$structure["type"]	= "input";
		$structure["sql"]	= "name_service LIKE '%value%' OR description LIKE '%value%'";
		$product_list->add_filter($structure);




		// heading
		print "<h3>SERVICE LIST</h3><br><br>";


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
			print "<p><b>You currently have no services in your database.</b></p>";
		}
		else
		{

			// view link
			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$product_list->add_link("view", "services/view.php", $structure);

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
