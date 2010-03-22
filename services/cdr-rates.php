<?php
/*
	services/cdr_rates.php

	access:	services_view

	Lists all the define CDR rate tables and allows a user with write access to update
	the pricing of these files.
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
		$this->obj_table->tablename	= "cdr_rate_tables";


		// define all the columns and structure
		$this->obj_table->add_column("standard", "rate_table_name", "");
		$this->obj_table->add_column("standard", "name_vendor", "vendors.name_vendor");
		$this->obj_table->add_column("standard", "rate_table_description", "");

		// defaults
		$this->obj_table->columns		= array("rate_table_name", "name_vendor", "rate_table_description");
		$this->obj_table->columns_order		= array("rate_table_name");
		$this->obj_table->columns_order_options	= array("rate_table_name", "name_vendor");

		// define SQL structure
		$this->obj_table->sql_obj->prepare_sql_settable("cdr_rate_tables");
		$this->obj_table->sql_obj->prepare_sql_addfield("id", "cdr_rate_tables.id");
		$this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN vendors ON vendors.id = cdr_rate_tables.id_vendor");

		// acceptable filter options
		$structure["fieldname"] = "searchbox";
		$structure["type"]	= "input";
		$structure["sql"]	= "(rate_table_name LIKE '%value%' OR rate_table_description LIKE '%value%')";
		$this->obj_table->add_filter($structure);
	
		$structure		= form_helper_prepare_dropdownfromdb("name_vendor", "SELECT id, code_vendor as label, name_vendor as label1 FROM vendors ORDER BY name_vendor ASC");
		$structure["sql"]	= "cdr_rate_tables.id_vendor='value'";
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
		print "<h3>CDR RATE TABLES</h3><br>";
		print "<p>This page contains the different phone call rate tables that have been configured. You can define as many different call rates tables as you like, typically this is done when using different call providers to deliver services when they charge at different rates.</p>";


		// display options form
		$this->obj_table->render_options_form();


		// display table
		if (!count($this->obj_table->columns))
		{
			format_msgbox("important", "<p>Please select some valid options to display.</p>");
		}
		elseif (!$this->obj_table->data_num_rows)
		{
			format_msgbox("important", "<p>There are no rate tables in the database that match the filters.</p>");
		}
		else
		{
			// links
			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$this->obj_table->add_link("tbl_lnk_details", "services/cdr-rates-view.php", $structure);

			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$this->obj_table->add_link("tbl_lnk_items", "services/cdr-rates-items.php", $structure);

			$structure = NULL;
			$structure["id"]["column"]	= "id";
			$this->obj_table->add_link("tbl_lnk_delete", "services/cdr-rates-delete.php", $structure);


			// display the table
			$this->obj_table->render_table_html();

			// display CSV/PDF download link
			print "<p align=\"right\"><a class=\"button_export\" href=\"index-export.php?mode=csv&page=services/cdr_rates.php\">Export as CSV</a></p>";
			print "<p align=\"right\"><a class=\"button_export\" href=\"index-export.php?mode=pdf&page=services/cdr_rates.php\">Export as PDF</a></p>";

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
