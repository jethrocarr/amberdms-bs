<?php
/*
	service-history-cdr.php

	Fetches the charges from the database for the selected period and calculate the cost.
	
	access: "customers_view"

	TODO: a lot of the stuff in this file should be moved into a service_period object as
		part of continued improvement of service billing.

*/

require("include/services/inc_services.php");
require("include/customers/inc_customers.php");


class page_output
{
	var $obj_customer;	
	var $obj_menu_nav;
	var $obj_table;

	var $id_service_period;


	function page_output()
	{
		$this->obj_customer				= New customer_services;


		// fetch variables
		$this->obj_customer->id				= @security_script_input('/^[0-9]*$/', $_GET["id_customer"]);
		$this->obj_customer->id_service_customer	= @security_script_input('/^[0-9]*$/', $_GET["id_service_customer"]);

		$this->id_service_period			= @security_script_input('/^[0-9]*$/', $_GET["id_service_period"]);


		// load service data
		$this->obj_customer->load_data_service();


		// define the navigiation menu
		$this->obj_menu_nav = New menu_nav;
	
		$this->obj_menu_nav->add_item("Return to Customer Services Page", "page=customers/services.php&id=". $this->obj_customer->id ."");
		$this->obj_menu_nav->add_item("Service Details", "page=customers/service-edit.php&id_customer=". $this->obj_customer->id ."&id_service_customer=". $this->obj_customer->id_service_customer ."");
		$this->obj_menu_nav->add_item("Service History", "page=customers/service-history.php&id_customer=". $this->obj_customer->id ."&id_service_customer=". $this->obj_customer->id_service_customer ."", TRUE);

		if (in_array($this->obj_customer->obj_service->data["typeid_string"], array("phone_single", "phone_tollfree", "phone_trunk")))
		{
			$this->obj_menu_nav->add_item("CDR Override", "page=customers/service-cdr-override.php&id_customer=". $this->obj_customer->id ."&id_service_customer=". $this->obj_customer->id_service_customer ."");
		}

		if ($this->obj_customer->obj_service->data["typeid_string"] == "phone_trunk")
		{
			$this->obj_menu_nav->add_item("DDI Configuration", "page=customers/service-ddi.php&id_customer=". $this->obj_customer->id ."&id_service_customer=". $this->obj_customer->id_service_customer ."");
		}
		
		if ($this->obj_customer->obj_service->data["typeid_string"] == "data_traffic")
		{
			$this->obj_menu_nav->add_item("IPv4 Addresses", "page=customers/service-ipv4.php&id_customer=". $this->obj_customer->id ."&id_service_customer=". $this->obj_customer->id_service_customer ."");
		}


		if (user_permissions_get("customers_write"))
		{
			$this->obj_menu_nav->add_item("Service Delete", "page=customers/service-delete.php&id_customer=". $this->obj_customer->id ."&id_service_customer=". $this->obj_customer->id_service_customer ."");
		}
	}



	function check_permissions()
	{
		return user_permissions_get("customers_view");
	}



	function check_requirements()
	{
		// verify that customer exists
		if (!$this->obj_customer->verify_id())
		{
			log_write("error", "page_output", "The requested customer (". $this->customerid .") does not exist - possibly the customer has been deleted.");
			return 0;
		}


		// verify that service-customer mapping is valid
		if (!$this->obj_customer->verify_id_service_customer())
		{
			log_write("error", "page_output", "The requested service (". $this->obj_customer->id_service_customer .") was not found and/or does not match the selected customer");
			return 0;
		}

		// verify that the selected period is valid
		if (!$this->id_service_period)
		{
			log_write("error", "page_output", "A valid service period must be supplied");
			return 0;
		}

		return 1;
	}


				
	
