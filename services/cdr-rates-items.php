<?php
/*
	services/cdr-rates-items.php

	access: services_view
		services_write

	Displays all the values/items inside the CDR rate table.
*/


require("include/services/inc_services_cdr.php");


class page_output
{
	var $obj_rate_table;
	var $obj_menu_nav;
	var $obj_table;



	function page_output()
	{
		$this->obj_rate_table	= New cdr_rate_table;


		// fetch variables
		$this->obj_rate_table->id = @security_script_input('/^[0-9]*$/', $_GET["id"]);

		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Rate Table Details", "page=services/cdr-rates-view.php&id=". $this->obj_rate_table->id ."");
		$this->obj_menu_nav->add_item("Rate Table Items", "page=services/cdr-rates-items.php&id=". $this->obj_rate_table->id ."", TRUE);

		if (user_permissions_get("services_write"))
		{
			$this->obj_menu_nav->add_item("Delete Rate Table", "page=services/cdr-rates-delete.php&id=". $this->obj_rate_table->id ."");
		}
	}


	function check_permissions()
	{
		return user_permissions_get("services_view");
	}


	function check_requirements()
	{
		if (!$this->obj_rate_table->verify_id())
		{
			log_write("error", "page_output", "The supplied rate table ID ". $this->obj_rate_table->id ." does not exist");
			return 0;
		}

		return 1;
	}


	function execute()
	{
		// establish a new table object
		$this->obj_table = New table;

		$this->obj_table->language	= $_SESSION["user"]["lang"];
		$this->obj_table->tablename	= "cdr_rate_table_items";


		// define all the columns and structure
		$this->obj_table->add_column("standard", "rate_prefix", "");
		$this->obj_table->add_column("standard", "rate_description", "");
		$this->obj_table->add_column("money", "rate_price_sale", "");
		$this->obj_table->add_column("money", "rate_price_cost", "");

		// defaults
		$this->obj_table->columns		= array("rate_prefix", "rate_description", "rate_price_sale", "rate_price_cost");
		$this->obj_table->columns_order		= array("rate_prefix");
		//$this->obj_table->columns_order_options	= array("rate_prefix");

		// define SQL structure
		$this->obj_table->sql_obj->prepare_sql_settable("cdr_rate_tables_values");
		$this->obj_table->sql_obj->prepare_sql_addfield("id", "id");
		$this->obj_table->sql_obj->prepare_sql_addfield("id_rate_table='". $this->rate_table->id ."'");


		// acceptable filter options
		$structure["fieldname"] = "searchbox";
		$structure["type"]	= "input";
		$structure["sql"]	= "(rate_prefix LIKE '%value%' OR rate_description LIKE '%value%')";
		$this->obj_table->add_filter($structure);
	

		// load options
		$this->obj_table->load_options_form();

		// fetch all the service information
		$this->obj_table->generate_sql();
		$this->obj_table->load_data_sql();
	}


	function render_html()
	{
		// title and summary
		print "<h3>RATE TABLE ITEMS</h3><br>";
		print "<p>Below is the full list of table rate items. Note the \"DEFAULT\" item which is used whenever a call doesn't match any other defined prefix in the rate table.</p>";


		// display options form
//		$this->obj_table->render_options_form();


		// display table
		if (!count($this->obj_table->columns))
		{
			format_msgbox("important", "<p>Please select some valid options to display.</p>");
		}
		elseif (!$this->obj_table->data_num_rows)
		{
			format_msgbox("important", "<p>There are no values in the rate table selected that match any of the filters.</p>");
		}
		else
		{
			// details link
			if (user_permissions_get("services_write"))
			{
				$structure = NULL;
				$structure["id"]["value"]	= $this->obj_rate_table->id;
				$structure["id_rate"]["column"]	= "id";
				$this->obj_table->add_link("tbl_lnk_item_edit", "services/cdr-rates-items-edit.php", $structure);

				$structure = NULL;
				$structure["id"]["value"]	= $this->obj_rate_table->id;
				$structure["id_rate"]["column"]	= "id";
				$this->obj_table->add_link("tbl_lnk_item_delete", "services/cdr-rates-items-delete.php", $structure);
			}

			// display the table
			$this->obj_table->render_table_html();

			// display CSV/PDF download link
			print "<p align=\"right\"><a class=\"button_export\" href=\"index-export.php?mode=csv&page=services/cdr-rates-items.php&id=". $this->obj_rate_table->id ."\">Export as CSV</a></p>";
			print "<p align=\"right\"><a class=\"button_export\" href=\"index-export.php?mode=pdf&page=services/cdr-rates-items.php&id=". $this->obj_rate_table->id ."\">Export as PDF</a></p>";
		}


		// add new entry link
		if (user_permissions_get("services_write"))
		{
			print "<p><a class=\"button\" href=\"index.php?page=services/cdr-rates-items-edit.php&id=". $this->obj_rate_table->id ."\">Add Item to Rate Table</a></p>";
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
