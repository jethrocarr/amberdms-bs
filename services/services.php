<?php
/*
	services/services.php

	access:	services_view

	Lists all the configured services and permits adjustments.
*/


class page_output
{
	var $obj_table;


	function check_permissions()
	{
		return user_permissions_get("services_view");
	}

	function check_requirements()
	{
		// nothing todo
		return 1;
	}


	/*
		Define table and load data
	*/
	function execute()
	{
		// establish a new table object
		$this->obj_table = New table;

		$this->obj_table->language	= $_SESSION["user"]["lang"];
		$this->obj_table->tablename	= "services_list";


		// define all the columns and structure
		$this->obj_table->add_column("standard", "name_service", "");
		$this->obj_table->add_column("standard", "chartid", "CONCAT_WS(' -- ',account_charts.code_chart,account_charts.description)");
		$this->obj_table->add_column("standard", "typeid", "service_types.name");
		$this->obj_table->add_column("standard", "units", "services.units");
		$this->obj_table->add_column("standard", "included_units", "");
		$this->obj_table->add_column("money", "price", "");
		$this->obj_table->add_column("money", "price_extraunits", "");
		$this->obj_table->add_column("standard", "billing_cycle", "billing_cycles.name");

		// defaults
		$this->obj_table->columns		= array("name_service", "typeid", "units", "included_units", "price", "price_extraunits", "billing_cycle");
		$this->obj_table->columns_order		= array("name_service");
		$this->obj_table->columns_order_options	= array("name_service", "chartid", "typeid", "units", "included_units", "price", "price_extraunits", "billing_cycle");

		// define SQL structure
		$this->obj_table->sql_obj->prepare_sql_settable("services");
		$this->obj_table->sql_obj->prepare_sql_addfield("id", "services.id");
		$this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN account_charts ON account_charts.id = services.chartid");
		$this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN billing_cycles ON billing_cycles.id = services.billing_cycle");
		$this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN service_types ON service_types.id = services.typeid");

		// acceptable filter options
		$structure["fieldname"] = "searchbox";
		$structure["type"]	= "input";
		$structure["sql"]	= "(services.name_service LIKE '%value%' OR services.description LIKE '%value%')";
		$this->obj_table->add_filter($structure);


		// load options
		$this->obj_table->load_options_form();

		// fetch all the service information
		$this->obj_table->generate_sql();
		$this->obj_table->load_data_sql();


		if ($this->obj_table->data_num_rows)
		{
			// load any units from the DB
			for ($i=0; $i < $this->obj_table->data_num_rows; $i++)
			{
				// fetch name
				if (preg_match("/^[0-9]*$/", $this->obj_table->data[$i]["units"]))
				{
					$this->obj_table->data[$i]["units"] = sql_get_singlevalue("SELECT name as value FROM service_units WHERE id='". $this->obj_table->data[$i]["units"] ."'");
				}


				// if still 0, then just blank - not all service types
				// have units - for example, generic_no_usage does not.
				if ($this->obj_table->data[$i]["units"] == "0")
				{
					$this->obj_table->data[$i]["units"] = "";
				}
				
			
			}
		}
	}



	function render_html()
	{

		// heading
		print "<h3>SERVICE LIST</h3><br><br>";


		// display options form
		$this->obj_table->render_options_form();


		// display table
		if (!count($this->obj_table->columns))
		{
			format_msgbox("important", "<p>Please select some valid options to display.</p>");
		}
		elseif (!$this->obj_table->data_num_rows)
		{
			format_msgbox("important", "<p>There are no services in the database that match the filters.</p>");
		}
		else
		{
			// details link
			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$this->obj_table->add_link("details", "services/view.php", $structure);

			// plan link
			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$this->obj_table->add_link("plan", "services/plan.php", $structure);

			// display the table
			$this->obj_table->render_table_html();

			// display CSV/PDF download link
			print "<p align=\"right\"><a class=\"button_export\" href=\"index-export.php?mode=csv&page=services/services.php\">Export as CSV</a></p>";
			print "<p align=\"right\"><a class=\"button_export\" href=\"index-export.php?mode=pdf&page=services/services.php\">Export as PDF</a></p>";

		}

	}


	function render_csv()
	{
		$this->obj_table->render_table_csv();
	}


	function render_pdf()
	{
		$this->obj_table->render_table_pdf();
	}
	
}

?>