	function execute()
	{
		/*
			Fetch period data
		*/

		$sql_period_obj			= New sql_query;
		$sql_period_obj->string		= "SELECT date_start, date_end FROM services_customers_periods WHERE id='". $this->id_service_period ."' LIMIT 1";
		$sql_period_obj->execute();
		$sql_period_obj->fetch_array();


		/*
			Fetch call charges for this period into table.
		*/


		// establish a new table object
		$this->obj_table = New table;

		$this->obj_table->language		= $_SESSION["user"]["lang"];
		$this->obj_table->tablename		= "service_history_cdr";

		// define all the columns and structure
		$this->obj_table->add_column("date", "date", "");
		$this->obj_table->add_column("standard", "rate_billgroup", "cdr_rate_billgroups.billgroup_name");
		$this->obj_table->add_column("standard", "number_src", "usage1");
		$this->obj_table->add_column("standard", "number_dst", "usage2");
		$this->obj_table->add_column("standard", "billable_seconds", "usage3");
		$this->obj_table->add_column("money_float", "price", "");

		// defaults
		$this->obj_table->columns		= array("date", "rate_billgroup", "number_src", "number_dst", "billable_seconds", "price");
		$this->obj_table->columns_order		= array("date", "rate_billgroup", "number_src", "number_dst");

		// totals
		$this->obj_table->total_columns		= array("billable_seconds", "price");

		// define SQL structure
		$this->obj_table->sql_obj->prepare_sql_settable("service_usage_records");
		$this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN cdr_rate_billgroups ON cdr_rate_billgroups.id = service_usage_records.billgroup");
		$this->obj_table->sql_obj->prepare_sql_addfield("id", "service_usage_records.id");
		$this->obj_table->sql_obj->prepare_sql_addwhere("id_service_customer = '". $this->obj_customer->id_service_customer ."'");
		$this->obj_table->sql_obj->prepare_sql_addwhere("date >= '". $sql_period_obj->data[0]["date_start"] ."'");
		$this->obj_table->sql_obj->prepare_sql_addwhere("date <= '". $sql_period_obj->data[0]["date_end"] ."'");


		// acceptable filter options
		$structure = NULL;
		$structure["fieldname"] = "searchbox";
		$structure["type"]	= "input";
		$structure["sql"]	= "(number_src LIKE '%value%' OR number_dst LIKE '%value%')";
		$this->obj_table->add_filter($structure);
		
	
		$this->obj_table->add_fixed_option("id_customer", $this->obj_customer->id);
		$this->obj_table->add_fixed_option("id_service_customer", $this->obj_customer->id_service_customer);
		$this->obj_table->add_fixed_option("id_service_period", $this->id_service_period);

		// load settings from options form
		$this->obj_table->load_options_form();


		// run SQL query
		$this->obj_table->generate_sql();
		$this->obj_table->load_data_sql();

	}


	function render_html()
	{
		// heading
		print "<h3>SERVICE CALL RECORD LOGS</h3>";
		print "<p>This page shows all the call records for the selected service period and the prices associated with each call. Note that the seconds shown are the number of billable seconds, however this doesn't shown any seconds that were rounded up to whole minutes for billing purposes.</p>";
		
		// summary boxes
		$this->obj_customer->service_render_summarybox();


		// options table
		$this->obj_table->render_options_form();

		// data
		if (!$this->obj_table->data_num_rows)
		{
			format_msgbox("info", "<p>This period does not have any records attached to it, this can often happen with the current ongoing period, as depending on the method of import, usage records may only be usable after the first month.</p>");
		}
		else
		{
			// display the table
			$this->obj_table->render_table_html();
			
			// display CSV/PDF download link
			print "<p align=\"right\"><a class=\"button_export\" href=\"index-export.php?mode=csv&page=customers/service-history-cdr-export.php&id_customer=". $this->obj_customer->id ."&id_service_customer=". $this->obj_customer->id_service_customer ."&id_service_period=". $this->id_service_period ."\">CDR Export Mode</a></p>";
			print "<br>";
			print "<p align=\"right\"><a class=\"button_export\" href=\"index-export.php?mode=csv&page=customers/service-history-cdr.php&id_customer=". $this->obj_customer->id ."&id_service_customer=". $this->obj_customer->id_service_customer ."&id_service_period=". $this->id_service_period ."\">Export as CSV</a></p>";
			print "<p align=\"right\"><a class=\"button_export\" href=\"index-export.php?mode=pdf&page=customers/service-history-cdr.php&id_customer=". $this->obj_customer->id ."&id_service_customer=". $this->obj_customer->id_service_customer ."&id_service_period=". $this->id_service_period ."\">Export as PDF</a></p>";
		}
	}


	function render_csv()
	{
		// display the table
		$this->obj_table->render_table_csv();
	}


	function render_pdf()
	{
		// display the table
		$this->obj_table->render_table_pdf();
	}


} // end page_output


?>
