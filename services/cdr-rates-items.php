<?php
/*
	services/cdr-rates-items.php

	access: services_view
		services_write

	Displays all the values/items inside the CDR rate table.
*/


require("include/services/inc_services.php");
require("include/services/inc_services_cdr.php");


class page_output
{
	var $obj_rate_table;
	var $obj_menu_nav;
	var $obj_table;

	var $mode;


	function page_output()
	{
		// includes
		$this->requires["javascript"][]	= "include/javascript/services.js";


		// load rate table
		$this->obj_rate_table	= New cdr_rate_table;


		// fetch variables
		$this->obj_rate_table->id = @security_script_input('/^[0-9]*$/', $_GET["id"]);

		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;

		$this->obj_menu_nav->add_item("Rate Table Details", "page=services/cdr-rates-view.php&id=". $this->obj_rate_table->id ."");
		$this->obj_menu_nav->add_item("Rate Table Items", "page=services/cdr-rates-items.php&id=". $this->obj_rate_table->id ."", TRUE);

		if (user_permissions_get("services_write"))
		{
			$this->obj_menu_nav->add_item("Import Rates", "page=services/cdr-rates-import.php&id=". $this->obj_rate_table->id ."");
			$this->obj_menu_nav->add_item("Delete Rate Table", "page=services/cdr-rates-delete.php&id=". $this->obj_rate_table->id ."");
		}


		// fetch the operational mode
		if (isset($_GET["table_display_options"]))
		{
			if ($_GET["filter_search_summarise"] == "on")
			{
				$this->mode = "group";
			}
			else
			{
				$this->mode = "full";
			}
		}
		elseif (isset($_SESSION["form"]["cdr_rate_table_items"]["filters"]["filter_search_summarise"]))
		{
			if (empty($_SESSION["form"]["cdr_rate_table_items"]["filters"]["filter_search_summarise"]))
			{
				$this->mode = "full";
			}
			else
			{
				$this->mode = "group";
			}
		}
		else
		{
			$this->mode = "group";
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
		if ($this->mode == "group")
		{
			$this->obj_table->add_column("standard", "rate_prefix", "GROUP_CONCAT(' ', rate_prefix)");
		}
		else
		{
			$this->obj_table->add_column("standard", "rate_prefix", "");
		}

		$this->obj_table->add_column("standard", "rate_description", "");
		$this->obj_table->add_column("standard", "rate_billgroup", "cdr_rate_billgroups.billgroup_name");
		$this->obj_table->add_column("money_float", "rate_price_sale", "");
		$this->obj_table->add_column("money_float", "rate_price_cost", "");

		// defaults
		$this->obj_table->columns		= array("rate_prefix", "rate_description", "rate_billgroup", "rate_price_sale", "rate_price_cost");
		$this->obj_table->columns_order		= array("rate_prefix");
		$this->obj_table->columns_order_options	= array("rate_prefix", "rate_description", "rate_billgroup");
		$this->obj_table->limit_rows		= "1000";

		// define SQL structure
		$this->obj_table->sql_obj->prepare_sql_settable("cdr_rate_tables_values");
		$this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN cdr_rate_billgroups ON cdr_rate_billgroups.id = cdr_rate_tables_values.rate_billgroup");
		$this->obj_table->sql_obj->prepare_sql_addfield("id", "cdr_rate_tables_values.id");

		$this->obj_table->sql_obj->prepare_sql_addwhere("id_rate_table='". $this->obj_rate_table->id ."'");
		$this->obj_table->sql_obj->prepare_sql_addgroupby("rate_description, rate_billgroup, rate_price_sale, rate_price_cost");


		// acceptable filter options
		$structure["fieldname"] = "searchbox_prefix";
		$structure["type"]	= "input";
		$structure["sql"]	= "rate_prefix LIKE 'value%'";
		$this->obj_table->add_filter($structure);
	
		$structure["fieldname"] = "searchbox_desc";
		$structure["type"]	= "input";
		$structure["sql"]	= "rate_description LIKE '%value%'";
		$this->obj_table->add_filter($structure);

		$structure				= form_helper_prepare_dropdownfromdb("billgroup", "SELECT id, billgroup_name as label FROM cdr_rate_billgroups");
		$structure["sql"]			= "rate_billgroup='value'";
		$structure["options"]["search_filter"]	= NULL;
		$this->obj_table->add_filter($structure);

		$structure["fieldname"] = "search_summarise";
		$structure["type"]	= "checkbox";
		$structure["sql"]	= "";
		$structure["defaultvalue"] = "on";
		$this->obj_table->add_filter($structure);

		$this->obj_table->add_fixed_option("id", $this->obj_rate_table->id);


		// load options
		$this->obj_table->load_options_form();


		// increase limit of group data
		$obj_group_sql		= New sql_query;
		$obj_group_sql->string	= "SET group_concat_max_len = 4092";
		$obj_group_sql->execute();

		// fetch all the service information
		$this->obj_table->generate_sql();
		$this->obj_table->load_data_sql();
	}


	function render_html()
	{
		// title and summary
		print "<h3>RATE TABLE ITEMS</h3><br>";
		print "<p>Below is the full list of table rate items. Note the \"DEFAULT\" item which is used whenever a call doesn't match any other defined prefix in the rate table.</p>";


		// add new entry link
		if (user_permissions_get("services_write"))
		{
			print "<p><a class=\"button\" href=\"index.php?page=services/cdr-rates-items-edit.php&id=". $this->obj_rate_table->id ."\">Add Item to Rate Table</a></p>";
		}


		// display options form
		$this->obj_table->render_options_form();


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
				if ($this->mode == "group")
				{
					$structure = NULL;
					$structure["id"]["value"]	= $this->obj_rate_table->id;
					$structure["id_rate"]["column"] = "id";
					$structure["class"]		= "cdr_expand";
					$this->obj_table->add_link("tbl_lnk_item_expand", "services/cdr-rates-items.php", $structure);
				}
					
				$structure = NULL;
				$structure["id"]["value"]	= $this->obj_rate_table->id;
				$structure["id_rate"]["column"]	= "id";
				$structure["class"]		= "cdr_edit";
				$this->obj_table->add_link("tbl_lnk_item_edit", "services/cdr-rates-items-edit.php", $structure);

				$structure = NULL;
				$structure["id"]["value"]	= $this->obj_rate_table->id;
				$structure["id_rate"]["column"]	= "id";
				$structure["full_link"]		= "yes";
				$structure["class"]		= "cdr_delete";
				$this->obj_table->add_link("tbl_lnk_item_delete", "services/cdr-rates-items-delete-process.php", $structure);
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
