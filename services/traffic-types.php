<?php
/*
	services/traffic-types.php

	access:	services_view

	Traffic types allow different types of data to be charged differently, such as national vs
	international traffic - this page displays all the configured regions in the database.
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
		$this->obj_table->tablename	= "traffic_types";


		// define all the columns and structure
		$this->obj_table->add_column("standard", "type_name", "");
		$this->obj_table->add_column("standard", "type_label", "");
		$this->obj_table->add_column("standard", "type_description", "");

		// defaults
		$this->obj_table->columns		= array("type_name", "type_label", "type_description");
		$this->obj_table->columns_order		= array("type_name");
		$this->obj_table->columns_order_options	= array("type_name", "type_label");

		// define SQL structure
		$this->obj_table->sql_obj->prepare_sql_settable("traffic_types");
		$this->obj_table->sql_obj->prepare_sql_addfield("id", "id");

		// acceptable filter options
		$structure["fieldname"] 		= "searchbox";
		$structure["type"]			= "input";
		$structure["sql"]			= "(type_name LIKE '%value%' OR type_description LIKE '%value%')";
		$this->obj_table->add_filter($structure);
	
		// load options
		$this->obj_table->load_options_form();

		// fetch all the service information
		$this->obj_table->generate_sql();
		$this->obj_table->load_data_sql();

	}



	function render_html()
	{
		// heading
		print "<h3>DATA TRAFFIC TYPES</h3><br>";
		print "<p>When billing data traffic services with differing prices/caps for specific traffic types - for example, national vs international traffic, the different traffic types will need to be defined here - once defined, services can have these traffic types assigned and configured with per-service data caps or unlimited options.</p>";


		// display options form
		$this->obj_table->render_options_form();


		// display table
		if (!count($this->obj_table->columns))
		{
			format_msgbox("important", "<p>Please select some valid options to display.</p>");
		}
		elseif (!$this->obj_table->data_num_rows)
		{
			format_msgbox("important", "<p>There are no traffic types in the database that match the filters.</p>");
		}
		else
		{
			// links
			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$this->obj_table->add_link("tbl_lnk_details", "services/traffic-types-view.php", $structure);

			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$this->obj_table->add_link("tbl_lnk_delete", "services/traffic-types-delete.php", $structure);


			// display the table
			$this->obj_table->render_table_html();

			// display CSV/PDF download link
			print "<p align=\"right\"><a class=\"button_export\" href=\"index-export.php?mode=csv&page=services/traffic-types.php\">Export as CSV</a></p>";
			print "<p align=\"right\"><a class=\"button_export\" href=\"index-export.php?mode=pdf&page=services/traffic-types.php\">Export as PDF</a></p>";

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
