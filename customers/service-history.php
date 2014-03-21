<?php
/*
	service-history.php

	Displays all the periods and invoices relating to this service
	
	access: "customers_view"

*/

require("include/services/inc_services.php");
require("include/customers/inc_customers.php");

class page_output
{
	var $obj_customer;	
	var $obj_menu_nav;
	var $obj_table;
	

	function page_output()
	{
		$this->obj_customer				= New customer_services;


		// fetch variables
		$this->obj_customer->id				= @security_script_input('/^[0-9]*$/', $_GET["id_customer"]);
		$this->obj_customer->id_service_customer	= @security_script_input('/^[0-9]*$/', $_GET["id_service_customer"]);

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
			log_write("error", "page_output", "The requested customer (". $this->obj_customer->id .") does not exist - possibly the customer has been deleted.");
			return 0;
		}


		// verify that service-customer mapping is valid
		if (!$this->obj_customer->verify_id_service_customer())
		{
			log_write("error", "page_output", "The requested service (". $this->obj_customer->id_service_customer .") was not found and/or does not match the selected customer");
			return 0;
		}

		return 1;
	}


				
	
	function execute()
	{
		// establish a new table object
		$this->obj_table = New table;

		$this->obj_table->language		= $_SESSION["user"]["lang"];
		$this->obj_table->tablename		= "service_history";

		// define all the columns and structure
		$this->obj_table->add_column("date", "date_start", "");
		$this->obj_table->add_column("date", "date_end", "");
		$this->obj_table->add_column("date", "invoice_gen_date", "date_billed");
		$this->obj_table->add_column("standard", "usage_summary", "");
		$this->obj_table->add_column("bool_tick", "invoiced_plan", "invoiceid");
		$this->obj_table->add_column("bool_tick", "invoiced_usage", "invoiceid_usage");
		$this->obj_table->add_column("bool_tick", "paid", "NONE");
		$this->obj_table->add_column("standard", "code_invoice", "account_ar.code_invoice");

		// defaults
		$this->obj_table->columns		= array("date_start", "date_end", "invoice_gen_date", "usage_summary", "invoiced_plan", "invoiced_usage", "paid", "code_invoice");
		$this->obj_table->columns_order		= array("date_start");

		// define SQL structure
		$this->obj_table->sql_obj->prepare_sql_settable("services_customers_periods");
		
		$this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN account_ar ON account_ar.id = services_customers_periods.invoiceid");
		$this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN services_customers ON services_customers.id = services_customers_periods.id_service_customer");
		$this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN services ON services.id = services_customers.serviceid");
		$this->obj_table->sql_obj->prepare_sql_addjoin("LEFT JOIN service_types ON service_types.id = services.typeid");
		
		$this->obj_table->sql_obj->prepare_sql_addfield("id", "services_customers_periods.id");
		$this->obj_table->sql_obj->prepare_sql_addfield("service_type", "service_types.name");
		$this->obj_table->sql_obj->prepare_sql_addfield("amount_total", "account_ar.amount_total");
		$this->obj_table->sql_obj->prepare_sql_addfield("amount_paid", "account_ar.amount_paid");
		$this->obj_table->sql_obj->prepare_sql_addwhere("id_service_customer = '". $this->obj_customer->id_service_customer ."'");

		// run SQL query
		$this->obj_table->generate_sql();
		$this->obj_table->load_data_sql();

	}


	function render_html()
	{
		// heading
		print "<h3>CUSTOMER SERVICE HISTORY</h3>";
		print "<p>This page displays all the periods of this service, showing when the service was active and when it has been billed.</p>";


		$this->obj_customer->service_render_summarybox();

	
		if (!$this->obj_table->data_num_rows)
		{
			format_msgbox("info", "<p>This service does not have any history - this is normal when the service has just recently been added.</p>");
		}
		else
		{
			// get the unit name if applicable
			if (preg_match("/^[0-9]*$/", $this->obj_customer->obj_service->data["units"]))
			{
				$unitname = sql_get_singlevalue("SELECT name as value FROM service_units WHERE id='". $this->obj_customer->obj_service->data["units"] ."'");
			}

			// run through all the data rows to make custom changes
			for ($i=0; $i < $this->obj_table->data_num_rows; $i++)
			{
				// make the invoice number a hyperlink
				if ($this->obj_table->data[$i]["code_invoice"] && user_permissions_get("accounts_ar_view"))
				{
					$this->obj_table->data[$i]["code_invoice"] = "<a href=\"index.php?page=accounts/ar/invoice-view.php&id=". $this->obj_table->data[$i]["invoiced_plan"] ."\">AR ". $this->obj_table->data[$i]["code_invoice"] ."</a>";
				}

				// tick the paid column if the invoice has been paid off completely
				if (!empty($this->obj_table->data[$i]["invoiced"]))
				{
					if ($this->obj_table->data[$i]["amount_total"] == $this->obj_table->data[$i]["amount_paid"])
					{
						$this->obj_table->data[$i]["paid"] = 1;
					}
				}

				// provide service-type specific options for displaying and querying usage.
				switch ($this->obj_table->data[$i]["service_type"])
				{
					case "phone_trunk":
					case "phone_tollfree":
					case "phone_single";

						// display a "view call records" link which looks up the historical
						// call data to fetch pricing

						$this->obj_table->data[$i]["usage_summary"] = "<a href=\"index.php?page=customers/service-history-cdr.php&id_customer=". $this->obj_customer->id ."&id_service_customer=". $this->obj_customer->id_service_customer ."&id_service_period=". $this->obj_table->data[$i]["id"] ."\">View Call Records</a>";

					break;


					case "data_traffic":
					case "generic_with_usage":

						if ($i == ($this->obj_table->data_num_rows - 1))
						{
							// if this is the most recent period, then add a check link next to the usage amount
							$this->obj_table->data[$i]["usage_summary"] = $this->obj_table->data[$i]["usage_summary"] ." $unitname <a href=\"customers/services-checkusage-process.php?id_customer=". $this->obj_customer->id ."&id_service_customer=". $this->obj_customer->id_service_customer ."\">(get latest)</a>";
						}
						else
						{
							// displau usage amount
							$this->obj_table->data[$i]["usage_summary"] = $this->obj_table->data[$i]["usage_summary"] ." $unitname";
						}
					break;
				} // end of service type

			}
			

			// display the table
			$this->obj_table->render_table_html();
			
			// display CSV/PDF download link
			print "<p align=\"right\"><a class=\"button_export\" href=\"index-export.php?mode=csv&page=customers/service-history.php&id_customer=". $this->obj_customer->id ."&id_service_customer=". $this->obj_customer->id_service_customer ."\">Export as CSV</a></p>";
			print "<p align=\"right\"><a class=\"button_export\" href=\"index-export.php?mode=pdf&page=customers/service-history.php&id_customer=". $this->obj_customer->id ."&id_service_customer=". $this->obj_customer->id_service_customer ."\">Export as PDF</a></p>";
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
