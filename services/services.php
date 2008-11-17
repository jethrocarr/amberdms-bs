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
		$service_list = New table;

		$service_list->language		= $_SESSION["user"]["lang"];
		$service_list->tablename	= "services_list";


		// define all the columns and structure
		$service_list->add_column("standard", "name_service", "");
		$service_list->add_column("standard", "chartid", "CONCAT_WS(' -- ',account_charts.code_chart,account_charts.description)");
		$service_list->add_column("standard", "typeid", "service_types.name");
		$service_list->add_column("standard", "units", "services.units");
		$service_list->add_column("standard", "included_units", "");
		$service_list->add_column("money", "price", "");
		$service_list->add_column("money", "price_extraunits", "");
		$service_list->add_column("standard", "billing_cycle", "billing_cycles.name");

		// defaults
		$service_list->columns		= array("name_service", "typeid", "units", "included_units", "price", "price_extraunits", "billing_cycle");
		$service_list->columns_order	= array("name_service");

		// define SQL structure
		$service_list->sql_obj->prepare_sql_settable("services");
		$service_list->sql_obj->prepare_sql_addfield("id", "services.id");
		$service_list->sql_obj->prepare_sql_addjoin("LEFT JOIN account_charts ON account_charts.id = services.chartid");
		$service_list->sql_obj->prepare_sql_addjoin("LEFT JOIN billing_cycles ON billing_cycles.id = services.billing_cycle");
		$service_list->sql_obj->prepare_sql_addjoin("LEFT JOIN service_types ON service_types.id = services.typeid");

		// acceptable filter options
		$structure["fieldname"] = "searchbox";
		$structure["type"]	= "input";
		$structure["sql"]	= "services.name_service LIKE '%value%' OR services.description LIKE '%value%'";
		$service_list->add_filter($structure);




		// heading
		print "<h3>SERVICE LIST</h3><br><br>";


		// options form
		$service_list->load_options_form();
		$service_list->render_options_form();


		// fetch all the service information
		$service_list->generate_sql();
		$service_list->load_data_sql();

		if (!count($service_list->columns))
		{
			print "<p><b>Please select some valid options to display.</b></p>";
		}
		elseif (!$service_list->data_num_rows)
		{
			print "<p><b>You currently have no services in your database.</b></p>";
		}
		else
		{
			// load any units from the DB
			for ($i=0; $i < $service_list->data_num_rows; $i++)
			{
				// fetch name
				if (preg_match("/^[0-9]*$/", $service_list->data[$i]["units"]))
				{
					$service_list->data[$i]["units"] = sql_get_singlevalue("SELECT name as value FROM service_units WHERE id='". $service_list->data[$i]["units"] ."'");
				}


				// if still 0, then just blank - not all service types
				// have units - for example, generic_no_usage does not.
				if ($service_list->data[$i]["units"] == "0")
				{
					$service_list->data[$i]["units"] = "";
				}
				
			
			}
		

			// details link
			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$service_list->add_link("details", "services/view.php", $structure);

			// plan link
			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$service_list->add_link("plan", "services/plan.php", $structure);

			// display the table
			$service_list->render_table();

			// TODO: display CSV download link

		}

		
	} // end page_render

} // end of if logged in
else
{
	error_render_noperms();
}

?>
